<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260907164500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align MFA and passkey text columns and generated unique-index names with Doctrine metadata.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'This Harbor migration can only be executed safely on MySQL or MariaDB.',
        );

        $this->addSql('ALTER TABLE nexum_passkey CHANGE recordJson recordJson LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE nexum_passkey RENAME INDEX uniq_nexum_passkey_credential TO UNIQ_A3D21A856D91E2AD');
        $this->addSql('ALTER TABLE nexum_passkey_challenge CHANGE optionsJson optionsJson LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE nexum_mfa_totp CHANGE encryptedSecret encryptedSecret LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE nexum_mfa_totp RENAME INDEX uniq_nexum_mfa_user TO UNIQ_C703FCB7750FAC43');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'This Harbor migration can only be executed safely on MySQL or MariaDB.',
        );

        $this->addSql('ALTER TABLE nexum_mfa_totp RENAME INDEX UNIQ_C703FCB7750FAC43 TO UNIQ_NEXUM_MFA_USER');
        $this->addSql('ALTER TABLE nexum_mfa_totp CHANGE encryptedSecret encryptedSecret TEXT NOT NULL');
        $this->addSql('ALTER TABLE nexum_passkey_challenge CHANGE optionsJson optionsJson TEXT NOT NULL');
        $this->addSql('ALTER TABLE nexum_passkey RENAME INDEX UNIQ_A3D21A856D91E2AD TO UNIQ_NEXUM_PASSKEY_CREDENTIAL');
        $this->addSql('ALTER TABLE nexum_passkey CHANGE recordJson recordJson TEXT NOT NULL');
    }
}
