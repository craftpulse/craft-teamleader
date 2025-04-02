<?php

namespace craftpulse\teamleader\elements\db;

use Craft;
use craft\elements\db\ElementQuery;
use craftpulse\teamleader\db\Table;
use craftpulse\teamleader\elements\Company;

/**
 * Company query
 */
class CompanyQuery extends ElementQuery
{
    protected function beforePrepare(): bool
    {
        // todo: join the `companies` table
         $this->joinElementTable('teamleader_focus_company');

        // todo: apply any custom query params
        $this->query->select([
            'teamleader_focus_company.marketingMailsConsent',
            'teamleader_focus_company.nationalIdentificationNumber',
            'teamleader_focus_company.vatNumber',
            'teamleader_focus_company.website',
            'teamleader_focus_company.emails',
            'teamleader_focus_company.telephones',
            'teamleader_focus_company.name',
        ]);

        return parent::beforePrepare();
    }
}
