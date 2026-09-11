<?php

declare(strict_types=1);

namespace Nexum\Tests\Integration;

use App\Kernel;
use App\Module\Identity\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Nexum\Security\Mfa\Entity\TotpEnrollment;
use Nexum\User\Security\PasswordManagerInterface;
use OTPHP\TOTP;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Clock\NativeClock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class MfaAndPasskeyTest extends TestCase
{
    private Kernel $kernel;
    private EntityManagerInterface $manager;
    private string $clientIp;

    protected function setUp(): void
    {
        $this->kernel = new Kernel('test', false);
        $this->clientIp = '192.0.2.'.random_int(1, 254);
        $this->kernel->boot();
        $testContainer = $this->kernel->getContainer()->get('test.service_container');
        self::assertInstanceOf(ContainerInterface::class, $testContainer);
        $rateLimiterCache = $testContainer->get('cache.rate_limiter');
        self::assertInstanceOf(CacheItemPoolInterface::class, $rateLimiterCache);
        $rateLimiterCache->clear();
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

    public function testTotpRecoveryCodeAndSecondFactorLifecycle(): void
    {
        $email = 'mfa-'.bin2hex(random_bytes(8)).'@example.test';
        $user = $this->createUser($email, 'mfa-password-123');
        $initialToken = $this->loginToken($email, 'mfa-password-123');

        $status = $this->request('GET', '/api/auth/mfa/status', accessToken: $initialToken);
        self::assertSame(200, $status->getStatusCode());
        self::assertFalse($this->json($status)['data']['enabled']);

        $begin = $this->request('POST', '/api/auth/mfa/totp', accessToken: $initialToken);
        self::assertSame(201, $begin->getStatusCode());
        $beginData = $this->json($begin)['data'];
        self::assertIsString($beginData['secret']);
        if ('' === $beginData['secret']) {
            self::fail('The generated TOTP secret must not be empty.');
        }
        self::assertIsString($beginData['provisioning_uri']);
        self::assertStringStartsWith('otpauth://totp/', $beginData['provisioning_uri']);

        $stored = $this->manager->getRepository(TotpEnrollment::class)->findOneBy([
            'user' => $user,
        ]);
        self::assertInstanceOf(TotpEnrollment::class, $stored);
        self::assertNotSame($beginData['secret'], $stored->encryptedSecret());
        self::assertStringNotContainsString($beginData['secret'], $stored->encryptedSecret());

        $totp = TOTP::createFromSecret($beginData['secret'], new NativeClock());
        $confirm = $this->request('POST', '/api/auth/mfa/totp/confirm', ['code' => $totp->now()], $initialToken);
        self::assertSame(200, $confirm->getStatusCode());
        $recoveryCodes = $this->json($confirm)['data']['recovery_codes'];
        self::assertIsArray($recoveryCodes);
        self::assertCount(10, $recoveryCodes);

        $oldToken = $this->request('GET', '/api/auth/me', accessToken: $initialToken);
        self::assertSame(401, $oldToken->getStatusCode());

        $login = $this->login($email, 'mfa-password-123');
        self::assertSame(202, $login->getStatusCode());
        $loginData = $this->json($login)['data'];
        self::assertTrue($loginData['mfa_required']);
        self::assertSame(['totp', 'recovery_code'], $loginData['methods']);

        $invalid = $this->request('POST', '/api/auth/mfa/verify', [
            'challenge' => $loginData['challenge'],
            'code' => '000000',
        ]);
        self::assertSame(401, $invalid->getStatusCode());
        self::assertSame('invalid_mfa_challenge', $this->json($invalid)['errors'][0]['code']);

        $verified = $this->request('POST', '/api/auth/mfa/verify', [
            'challenge' => $loginData['challenge'],
            'code' => $totp->now(),
        ]);
        self::assertSame(201, $verified->getStatusCode());
        $mfaToken = $this->json($verified)['data']['access_token'];
        self::assertIsString($mfaToken);

        $replay = $this->request('POST', '/api/auth/mfa/verify', [
            'challenge' => $loginData['challenge'],
            'code' => $totp->now(),
        ]);
        self::assertSame(401, $replay->getStatusCode());

        $recoveryLogin = $this->json($this->login($email, 'mfa-password-123'))['data'];
        $recovery = $this->request('POST', '/api/auth/mfa/verify', [
            'challenge' => $recoveryLogin['challenge'],
            'code' => $recoveryCodes[0],
        ]);
        self::assertSame(201, $recovery->getStatusCode());

        $reuseLogin = $this->json($this->login($email, 'mfa-password-123'))['data'];
        $reuse = $this->request('POST', '/api/auth/mfa/verify', [
            'challenge' => $reuseLogin['challenge'],
            'code' => $recoveryCodes[0],
        ]);
        self::assertSame(401, $reuse->getStatusCode());

        $disable = $this->request('DELETE', '/api/auth/mfa/totp', ['code' => $recoveryCodes[1]], $mfaToken);
        self::assertSame(204, $disable->getStatusCode());
        self::assertSame(201, $this->login($email, 'mfa-password-123')->getStatusCode());
    }

    public function testPasskeyOptionsAreSecureAndInvalidAssertionsStayGeneric(): void
    {
        $email = 'passkey-'.bin2hex(random_bytes(8)).'@example.test';
        $this->createUser($email, 'passkey-password-123');
        $token = $this->loginToken($email, 'passkey-password-123');

        $registration = $this->request('POST', '/api/auth/passkeys/register/options', accessToken: $token);
        self::assertSame(201, $registration->getStatusCode());
        $registrationData = $this->json($registration)['data'];
        self::assertIsString($registrationData['challenge']);
        self::assertSame('localhost', $registrationData['publicKey']['rp']['id']);
        self::assertSame('required', $registrationData['publicKey']['authenticatorSelection']['residentKey']);
        self::assertSame('required', $registrationData['publicKey']['authenticatorSelection']['userVerification']);
        self::assertSame('none', $registrationData['publicKey']['attestation']);

        $authentication = $this->request('POST', '/api/auth/passkeys/authenticate/options');
        self::assertSame(201, $authentication->getStatusCode());
        $authenticationData = $this->json($authentication)['data'];
        self::assertIsString($authenticationData['challenge']);
        self::assertSame('localhost', $authenticationData['publicKey']['rpId']);
        self::assertSame('required', $authenticationData['publicKey']['userVerification']);
        self::assertSame([], $authenticationData['publicKey']['allowCredentials']);

        $invalid = $this->request('POST', '/api/auth/passkeys/authenticate', [
            'challenge' => $authenticationData['challenge'],
            'credential' => [],
        ]);
        self::assertSame(401, $invalid->getStatusCode());
        self::assertSame('invalid_passkey_challenge', $this->json($invalid)['errors'][0]['code']);

        $list = $this->request('GET', '/api/auth/passkeys', accessToken: $token);
        self::assertSame(200, $list->getStatusCode());
        self::assertSame([], $this->json($list)['data']);
    }

    public function testMfaAccountLimitCannotBeBypassedByChangingIp(): void
    {
        $this->createUser('limited@example.test', 'limited-password-123');
        $token = $this->loginToken('limited@example.test', 'limited-password-123');
        $begin = $this->json($this->request('POST', '/api/auth/mfa/totp', accessToken: $token))['data'];
        self::assertIsString($begin['secret']);
        if ('' === $begin['secret']) {
            self::fail('The generated TOTP secret must not be empty.');
        }
        $totp = TOTP::createFromSecret($begin['secret'], new NativeClock());
        self::assertSame(200, $this->request('POST', '/api/auth/mfa/totp/confirm', ['code' => $totp->now()], $token)->getStatusCode());

        $login = $this->json($this->login('limited@example.test', 'limited-password-123'))['data'];
        self::assertIsString($login['challenge']);

        for ($attempt = 1; $attempt <= 8; ++$attempt) {
            $this->clientIp = '198.51.100.'.$attempt;
            $response = $this->request('POST', '/api/auth/mfa/verify', [
                'challenge' => $login['challenge'],
                'code' => 'invalid',
            ]);
            self::assertSame(401, $response->getStatusCode());
        }

        $this->clientIp = '198.51.100.9';
        $limited = $this->request('POST', '/api/auth/mfa/verify', [
            'challenge' => $login['challenge'],
            'code' => 'invalid',
        ]);
        self::assertSame(429, $limited->getStatusCode());
        self::assertSame('rate_limit_exceeded', $this->json($limited)['errors'][0]['code']);
    }

    public function testApiRedirectsPlainHttpToHttps(): void
    {
        $request = Request::create('/api/auth/passkeys/authenticate/options', 'POST', server: [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_HOST' => 'localhost',
        ]);
        $response = $this->kernel->handle($request);
        $this->kernel->terminate($request, $response);

        self::assertTrue($response->isRedirect('https://localhost/api/auth/passkeys/authenticate/options'));

        $rootRequest = Request::create('/', 'GET', server: ['HTTP_HOST' => 'localhost']);
        $rootResponse = $this->kernel->handle($rootRequest);
        $this->kernel->terminate($rootRequest, $rootResponse);
        self::assertTrue($rootResponse->isRedirect('https://localhost/'), \sprintf('status=%d location=%s', $rootResponse->getStatusCode(), (string) $rootResponse->headers->get('Location')));
    }

    private function createUser(string $email, string $password): User
    {
        $user = new User($email);
        $passwords = $this->kernel->getContainer()->get(PasswordManagerInterface::class);
        self::assertInstanceOf(PasswordManagerInterface::class, $passwords);
        $user->setPasswordHash($passwords->hash($user, $password));
        $this->manager->persist($user);
        $this->manager->flush();

        return $user;
    }

    private function login(string $identifier, string $password): Response
    {
        return $this->request('POST', '/api/auth/token', compact('identifier', 'password'));
    }

    private function loginToken(string $identifier, string $password): string
    {
        $response = $this->login($identifier, $password);
        self::assertSame(201, $response->getStatusCode());
        $token = $this->json($response)['data']['access_token'];
        self::assertIsString($token);

        return $token;
    }

    /** @param array<string, mixed>|object|null $payload */
    private function request(string $method, string $uri, array|object|null $payload = null, ?string $accessToken = null): Response
    {
        $server = [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_HOST' => 'localhost',
            'HTTPS' => 'on',
            'SERVER_PORT' => 443,
            'REMOTE_ADDR' => $this->clientIp,
        ];
        if (null !== $accessToken) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$accessToken;
        }
        $content = null;
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
