<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230803121436 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users ALTER last_artists SET NOT NULL');
        $this->addSql('ALTER TABLE users ALTER last_albums SET NOT NULL');
        $this->addSql('COMMENT ON COLUMN users.last_artists IS NULL');
        $this->addSql('COMMENT ON COLUMN users.last_albums IS NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE users ALTER last_artists DROP NOT NULL');
        $this->addSql('ALTER TABLE users ALTER last_albums DROP NOT NULL');
        $this->addSql('COMMENT ON COLUMN users.last_artists IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN users.last_albums IS \'(DC2Type:array)\'');
    }
}
