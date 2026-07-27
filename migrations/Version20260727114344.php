<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260727114344 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE transactions (id INT AUTO_INCREMENT NOT NULL, transaction_type VARCHAR(255) NOT NULL, category_type VARCHAR(255) NOT NULL, category_group VARCHAR(255) NOT NULL, label VARCHAR(255) DEFAULT NULL, amount NUMERIC(13, 3) NOT NULL, operation_date DATE NOT NULL, payment_method VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, account_id INT NOT NULL, bank_account_id INT DEFAULT NULL, INDEX IDX_EAA81A4C9B6B5FBA (account_id), INDEX IDX_EAA81A4C12CB990C (bank_account_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE transactions ADD CONSTRAINT FK_EAA81A4C9B6B5FBA FOREIGN KEY (account_id) REFERENCES management_account (id)');
        $this->addSql('ALTER TABLE transactions ADD CONSTRAINT FK_EAA81A4C12CB990C FOREIGN KEY (bank_account_id) REFERENCES bank_account (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transactions DROP FOREIGN KEY FK_EAA81A4C9B6B5FBA');
        $this->addSql('ALTER TABLE transactions DROP FOREIGN KEY FK_EAA81A4C12CB990C');
        $this->addSql('DROP TABLE transactions');
    }
}
