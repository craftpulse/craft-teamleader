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

use craftpulse\teamleader\integrations\formie\TeamleaderFocus;

use verbb\formie\events\RegisterFieldsEvent;
use verbb\formie\events\RegisterIntegrationsEvent;
use verbb\formie\services\Fields;
use verbb\formie\services\Integrations;
use yii\base\Event;

/**
 * Class Teamleader
 *
 * @author      CraftPulse
 * @package     Teamleader
 * @since       5.0.0
 *
 */
class Teamleader extends Plugin {
    // Traits
    // =========================================================================

    // Static Properties
    // =========================================================================
    /**
     * @var ?Teamleader
     */
    public static ?Teamleader $plugin = null;
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
    public function init(): void {
        parent::init();
        self::$plugin = $this;

        $request = Craft::$app->getRequest();
        if ($request->getIsConsoleRequest()) {
            $this->controllerNamespace = 'craftpulse\teamleader\console\controllers';
        }

        // Register our Formie event handlers
        if(class_exists(Integrations::class)) {
            $this->_registerFormieEventHandlers();
        }
    }

    // Private Methods
    // =========================================================================
    private function _registerFormieEventHandlers(): void {
        Event::on(
            Integrations::class,
            Integrations::EVENT_REGISTER_INTEGRATIONS,
            function (RegisterIntegrationsEvent $event) {
                $event->crm[] = TeamleaderFocus::class;
            }
        );
    }
}
