<?php

namespace craftpulse\teamleader\fieldlayoutelements;

use Craft;
use craft\base\Element;
use craft\base\NestedElementInterface;
use craft\elements\Address;
use craft\elements\db\ElementQueryInterface;
use craft\elements\ElementCollection;
use craft\fieldlayoutelements\BaseNativeField;
use craft\base\ElementInterface;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\web\assets\tablesettings\TableSettingsAsset;
use craft\web\assets\timepicker\TimepickerAsset;
use craftpulse\teamleader\elements\Company;
use InvalidArgumentException;

class AddressField extends BaseNativeField
{
    public bool $mandatory = true;

    /**
     * @inheritdoc
     */
    public bool $required = true;

    /**
     * @inheritdoc
     */
    public ?string $name = null;


    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {

        $config = [
            'showInGrid' => true,
            'canCreate' => true,
        ];

        // Use an element index view if there's more than 50 addresses
        $total = $element->getAddresses()->count();
        if ($total > 50) {
            return $element->getAddressManager()->getIndexHtml($element, $config);
        }

        return Html::tag('h2', Craft::t('app', 'Addresses')) .
            $element->getAddressManager()->getCardsHtml($element, $config);
//        return '<span>@TODO: Address</span>';

//        return Cp::cardPreviewHtml(
//            Craft::$app->fields->getLayoutByType(Address::class),
//            $element->getAddresses(),
//        );

//        return Cp::elementSelectHtml([
//            'allowAdd' => true,
//            'allowRemove' => true,
//            'required' => $this->required,
//            'mandatory' => $this->mandatory,
//            'siteId' => Craft::$app->sites->currentSite->id,
//            'name' => $this->name,
//            'elements' => $element[$this->field],
//            'elementType' => Address::class,
//            'criteria' => [
//                'siteId' => Craft::$app->sites->currentSite->id,
//            ],
//            'viewMode' => 'cards',
//            'showCardsInGrid' => true,
//        ]);

    }
}
