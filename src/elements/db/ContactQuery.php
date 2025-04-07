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
        $this->query->select([
            'teamleader_focus_contacts.emails',
            'teamleader_focus_contacts.firstName',
            'teamleader_focus_contacts.language',
            'teamleader_focus_contacts.lastName',
            'teamleader_focus_contacts.marketingMailsConsent',
            'teamleader_focus_contacts.salutation',
            'teamleader_focus_contacts.teamleaderId',
            'teamleader_focus_contacts.telephones',
        ]);

        return parent::beforePrepare();
    }
}
