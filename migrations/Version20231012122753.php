<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231012122753 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE comment_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE publication_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE tag_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "user_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE comment (id INT NOT NULL, post_id INT NOT NULL, author_id INT NOT NULL, content TEXT NOT NULL, published_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_9474526C4B89032C ON comment (post_id)');
        $this->addSql('CREATE INDEX IDX_9474526CF675F31B ON comment (author_id)');
        $this->addSql('COMMENT ON COLUMN comment.published_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE publication (id INT NOT NULL, author_id INT NOT NULL, title VARCHAR(180) NOT NULL, summary VARCHAR(255) NOT NULL, content TEXT NOT NULL, published_at DATE NOT NULL, updated_at DATE NOT NULL, file_path VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_AF3C6779F675F31B ON publication (author_id)');
        $this->addSql('CREATE TABLE tag (id INT NOT NULL, name VARCHAR(25) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE tag_publication (tag_id INT NOT NULL, publication_id INT NOT NULL, PRIMARY KEY(tag_id, publication_id))');
        $this->addSql('CREATE INDEX IDX_B3F51D99BAD26311 ON tag_publication (tag_id)');
        $this->addSql('CREATE INDEX IDX_B3F51D9938B217A7 ON tag_publication (publication_id)');
        $this->addSql('CREATE TABLE "user" (id INT NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, email VARCHAR(50) NOT NULL, is_verified BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON "user" (username)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C4B89032C FOREIGN KEY (post_id) REFERENCES publication (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CF675F31B FOREIGN KEY (author_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE publication ADD CONSTRAINT FK_AF3C6779F675F31B FOREIGN KEY (author_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tag_publication ADD CONSTRAINT FK_B3F51D99BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tag_publication ADD CONSTRAINT FK_B3F51D9938B217A7 FOREIGN KEY (publication_id) REFERENCES publication (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE comment_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE publication_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE tag_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "user_id_seq" CASCADE');
        $this->addSql('ALTER TABLE comment DROP CONSTRAINT FK_9474526C4B89032C');
        $this->addSql('ALTER TABLE comment DROP CONSTRAINT FK_9474526CF675F31B');
        $this->addSql('ALTER TABLE publication DROP CONSTRAINT FK_AF3C6779F675F31B');
        $this->addSql('ALTER TABLE tag_publication DROP CONSTRAINT FK_B3F51D99BAD26311');
        $this->addSql('ALTER TABLE tag_publication DROP CONSTRAINT FK_B3F51D9938B217A7');
        $this->addSql('DROP TABLE comment');
        $this->addSql('DROP TABLE publication');
        $this->addSql('DROP TABLE tag');
        $this->addSql('DROP TABLE tag_publication');
        $this->addSql('DROP TABLE "user"');
    }
}
