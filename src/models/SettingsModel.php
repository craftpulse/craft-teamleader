<?php

namespace craftpulse\teamleader\models;

use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;

/**
 * Class SettingsModel
 *
 * @author      CraftPulse
 * @package     Teamleader
 * @since       5.0.0
 *
 */
class SettingsModel extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string|null the Client ID of the Teamleader Focus App
     */
    public ?string $clientId = null;

    /**
     * @var string|null the Client Secret of the Teamleader Focus App
     */
    public ?string $clientSecret = null;

    /**
     * @var bool if we want to sync custom fields
     */
    public bool $syncCustomFields = false;


    // Protected Methods
    // =========================================================================

    protected function defineBehaviors(): array
    {
        return [
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => ['clientId', 'clientSecret'],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return [
            [['clientId', 'clientSecret'], 'required'],
        ];
    }
}
