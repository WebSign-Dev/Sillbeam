<?php

declare(strict_types=1);

namespace Nexum\Tests\Integration;

use App\Kernel;
use App\Module\Billing\Invoice\Entity\Invoice;
use App\Module\Identity\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Nexum\User\Security\PasswordManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApiResourceTest extends TestCase
{
    private Kernel $kernel;
    private ?string $accessToken = null;
    private User $user;

    protected function setUp(): void
    {
        $this->kernel = new Kernel('test', false);
        $this->kernel->boot();
        $manager = $this->kernel->getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $manager);
        $metadata = $manager->getMetadataFactory()->getAllMetadata();
        $schema = new SchemaTool($manager);
        $schema->dropSchema($metadata);
        $schema->createSchema($metadata);

        $user = new User('manager@example.test');
        $user->setRoles(['ROLE_BILLING_MANAGER']);
        $passwords = $this->kernel->getContainer()->get(PasswordManagerInterface::class);
        self::assertInstanceOf(PasswordManagerInterface::class, $passwords);
        $user->setPasswordHash($passwords->hash($user, 'correct-horse-battery-staple'));
        $manager->persist($user);
        $manager->flush();
        $this->user = $user;

        $response = $this->request('POST', '/api/auth/token', [
            'identifier' => 'manager@example.test',
            'password' => 'correct-horse-battery-staple',
            'label' => 'PHPUnit',
        ]);
        self::assertSame(201, $response->getStatusCode());
        $token = $this->json($response)['data']['access_token'] ?? null;
        self::assertIsString($token);
        $this->accessToken = $token;
    }

    protected function tearDown(): void
    {
        $this->kernel->shutdown();
    }

    public function testCompleteResourceLifecycleWithBothPaginationModes(): void
    {
        $created = [];

        foreach (['NEX-1002', 'NEX-1001', 'OTHER-1'] as $number) {
            $response = $this->request('POST', '/api/billing/invoices', ['number' => $number]);
            self::assertSame(201, $response->getStatusCode());
            self::assertNotNull($response->headers->get('Location'));
            $created[] = $this->json($response)['data'];
        }

        self::assertCount(3, $created);
        self::assertIsString($created[0]['id']);
        $manager = $this->kernel->getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $manager);
        self::assertCount(3, $manager->getRepository(Invoice::class)->findAll());
        self::assertCount(3, $manager->getRepository(Invoice::class)->findBy(['owner' => $this->user]));
        $me = $this->json($this->request('GET', '/api/auth/me'))['data'];
        self::assertSame($this->user->getSubjectIdentifier(), $me['subject']);
        $unfiltered = $this->request('GET', '/api/billing/invoices');
        self::assertCount(3, $this->json($unfiltered)['data']);

        $page = $this->request('GET', '/api/billing/invoices?page%5Bnumber%5D=1&page%5Bsize%5D=1&sort=number&filter%5Bnumber%5D%5Bcontains%5D=NEX-');
        self::assertSame(200, $page->getStatusCode());
        $pageData = $this->json($page);
        self::assertCount(1, $pageData['data'], json_encode($pageData, \JSON_THROW_ON_ERROR));
        self::assertSame('NEX-1001', $pageData['data'][0]['number']);
        self::assertSame(2, $pageData['meta']['pagination']['total_items']);
        self::assertSame(2, $pageData['meta']['pagination']['total_pages']);

        $cursorPage = $this->request('GET', '/api/billing/invoices?page%5Bmode%5D=cursor&page%5Bsize%5D=2');
        self::assertSame(200, $cursorPage->getStatusCode());
        $cursorData = $this->json($cursorPage);
        self::assertCount(2, $cursorData['data']);
        self::assertTrue($cursorData['meta']['pagination']['has_more']);
        self::assertIsString($cursorData['meta']['pagination']['next']);
        $cursor = $cursorData['meta']['pagination']['next'];
        $tampered = substr($cursor, 0, -1).('A' === substr($cursor, -1) ? 'B' : 'A');
        self::assertSame(400, $this->request('GET', '/api/billing/invoices?page%5Bafter%5D='.rawurlencode($tampered))->getStatusCode());

        $nextPage = $this->request('GET', '/api/billing/invoices?page%5Bafter%5D='.rawurlencode($cursor).'&page%5Bsize%5D=2');
        $nextData = $this->json($nextPage);
        self::assertCount(1, $nextData['data']);
        self::assertFalse($nextData['meta']['pagination']['has_more']);
        self::assertNull($nextData['meta']['pagination']['next']);

        $identifier = $created[0]['id'];
        $read = $this->request('GET', '/api/billing/invoices/'.$identifier);
        self::assertStringContainsString('"meta":{}', (string) $read->getContent());
        $readData = $this->json($read);
        self::assertSame('NEX-1002', $readData['data']['number']);
        self::assertIsArray($readData['meta']);
        self::assertSame([], $readData['meta']);

        $updated = $this->request('PATCH', '/api/billing/invoices/'.$identifier, ['number' => 'NEX-2000']);
        self::assertSame(200, $updated->getStatusCode());
        self::assertSame('NEX-2000', $this->json($updated)['data']['number']);

        $invalid = $this->request('PATCH', '/api/billing/invoices/'.$identifier, ['number' => '']);
        self::assertSame(422, $invalid->getStatusCode());
        self::assertSame('validation_failed', $this->json($invalid)['errors'][0]['code']);

        $stillValid = $this->request('GET', '/api/billing/invoices/'.$identifier);
        self::assertSame('NEX-2000', $this->json($stillValid)['data']['number']);

        $incompleteReplacement = $this->request('PUT', '/api/billing/invoices/'.$identifier, (object) []);
        self::assertSame(400, $incompleteReplacement->getStatusCode());
        self::assertSame('missing_fields', $this->json($incompleteReplacement)['errors'][0]['code']);

        $replaced = $this->request('PUT', '/api/billing/invoices/'.$identifier, ['number' => 'NEX-3000']);
        self::assertSame(200, $replaced->getStatusCode());
        self::assertSame('NEX-3000', $this->json($replaced)['data']['number']);

        $unknownFilter = $this->request('GET', '/api/billing/invoices?filter%5Bsecret%5D=value');
        self::assertSame(400, $unknownFilter->getStatusCode());
        self::assertSame('filter_not_allowed', $this->json($unknownFilter)['errors'][0]['code']);

        $deleted = $this->request('DELETE', '/api/billing/invoices/'.$identifier);
        self::assertSame(204, $deleted->getStatusCode());
        self::assertSame('', $deleted->getContent());

        $missing = $this->request('GET', '/api/billing/invoices/'.$identifier);
        self::assertSame(404, $missing->getStatusCode());
        self::assertSame('resource_not_found', $this->json($missing)['errors'][0]['code']);

        $conflict = $this->request('POST', '/api/billing/invoices', ['number' => 'OTHER-1']);
        self::assertSame(409, $conflict->getStatusCode());
        self::assertSame('resource_conflict', $this->json($conflict)['errors'][0]['code']);
    }

    public function testResourcesAreAlwaysRestrictedToTheirOwner(): void
    {
        $created = $this->request('POST', '/api/billing/invoices', ['number' => 'PRIVATE-1']);
        self::assertSame(201, $created->getStatusCode());
        $identifier = $this->json($created)['data']['id'];
        self::assertIsString($identifier);

        $manager = $this->kernel->getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $manager);
        $other = new User('other-manager@example.test');
        $other->setRoles(['ROLE_BILLING_MANAGER']);
        $passwords = $this->kernel->getContainer()->get(PasswordManagerInterface::class);
        self::assertInstanceOf(PasswordManagerInterface::class, $passwords);
        $other->setPasswordHash($passwords->hash($other, 'other-manager-password'));
        $manager->persist($other);
        $manager->flush();

        $login = $this->request('POST', '/api/auth/token', [
            'identifier' => 'other-manager@example.test',
            'password' => 'other-manager-password',
        ]);
        self::assertSame(201, $login->getStatusCode());
        $otherToken = $this->json($login)['data']['access_token'];
        self::assertIsString($otherToken);
        $this->accessToken = $otherToken;

        $list = $this->request('GET', '/api/billing/invoices');
        self::assertSame(200, $list->getStatusCode());
        self::assertSame([], $this->json($list)['data']);

        $read = $this->request('GET', '/api/billing/invoices/'.$identifier);
        self::assertSame(404, $read->getStatusCode());
    }

    /** @param array<string, mixed>|object|null $payload */
    private function request(string $method, string $uri, array|object|null $payload = null): Response
    {
        $server = [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_HOST' => 'localhost',
            'HTTPS' => 'on',
            'SERVER_PORT' => 443,
        ];
        $content = null;

        if (null !== $this->accessToken) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$this->accessToken;
        }

        if (null !== $payload) {
            $server['CONTENT_TYPE'] = 'application/json';
            $content = json_encode($payload, \JSON_THROW_ON_ERROR);
        }

        $request = Request::create($uri, $method, server: $server, content: $content);
        $response = $this->kernel->handle($request);
        $this->kernel->terminate($request, $response);

        return $response;
    }

    /** @return array<string, mixed> */
    private function json(Response $response): array
    {
        $data = json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertIsArray($data);

        return $data;
    }
}
