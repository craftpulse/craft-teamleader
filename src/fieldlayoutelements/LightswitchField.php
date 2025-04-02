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

class LightswitchField extends BaseNativeField
{
    // Public Properties
    // =========================================================================
    /**
     * @inheritdoc
     */
    public bool $mandatory = true;

    /**
     * @inheritdoc
     */
    public bool $required = true;

    /**
     * @var string|null $name
     */
    public ?string $name = null;

    public bool $on = false;

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
        if (!$element instanceof Company) {
            throw new InvalidArgumentException(sprintf('%s can only be used in route field layouts.', __CLASS__));
        }

        return Cp::lightswitchHtml([
            'on' => $element[$this->name],
            'mandatory' => $this->mandatory,
            'name' => $this->name,
            'required' => $this->required,
        ]);
    }

    /**
     * @param $value
     * @return string|array|null
     */
    protected function encodeValue($value): string|array|null
    {
        return $value === null || $value === '' ? '__blank__' : $value;
    }
}
