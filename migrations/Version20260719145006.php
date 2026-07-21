<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260719145006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE rubrique_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE rubrique (id INT NOT NULL, parent_id INT DEFAULT NULL, titre VARCHAR(75) NOT NULL, slug VARCHAR(95) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8FA4097C727ACA70 ON rubrique (parent_id)');
        $this->addSql('ALTER TABLE rubrique ADD CONSTRAINT FK_8FA4097C727ACA70 FOREIGN KEY (parent_id) REFERENCES rubrique (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE publication ADD rubrique_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE publication ADD CONSTRAINT FK_AF3C67793BD38833 FOREIGN KEY (rubrique_id) REFERENCES rubrique (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_AF3C67793BD38833 ON publication (rubrique_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE publication DROP CONSTRAINT FK_AF3C67793BD38833');
        $this->addSql('DROP SEQUENCE rubrique_id_seq CASCADE');
        $this->addSql('ALTER TABLE rubrique DROP CONSTRAINT FK_8FA4097C727ACA70');
        $this->addSql('DROP TABLE rubrique');
        $this->addSql('DROP INDEX IDX_AF3C67793BD38833');
        $this->addSql('ALTER TABLE publication DROP rubrique_id');
    }
}
