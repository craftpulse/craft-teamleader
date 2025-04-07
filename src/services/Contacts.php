<?php

namespace craftpulse\teamleader\services;

use Craft;
use craft\fieldlayoutelements\TextField;

use craftpulse\teamleader\fieldlayoutelements\CompaniesField;
use craftpulse\teamleader\fieldlayoutelements\DropdownField;
use craftpulse\teamleader\fieldlayoutelements\LightswitchField;
use craftpulse\teamleader\fieldlayoutelements\TableField;

use craftpulse\teamleader\records\ContactCompanyRecord;
use Illuminate\Support\Collection;
use yii\base\Component;

/**
 * Class Contacts
 *
 * @author      CraftPulse
 * @package     Teamleader
 * @kga mn native fields hebben, maar kwil kijken om die tabsince       5.0.0
 *
 */
class Contacts extends Component
{
    public function getContactsCompaniesByContactId(int $contactId): array|Collection|null
    {
        $contactCompanies = ContactCompanyRecord::find()
            ->andWhere(['contactId' => $contactId])
            ->asArray() // Return results as an array
            ->collect(); // Retrieve all matching records

        if (empty($contactCompanies)) return [];

        return $contactCompanies;
    }
    public function getContactsCompaniesByConmpanyId(int $companyId): array|Collection|null
    {
        $contactCompanies = ContactCompanyRecord::find()
            ->andWhere(['companyId' => $companyId])
            ->asArray()
            ->collect();

        if (empty($contactCompanies)) return [];

        return $contactCompanies;
    }

    public function getRelationById(string $id): ?ContactCompanyRecord
    {
        return ContactCompanyRecord::findOne(['id' => $id]);
    }

    public function createFields(): ?array
    {
        $fields = [
            [
                'class' => DropdownField::class,
                'attribute' => 'salutation',
                'name' => 'salutation',
                'label' => Craft::t('teamleader-focus', 'Salutation'),
                'options' => [
                    [
                        'label' => Craft::t('teamleader-focus', 'Mr.'),
                        'value' => 'Mr.'
                    ],
                    [
                        'label' => Craft::t('teamleader-focus', 'Ms.'),
                        'value' => 'Ms.'
                    ],
                    [
                        'label' => Craft::t('teamleader-focus', 'Mrs.'),
                        'value' => 'Mrs.'
                    ]
                ],
                'mandatory' => true,
                'required' => false,
                'width' => '20%',
            ], [
                'class' => TextField::class,
                'attribute' => 'firstName',
                'name' => 'firstName',
                'label' => Craft::t('teamleader-focus', 'First Name'),
                'inputType' => 'text',
                'mandatory' => true,
                'required' => true,
                'width' => '50%',
            ], [
                'class' => TextField::class,
                'attribute' => 'lastName',
                'name' => 'lastName',
                'label' => Craft::t('teamleader-focus', 'Last Name'),
                'inputType' => 'text',
                'mandatory' => true,
                'required' => true,
                'width' => '50%',
            ],
            [
                'class' => TableField::class,
                'attribute' => 'emails',
                'name' => 'emails',
                'label' => Craft::t('teamleader-focus', 'Emails'),
                'required' => false,
                'width' => '50%',
                'columns' => [
                    'type' => [
                        'heading' => Craft::t('teamleader-focus', 'Type'),
                        'type' => 'select',
                        'options' => [
                            [
                                'label' => Craft::t('teamleader-focus', 'Primary'),
                                'value' => 'primary',
                            ],
                            [
                                'label' => Craft::t('teamleader-focus', 'Invoicing'),
                                'value' => 'invoicing',
                            ]
                        ],
                    ],
                    'email' => [
                        'heading' => Craft::t('teamleader-focus', 'Email'),
                        'type' => 'email',
                    ],
                ],
            ], [
                'class' => TableField::class,
                'attribute' => 'telephones',
                'name' => 'telephones',
                'label' => Craft::t('teamleader-focus', 'Telephones'),
                'required' => false,
                'width' => '50%',
                'columns' => [
                    'type' => [
                        'heading' => Craft::t('teamleader-focus', 'Type'),
                        'type' => 'select',
                        'options' => [
                            [
                                'label' => Craft::t('teamleader-focus', 'Phone'),
                                'value' => 'phone',
                            ],
                            [
                                'label' => Craft::t('teamleader-focus', 'Fax'),
                                'value' => 'fax',
                            ]
                        ],
                    ],
                    'number' => [
                        'heading' => Craft::t('teamleader-focus', 'Telephone'),
                        'type' => 'number',
                    ],
                ],
            ], [
                'class' => DropdownField::class,
                'attribute' => 'languages',
                'name' => 'languages',
                'label' => Craft::t('teamleader-focus', 'Salutation'),
                'options' => [
                    [
                        'label' => Craft::t('teamleader-focus', 'English'),
                        'value' => 'en'
                    ],
                    [
                        'label' => Craft::t('teamleader-focus', 'Dutch'),
                        'value' => 'nl'
                    ],
                    [
                        'label' => Craft::t('teamleader-focus', 'French'),
                        'value' => 'fr'
                    ]
                ],
                'mandatory' => true,
                'required' => false,
                'width' => '50%',
            ], [
                'class' => LightswitchField::class,
                'attribute' => 'marketingMailsConsent',
                'name' => 'marketingMailsConsent',
                'label' => Craft::t('teamleader-focus', 'Marketing Mails Consent'),
                'instructions' => Craft::t('teamleader-focus', 'Subscribe for marketing emails'),
                'mandatory' => true,
                'required' => false,
                'width' => '100%',
            ], [
                'class' => CompaniesField::class,
                'attribute' => 'companies',
                'name' => 'companies',
                'title' => Craft::t('teamleader-focus', 'Companies'),
            ]
        ];

        return $fields;
    }
}
