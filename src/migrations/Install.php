<?php

namespace craft\teamleader\migrations;

use Craft;
use craft\db\Migration;
use craft\db\MigrationManager;
use craft\helpers\Db;

use craftpulse\teamleader\db\Table;
use craftpulse\teamleader\elements\Company as CompanyElement;
use craftpulse\teamleader\elements\Contact as ContactElement;
use craftpulse\teamleader\elements\Deal as DealElement;

use Exception;
use Throwable;
use verbb\auth\Auth;

class Install extends Migration
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

        // Making sure to Auth module creates tables
        /** @var MigrationManager $migrator */
        $migrator = Auth::getInstance()->get('migrator');
        $migrator->up();

        if ($this->createTables()) {
            $this->addForeignKeys();
            //$this->addFieldLayouts();

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
        Craft::$app->getFields()->deleteLayoutsByType(CompanyElement::class);
        Craft::$app->getFields()->deleteLayoutsByType(ContactElement::class);
        Craft::$app->getFields()->deleteLayoutsByType(DealElement::class);

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

        if(!$this->db->tableExists(Table::CONTACTS)) {
            $this->createTable(
                Table::CONTACTS,
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                    'fieldLayoutId' => $this->integer(),

                    // data
                ]
            );
        }

        if(!$this->db->tableExists(Table::DEALS)) {
            $this->createTable(
                Table::DEALS,
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                    'fieldLayoutId' => $this->integer(),

                    // data
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

        $this->addForeignKey(
            null,
            Table::CONTACTS,
            'id',
            '{{%elements}}',
            'id',
            'CASCADE',
            null
        );

        $this->addForeignKey(
            null,
            Table::DEALS,
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

        if ($this->db->tableExists(Table::CONTACTS)) {
            Db::dropAllForeignKeysToTable(Table::CONTACTS);
        }

        if ($this->db->tableExists(Table::DEALS)) {
            Db::dropAllForeignKeysToTable(Table::DEALS);
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

        if (Craft::$app->db->schema->getTableSchema(Table::CONTACTS)) {
            $this->dropTable(Table::CONTACTS);
        }

        if (Craft::$app->db->schema->getTableSchema(Table::DEALS)) {
            $this->dropTable(Table::DEALS);
        }
    }
}
