<?php

namespace craftpulse\teamleader\fieldlayoutelements;

use Craft;
use craft\fieldlayoutelements\BaseNativeField;
use craft\base\ElementInterface;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\web\assets\tablesettings\TableSettingsAsset;
use craft\web\assets\timepicker\TimepickerAsset;
use craftpulse\teamleader\elements\Company;
use InvalidArgumentException;

class QuotationInfoField extends BaseNativeField
{
    // Public Properties
    // =========================================================================
    /**
     * @var string|null $name
     */
    public ?string $name = null;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::$app->getView()->renderTemplate('teamleader-focus/quotations/_detail', [
            'element' => $element,
        ]);
    }
}
