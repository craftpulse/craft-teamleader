<?php

namespace craftpulse\teamleader\elements\db;

use Craft;
use craft\elements\db\ElementQuery;
use craftpulse\teamleader\db\Table;

/**
 * Quotation query
 */
class QuotationQuery extends ElementQuery
{
    protected function beforePrepare(): bool
    {
         $this->joinElementTable(Table::QUOTATIONS);

        $this->query->select([
            'teamleader_focus_quotations.dealId',
            'teamleader_focus_quotations.elementId',
            'teamleader_focus_quotations.phase',
        ]);

        return parent::beforePrepare();
    }
}
