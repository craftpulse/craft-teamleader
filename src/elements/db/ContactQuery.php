<?php

namespace craftpulse\teamleader\elements\db;

use Craft;
use craft\elements\db\ElementQuery;
use craftpulse\teamleader\db\Table;

/**
 * Contact query
 */
class ContactQuery extends ElementQuery
{
    protected function beforePrepare(): bool
    {
        // todo: join the `contacts` table
        $this->joinElementTable(Table::CONTACTS);

        // todo: apply any custom query params
        // ...

        return parent::beforePrepare();
    }
}
