<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250605161320 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP INDEX idx_user_created
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE action_history ADD programming_language VARCHAR(50) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE action_history ADD generator_version VARCHAR(20) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE action_history ADD total_lines_of_code INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE action_history ADD diagram_name VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE action_history ADD diagram_size INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN action_history.files IS NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE action_history DROP programming_language
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE action_history DROP generator_version
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE action_history DROP total_lines_of_code
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE action_history DROP diagram_name
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE action_history DROP diagram_size
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN action_history.files IS 'JSON array of {filename, content} objects'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_user_created ON action_history (user_id, created_at)
        SQL);
    }
}
