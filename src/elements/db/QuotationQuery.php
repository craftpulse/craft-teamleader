<?php

namespace craftpulse\teamleader\elements\db;

use Craft;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
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
            'teamleader_focus_quotations.currency',
            'teamleader_focus_quotations.dealId',
            'teamleader_focus_quotations.phase',
            'teamleader_focus_quotations.productId',
            'teamleader_focus_quotations.quotationLines',
            'teamleader_focus_quotations.totalTaxInclusiveAmount',
        ]);
        return parent::beforePrepare();
    }
}
