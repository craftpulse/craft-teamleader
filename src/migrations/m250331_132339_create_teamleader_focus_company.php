<?php

namespace craftpulse\teamleader\migrations;

use Craft;
use craft\db\Migration;
use craftpulse\teamleader\db\Table;

/**
 * m250331_132339_create_teamleader_focus_company migration.
 */
class m250331_132339_create_teamleader_focus_company extends Migration
{
    // Public Methods
    // =========================================================================
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Create the Company table:
        if (!$this->db->tableExists(Table::COMPANY)) {
            $this->createTable(Table::COMPANY, [
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'emails' => $this->json(),
                'id' => $this->primaryKey(),
                'marketing_mails_consent' => $this->boolean(),
                'name' => $this->string()->notNull(),
                'national_identification_number' => $this->string(),
                'telephones' => $this->json(),
                'uid' => $this->uid(),
                'vat_number' => $this->string(),
                'website' => $this->json(),
            ]);
        }

        // Give it a foreign key to the elements table:
        $this->addForeignKey(
            null,
            Table::COMPANY,
            'id',
            '{{%elements}}',
            'id',
            'CASCADE',
            null
        );

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        if ($this->db->tableExists(Table::COMPANY)) {
            // Drop the foreign key before dropping the table
            $this->dropForeignKeyIfExists(Table::COMPANY, 'id');

            // Drop the table
            $this->dropTableIfExists(Table::COMPANY);
        }

        return true;
    }

    // Private Methods
    // =========================================================================
    /**
     * Drops a foreign key if it exists.
     */
    private function dropForeignKeyIfExists(string $table, string $column)
    {
        $tableSchema = Craft::$app->db->schema->getTableSchema($table);

        if ($tableSchema !== null) {
            foreach ($tableSchema->foreignKeys as $fkName => $fk) {
                if (isset($fk[$column])) {
                    $this->dropForeignKey($fkName, $table);
                    break;
                }
            }
        }
    }

}
