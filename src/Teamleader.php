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
use Monolog\Formatter\LineFormatter;
use Psr\Log\LogLevel;
use Throwable;
use craft\base\Plugin;
use craft\elements\User;
use craft\events\RegisterComponentTypesEvent;
use craft\log\MonologTarget;
use craft\services\Elements;
use craft\services\Plugins;
use craft\services\UserPermissions;
use craftpulse\teamleader\elements\Company;
use craftpulse\teamleader\integrations\formie\TeamleaderFocus;
use craftpulse\teamleader\models\SettingsModel;
use verbb\formie\events\RegisterIntegrationsEvent;
use verbb\formie\services\Integrations;
use yii\base\Event;
use yii\log\Dispatcher;
use yii\log\Logger;

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

    // Constant Properties
    // =========================================================================
    /**
     * Lite
     */
    public const EDITION_LITE = 'lite';

    /**
     * Plus
     */
    public const EDITION_PLUS = 'plus';

    /**
     * Pro
     */
    public const EDITION_PRO = 'pro';

    // Static Properties
    // =========================================================================
    /**
     * @var ?Teamleader
     */
    public static ?Teamleader $plugin = null;

    // Public Properties
    // =========================================================================
    /**
     * @var null|SettingsModel
     */
    public static ?SettingsModel $settings = null;
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

    /**
     * @inheritdoc
     */
    public static function editions(): array
    {
        return [
            self::EDITION_LITE,
            self::EDITION_PLUS,
            self::EDITION_PRO,
        ];
    }

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

        // Register custom elements
        Event::on(Elements::class, Elements::EVENT_REGISTER_ELEMENT_TYPES, function (RegisterComponentTypesEvent $event) {
            $event->types[] = Company::class;
        });

        // Run all the migrations after install
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    Craft::$app->runAction('migrate/up', ['pluginHandle' => self::$plugin->handle]);
                }
            }
        );
    }

    /**
     * Returns true if lite version.
     *
     * @return bool
     */
    public function getIsLite(): bool
    {
        return $this->is(self::EDITION_LITE);
    }

    /**
     * Returns true if plus version.
     *
     * @return bool
     */
    public function getIsPlus(): bool
    {
        return $this->is(self::EDITION_PLUS);
    }

    /**
     * Returns true if pro version.
     *
     * @return bool
     */
    public function getIsPro(): bool
    {
        return $this->is(self::EDITION_PRO);
    }

    /**
     * Logs a message
     * @throws Throwable
     */
    public function log(string $message, array $params = [], int $type = Logger::LEVEL_INFO): void
    {
        /** @var User|null $user */
        $user = Craft::$app->getUser()->getIdentity();

        if ($user !== null) {
            $params['username'] = $user->username;
        }

        $encoded_params = str_replace('\\', '', Json::encode($params));

        $message = Craft::t('teamleader-focus', $message . ' ' . $encoded_params, $params);

        Craft::getLogger()->log($message, $type, 'teamleader-focus');
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

    /**
     * Registers a custom log target
     *
     * @see LineFormatter::SIMPLE_FORMAT
     */
    private function registerLogTarget(): void
    {
        if (Craft::getLogger()->dispatcher instanceof Dispatcher) {
            Craft::getLogger()->dispatcher->targets[] = new MonologTarget([
                'name' => 'password-policy',
                'categories' => ['password-policy'],
                'level' => LogLevel::INFO,
                'logContext' => false,
                'allowLineBreaks' => true,
                'formatter' => new LineFormatter(
                    format: "%datetime% [%channel%.%level_name%] %message% %context%\n",
                    dateFormat: 'Y-m-d H:i:s',
                ),
            ]);
        }
    }
}
