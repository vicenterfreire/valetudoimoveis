<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261024154849 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE account_action_tokens (id BIGINT AUTO_INCREMENT NOT NULL, type VARCHAR(32) NOT NULL, token_hash VARCHAR(64) NOT NULL, requested_email VARCHAR(180) DEFAULT NULL, expires_at DATETIME NOT NULL, used_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, user_id BIGINT NOT NULL, UNIQUE INDEX UNIQ_F74C1C56B3BC57DA (token_hash), INDEX IDX_F74C1C56A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE agencies (id BIGINT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, slug VARCHAR(180) NOT NULL, document VARCHAR(20) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, phone VARCHAR(30) DEFAULT NULL, whatsapp VARCHAR(30) DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, logo_path VARCHAR(255) DEFAULT NULL, city VARCHAR(100) NOT NULL, state VARCHAR(2) NOT NULL, status VARCHAR(20) NOT NULL, blocked_at DATETIME DEFAULT NULL, status_changed_at DATETIME DEFAULT NULL, status_reason LONGTEXT DEFAULT NULL, blocked_reason LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_F65A4DC4989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE agency_status_history (id BIGINT AUTO_INCREMENT NOT NULL, previous_status VARCHAR(20) NOT NULL, new_status VARCHAR(20) NOT NULL, reason LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, agency_id BIGINT NOT NULL, admin_user_id BIGINT DEFAULT NULL, INDEX IDX_DEF55D06CDEADB2A (agency_id), INDEX IDX_DEF55D066352511C (admin_user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE contact_events (id BIGINT AUTO_INCREMENT NOT NULL, event_type VARCHAR(20) NOT NULL, channel VARCHAR(20) DEFAULT NULL, occurred_at DATETIME NOT NULL, agency_id BIGINT NOT NULL, property_id BIGINT NOT NULL, INDEX IDX_C31560CBCDEADB2A (agency_id), INDEX IDX_C31560CB549213EC (property_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE properties (id BIGINT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, slug VARCHAR(220) NOT NULL, description LONGTEXT NOT NULL, property_type VARCHAR(30) NOT NULL, listing_type VARCHAR(20) NOT NULL, price NUMERIC(12, 2) NOT NULL, condo_fee NUMERIC(12, 2) DEFAULT NULL, iptu_amount NUMERIC(12, 2) DEFAULT NULL, area_m2 NUMERIC(10, 2) DEFAULT NULL, bedrooms SMALLINT DEFAULT NULL, bathrooms SMALLINT DEFAULT NULL, parking_spaces SMALLINT DEFAULT NULL, city VARCHAR(100) NOT NULL, state VARCHAR(2) NOT NULL, neighborhood VARCHAR(100) DEFAULT NULL, street VARCHAR(180) DEFAULT NULL, street_number VARCHAR(20) DEFAULT NULL, zip_code VARCHAR(10) DEFAULT NULL, status VARCHAR(20) NOT NULL, moderation_status VARCHAR(30) NOT NULL, published_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, agency_id BIGINT NOT NULL, INDEX IDX_87C331C7CDEADB2A (agency_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE property_highlights (id BIGINT AUTO_INCREMENT NOT NULL, starts_at DATETIME NOT NULL, ends_at DATETIME NOT NULL, reason LONGTEXT NOT NULL, revoked_at DATETIME DEFAULT NULL, revocation_reason LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, property_id BIGINT NOT NULL, granted_by_id BIGINT DEFAULT NULL, revoked_by_id BIGINT DEFAULT NULL, INDEX IDX_8FD28355549213EC (property_id), INDEX IDX_8FD283553151C11F (granted_by_id), INDEX IDX_8FD28355FB8FE773 (revoked_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE property_images (id BIGINT AUTO_INCREMENT NOT NULL, file_path VARCHAR(255) NOT NULL, thumbnail_path VARCHAR(255) DEFAULT NULL, alt_text VARCHAR(180) DEFAULT NULL, sort_order SMALLINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, property_id BIGINT NOT NULL, INDEX IDX_9E68D116549213EC (property_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE property_status_history (id BIGINT AUTO_INCREMENT NOT NULL, status_type VARCHAR(20) NOT NULL, previous_status VARCHAR(30) DEFAULT NULL, new_status VARCHAR(30) NOT NULL, reason LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, property_id BIGINT NOT NULL, actor_id BIGINT DEFAULT NULL, INDEX IDX_F9AEDCCA549213EC (property_id), INDEX IDX_F9AEDCCA10DAF24A (actor_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_audit_logs (id BIGINT AUTO_INCREMENT NOT NULL, action VARCHAR(40) NOT NULL, details JSON NOT NULL, created_at DATETIME NOT NULL, actor_user_id BIGINT DEFAULT NULL, target_user_id BIGINT DEFAULT NULL, agency_id BIGINT DEFAULT NULL, INDEX IDX_7148EEB4859B83FF (actor_user_id), INDEX IDX_7148EEB46C066AFE (target_user_id), INDEX IDX_7148EEB4CDEADB2A (agency_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (id BIGINT AUTO_INCREMENT NOT NULL, full_name VARCHAR(150) NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, agency_id BIGINT DEFAULT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), INDEX IDX_1483A5E9CDEADB2A (agency_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE account_action_tokens ADD CONSTRAINT FK_F74C1C56A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE agency_status_history ADD CONSTRAINT FK_DEF55D06CDEADB2A FOREIGN KEY (agency_id) REFERENCES agencies (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE agency_status_history ADD CONSTRAINT FK_DEF55D066352511C FOREIGN KEY (admin_user_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE contact_events ADD CONSTRAINT FK_C31560CBCDEADB2A FOREIGN KEY (agency_id) REFERENCES agencies (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contact_events ADD CONSTRAINT FK_C31560CB549213EC FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE properties ADD CONSTRAINT FK_87C331C7CDEADB2A FOREIGN KEY (agency_id) REFERENCES agencies (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE property_highlights ADD CONSTRAINT FK_8FD28355549213EC FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE property_highlights ADD CONSTRAINT FK_8FD283553151C11F FOREIGN KEY (granted_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE property_highlights ADD CONSTRAINT FK_8FD28355FB8FE773 FOREIGN KEY (revoked_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE property_images ADD CONSTRAINT FK_9E68D116549213EC FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE property_status_history ADD CONSTRAINT FK_F9AEDCCA549213EC FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE property_status_history ADD CONSTRAINT FK_F9AEDCCA10DAF24A FOREIGN KEY (actor_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user_audit_logs ADD CONSTRAINT FK_7148EEB4859B83FF FOREIGN KEY (actor_user_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user_audit_logs ADD CONSTRAINT FK_7148EEB46C066AFE FOREIGN KEY (target_user_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user_audit_logs ADD CONSTRAINT FK_7148EEB4CDEADB2A FOREIGN KEY (agency_id) REFERENCES agencies (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9CDEADB2A FOREIGN KEY (agency_id) REFERENCES agencies (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account_action_tokens DROP FOREIGN KEY FK_F74C1C56A76ED395');
        $this->addSql('ALTER TABLE agency_status_history DROP FOREIGN KEY FK_DEF55D06CDEADB2A');
        $this->addSql('ALTER TABLE agency_status_history DROP FOREIGN KEY FK_DEF55D066352511C');
        $this->addSql('ALTER TABLE contact_events DROP FOREIGN KEY FK_C31560CBCDEADB2A');
        $this->addSql('ALTER TABLE contact_events DROP FOREIGN KEY FK_C31560CB549213EC');
        $this->addSql('ALTER TABLE properties DROP FOREIGN KEY FK_87C331C7CDEADB2A');
        $this->addSql('ALTER TABLE property_highlights DROP FOREIGN KEY FK_8FD28355549213EC');
        $this->addSql('ALTER TABLE property_highlights DROP FOREIGN KEY FK_8FD283553151C11F');
        $this->addSql('ALTER TABLE property_highlights DROP FOREIGN KEY FK_8FD28355FB8FE773');
        $this->addSql('ALTER TABLE property_images DROP FOREIGN KEY FK_9E68D116549213EC');
        $this->addSql('ALTER TABLE property_status_history DROP FOREIGN KEY FK_F9AEDCCA549213EC');
        $this->addSql('ALTER TABLE property_status_history DROP FOREIGN KEY FK_F9AEDCCA10DAF24A');
        $this->addSql('ALTER TABLE user_audit_logs DROP FOREIGN KEY FK_7148EEB4859B83FF');
        $this->addSql('ALTER TABLE user_audit_logs DROP FOREIGN KEY FK_7148EEB46C066AFE');
        $this->addSql('ALTER TABLE user_audit_logs DROP FOREIGN KEY FK_7148EEB4CDEADB2A');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9CDEADB2A');
        $this->addSql('DROP TABLE account_action_tokens');
        $this->addSql('DROP TABLE agencies');
        $this->addSql('DROP TABLE agency_status_history');
        $this->addSql('DROP TABLE contact_events');
        $this->addSql('DROP TABLE properties');
        $this->addSql('DROP TABLE property_highlights');
        $this->addSql('DROP TABLE property_images');
        $this->addSql('DROP TABLE property_status_history');
        $this->addSql('DROP TABLE user_audit_logs');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
