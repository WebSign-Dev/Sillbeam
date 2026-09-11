<?php

declare(strict_types=1);

namespace Nexum\Tests\Unit;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Nexum\Entity\AbstractSoftDeletableEntity;
use Nexum\Filesystem\Adapter\FlysystemStorage;
use Nexum\Filesystem\ValueObject\SafePath;
use Nexum\Health\HealthCheckInterface;
use Nexum\Health\HealthRegistry;
use Nexum\Health\HealthResult;
use Nexum\Health\HealthStatus;
use Nexum\Location\Coordinates;
use Nexum\Logger\ContextSanitizer;
use Nexum\Notification\Notification;
use Nexum\Notification\NotificationChannelInterface;
use Nexum\Notification\NotificationRouter;
use Nexum\Payment\Money;
use Nexum\Payment\PaymentRequest;
use Nexum\QrCode\Renderer\EndroidQrCodeGenerator;
use Nexum\Reporting\CsvReportRenderer;
use Nexum\Reporting\Report;
use Nexum\Search\SearchQuery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

final class InfrastructureModulesTest extends TestCase
{
    public function testStorageRejectsTraversalAndLimitsReads(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new SafePath('../secret');
    }

    public function testFlysystemStorageRoundTrip(): void
    {
        $directory = sys_get_temp_dir().'/nexum-storage-test-'.bin2hex(random_bytes(8));
        $filesystem = new SymfonyFilesystem();
        try {
            $storage = new FlysystemStorage(new Filesystem(new LocalFilesystemAdapter($directory)), 16);
            $storage->write('documents/test.txt', 'safe');
            self::assertTrue($storage->exists('documents/test.txt'));
            self::assertSame('safe', $storage->read('documents/test.txt'));
            $storage->delete('documents/test.txt');
            self::assertFalse($storage->exists('documents/test.txt'));
        } finally {
            $filesystem->remove($directory);
        }
    }

    public function testSensitiveLoggingContextIsRecursivelyRedacted(): void
    {
        $sanitized = (new ContextSanitizer())->sanitize([
            'authorization' => 'Bearer secret',
            'nested' => ['access_token' => 'secret', 'safe' => 'visible'],
        ]);

        self::assertSame('[REDACTED]', $sanitized['authorization']);
        self::assertSame('[REDACTED]', $sanitized['nested']['access_token']);
        self::assertSame('visible', $sanitized['nested']['safe']);
    }

    public function testHealthAggregationIsFailClosed(): void
    {
        $healthy = new class implements HealthCheckInterface {
            public function check(): HealthResult
            {
                return new HealthResult('database', HealthStatus::Healthy);
            }
        };
        $failing = new class implements HealthCheckInterface {
            public function check(): HealthResult
            {
                throw new \RuntimeException('credential must not leak');
            }
        };
        $result = (new HealthRegistry([$healthy, $failing]))->check();

        self::assertSame(HealthStatus::Unhealthy, $result['status']);
        self::assertSame(\RuntimeException::class, $result['checks'][1]->metadata['error']);
    }

    public function testOptionalValueObjectsEnforceBoundaries(): void
    {
        self::assertSame(52.52, (new Coordinates(52.52, 13.405))->latitude);
        self::assertSame('EUR', (new Money(1999, 'EUR'))->currency);
        self::assertSame(20, (new SearchQuery('nexum'))->limit);
        self::assertSame('key-123456789012', (new PaymentRequest('key-123456789012', new Money(1, 'EUR'), 'invoice'))->idempotencyKey);
        self::assertNotEmpty((new class extends AbstractSoftDeletableEntity {})->getId()->toRfc4122());
    }

    public function testNotificationRoutingFailsForUnknownChannels(): void
    {
        $sent = new \ArrayObject();
        $channel = new class($sent) implements NotificationChannelInterface {
            /** @param \ArrayObject<int, Notification> $sent */
            public function __construct(private readonly \ArrayObject $sent)
            {
            }

            public function name(): string
            {
                return 'memory';
            }

            public function send(Notification $notification): void
            {
                $this->sent[] = $notification;
            }
        };
        $router = new NotificationRouter([$channel]);
        $notification = new Notification('invoice.created', 'user-1', 'Invoice', 'Ready');
        $router->send('memory', $notification);
        self::assertCount(1, $sent);

        $this->expectException(\OutOfBoundsException::class);
        $router->send('unknown', $notification);
    }

    public function testCsvRendererNeutralizesSpreadsheetFormulas(): void
    {
        $csv = (new CsvReportRenderer())->render(new Report(['name', 'value'], [['name' => 'cell', 'value' => '=1+1']]));

        self::assertStringContainsString("'=1+1", $csv);
    }

    public function testQrCodeGeneratorUsesBoundedSvgOutput(): void
    {
        $svg = (new EndroidQrCodeGenerator())->svg('otpauth://totp/Nexum:test', 128);

        self::assertStringContainsString('<svg', $svg);
    }
}
