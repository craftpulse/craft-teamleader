<?php

namespace craftpulse\teamleader\elements\db;

use Craft;
use craft\elements\db\ElementQuery;

/**
 * Quotation query
 */
class QuotationQuery extends ElementQuery
{
    protected function beforePrepare(): bool
    {
        // todo: join the `quotations` table
        // $this->joinElementTable('quotations');

        // todo: apply any custom query params
        // ...

        return parent::beforePrepare();
    }
}
