<?php

namespace craft\teamleader\migrations;

use Craft;
use craft\db\Migration;
use craft\db\MigrationManager;
use craft\helpers\Db;

use craftpulse\teamleader\db\Table;
use craftpulse\teamleader\elements\Company as CompanyElement;
use craftpulse\teamleader\records\CompanyRecord;

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
            $this->addFieldLayout();

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

    public function addFieldLayout(): void
    {
//        $fieldLayout = Craft::$app->getFields()->getLayoutByType(RouteElement::class) ?? new FieldLayout();
//
//        $tab = new FieldLayoutTab(['name' => 'Route']);
//        $tab->setLayout($fieldLayout);
//
//        $tab->setElements(Shortlink::$plugin->routes->createFields());
//        $fieldLayout->setTabs([$tab]);
//
//        Craft::$app->getFields()->saveLayout($fieldLayout);
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
