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
 * @property string $name Name
 * @property string|null $nationalIdentificationNumber National identification number
 * @property array|null $telephones Telephones
 * @property string $uid Uid
 * @property string|null $vatNumber Vat number
 * @property string|null $website Website
 * @property int|mixed|null $fieldLayoutId
 */
class CompanyRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return TABLE::COMPANIES;
    }
}
