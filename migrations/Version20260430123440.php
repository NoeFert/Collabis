<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260430123440 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE conversation (id INT AUTO_INCREMENT NOT NULL, subject VARCHAR(255) DEFAULT NULL, user_profile_id INT NOT NULL, interlocuteur_id INT NOT NULL, INDEX IDX_8A8E26E96B9DD454 (user_profile_id), INDEX IDX_8A8E26E95DC4D72E (interlocuteur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E96B9DD454 FOREIGN KEY (user_profile_id) REFERENCES user_profile (id)');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E95DC4D72E FOREIGN KEY (interlocuteur_id) REFERENCES user_profile (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E96B9DD454');
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E95DC4D72E');
        $this->addSql('DROP TABLE conversation');
    }
}
