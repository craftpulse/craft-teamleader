<?php
/**
 * Teamleader Focus plugin for Craft CMS
 *
 * @link      https://craft-pulse.com
 * @copyright Copyright (c) 2025 CraftPulse
 */

namespace craftpulse\teamleader\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;
use craft\helpers\Cp;

use craftpulse\shortlink\elements\Route;
use craftpulse\teamleader\elements\Contact;
use http\Exception\InvalidArgumentException;


/**
 * DropdownField Fieldlayoutelement
 *
 * @author      CraftPulse
 * @package     Teamleader Focus
 *
 */
class DropdownField extends BaseNativeField
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
    public array $options = [];

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
        return
            Cp::selectizeHtml([
                'id' => $this->id,
                'name' => $this->name ?? $this->attribute(),
                'options' => $this->options,
                'value' => $this->value($element),
                'autocomplete' => 'off',
                'disabled' => $static,
            ]);
    }

    /**
     * @inheritdoc
     */
    protected function encodeValue(MultiOptionsFieldData|OptionData|string|null $value): string|array|null
    {
        $encValue = parent::encodeValue($value);
        return $encValue === null || $encValue === '' ? '__blank__' : $encValue;
    }
}
