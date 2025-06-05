<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250605131822 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE action_history (id SERIAL NOT NULL, user_id INT NOT NULL, action_type VARCHAR(20) NOT NULL, diagram_type VARCHAR(50) NOT NULL, files JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_FD18F8AAA76ED395 ON action_history (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN action_history.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE action_history ADD CONSTRAINT FK_FD18F8AAA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);

        // Add indexes for performance
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_user_action_created ON action_history (user_id, action_type, created_at DESC)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_user_created ON action_history (user_id, created_at DESC)');

        // Add comment
        $this->addSql('COMMENT ON TABLE action_history IS \'Tracks user actions for convert, parse, and generate operations\'');
        $this->addSql('COMMENT ON COLUMN action_history.files IS \'JSON array of {filename, content} objects\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE action_history DROP CONSTRAINT FK_FD18F8AAA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE action_history
        SQL);
    }
}
