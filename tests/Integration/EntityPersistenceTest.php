<?php

declare(strict_types=1);

namespace Nexum\Tests\Integration;

use App\Kernel;
use App\Module\Billing\Invoice\Entity\Invoice;
use App\Module\Identity\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Nexum\Doctrine\Store\EntityStoreInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

final class EntityPersistenceTest extends TestCase
{
    private Kernel $kernel;
    private EntityManagerInterface $entityManager;
    private EntityStoreInterface $store;

    protected function setUp(): void
    {
        $this->kernel = new Kernel('test', true);
        $this->kernel->boot();
        $container = $this->kernel->getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $store = $container->get(EntityStoreInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        self::assertInstanceOf(EntityStoreInterface::class, $store);
        $this->entityManager = $entityManager;
        $this->store = $store;

        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schema = new SchemaTool($this->entityManager);
        $schema->dropSchema($metadata);
        $schema->createSchema($metadata);
    }

    protected function tearDown(): void
    {
        $this->entityManager->close();
        $this->kernel->shutdown();
    }

    public function testUuidTimestampsSoftDeleteAndRestore(): void
    {
        $invoice = new Invoice('NEX-1000');
        $owner = new User('persistence@example.test');
        $invoice->assignOwner($owner);
        self::assertInstanceOf(UuidV7::class, $invoice->getId());

        $this->store->persist($owner);
        $this->store->persist($invoice);
        $this->store->flush();
        self::assertNotNull($invoice->getCreatedAt());
        self::assertNotNull($invoice->getUpdatedAt());

        $id = $invoice->getId();
        $this->store->delete($invoice);
        $this->store->flush();
        self::assertNotNull($invoice->getDeletedAt());

        $this->entityManager->clear();
        self::assertNull($this->entityManager->find(Invoice::class, $id));

        $filters = $this->entityManager->getFilters();
        $filters->disable('nexum_soft_delete');
        $deletedInvoice = $this->entityManager->find(Invoice::class, $id);
        self::assertInstanceOf(Invoice::class, $deletedInvoice);

        $this->store->restore($deletedInvoice);
        $this->store->flush();
        $filters->enable('nexum_soft_delete');
        $this->entityManager->clear();

        self::assertInstanceOf(Invoice::class, $this->entityManager->find(Invoice::class, $id));
    }
}
