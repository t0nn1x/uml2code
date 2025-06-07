<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250607123857 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user_statistics table for comprehensive dashboard statistics tracking';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE user_statistics (id SERIAL NOT NULL, user_id INT NOT NULL, total_parse_actions INT DEFAULT 0 NOT NULL, total_convert_actions INT DEFAULT 0 NOT NULL, total_generate_actions INT DEFAULT 0 NOT NULL, total_files_generated INT DEFAULT 0 NOT NULL, total_lines_of_code BIGINT DEFAULT 0 NOT NULL, language_statistics JSON NOT NULL, last_updated TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_45B44DCEA76ED395 ON user_statistics (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_user_statistics_user ON user_statistics (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN user_statistics.last_updated IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN user_statistics.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_statistics ADD CONSTRAINT FK_45B44DCEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_statistics DROP CONSTRAINT FK_45B44DCEA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_statistics
        SQL);
    }
}
