<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260721084711 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE measure_protection (id INT AUTO_INCREMENT NOT NULL, measure_type VARCHAR(255) NOT NULL, judgment_date DATE NOT NULL, start_date DATE NOT NULL, end_date DATE DEFAULT NULL, duration_years INT DEFAULT NULL, tribunal_name VARCHAR(255) DEFAULT NULL, tribunal_city VARCHAR(255) DEFAULT NULL, cabinet_number VARCHAR(255) DEFAULT NULL, note LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, dossier_id INT NOT NULL, INDEX IDX_CF84BE69611C0C56 (dossier_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE measure_protection ADD CONSTRAINT FK_CF84BE69611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE measure_protection DROP FOREIGN KEY FK_CF84BE69611C0C56');
        $this->addSql('DROP TABLE measure_protection');
    }
}
