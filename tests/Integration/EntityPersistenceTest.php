<?php

declare(strict_types=1);

namespace Nexum\Tests\Integration;

use App\Entity\LegacyWidget;
use App\Kernel;
use App\Module\Billing\Invoice\Entity\Invoice;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;

final class EntityPersistenceTest extends TestCase
{
    public function testNativeAndModuleEntitiesPersistThroughTheSameDoctrineManager(): void
    {
        $kernel = new Kernel('test', true);
        try {
            $kernel->boot();
            $manager = $kernel->getContainer()->get('doctrine.orm.entity_manager');
            self::assertInstanceOf(EntityManagerInterface::class, $manager);
            $metadata = $manager->getMetadataFactory()->getAllMetadata();
            (new SchemaTool($manager))->createSchema($metadata);
            $invoice = new Invoice('NEX-1000');
            $legacy = new LegacyWidget('native');
            $manager->persist($invoice);
            $manager->persist($legacy);
            $manager->flush();
            $invoiceId = $invoice->getId();
            $legacyId = $legacy->getId();
            $manager->clear();
            self::assertNotNull($invoiceId);
            self::assertSame('NEX-1000', $manager->find(Invoice::class, $invoiceId)?->getName());
            self::assertSame('native', $manager->find(LegacyWidget::class, $legacyId)?->getName());
        } finally {
            $kernel->shutdown();
        }
    }
}
