<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220407132700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ranking ADD rounds_played INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE ranking ADD rounds_played_ratio NUMERIC(5, 2) DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE ranking ADD rounds_won INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE ranking ADD rounds_won_ratio NUMERIC(5, 2) DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE ranking ADD rounds_lost INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE ranking ADD games_played INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE ranking ADD games_played_ratio NUMERIC(5, 2) DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE ranking ADD games_won INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE ranking ADD games_won_ratio NUMERIC(5, 2) DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE ranking ADD games_lost INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE ranking ADD streak INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE ranking ADD recent VARCHAR(5) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE ranking ADD streak_best INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE ranking ADD activity NUMERIC(5, 2) DEFAULT \'0\' NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX owner_season_uidx ON ranking (owner_id, season_id)');
        $this->addSql('DROP INDEX season_active_uidx');
        $this->addSql('CREATE UNIQUE INDEX season_active_uidx ON season (active) WHERE active = true');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX owner_season_uidx');
        $this->addSql('ALTER TABLE ranking DROP rounds_played');
        $this->addSql('ALTER TABLE ranking DROP rounds_played_ratio');
        $this->addSql('ALTER TABLE ranking DROP rounds_won');
        $this->addSql('ALTER TABLE ranking DROP rounds_won_ratio');
        $this->addSql('ALTER TABLE ranking DROP rounds_lost');
        $this->addSql('ALTER TABLE ranking DROP games_played');
        $this->addSql('ALTER TABLE ranking DROP games_played_ratio');
        $this->addSql('ALTER TABLE ranking DROP games_won');
        $this->addSql('ALTER TABLE ranking DROP games_won_ratio');
        $this->addSql('ALTER TABLE ranking DROP games_lost');
        $this->addSql('ALTER TABLE ranking DROP streak');
        $this->addSql('ALTER TABLE ranking DROP recent');
        $this->addSql('ALTER TABLE ranking DROP streak_best');
        $this->addSql('ALTER TABLE ranking DROP activity');
        $this->addSql('DROP INDEX season_active_uidx');
        $this->addSql('CREATE UNIQUE INDEX season_active_uidx ON season (active) WHERE (active = true)');
    }
}
