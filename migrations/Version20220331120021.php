<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220331120021 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE replay ADD replay_data_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE replay ADD CONSTRAINT FK_D937F4F2F3586781 FOREIGN KEY (replay_data_id) REFERENCES replay_data (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D937F4F2F3586781 ON replay (replay_data_id)');
        $this->addSql('ALTER TABLE replay_data DROP CONSTRAINT fk_baf88cf6186ce3e1');
        $this->addSql('DROP INDEX uniq_baf88cf6186ce3e1');
        $this->addSql('ALTER TABLE replay_data DROP replay_id');
        $this->addSql('DROP INDEX season_active_uidx');
        $this->addSql('CREATE UNIQUE INDEX season_active_uidx ON season (active) WHERE active = true');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX season_active_uidx');
        $this->addSql('CREATE UNIQUE INDEX season_active_uidx ON season (active) WHERE (active = true)');
        $this->addSql('ALTER TABLE replay_data ADD replay_id INT NOT NULL');
        $this->addSql('ALTER TABLE replay_data ADD CONSTRAINT fk_baf88cf6186ce3e1 FOREIGN KEY (replay_id) REFERENCES replay (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_baf88cf6186ce3e1 ON replay_data (replay_id)');
        $this->addSql('ALTER TABLE replay DROP CONSTRAINT FK_D937F4F2F3586781');
        $this->addSql('DROP INDEX UNIQ_D937F4F2F3586781');
        $this->addSql('ALTER TABLE replay DROP replay_data_id');
    }
}
