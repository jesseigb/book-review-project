<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211122153159 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book ADD image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY review_book_id_fk');
        $this->addSql('DROP INDEX review_book_id_fk ON review');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book DROP image');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT review_book_id_fk FOREIGN KEY (book_id) REFERENCES book (id)');
        $this->addSql('CREATE INDEX review_book_id_fk ON review (book_id)');
    }
}
