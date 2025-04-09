<?php

namespace craftpulse\teamleader\migrations;

use Craft;
use craft\db\Migration;
use craft\db\MigrationManager;
use craft\helpers\Db;

use craftpulse\teamleader\db\Table;
use craft\db\Table as CraftTable;
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
        if(!$this->db->tableExists(Table::COMPANIES)) {
            $this->createTable(
                TABLE::COMPANIES,
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                    'fieldLayoutId' => $this->integer(),

                    // connectors
                    'teamleaderId' => $this->uid(),

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

        if(!$this->db->tableExists(Table::COMPANIES_ADDRESSES)) {
            $this->createTable(
                TABLE::COMPANIES_ADDRESSES,
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),

                    // foreign keys
                    'addressId' => $this->integer(),
                    'companyId' => $this->integer(),
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

                    // connectors
                    'teamleaderId' => $this->uid(),

                    // data
                    'emails' => $this->json(),
                    'firstName' => $this->string()->notNull(),
                    'lastName' => $this->string()->notNull(),
                    'marketingMailsConsent' => $this->boolean(),
                    'nationalIdentificationNumber' => $this->string(),
                    'salutation' => $this->string(),
                    'language' => $this->string(),
                    'telephones' => $this->json(),
                ]
            );
        }

        if(!$this->db->tableExists(Table::CONTACTS_COMPANIES)) {
            $this->createTable(
                Table::CONTACTS_COMPANIES,
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),

                    // foreign keys
                    'companyId' => $this->integer(),
                    'contactId' => $this->integer(),
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

                    // connectors
                    'teamleaderId' => $this->uid(),

                    // foreign keys
                    'companyId' => $this->integer(),
                    'contactId' => $this->integer(),

                    // data
                    'amount' => $this->float()->notNull(),
                    'currency' => $this->string()->notNull(),
                    'dateClosed' => $this->dateTime(),
                    'dateClosing' => $this->dateTime(),
                    'phase' => $this->string(),
                    'reference' => $this->string(),
                    'state' => $this->string(),
                    'summary' => $this->string(),
                    'webUrl' => $this->string(),
                ]
            );
        }

        if(!$this->db->tableExists(Table::QUOTATIONS)) {
            $this->createTable(
                Table::QUOTATIONS,
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'dateExperiy' => $this->dateTime(),
                    'uid' => $this->uid(),

                    // foreign keys
                    'dealId' => $this->integer(),
                    'elementId' => $this->integer(),

                    // data
                    'currency' => $this->string()->notNull(),
                    'discounts' => $this->json(),
                    'puchasePrice' => $this->float()->notNull(),
                    'status' => $this->string(),
                    'taxAmount' => $this->float()->notNull(),
                    'taxRate' => $this->float()->notNull(),
                    'taxableAmount' => $this->float()->notNull(),
                    'totalTaxExclusiveAmount' => $this->float()->notNull(),
                    'totalTaxInclusiveAmount' => $this->float()->notNull(),
                    'quotationLines' => $this->json(),
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
        if($this->db->tableExists(Table::COMPANIES)) {
            $this->addForeignKey(
                null,
                Table::COMPANIES,
                'id',
                CraftTable::ELEMENTS,
                'id',
                'CASCADE',
                null
            );
        }

        if(
            $this->db->tableExists(Table::COMPANIES) &&
            $this->db->tableExists(Table::COMPANIES_ADDRESSES)
        ) {
            $this->addForeignKey(
                null,
                Table::COMPANIES_ADDRESSES,
                'addressId',
                CraftTable::ADDRESSES,
                'id',
                'CASCADE',
                'CASCADE'
            );

            $this->addForeignKey(
                null,
                Table::COMPANIES_ADDRESSES,
                'companyId',
                Table::COMPANIES,
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

        if($this->db->tableExists(Table::CONTACTS)) {
            $this->addForeignKey(
                null,
                Table::CONTACTS,
                'id',
                CraftTable::ELEMENTS,
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

        if(
            $this->db->tableExists(TABLE::COMPANIES) &&
            $this->db->tableExists(Table::CONTACTS) &&
            $this->db->tableExists(Table::CONTACTS_COMPANIES)
        ) {
            $this->addForeignKey(
                null,
                Table::CONTACTS_COMPANIES,
                'companyId',
                Table::COMPANIES,
                'id',
                'CASCADE',
                'CASCADE'
            );
            $this->addForeignKey(
                null,
                Table::CONTACTS_COMPANIES,
                'contactId',
                Table::CONTACTS,
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

        if($this->db->tableExists(Table::DEALS)) {
            $this->addForeignKey(
                null,
                Table::DEALS,
                'id',
                CraftTable::ELEMENTS,
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

        if(
            $this->db->tableExists(Table::DEALS) &&
            $this->db->tableExists(TABLE::COMPANIES) &&
            $this->db->tableExists(Table::CONTACTS)
        ) {
            $this->addForeignKey(
                null,
                Table::DEALS,
                'companyId',
                Table::COMPANIES,
                'id',
                'CASCADE',
                'CASCADE'
            );
            $this->addForeignKey(
                null,
                Table::DEALS,
                'contactId',
                Table::CONTACTS,
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

        if(
            $this->db->tableExists(Table::DEALS) &&
            $this->db->tableExists(Table::QUOTATIONS)
        ) {
            $this->addForeignKey(
                null,
                Table::QUOTATIONS,
                'id',
                CraftTable::ELEMENTS,
                'id',
                'CASCADE',
                'CASCADE'
            );

            $this->addForeignKey(
                null,
                Table::QUOTATIONS,
                'dealId',
                Table::DEALS,
                'id',
                'CASCADE',
                'CASCADE'
            );

            $this->addForeignKey(
                null,
                Table::QUOTATIONS,
                'elementId',
                CraftTable::ELEMENTS,
                'id',
                'CASCADE',
                null,
            );


        }
    }

    /**
     * @return void
     */
    public function dropForeignKeys(): void
    {
        if ($this->db->tableExists(TABLE::COMPANIES)) {
            Db::dropAllForeignKeysToTable(TABLE::COMPANIES);
        }

        if ($this->db->tableExists(TABLE::COMPANIES_ADDRESSES)) {
            Db::dropAllForeignKeysToTable(TABLE::COMPANIES_ADDRESSES);
        }

        if ($this->db->tableExists(Table::CONTACTS)) {
            Db::dropAllForeignKeysToTable(Table::CONTACTS);
        }

        if ($this->db->tableExists(Table::CONTACTS_COMPANIES)) {
            Db::dropAllForeignKeysToTable(Table::CONTACTS_COMPANIES);
        }

        if ($this->db->tableExists(Table::DEALS)) {
            Db::dropAllForeignKeysToTable(Table::DEALS);
        }

        if ($this->db->tableExists(Table::QUOTATIONS)) {
            Db::dropAllForeignKeysToTable(Table::QUOTATIONS);
        }
    }

    /**
     * @return void
     */
    public function dropTables(): void
    {
        if (Craft::$app->db->schema->getTableSchema(TABLE::COMPANIES)) {
            $this->dropTable(TABLE::COMPANIES);
        }

        if (Craft::$app->db->schema->getTableSchema(TABLE::COMPANIES_ADDRESSES)) {
            $this->dropTable(TABLE::COMPANIES_ADDRESSES);
        }

        if (Craft::$app->db->schema->getTableSchema(Table::CONTACTS)) {
            $this->dropTable(Table::CONTACTS);
        }

        if (Craft::$app->db->schema->getTableSchema(Table::CONTACTS_COMPANIES)) {
            $this->dropTable(Table::CONTACTS_COMPANIES);
        }

        if (Craft::$app->db->schema->getTableSchema(Table::DEALS)) {
            $this->dropTable(Table::DEALS);
        }

        if (Craft::$app->db->schema->getTableSchema(Table::QUOTATIONS)) {
            $this->dropTable(Table::QUOTATIONS);
        }
    }
}
