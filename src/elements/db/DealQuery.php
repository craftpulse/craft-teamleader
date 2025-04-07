<?php

namespace craftpulse\teamleader\elements\db;

use Craft;
use craft\elements\db\ElementQuery;
use craftpulse\teamleader\db\Table;

/**
 * Deal query
 */
class DealQuery extends ElementQuery
{
    protected function beforePrepare(): bool
    {
        // todo: join the `deals` table
        $this->joinElementTable(Table::DEALS);

        // todo: apply any custom query params
        // ...

        return parent::beforePrepare();
    }
}
