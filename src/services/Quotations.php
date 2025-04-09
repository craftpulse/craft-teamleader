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
 * Class Deals
 *
 * @author      CraftPulse
 * @package     Teamleader
 * @since       5.0.0
 *
 */
class Quotations extends Component
{
    public function createFields(): ?array
    {
        $fields = [
            [
                'class' => TextField::class,
                'attribute' => 'title',
                'name' => 'title',
                'label' => Craft::t('teamleader-focus', 'Title'),
                'inputType' => 'text',
                'mandatory' => true,
                'required' => true,
                'disabled' => true,
                'width' => '100%',
            ],
        ];

        return $fields;
    }
}
