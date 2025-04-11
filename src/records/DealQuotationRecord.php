<?php

namespace craftpulse\teamleader\records;

use Craft;
use craft\db\ActiveRecord;
use craftpulse\teamleader\db\Table;

/**
 * Class CompanyRecord
 *
 * @author      CraftPulse
 * @package     Teamleader
 * @since       5.0.0
 *
 * @property string $dateCreated Date created
 * * @property string $dateUpdated Date updated
 * * @property int $id ID
 * * @property int|null $dealId
 * * @property string $quotationId
 */
class DealQuotationRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return Table::DEALS_QUOTATIONS;
    }
}
