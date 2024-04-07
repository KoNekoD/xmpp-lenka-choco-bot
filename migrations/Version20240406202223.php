<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240406202223
    extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(
            'CREATE TABLE chat_configuration (id VARCHAR(26) NOT NULL, newbie_notify BOOLEAN NOT NULL, mute_enabled BOOLEAN NOT NULL, PRIMARY KEY(id))'
        );
        $this->addSql(
            'CREATE TABLE chat_member_rank (id VARCHAR(26) NOT NULL, rank SMALLINT NOT NULL, PRIMARY KEY(id))'
        );
        $this->addSql(
            'CREATE TABLE job (id VARCHAR(26) NOT NULL, handled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, result_payload JSON NOT NULL, payload JSON NOT NULL, type VARCHAR(255) NOT NULL, PRIMARY KEY(id))'
        );
        $this->addSql(
            'COMMENT ON COLUMN job.handled_at IS \'(DC2Type:datetime_immutable)\''
        );
        $this->addSql(
            'COMMENT ON COLUMN job.completed_at IS \'(DC2Type:datetime_immutable)\''
        );
        $this->addSql(
            'CREATE TABLE marry (id VARCHAR(26) NOT NULL, marry_general_status VARCHAR(255) NOT NULL, PRIMARY KEY(id))'
        );
        $this->addSql(
            'CREATE TABLE member (id VARCHAR(26) NOT NULL, rank_id VARCHAR(26) DEFAULT NULL, chat_id VARCHAR(26) DEFAULT NULL, user_id VARCHAR(26) DEFAULT NULL, since_spent_time TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, reputation INT NOT NULL, reputation_change_quota_last_updated TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, reputation_change_quota INT NOT NULL, status SMALLINT NOT NULL, PRIMARY KEY(id))'
        );
        $this->addSql(
            'CREATE UNIQUE INDEX UNIQ_70E4FA787616678F ON member (rank_id)'
        );
        $this->addSql('CREATE INDEX IDX_70E4FA781A9A7125 ON member (chat_id)');
        $this->addSql('CREATE INDEX IDX_70E4FA78A76ED395 ON member (user_id)');
        $this->addSql(
            'COMMENT ON COLUMN member.since_spent_time IS \'(DC2Type:datetime_immutable)\''
        );
        $this->addSql(
            'COMMENT ON COLUMN member.reputation_change_quota_last_updated IS \'(DC2Type:datetime_immutable)\''
        );
        $this->addSql(
            'CREATE TABLE member_warn (id VARCHAR(26) NOT NULL, warned_id VARCHAR(26) DEFAULT NULL, creator_id VARCHAR(26) DEFAULT NULL, chat_id VARCHAR(26) DEFAULT NULL, expired BOOLEAN NOT NULL, reason VARCHAR(255) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))'
        );
        $this->addSql(
            'CREATE INDEX IDX_D5329D595B1531F4 ON member_warn (warned_id)'
        );
        $this->addSql(
            'CREATE INDEX IDX_D5329D5961220EA6 ON member_warn (creator_id)'
        );
        $this->addSql(
            'CREATE INDEX IDX_D5329D591A9A7125 ON member_warn (chat_id)'
        );
        $this->addSql(
            'COMMENT ON COLUMN member_warn.expires_at IS \'(DC2Type:datetime_immutable)\''
        );
        $this->addSql(
            'CREATE TABLE unknown_update_element (id VARCHAR(26) NOT NULL, payload JSON NOT NULL, PRIMARY KEY(id))'
        );
        $this->addSql(
            'CREATE TABLE update (id VARCHAR(26) NOT NULL, chat_id VARCHAR(26) DEFAULT NULL, message_id VARCHAR(26) DEFAULT NULL, handle_status SMALLINT NOT NULL, handle_retries_count SMALLINT NOT NULL, PRIMARY KEY(id))'
        );
        $this->addSql('CREATE INDEX IDX_982535781A9A7125 ON update (chat_id)');
        $this->addSql(
            'CREATE INDEX IDX_98253578537A1329 ON update (message_id)'
        );
        $this->addSql(
            'CREATE TABLE update_chat (id VARCHAR(26) NOT NULL, configuration_id VARCHAR(26) DEFAULT NULL, type VARCHAR(255) NOT NULL, jid VARCHAR(255) NOT NULL, title VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))'
        );
        $this->addSql(
            'CREATE UNIQUE INDEX UNIQ_B1F1A53544E1E24B ON update_chat (jid)'
        );
        $this->addSql(
            'CREATE UNIQUE INDEX UNIQ_B1F1A53573F32DD8 ON update_chat (configuration_id)'
        );
        $this->addSql(
            'CREATE TABLE update_message (id VARCHAR(26) NOT NULL, from_id VARCHAR(26) DEFAULT NULL, text TEXT NOT NULL, from_resource VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))'
        );
        $this->addSql(
            'CREATE INDEX IDX_F10C298678CED90B ON update_message (from_id)'
        );
        $this->addSql(
            'CREATE TABLE update_user (id VARCHAR(26) NOT NULL, marry_id VARCHAR(26) DEFAULT NULL, marry_status VARCHAR(255) DEFAULT NULL, type VARCHAR(255) NOT NULL, jid VARCHAR(255) NOT NULL, PRIMARY KEY(id))'
        );
        $this->addSql(
            'CREATE UNIQUE INDEX UNIQ_59FF81D644E1E24B ON update_user (jid)'
        );
        $this->addSql(
            'CREATE INDEX IDX_59FF81D649C2C103 ON update_user (marry_id)'
        );
        $this->addSql(
            'ALTER TABLE member ADD CONSTRAINT FK_70E4FA787616678F FOREIGN KEY (rank_id) REFERENCES chat_member_rank (id) NOT DEFERRABLE INITIALLY IMMEDIATE'
        );
        $this->addSql(
            'ALTER TABLE member ADD CONSTRAINT FK_70E4FA781A9A7125 FOREIGN KEY (chat_id) REFERENCES update_chat (id) NOT DEFERRABLE INITIALLY IMMEDIATE'
        );
        $this->addSql(
            'ALTER TABLE member ADD CONSTRAINT FK_70E4FA78A76ED395 FOREIGN KEY (user_id) REFERENCES update_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE'
        );
        $this->addSql(
            'ALTER TABLE member_warn ADD CONSTRAINT FK_D5329D595B1531F4 FOREIGN KEY (warned_id) REFERENCES member (id) NOT DEFERRABLE INITIALLY IMMEDIATE'
        );
        $this->addSql(
            'ALTER TABLE member_warn ADD CONSTRAINT FK_D5329D5961220EA6 FOREIGN KEY (creator_id) REFERENCES member (id) NOT DEFERRABLE INITIALLY IMMEDIATE'
        );
        $this->addSql(
            'ALTER TABLE member_warn ADD CONSTRAINT FK_D5329D591A9A7125 FOREIGN KEY (chat_id) REFERENCES update_chat (id) NOT DEFERRABLE INITIALLY IMMEDIATE'
        );
        $this->addSql(
            'ALTER TABLE update ADD CONSTRAINT FK_982535781A9A7125 FOREIGN KEY (chat_id) REFERENCES update_chat (id) NOT DEFERRABLE INITIALLY IMMEDIATE'
        );
        $this->addSql(
            'ALTER TABLE update ADD CONSTRAINT FK_98253578537A1329 FOREIGN KEY (message_id) REFERENCES update_message (id) NOT DEFERRABLE INITIALLY IMMEDIATE'
        );
        $this->addSql(
            'ALTER TABLE update_chat ADD CONSTRAINT FK_B1F1A53573F32DD8 FOREIGN KEY (configuration_id) REFERENCES chat_configuration (id) NOT DEFERRABLE INITIALLY IMMEDIATE'
        );
        $this->addSql(
            'ALTER TABLE update_message ADD CONSTRAINT FK_F10C298678CED90B FOREIGN KEY (from_id) REFERENCES update_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE'
        );
        $this->addSql(
            'ALTER TABLE update_user ADD CONSTRAINT FK_59FF81D649C2C103 FOREIGN KEY (marry_id) REFERENCES marry (id) NOT DEFERRABLE INITIALLY IMMEDIATE'
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE member DROP CONSTRAINT FK_70E4FA787616678F');
        $this->addSql('ALTER TABLE member DROP CONSTRAINT FK_70E4FA781A9A7125');
        $this->addSql('ALTER TABLE member DROP CONSTRAINT FK_70E4FA78A76ED395');
        $this->addSql(
            'ALTER TABLE member_warn DROP CONSTRAINT FK_D5329D595B1531F4'
        );
        $this->addSql(
            'ALTER TABLE member_warn DROP CONSTRAINT FK_D5329D5961220EA6'
        );
        $this->addSql(
            'ALTER TABLE member_warn DROP CONSTRAINT FK_D5329D591A9A7125'
        );
        $this->addSql('ALTER TABLE update DROP CONSTRAINT FK_982535781A9A7125');
        $this->addSql('ALTER TABLE update DROP CONSTRAINT FK_98253578537A1329');
        $this->addSql(
            'ALTER TABLE update_chat DROP CONSTRAINT FK_B1F1A53573F32DD8'
        );
        $this->addSql(
            'ALTER TABLE update_message DROP CONSTRAINT FK_F10C298678CED90B'
        );
        $this->addSql(
            'ALTER TABLE update_user DROP CONSTRAINT FK_59FF81D649C2C103'
        );
        $this->addSql('DROP TABLE chat_configuration');
        $this->addSql('DROP TABLE chat_member_rank');
        $this->addSql('DROP TABLE job');
        $this->addSql('DROP TABLE marry');
        $this->addSql('DROP TABLE member');
        $this->addSql('DROP TABLE member_warn');
        $this->addSql('DROP TABLE unknown_update_element');
        $this->addSql('DROP TABLE update');
        $this->addSql('DROP TABLE update_chat');
        $this->addSql('DROP TABLE update_message');
        $this->addSql('DROP TABLE update_user');
    }
}
