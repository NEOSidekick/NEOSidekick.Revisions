<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Migrations\AbortMigrationException;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20220131141858 extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'Add revision table';
    }

    /**
     * @throws Exception
     * @throws AbortMigrationException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('CREATE TABLE codeq_revisions_domain_model_revision (persistence_object_identifier VARCHAR(40) NOT NULL, creationdatetime DATETIME NOT NULL, creator VARCHAR(255) NOT NULL, content LONGBLOB NOT NULL, nodeidentifier VARCHAR(255) NOT NULL, PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    /**
     * @throws AbortMigrationException
     * @throws Exception
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('DROP TABLE codeq_revisions_domain_model_revision');
    }
}
