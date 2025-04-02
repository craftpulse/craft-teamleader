<?php

namespace craftpulse\teamleader\records;

use Craft;
use craft\db\ActiveRecord;
use craftpulse\teamleader\db\Table;

/**
 * Class ContactRecord
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
 */
class ContactRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return Table::CONTACTS;
    }
}
