<?php

namespace craftpulse\teamleader\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\Db;

use craftpulse\teamleader\db\Table;
use craftpulse\teamleader\records\CompanyRecord;

use Exception;
use Throwable;

/**
 * m250331_132339_create_teamleader_focus_company migration.
 */
class m250331_132339_create_teamleader_focus_company extends Migration
{
    // Public Properties
    // =========================================================================

    /**
     * @var ?string The database driver to use
     */
    public ?string $driver = null;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @throws Exception|Throwable
     */
    public function safeUp(): bool
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->addForeignKeys();

            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropForeignKeys();
        $this->dropTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates the tables.
     *
     * @return bool
     * @throws Exception|Throwable
     */
    protected function createTables(): bool
    {
        if(!$this->db->tableExists(CompanyRecord::tableName())) {
            $this->createTable(
                Table::COMPANY,
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                    'fieldLayoutId' => $this->integer(),

                    // data
                    'emails' => $this->json(),
                    'marketingMailsConsent' => $this->boolean(),
                    'name' => $this->string()->notNull(),
                    'nationalIdentificationNumber' => $this->string(),
                    'telephones' => $this->json(),
                    'vatNumber' => $this->string(),
                    'website' => $this->string(),
                ]
            );
        }

        return true;
    }

    /**
     * @return void
     */
    public function addForeignKeys(): void
    {
        $this->addForeignKey(
            null,
            Table::COMPANY,
            'id',
            '{{%elements}}',
            'id',
            'CASCADE',
            null
        );
    }

    /**
     * @return void
     */
    public function dropForeignKeys(): void
    {
        if ($this->db->tableExists(Table::COMPANY)) {
            Db::dropAllForeignKeysToTable(Table::COMPANY);
        }
    }

    /**
     * @return void
     */
    public function dropTables(): void
    {
        if (Craft::$app->db->schema->getTableSchema(Table::COMPANY)) {
            $this->dropTable(Table::COMPANY);
        }
    }
}
