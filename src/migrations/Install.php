<?php

namespace craft\teamleader\migrations;

use craft\db\Migration;
use craftpulse\teamleader\db\Table;
use craftpulse\teamleader\records\CompanyRecord;

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
     * @throws Exception
     */
    public function safeUp(): bool
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
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
        Craft::$app->getFields()->deleteLayoutsByType(RouteElement::class);

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
