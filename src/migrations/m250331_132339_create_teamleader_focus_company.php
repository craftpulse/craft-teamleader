<?php

namespace craftpulse\teamleader\migrations;

use Craft;
use craft\db\Migration;
use craftpulse\teamleader\db\Table;
use craftpulse\teamleader\records\CompanyRecord;

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
     * @throws Exception
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
     * @throws Exception
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
                    'marketing_mails_consent' => $this->boolean(),
                    'name' => $this->string()->notNull(),
                    'national_identification_number' => $this->string(),
                    'telephones' => $this->json(),
                    'vat_number' => $this->string(),
                    'website' => $this->string(),
                ]
            );
        }

        return true;
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function dropForeignKeys(): void
    {
        if ($this->db->tableExists(Table::COMPANY)) {
            Db::dropAllForeignKeysToTable(Table::COMPANY);
        }
    }

    /**
     * @inheritdoc
     */
    public function dropTables(): void
    {
        if (Craft::$app->db->schema->getTableSchema(Table::COMPANY)) {
            $this->dropTable(Table::COMPANY);
        }
    }
}
