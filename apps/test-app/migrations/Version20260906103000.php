<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260906103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the legacy widget and modular billing invoice tables.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'This initial Harbor migration can only be executed safely on MySQL or MariaDB.',
        );

        $this->addSql('CREATE TABLE LegacyWidget (id BINARY(16) NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, name VARCHAR(120) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE Invoice (id BINARY(16) NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, deletedAt DATETIME DEFAULT NULL, number VARCHAR(64) NOT NULL, UNIQUE INDEX UNIQ_5FD82ED896901F54 (number), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'This initial Harbor migration can only be executed safely on MySQL or MariaDB.',
        );

        $this->addSql('DROP TABLE Invoice');
        $this->addSql('DROP TABLE LegacyWidget');
    }
}
