<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260725154249 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contacts (id INT AUTO_INCREMENT NOT NULL, contact_category VARCHAR(255) NOT NULL, contact_type VARCHAR(255) NOT NULL, firstname VARCHAR(255) DEFAULT NULL, lastname VARCHAR(255) DEFAULT NULL, organization_name VARCHAR(255) DEFAULT NULL, job_function VARCHAR(255) DEFAULT NULL, profession VARCHAR(255) DEFAULT NULL, birth_date DATE DEFAULT NULL, birth_place VARCHAR(255) DEFAULT NULL, address VARCHAR(255) NOT NULL, phone VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, identifier VARCHAR(255) DEFAULT NULL, contact_person VARCHAR(255) DEFAULT NULL, protection_role VARCHAR(255) DEFAULT NULL, note LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, protected_person_id INT NOT NULL, INDEX IDX_33401573F493C7D1 (protected_person_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE contacts ADD CONSTRAINT FK_33401573F493C7D1 FOREIGN KEY (protected_person_id) REFERENCES protected_person (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contacts DROP FOREIGN KEY FK_33401573F493C7D1');
        $this->addSql('DROP TABLE contacts');
    }
}
