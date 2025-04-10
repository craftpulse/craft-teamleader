<?php

namespace craftpulse\teamleader\records;

use Craft;
use craft\db\ActiveRecord;
use craftpulse\teamleader\db\Table;

/**
 * Class DealRecord
 *
 * @author      CraftPulse
 * @package     Teamleader
 * @since       5.0.0
 *
 * @property float $amount
 * @property int $id ID
 * @property int|mixed|null $fieldLayoutId
 * @property string $currency
 * @property string $dateClosed
 * @property string $dateClosing
 * @property string $dateCreated Date created
 * @property string $dateUpdated Date updated
 * @property string $phase
 * @property string $reference
 * @property string $state
 * @property string $summary
 * @property string $uid Uid
 * @property string $webUrl
 * @property int $companyId
 * @property int $contactId
 */
class DealRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return Table::DEALS;
    }
}
