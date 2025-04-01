<?php

namespace craftpulse\teamleader\fieldlayoutelements;

use Craft;
use craft\fieldlayoutelements\BaseNativeField;
use craft\base\ElementInterface;
use craft\helpers\Cp;
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
     * @var array<int, array{label: string, value: string}>
     */
    public array $columns = [];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        unset(
            $config['mandatory'],
            $config['translatable'],
            $config['maxlength'],
            $config['required'],
            $config['autofocus']
        );

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

//        $view = Craft::$app->getView();
//
//        $view->registerAssetBundle(TimepickerAsset::class);
//        $view->registerAssetBundle(TableSettingsAsset::class);
//        $view->registerJs('new Craft.TableFieldSettings(' .
//            Json::encode($view->namespaceInputName('columns')) . ', ' .
//            Json::encode($view->namespaceInputName('defaults')) . ', ' .
//            Json::encode($columns) . ', ' .
//            Json::encode($this->defaults ?? []) . ', ' .
//            Json::encode($columnSettings) . ', ' .
//            Json::encode($dropdownSettingsHtml) . ', ' .
//            Json::encode($dropdownSettingsCols) .
//            ');');
//
//        $columnsField = $view->renderTemplate('_components/fieldtypes/Table/columntable.twig', [
//            'cols' => $columnSettings,
//            'rows' => $this->columns,
//            'errors' => $this->getErrors('columns'),
//        ]);

        return Cp::editableTableFieldHtml([
            'allowAdd' => true,
            'allowReorder' => true,
            'allowDelete' => true,
            'cols' => $this->columns,
            'name' => $this->name,
            'rows' => [],
            'initJs' => false,
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
