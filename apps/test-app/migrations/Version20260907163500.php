<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260907163500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create encrypted TOTP MFA, MFA challenge, passkey credential and WebAuthn challenge tables.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'This Harbor migration can only be executed safely on MySQL or MariaDB.',
        );

        $this->addSql('CREATE TABLE nexum_mfa_totp (id BINARY(16) NOT NULL, userIdentifier VARCHAR(180) NOT NULL, encryptedSecret TEXT NOT NULL, confirmedAt DATETIME DEFAULT NULL, recoveryCodeHashes JSON NOT NULL, lastUsedTimeStep INT DEFAULT NULL, UNIQUE INDEX UNIQ_NEXUM_MFA_USER (userIdentifier), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE nexum_mfa_challenge (id BINARY(16) NOT NULL, userIdentifier VARCHAR(180) NOT NULL, expiresAt DATETIME NOT NULL, failedAttempts INT NOT NULL, consumedAt DATETIME DEFAULT NULL, INDEX IDX_NEXUM_MFA_CHALLENGE_USER (userIdentifier), INDEX IDX_NEXUM_MFA_CHALLENGE_EXPIRY (expiresAt), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE nexum_passkey (id BINARY(16) NOT NULL, credentialIdHash VARCHAR(64) NOT NULL, userIdentifier VARCHAR(180) NOT NULL, label VARCHAR(100) NOT NULL, recordJson TEXT NOT NULL, createdAt DATETIME NOT NULL, lastUsedAt DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_NEXUM_PASSKEY_CREDENTIAL (credentialIdHash), INDEX IDX_NEXUM_PASSKEY_USER (userIdentifier), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE nexum_passkey_challenge (id BINARY(16) NOT NULL, ceremony VARCHAR(20) NOT NULL, userIdentifier VARCHAR(180) DEFAULT NULL, optionsJson TEXT NOT NULL, expiresAt DATETIME NOT NULL, failedAttempts INT NOT NULL, consumedAt DATETIME DEFAULT NULL, INDEX IDX_NEXUM_PASSKEY_CHALLENGE_EXPIRY (expiresAt), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'This Harbor migration can only be executed safely on MySQL or MariaDB.',
        );

        $this->addSql('DROP TABLE nexum_passkey_challenge');
        $this->addSql('DROP TABLE nexum_passkey');
        $this->addSql('DROP TABLE nexum_mfa_challenge');
        $this->addSql('DROP TABLE nexum_mfa_totp');
    }
}
