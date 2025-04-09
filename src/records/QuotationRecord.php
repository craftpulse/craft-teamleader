<?php

namespace craftpulse\teamleader\records;

use Craft;
use craft\db\ActiveRecord;
use craftpulse\teamleader\db\Table;

/**
 * Class QuotationRecord
 *
 * @author      CraftPulse
 * @package     Teamleader
 * @since       5.0.0
 *
 * @property int $id ID
 * @property int|mixed|null $fieldLayoutId
 * @property string $dateCreated Date created
 * @property string $dateUpdated Date updated
 * @property string $phase
 * @property string $currency
 * @property string|array $discounts
 * @property string $purchasePrice
 * @property float $taxAmount
 * @property float $taxRate
 * @property float $taxableAmount
 * @property float $totalTaxExclusiveAmount
 * @property float $totalTaxInclusiveAmount
 * @property string|array $quotationLines
 * @property string $uid Uid
 */
class QuotationRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return Table::QUOTATIONS;
    }
}
