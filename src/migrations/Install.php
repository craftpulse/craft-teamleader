<?php

namespace craft\teamleader\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\Db;

use craftpulse\teamleader\Teamleader;
use craftpulse\teamleader\db\Table;

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
     */
    public function safeUp(): bool
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;

        // Making sure to Auth module creates tables
        Auth::getInstance()->migrator->up();

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
        //Craft::$app->getFields()->deleteLayoutsByType(Element::class);

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates the tables.
     *
     * @return bool
     */
    protected function createTables(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function addForeignKeys(): void
    {}

    public function addFieldLayout(): void
    {}

    /**
     * @inheritdoc
     */
    public function dropForeignKeys(): void
    {}

    /**
     * @inheritdoc
     */
    public function dropTables(): void
    {}
}
