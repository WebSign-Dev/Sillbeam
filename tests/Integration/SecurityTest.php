<?php

declare(strict_types=1);

namespace Nexum\Tests\Integration;

use App\Kernel;
use App\Module\Identity\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Nexum\User\Security\PasswordManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

final class SecurityTest extends TestCase
{
    private Kernel $kernel;
    private EntityManagerInterface $manager;
    private string $clientIp;

    protected function setUp(): void
    {
        $this->kernel = new Kernel('test', false);
        $this->clientIp = '192.0.2.'.random_int(1, 254);
        $this->kernel->boot();
        $manager = $this->kernel->getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $manager);
        $this->manager = $manager;
        $metadata = $manager->getMetadataFactory()->getAllMetadata();
        $schema = new SchemaTool($manager);
        $schema->dropSchema($metadata);
        $schema->createSchema($metadata);
    }

    protected function tearDown(): void
    {
        $this->kernel->shutdown();
    }

    public function testAuthenticationPermissionAndRevocationLifecycle(): void
    {
        $anonymous = $this->request('GET', '/api/billing/invoices');
        self::assertSame(401, $anonymous->getStatusCode());
        self::assertSame('authentication_required', $this->json($anonymous)['errors'][0]['code']);
        self::assertSame('Bearer realm="Nexum"', $anonymous->headers->get('WWW-Authenticate'));

        $this->createUser('reader@example.test', 'reader-password', permissions: ['billing.invoice.read']);
        $testContainer = $this->kernel->getContainer()->get('test.service_container');
        self::assertInstanceOf(ContainerInterface::class, $testContainer);
        $serializer = $testContainer->get(SerializerInterface::class);
        self::assertInstanceOf(SerializerInterface::class, $serializer);
        $serializedUser = $serializer->serialize(
            $this->manager->getRepository(User::class)->findOneBy(['email' => 'reader@example.test']),
            'json',
        );
        self::assertStringNotContainsString('password', strtolower($serializedUser));
        $invalidLogin = $this->request('POST', '/api/auth/token', [
            'identifier' => 'reader@example.test',
            'password' => 'wrong-password',
        ]);
        self::assertSame(401, $invalidLogin->getStatusCode());
        self::assertSame('authentication_failed', $this->json($invalidLogin)['errors'][0]['code']);
        $login = $this->request('POST', '/api/auth/token', [
            'identifier' => 'reader@example.test',
            'password' => 'reader-password',
            'label' => 'Security test',
        ]);
        self::assertSame(201, $login->getStatusCode(), (string) $login->getContent());
        $loginData = $this->json($login);
        self::assertSame('Bearer', $loginData['data']['token_type']);
        self::assertIsString($loginData['data']['expires_at']);
        $accessToken = $loginData['data']['access_token'];
        self::assertIsString($accessToken);
        self::assertStringStartsWith('nxm_', $accessToken);

        $current = $this->request('GET', '/api/auth/me', accessToken: $accessToken);
        self::assertSame(200, $current->getStatusCode());
        $currentData = $this->json($current);
        self::assertSame('reader@example.test', $currentData['data']['identifier']);
        self::assertSame(['ROLE_USER'], $currentData['data']['roles']);
        self::assertSame(['billing.invoice.read'], $currentData['data']['permissions']);

        $allowed = $this->request('GET', '/api/billing/invoices', accessToken: $accessToken);
        self::assertSame(200, $allowed->getStatusCode());

        $denied = $this->request('POST', '/api/billing/invoices', ['number' => 'NO-PERMISSION'], $accessToken);
        self::assertSame(403, $denied->getStatusCode());
        self::assertSame('permission_denied', $this->json($denied)['errors'][0]['code']);

        $revoked = $this->request('DELETE', '/api/auth/token', accessToken: $accessToken);
        self::assertSame(204, $revoked->getStatusCode());

        $afterRevocation = $this->request('GET', '/api/auth/me', accessToken: $accessToken);
        self::assertSame(401, $afterRevocation->getStatusCode());
        self::assertSame('authentication_failed', $this->json($afterRevocation)['errors'][0]['code']);
    }

    public function testDisabledUsersCannotAuthenticate(): void
    {
        $user = $this->createUser('disabled@example.test', 'disabled-password');
        $user->deactivate();
        $this->manager->flush();

        $response = $this->request('POST', '/api/auth/token', [
            'identifier' => 'disabled@example.test',
            'password' => 'disabled-password',
        ]);

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('authentication_failed', $this->json($response)['errors'][0]['code']);
    }

    public function testExistingTokenStaysBoundToUserUuidAfterEmailReuse(): void
    {
        $original = $this->createUser('reused@example.test', 'original-password');
        $login = $this->request('POST', '/api/auth/token', [
            'identifier' => 'reused@example.test',
            'password' => 'original-password',
        ]);
        self::assertSame(201, $login->getStatusCode());
        $token = $this->json($login)['data']['access_token'];
        self::assertIsString($token);

        $original->setEmail('renamed@example.test');
        $this->manager->flush();
        $this->createUser('reused@example.test', 'replacement-password');

        $current = $this->request('GET', '/api/auth/me', accessToken: $token);
        self::assertSame(200, $current->getStatusCode());
        self::assertSame('renamed@example.test', $this->json($current)['data']['identifier']);
    }

    public function testUntrustedForwardingHeadersCannotSpoofHttpsOrHost(): void
    {
        $request = Request::create('/api/auth/token', 'POST', server: [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_HOST' => 'localhost',
            'HTTP_X_FORWARDED_HOST' => 'evil.example',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'REMOTE_ADDR' => '203.0.113.10',
        ], content: '{}');
        $response = $this->kernel->handle($request);
        $this->kernel->terminate($request, $response);

        self::assertTrue($response->isRedirect('https://localhost/api/auth/token'));

        $badHost = Request::create('/api/auth/token', 'POST', server: [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_HOST' => 'evil.example',
            'HTTPS' => 'on',
            'SERVER_PORT' => 443,
            'REMOTE_ADDR' => $this->clientIp,
        ], content: '{}');
        $badHostResponse = $this->kernel->handle($badHost);
        $this->kernel->terminate($badHost, $badHostResponse);
        self::assertSame(400, $badHostResponse->getStatusCode());
    }

    public function testUserCanBeCreatedWithTheNexumCommand(): void
    {
        $application = new Application($this->kernel);
        $command = $application->find('nexum:user:create');
        $tester = new CommandTester($command);
        $tester->setInputs(['command-password-123']);

        $exitCode = $tester->execute([
            'email' => 'command@example.test',
            '--role' => ['ROLE_BILLING_MANAGER'],
            '--permission' => ['billing.invoice.read'],
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('command@example.test', $tester->getDisplay());

        $login = $this->request('POST', '/api/auth/token', [
            'identifier' => 'command@example.test',
            'password' => 'command-password-123',
        ]);
        self::assertSame(201, $login->getStatusCode());
    }

    /** @param list<string> $permissions */
    private function createUser(string $email, string $password, array $permissions = []): User
    {
        $user = new User($email);
        $user->setPermissions($permissions);
        $passwords = $this->kernel->getContainer()->get(PasswordManagerInterface::class);
        self::assertInstanceOf(PasswordManagerInterface::class, $passwords);
        $user->setPasswordHash($passwords->hash($user, $password));
        $this->manager->persist($user);
        $this->manager->flush();

        return $user;
    }

    /** @param array<string, mixed>|object|null $payload */
    private function request(string $method, string $uri, array|object|null $payload = null, ?string $accessToken = null): Response
    {
        $server = [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_HOST' => 'localhost',
            'HTTPS' => 'on',
            'SERVER_PORT' => 443,
        ];
        $content = null;

        if (null !== $accessToken) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$accessToken;
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
