<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220201140541 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE material DROP FOREIGN KEY FK_7CBE7595922726E9');
        $this->addSql('ALTER TABLE material ADD CONSTRAINT FK_7CBE7595922726E9 FOREIGN KEY (cover_id) REFERENCES cover (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE messenger_messages CHANGE queue_name queue_name VARCHAR(190) NOT NULL');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cover CHANGE file_path file_path VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE agency_id agency_id VARCHAR(16) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE material DROP FOREIGN KEY FK_7CBE7595922726E9');
        $this->addSql('ALTER TABLE material CHANGE is_identifier is_identifier VARCHAR(50) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE is_type is_type VARCHAR(5) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE agency_id agency_id VARCHAR(16) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE material ADD CONSTRAINT FK_7CBE7595922726E9 FOREIGN KEY (cover_id) REFERENCES cover (id)');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0 ON messenger_messages');
        $this->addSql('DROP INDEX IDX_75EA56E0E3BD61CE ON messenger_messages');
        $this->addSql('ALTER TABLE messenger_messages CHANGE body body LONGTEXT NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE headers headers LONGTEXT NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE queue_name queue_name VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
