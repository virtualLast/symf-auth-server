<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251219220527 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'added access level and roles to db ';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE app_users ADD access_levels JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE app_users ALTER roles SET DEFAULT \'["ROLE_USER"]\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE app_users DROP access_levels');
        $this->addSql('ALTER TABLE app_users ALTER roles DROP DEFAULT');
    }
}
