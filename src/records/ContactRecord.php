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
 * @property string $dateUpdated Date updated
 * @property array|null $emails Emails
 * @property int $id ID
 * @property int|null $marketingMailsConsent Marketing mails consent
 * @property string $firstName 
 * @property string $lastName
 * @property array|null $telephones Telephones
 * @property string $uid Uid
 * @property string|null $language
 * @property string|null $salutation
 * @property int|mixed|null $fieldLayoutId
 * @property int|mixed|null $teamleaderId
 */
class ContactRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return Table::CONTACTS;
    }
}
