<?php

namespace craftpulse\teamleader\elements\db;

use Craft;
use craft\elements\db\ElementQuery;
use craftpulse\teamleader\db\Table;

/**
 * Company query
 */
class CompanyQuery extends ElementQuery
{
    protected function beforePrepare(): bool
    {
        // todo: join the `companies` table
         $this->joinElementTable(TABLE::COMPANIES);

        // todo: apply any custom query params
        $this->query->select([
            'teamleader_focus_companies.marketingMailsConsent',
            'teamleader_focus_companies.nationalIdentificationNumber',
            'teamleader_focus_companies.vatNumber',
            'teamleader_focus_companies.website',
            'teamleader_focus_companies.emails',
            'teamleader_focus_companies.telephones',
            'teamleader_focus_companies.name',
        ]);

        return parent::beforePrepare();
    }
}
