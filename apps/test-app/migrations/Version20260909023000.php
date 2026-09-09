<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260909023000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Bind tokens, MFA, passkeys and invoices to immutable user UUIDs with foreign keys.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'This migration can only be executed safely on MySQL or MariaDB.',
        );

        $this->addSql('ALTER TABLE nexum_access_token ADD user_id BINARY(16) DEFAULT NULL');
        $this->addSql('UPDATE nexum_access_token token INNER JOIN nexum_user u ON u.email = token.userIdentifier SET token.user_id = u.id');
        $this->addSql('DELETE FROM nexum_access_token WHERE user_id IS NULL');
        $this->addSql('ALTER TABLE nexum_access_token DROP userIdentifier, MODIFY user_id BINARY(16) NOT NULL');
        $this->addSql('CREATE INDEX IDX_5FB2E056A76ED395 ON nexum_access_token (user_id)');
        $this->addSql('ALTER TABLE nexum_access_token ADD CONSTRAINT FK_5FB2E056A76ED395 FOREIGN KEY (user_id) REFERENCES nexum_user (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE nexum_mfa_totp ADD user_id BINARY(16) DEFAULT NULL');
        $this->addSql('UPDATE nexum_mfa_totp enrollment INNER JOIN nexum_user u ON u.email = enrollment.userIdentifier SET enrollment.user_id = u.id');
        $this->addSql('DELETE FROM nexum_mfa_totp WHERE user_id IS NULL');
        $this->addSql('DROP INDEX UNIQ_C703FCB7750FAC43 ON nexum_mfa_totp');
        $this->addSql('ALTER TABLE nexum_mfa_totp DROP userIdentifier, MODIFY user_id BINARY(16) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C703FCB7A76ED395 ON nexum_mfa_totp (user_id)');
        $this->addSql('ALTER TABLE nexum_mfa_totp ADD CONSTRAINT FK_C703FCB7A76ED395 FOREIGN KEY (user_id) REFERENCES nexum_user (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE nexum_mfa_challenge ADD user_id BINARY(16) DEFAULT NULL');
        $this->addSql('UPDATE nexum_mfa_challenge challenge INNER JOIN nexum_user u ON u.email = challenge.userIdentifier SET challenge.user_id = u.id');
        $this->addSql('DELETE FROM nexum_mfa_challenge WHERE user_id IS NULL');
        $this->addSql('DROP INDEX IDX_NEXUM_MFA_CHALLENGE_USER ON nexum_mfa_challenge');
        $this->addSql('ALTER TABLE nexum_mfa_challenge DROP userIdentifier, MODIFY user_id BINARY(16) NOT NULL');
        $this->addSql('CREATE INDEX IDX_NEXUM_MFA_CHALLENGE_USER ON nexum_mfa_challenge (user_id)');
        $this->addSql('ALTER TABLE nexum_mfa_challenge ADD CONSTRAINT FK_A809311EA76ED395 FOREIGN KEY (user_id) REFERENCES nexum_user (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE nexum_passkey ADD user_id BINARY(16) DEFAULT NULL');
        $this->addSql('UPDATE nexum_passkey passkey INNER JOIN nexum_user u ON u.email = passkey.userIdentifier SET passkey.user_id = u.id');
        $this->addSql('DELETE FROM nexum_passkey WHERE user_id IS NULL');
        $this->addSql('DROP INDEX IDX_NEXUM_PASSKEY_USER ON nexum_passkey');
        $this->addSql('ALTER TABLE nexum_passkey DROP userIdentifier, MODIFY user_id BINARY(16) NOT NULL');
        $this->addSql('CREATE INDEX IDX_NEXUM_PASSKEY_USER ON nexum_passkey (user_id)');
        $this->addSql('ALTER TABLE nexum_passkey ADD CONSTRAINT FK_A3D21A85A76ED395 FOREIGN KEY (user_id) REFERENCES nexum_user (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE nexum_passkey_challenge ADD user_id BINARY(16) DEFAULT NULL');
        $this->addSql('UPDATE nexum_passkey_challenge challenge INNER JOIN nexum_user u ON u.email = challenge.userIdentifier SET challenge.user_id = u.id');
        $this->addSql("DELETE FROM nexum_passkey_challenge WHERE userIdentifier IS NOT NULL AND user_id IS NULL");
        $this->addSql('ALTER TABLE nexum_passkey_challenge DROP userIdentifier');
        $this->addSql('CREATE INDEX IDX_NEXUM_PASSKEY_CHALLENGE_USER ON nexum_passkey_challenge (user_id)');
        $this->addSql('ALTER TABLE nexum_passkey_challenge ADD CONSTRAINT FK_912C9A74A76ED395 FOREIGN KEY (user_id) REFERENCES nexum_user (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE Invoice ADD owner_id BINARY(16) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_5FD82ED87E3C61F9 ON Invoice (owner_id)');
        $this->addSql('ALTER TABLE Invoice ADD CONSTRAINT FK_5FD82ED87E3C61F9 FOREIGN KEY (owner_id) REFERENCES nexum_user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'This migration can only be executed safely on MySQL or MariaDB.',
        );

        $this->addSql('ALTER TABLE Invoice DROP FOREIGN KEY FK_5FD82ED87E3C61F9');
        $this->addSql('DROP INDEX IDX_5FD82ED87E3C61F9 ON Invoice');
        $this->addSql('ALTER TABLE Invoice DROP owner_id');

        $this->addSql('ALTER TABLE nexum_passkey_challenge ADD userIdentifier VARCHAR(180) DEFAULT NULL');
        $this->addSql('UPDATE nexum_passkey_challenge challenge INNER JOIN nexum_user u ON u.id = challenge.user_id SET challenge.userIdentifier = u.email');
        $this->addSql('ALTER TABLE nexum_passkey_challenge DROP FOREIGN KEY FK_912C9A74A76ED395');
        $this->addSql('DROP INDEX IDX_NEXUM_PASSKEY_CHALLENGE_USER ON nexum_passkey_challenge');
        $this->addSql('ALTER TABLE nexum_passkey_challenge DROP user_id');

        $this->addSql('ALTER TABLE nexum_passkey ADD userIdentifier VARCHAR(180) DEFAULT NULL');
        $this->addSql('UPDATE nexum_passkey passkey INNER JOIN nexum_user u ON u.id = passkey.user_id SET passkey.userIdentifier = u.email');
        $this->addSql('ALTER TABLE nexum_passkey MODIFY userIdentifier VARCHAR(180) NOT NULL');
        $this->addSql('ALTER TABLE nexum_passkey DROP FOREIGN KEY FK_A3D21A85A76ED395');
        $this->addSql('DROP INDEX IDX_NEXUM_PASSKEY_USER ON nexum_passkey');
        $this->addSql('ALTER TABLE nexum_passkey DROP user_id');
        $this->addSql('CREATE INDEX IDX_NEXUM_PASSKEY_USER ON nexum_passkey (userIdentifier)');

        $this->addSql('ALTER TABLE nexum_mfa_challenge ADD userIdentifier VARCHAR(180) DEFAULT NULL');
        $this->addSql('UPDATE nexum_mfa_challenge challenge INNER JOIN nexum_user u ON u.id = challenge.user_id SET challenge.userIdentifier = u.email');
        $this->addSql('ALTER TABLE nexum_mfa_challenge MODIFY userIdentifier VARCHAR(180) NOT NULL');
        $this->addSql('ALTER TABLE nexum_mfa_challenge DROP FOREIGN KEY FK_A809311EA76ED395');
        $this->addSql('DROP INDEX IDX_NEXUM_MFA_CHALLENGE_USER ON nexum_mfa_challenge');
        $this->addSql('ALTER TABLE nexum_mfa_challenge DROP user_id');
        $this->addSql('CREATE INDEX IDX_NEXUM_MFA_CHALLENGE_USER ON nexum_mfa_challenge (userIdentifier)');

        $this->addSql('ALTER TABLE nexum_mfa_totp ADD userIdentifier VARCHAR(180) DEFAULT NULL');
        $this->addSql('UPDATE nexum_mfa_totp enrollment INNER JOIN nexum_user u ON u.id = enrollment.user_id SET enrollment.userIdentifier = u.email');
        $this->addSql('ALTER TABLE nexum_mfa_totp MODIFY userIdentifier VARCHAR(180) NOT NULL');
        $this->addSql('ALTER TABLE nexum_mfa_totp DROP FOREIGN KEY FK_C703FCB7A76ED395');
        $this->addSql('DROP INDEX UNIQ_C703FCB7A76ED395 ON nexum_mfa_totp');
        $this->addSql('ALTER TABLE nexum_mfa_totp DROP user_id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C703FCB7750FAC43 ON nexum_mfa_totp (userIdentifier)');

        $this->addSql('ALTER TABLE nexum_access_token ADD userIdentifier VARCHAR(180) DEFAULT NULL');
        $this->addSql('UPDATE nexum_access_token token INNER JOIN nexum_user u ON u.id = token.user_id SET token.userIdentifier = u.email');
        $this->addSql('ALTER TABLE nexum_access_token MODIFY userIdentifier VARCHAR(180) NOT NULL');
        $this->addSql('ALTER TABLE nexum_access_token DROP FOREIGN KEY FK_5FB2E056A76ED395');
        $this->addSql('DROP INDEX IDX_5FB2E056A76ED395 ON nexum_access_token');
        $this->addSql('ALTER TABLE nexum_access_token DROP user_id');
    }
}
