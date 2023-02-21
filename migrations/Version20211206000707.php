<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211206000707 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE review RENAME INDEX idx_6970eb0f61220ea6 TO IDX_794381C661220EA6');
        $this->addSql('ALTER TABLE review RENAME INDEX idx_6970eb0f16a2b381 TO IDX_794381C616A2B381');
        $this->addSql('ALTER TABLE user ADD status TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE review RENAME INDEX idx_794381c616a2b381 TO IDX_6970EB0F16A2B381');
        $this->addSql('ALTER TABLE review RENAME INDEX idx_794381c661220ea6 TO IDX_6970EB0F61220EA6');
        $this->addSql('ALTER TABLE user DROP status');
    }
}
