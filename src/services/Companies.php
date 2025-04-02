<?php

namespace craftpulse\teamleader\services;

use Craft;
use craft\fieldlayoutelements\TextField;

use craftpulse\teamleader\fieldlayoutelements\AddressField;
use craftpulse\teamleader\fieldlayoutelements\LightswitchField;
use craftpulse\teamleader\fieldlayoutelements\TableField;

use yii\base\Component;

/**
 * Class Companies
 *
 * @author      CraftPulse
 * @package     Teamleader
 * @since       5.0.0
 *
 */
class Companies extends Component
{
    public function createFields(): ?array
    {
        $fields = [
            [
                'class' => TextField::class,
                'attribute' => 'title',
                'name' => 'title',
                'label' => Craft::t('teamleader-focus', 'Name'),
                'inputType' => 'text',
                'mandatory' => true,
                'required' => true,
                'width' => '100%',
            ], [
                'class' => TextField::class,
                'attribute' => 'vatNumber',
                'name' => 'vatNumber',
                'label' => Craft::t('teamleader-focus', 'VAT Number'),
                'inputType' => 'text',
                'mandatory' => true,
                'required' => false,
                'width' => '50%',
            ], [
                'class' => TextField::class,
                'attribute' => 'nationalIdentificationNumber',
                'name' => 'nationalIdentificationNumber',
                'label' => Craft::t('teamleader-focus', 'National Identification Number'),
                'inputType' => 'text',
                'mandatory' => true,
                'required' => false,
                'width' => '50%',
            ], [
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
                'class' => AddressField::class,
                'field' => 'addresses',
                'attribute' => 'addresses',
                'name' => 'addresses',
                'label' => Craft::t('teamleader-focus', 'Addresses'),
                'required' => false,
                'width' => '100%',
            ], [
                'class' => TextField::class,
                'attribute' => 'website',
                'name' => 'website',
                'label' => Craft::t('teamleader-focus', 'Website'),
                'inputType' => 'text',
                'mandatory' => true,
                'required' => false,
                'width' => '100%',
            ], [
                'class' => LightswitchField::class,
                'attribute' => 'marketingMailsConsent',
                'name' => 'marketingMailsConsent',
                'label' => Craft::t('teamleader-focus', 'Marketing Mails Consent'),
                'instructions' => Craft::t('teamleader-focus', 'Subscribe for marketing emails'),
                'mandatory' => true,
                'required' => false,
                'width' => '100%',
            ]
        ];

        return $fields;
    }
}
