<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260720102050 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE protected_person (id INT AUTO_INCREMENT NOT NULL, photo_url VARCHAR(500) DEFAULT NULL, civility VARCHAR(255) NOT NULL, firstname VARCHAR(255) NOT NULL, lastname VARCHAR(255) NOT NULL, birth_date DATE NOT NULL, birth_place VARCHAR(255) DEFAULT NULL, nationality VARCHAR(255) DEFAULT NULL, family_situation VARCHAR(255) DEFAULT NULL, children_situation INT DEFAULT NULL, address LONGTEXT DEFAULT NULL, postal_code VARCHAR(255) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, phone_number VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, profession VARCHAR(255) DEFAULT NULL, autonomy_level VARCHAR(255) DEFAULT NULL, situation_summary LONGTEXT DEFAULT NULL, deceased_at DATE DEFAULT NULL, family_note LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, dossier_id INT NOT NULL, UNIQUE INDEX UNIQ_A9B0F35E611C0C56 (dossier_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE protected_person ADD CONSTRAINT FK_A9B0F35E611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE protected_person DROP FOREIGN KEY FK_A9B0F35E611C0C56');
        $this->addSql('DROP TABLE protected_person');
    }
}
