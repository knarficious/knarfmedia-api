<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260328210859 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE media_object_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE media_object (id INT NOT NULL, publication_id INT NOT NULL, file_path VARCHAR(255) DEFAULT NULL, content_url VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_14D4313238B217A7 ON media_object (publication_id)');
        $this->addSql('ALTER TABLE media_object ADD CONSTRAINT FK_14D4313238B217A7 FOREIGN KEY (publication_id) REFERENCES publication (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE publication DROP file_path');
        $this->addSql('ALTER TABLE publication DROP mime_type');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE media_object_id_seq CASCADE');
        $this->addSql('ALTER TABLE media_object DROP CONSTRAINT FK_14D4313238B217A7');
        $this->addSql('DROP TABLE media_object');
        $this->addSql('ALTER TABLE publication ADD file_path VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE publication ADD mime_type VARCHAR(255) DEFAULT NULL');
    }
}
