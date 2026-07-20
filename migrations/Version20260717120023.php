<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260717120023 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dossier_users (id INT AUTO_INCREMENT NOT NULL, role_type VARCHAR(100) NOT NULL, user_id INT NOT NULL, dossier_id INT NOT NULL, INDEX IDX_DD03BFAEA76ED395 (user_id), INDEX IDX_DD03BFAE611C0C56 (dossier_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE dossier_users ADD CONSTRAINT FK_DD03BFAEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE dossier_users ADD CONSTRAINT FK_DD03BFAE611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier_users DROP FOREIGN KEY FK_DD03BFAEA76ED395');
        $this->addSql('ALTER TABLE dossier_users DROP FOREIGN KEY FK_DD03BFAE611C0C56');
        $this->addSql('DROP TABLE dossier_users');
    }
}
