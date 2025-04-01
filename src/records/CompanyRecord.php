<?php

namespace craftpulse\teamleader\records;

use Craft;
use craft\db\ActiveRecord;
use craftpulse\teamleader\db\Table;

/**
 * Company Record record
 *
 * @property string $dateCreated Date created
 * @property string $dateUpdated Date updated
 * @property array|null $emails Emails
 * @property int $id ID
 * @property int|null $marketing_mails_consent Marketing mails consent
 * @property string $name Name
 * @property string|null $national_identification_number National identification number
 * @property array|null $telephones Telephones
 * @property string $uid Uid
 * @property string|null $vat_number Vat number
 * @property string|null $website Website
 */
class CompanyRecord extends ActiveRecord
{
    public static function tableName()
    {
        return Table::COMPANY;
    }
}
