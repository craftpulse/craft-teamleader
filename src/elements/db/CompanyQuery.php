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
         $this->joinElementTable('teamleader_focus_company');

        // todo: apply any custom query params
        // ...

        return parent::beforePrepare();
    }
}
