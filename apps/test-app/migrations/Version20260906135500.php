<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260906135500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the Nexum user and hashed API access-token tables.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'This Harbor migration can only be executed safely on MySQL or MariaDB.',
        );

        $this->addSql('CREATE TABLE nexum_access_token (id BINARY(16) NOT NULL, userIdentifier VARCHAR(180) NOT NULL, tokenHash VARCHAR(64) NOT NULL, label VARCHAR(100) DEFAULT NULL, issuedAt DATETIME NOT NULL, expiresAt DATETIME NOT NULL, revokedAt DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_5FB2E056E5C96920 (tokenHash), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE nexum_user (id BINARY(16) NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, deletedAt DATETIME DEFAULT NULL, email VARCHAR(180) NOT NULL, passwordHash VARCHAR(255) NOT NULL, roles JSON NOT NULL, permissions JSON NOT NULL, active TINYINT DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_594BA03CE7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'This Harbor migration can only be executed safely on MySQL or MariaDB.',
        );

        $this->addSql('DROP TABLE nexum_access_token');
        $this->addSql('DROP TABLE nexum_user');
    }
}
