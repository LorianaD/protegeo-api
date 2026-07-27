<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260725164640 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bank_account (id INT AUTO_INCREMENT NOT NULL, bank_name VARCHAR(255) NOT NULL, agency_name VARCHAR(255) DEFAULT NULL, account_type VARCHAR(255) NOT NULL, account_label VARCHAR(255) DEFAULT NULL, account_number_masked VARCHAR(255) NOT NULL, iban_masked VARCHAR(255) DEFAULT NULL, bic VARCHAR(255) DEFAULT NULL, opened_at DATETIME NOT NULL, closed_at DATETIME DEFAULT NULL, validated_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, dossier_id INT NOT NULL, INDEX IDX_53A23E0A611C0C56 (dossier_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE bank_account ADD CONSTRAINT FK_53A23E0A611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bank_account DROP FOREIGN KEY FK_53A23E0A611C0C56');
        $this->addSql('DROP TABLE bank_account');
    }
}
