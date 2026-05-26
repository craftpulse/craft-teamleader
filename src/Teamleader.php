<?php
/**
 * Teamleader plugin for Craft CMS
 *
 * This plugin integrates with teamleader focus to generate and manage deals, and comes with a Formie integration.
 *
 * @link      https://craft-pulse.com
 * @copyright Copyright (c) 2025 CraftPulse
 */

namespace craftpulse\teamleader;

use Craft;
use craft\base\Plugin;

use craftpulse\teamleader\fields\formie\ClientType;
use craftpulse\teamleader\integrations\formie\TeamleaderFocus;

use verbb\formie\events\RegisterFieldsEvent;
use verbb\formie\events\RegisterIntegrationsEvent;
use verbb\formie\services\Fields;
use verbb\formie\services\Integrations;

use yii\base\Event;

/**
 * Class Teamleader
 *
 * @author CraftPulse
 * @since  5.0.0
 */
class Teamleader extends Plugin
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public string $schemaVersion = '1.0.0';

    /**
     * @var bool
     */
    public bool $hasCpSection = false;

    /**
     * @var bool
     */
    public bool $hasCpSettings = false;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @author CraftPulse
     */
    public function init(): void
    {
        parent::init();

        $request = Craft::$app->getRequest();

        if ($request->getIsConsoleRequest()) {
            $this->controllerNamespace = 'craftpulse\teamleader\console\controllers';
        }

        if (class_exists(Integrations::class)) {
            $this->_registerFormieEventHandlers();
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * Register Formie event handlers for the Teamleader Focus CRM integration
     * and the ClientType custom field.
     *
     * @author CraftPulse
     */
    private function _registerFormieEventHandlers(): void
    {
        Event::on(
            Integrations::class,
            Integrations::EVENT_REGISTER_INTEGRATIONS,
            function(RegisterIntegrationsEvent $event) {
                $event->crm[] = TeamleaderFocus::class;
            }
        );

        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELDS,
            function(RegisterFieldsEvent $event) {
                $event->fields[] = ClientType::class;
            }
        );
    }
}
