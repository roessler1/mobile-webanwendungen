<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230707094240 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE song_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE track_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "user_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE track (id INT NOT NULL, album_id INT NOT NULL, name VARCHAR(255) NOT NULL, track_number INT NOT NULL, path VARCHAR(255) NOT NULL, duration INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D6E3F8A61137ABCF ON track (album_id)');
        $this->addSql('CREATE TABLE "user" (id INT NOT NULL, username VARCHAR(40) NOT NULL, password VARCHAR(100) NOT NULL, admin BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON "user" (username)');
        $this->addSql('ALTER TABLE track ADD CONSTRAINT FK_D6E3F8A61137ABCF FOREIGN KEY (album_id) REFERENCES album (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE song DROP CONSTRAINT fk_33edeea11137abcf');
        $this->addSql('DROP TABLE song');
        $this->addSql('ALTER TABLE album ADD year_created INT NOT NULL');
        $this->addSql('ALTER TABLE album RENAME COLUMN cover_directory TO cover');
        $this->addSql('ALTER TABLE artist ADD picture VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE track_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "user_id_seq" CASCADE');
        $this->addSql('CREATE SEQUENCE song_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE song (id INT NOT NULL, album_id INT NOT NULL, name VARCHAR(255) NOT NULL, duration INT NOT NULL, directory VARCHAR(255) NOT NULL, cover_directory VARCHAR(255) DEFAULT NULL, track_number INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_33edeea11137abcf ON song (album_id)');
        $this->addSql('ALTER TABLE song ADD CONSTRAINT fk_33edeea11137abcf FOREIGN KEY (album_id) REFERENCES album (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE track DROP CONSTRAINT FK_D6E3F8A61137ABCF');
        $this->addSql('DROP TABLE track');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('ALTER TABLE album DROP year_created');
        $this->addSql('ALTER TABLE album RENAME COLUMN cover TO cover_directory');
        $this->addSql('ALTER TABLE artist DROP picture');
    }
}
