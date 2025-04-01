<?php

namespace craftpulse\teamleader\services;

use Craft;
use craft\fieldlayoutelements\TextField;
use craft\fields\Addresses;
use craft\fields\Table;

use craftpulse\teamleader\fieldlayoutelements\AddressField;
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
                'attribute' => 'vat_number',
                'name' => 'vat_number',
                'label' => Craft::t('teamleader-focus', 'VAT Number'),
                'inputType' => 'text',
                'mandatory' => true,
                'required' => false,
                'width' => '50%',
            ], [
                'class' => TextField::class,
                'attribute' => 'national_identification_number',
                'name' => 'national_identification_number',
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
                'width' => '100%',
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
                'class' => AddressField::class,
                'field' => 'addresses',
                'attribute' => 'address',
                'name' => 'addresses',
                'label' => Craft::t('teamleader-focus', 'Addresses'),
                'required' => false,
                'width' => '100%',
            ]
        ];

        return $fields;
    }
}
