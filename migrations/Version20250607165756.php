<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250607165756 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE system_logs (id SERIAL NOT NULL, user_id INT DEFAULT NULL, level VARCHAR(20) NOT NULL, channel VARCHAR(100) NOT NULL, message TEXT NOT NULL, context JSON DEFAULT NULL, extra JSON DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT NULL, request_uri VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_E14375AAA76ED395 ON system_logs (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_level_created ON system_logs (level, created_at)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_channel_created ON system_logs (channel, created_at)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN system_logs.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE system_logs ADD CONSTRAINT FK_E14375AAA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE system_logs DROP CONSTRAINT FK_E14375AAA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE system_logs
        SQL);
    }
}
