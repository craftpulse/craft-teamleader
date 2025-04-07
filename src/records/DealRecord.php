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
 * @property string $dateCreated Date created
 * @property string $dateUpdated Date updated
 * @property int $id ID
 * @property string $uid Uid
 * @property int|mixed|null $fieldLayoutId
 * @property float $amount
 * @property string $currency
 * @property string $dateClosed
 * @property string $dateClosing
 * @property string $reference
 * @property string $summary
 * @property string $webUrl
 * @property string $phase
 */
class DealRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return Table::DEALS;
    }
}
