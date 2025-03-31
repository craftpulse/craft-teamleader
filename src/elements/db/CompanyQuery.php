<?php

namespace craftpulse\teamleader\elements\db;

use Craft;
use craft\elements\db\ElementQuery;

/**
 * Company query
 */
class CompanyQuery extends ElementQuery
{
    protected function beforePrepare(): bool
    {
        // todo: join the `companies` table
        // $this->joinElementTable('companies');

        // todo: apply any custom query params
        // ...

        return parent::beforePrepare();
    }
}
