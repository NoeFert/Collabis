<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260505093432 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY `FK_8A8E26E95DC4D72E`');
        $this->addSql('DROP INDEX IDX_8A8E26E95DC4D72E ON conversation');
        $this->addSql('ALTER TABLE conversation DROP interlocuteur_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conversation ADD interlocuteur_id INT NOT NULL');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT `FK_8A8E26E95DC4D72E` FOREIGN KEY (interlocuteur_id) REFERENCES user_profile (id)');
        $this->addSql('CREATE INDEX IDX_8A8E26E95DC4D72E ON conversation (interlocuteur_id)');
    }
}
