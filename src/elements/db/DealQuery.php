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
        $this->query->select([
            'teamleader_focus_deals.amount',
            'teamleader_focus_deals.currency',
            'teamleader_focus_deals.dateClosed',
            'teamleader_focus_deals.dateClosing',
            'teamleader_focus_deals.phase',
            'teamleader_focus_deals.reference',
            'teamleader_focus_deals.summary',
            'teamleader_focus_deals.webUrl',
        ]);

        return parent::beforePrepare();
    }
}
