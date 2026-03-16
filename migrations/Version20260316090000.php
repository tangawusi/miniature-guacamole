<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260316090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users and notes tables for the AJE challenge';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE app_user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, is_verified TINYINT(1) NOT NULL, verification_token VARCHAR(64) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \"(DC2Type:datetime_immutable)\", UNIQUE INDEX uniq_user_email (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE note (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, category VARCHAR(100) NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT \"(DC2Type:datetime_immutable)\", INDEX IDX_BF5476CAA76ED395 (user_id), INDEX idx_note_status (status), INDEX idx_note_category (category), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('DROP TABLE note');
        $this->addSql('DROP TABLE app_user');
    }
}
