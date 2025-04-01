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

class TableField extends BaseNativeField
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
     * @inheritdoc
     */
    public ?string $name = null;

    /**
     * @var array<int, array{heading: string, handle: string, type: string}>
     */
    public array $columns = [];

    /**
     * @var array<int, array{label: string, value: string}>
     */
    public array $defaults = [[]];

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

        return Cp::editableTableFieldHtml([
            'allowAdd' => true,
            'allowDelete' => true,
            'allowReorder' => true,
            'cols' => $this->columns,
            'initJs' => true,
            'mandatory' => $this->mandatory,
            'name' => $this->name,
            'required' => $this->required,
            'rows' => [],
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function encodeValue($value): string|array|null
    {
        $encValue = parent::encodeValue($value);
        return $encValue === null || $encValue === '' ? '__blank__' : $encValue;
    }
}
