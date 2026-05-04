<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260504093602 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE media (id INT AUTO_INCREMENT NOT NULL, url VARCHAR(255) NOT NULL, type VARCHAR(50) DEFAULT NULL, post_id INT NOT NULL, INDEX IDX_6A2CA10C4B89032C (post_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE media ADD CONSTRAINT FK_6A2CA10C4B89032C FOREIGN KEY (post_id) REFERENCES post (id)');
        $this->addSql('ALTER TABLE conversation ADD post_id INT DEFAULT NULL, ADD interlocutor_id INT NOT NULL');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E94B89032C FOREIGN KEY (post_id) REFERENCES post (id)');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E9B3F944DB FOREIGN KEY (interlocutor_id) REFERENCES user_profile (id)');
        $this->addSql('CREATE INDEX IDX_8A8E26E94B89032C ON conversation (post_id)');
        $this->addSql('CREATE INDEX IDX_8A8E26E9B3F944DB ON conversation (interlocutor_id)');
        $this->addSql('ALTER TABLE post DROP media_1_url, DROP media_2_url, DROP media_3_url');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE media DROP FOREIGN KEY FK_6A2CA10C4B89032C');
        $this->addSql('DROP TABLE media');
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E94B89032C');
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E9B3F944DB');
        $this->addSql('DROP INDEX IDX_8A8E26E94B89032C ON conversation');
        $this->addSql('DROP INDEX IDX_8A8E26E9B3F944DB ON conversation');
        $this->addSql('ALTER TABLE conversation DROP post_id, DROP interlocutor_id');
        $this->addSql('ALTER TABLE post ADD media_1_url VARCHAR(255) NOT NULL, ADD media_2_url VARCHAR(255) DEFAULT NULL, ADD media_3_url VARCHAR(255) DEFAULT NULL');
    }
}
