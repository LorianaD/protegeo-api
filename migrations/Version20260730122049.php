<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260730122049 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE banking_transaction (id INT AUTO_INCREMENT NOT NULL, amount NUMERIC(13, 3) NOT NULL, operation_date DATE NOT NULL, movement_type VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, source_bank_account_id INT NOT NULL, destination_bank_account_id INT NOT NULL, INDEX IDX_CB2707D2B4D4B707 (source_bank_account_id), INDEX IDX_CB2707D2362259DB (destination_bank_account_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE banking_transaction ADD CONSTRAINT FK_CB2707D2B4D4B707 FOREIGN KEY (source_bank_account_id) REFERENCES bank_account (id)');
        $this->addSql('ALTER TABLE banking_transaction ADD CONSTRAINT FK_CB2707D2362259DB FOREIGN KEY (destination_bank_account_id) REFERENCES bank_account (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE banking_transaction DROP FOREIGN KEY FK_CB2707D2B4D4B707');
        $this->addSql('ALTER TABLE banking_transaction DROP FOREIGN KEY FK_CB2707D2362259DB');
        $this->addSql('DROP TABLE banking_transaction');
    }
}
