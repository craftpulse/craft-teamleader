<?php

namespace craftpulse\teamleader\fieldlayoutelements;

use Craft;
use craft\elements\Address;
use craft\fieldlayoutelements\BaseNativeField;
use craft\base\ElementInterface;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\web\assets\tablesettings\TableSettingsAsset;
use craft\web\assets\timepicker\TimepickerAsset;
use craftpulse\teamleader\elements\Company;
use InvalidArgumentException;

class CompaniesField extends BaseNativeField
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

    public string $title = '';


    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        $html = '<hr/>';
        $html .= Html::label($this->title);

        $html .= Cp::elementSelectHtml([
            'allowAdd' => true,
            'allowRemove' => true,
            'required' => $this->required,
            'mandatory' => $this->mandatory,
            'siteId' => Craft::$app->sites->currentSite->id,
            'name' => $this->name,
            'elements' => $element->getCompanies(),
            'elementType' => Company::class,
            'criteria' => [
                'siteId' => Craft::$app->sites->currentSite->id,
            ],
            'viewMode' => 'cards',
            'showCardsInGrid' => true,
        ]);

        return $html;
    }
}
