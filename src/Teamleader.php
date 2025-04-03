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
use craft\base\Model;
use craft\base\Plugin;
use craft\elements\User;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\Json;
use craft\log\MonologTarget;
use craft\models\FieldLayout;
use craft\services\Elements;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use craftpulse\teamleader\elements\Company;
use craftpulse\teamleader\elements\Contact;
use craftpulse\teamleader\elements\Deal;
use craftpulse\teamleader\integrations\formie\TeamleaderFocus;
use craftpulse\teamleader\models\SettingsModel;
use craftpulse\teamleader\services\ServicesTrait;
use verbb\formie\events\RegisterIntegrationsEvent;
use verbb\formie\services\Integrations;
use yii\base\Event;
use yii\base\InvalidRouteException;
use yii\log\Dispatcher;
use yii\log\Logger;

/**
 * Class Teamleader
 *
 * @author      CraftPulse
 * @package     Teamleader
 * @since       5.0.0
 *
 * @property SettingsModel|null $settings
 * @method SettingsModel getSettings()
 *
 */
class Teamleader extends Plugin {

    // Traits
    // =========================================================================
    use ServicesTrait;

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
     * @var mixed|object|null
     */
    public mixed $queue = null;


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

        // Register custom log target
        $this->_registerLogTarget();

        $request = Craft::$app->getRequest();
        if ($request->getIsConsoleRequest()) {
            $this->controllerNamespace = 'craftpulse\teamleader\console\controllers';
        }

        // Register our Formie event handlers
        if(class_exists(Integrations::class)) {
            $this->_registerFormieEventHandlers();
        }

        if ($this->getIsPlus() || $this->getIsPro()) {
            $this->hasCpSettings = true;
            $this->hasCpSection = true;

            // Register control panel events
            if (Craft::$app->getRequest()->getIsCpRequest()) {
                $this->_registerCpUrlRules();
                $this->_registerFieldLayout();
            }

            // Permissions
            $this->_registerUserPermissions();

            // Elements
            $this->_registerElements();
        }


        // Run all the migrations after install
//        Event::on(
//            Plugins::class,
//            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
//            function (PluginEvent $event) {
//                if ($event->plugin === $this) {
//                    Craft::$app->runAction('migrate/up', ['pluginHandle' => self::$plugin->handle]);
//                }
//            }
//        );
    }

    /**
     * @inheritdoc
     * @throws InvalidRouteException
     */
    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect('teamleader-focus/settings');
    }

    /**
     * @inheritdoc
     * @throws Throwable
     */
    public function getCpNavItem(): ?array
    {
        $subNavs = [];
        $navItem = parent::getCpNavItem();
        $currentUser = Craft::$app->getUser()->getIdentity();

        $editableSettings = true;
        $general = Craft::$app->getConfig()->getGeneral();

        if (!$general->allowAdminChanges) {
            $editableSettings = false;
        }

        if ($currentUser->can('teamleader-focus:view-companies')) {
            $subNavs['companies'] = [
                'label' => Craft::t('teamleader-focus', 'Companies'),
                'url' => 'teamleader-focus/companies',
            ];
        }

        if ($currentUser->can('teamleader-focus:view-contacts')) {
            $subNavs['contacts'] = [
                'label' => Craft::t('teamleader-focus', 'Contacts'),
                'url' => 'teamleader-focus/contacts',
            ];
        }

        if ($currentUser->can('teamleader-focus:settings') && $editableSettings) {
            $subNavs['settings'] = [
                'label' => 'Settings',
                'url' => 'teamleader-focus/settings',
            ];
        }

        if (empty($subNavs)) {
            return null; // Don't show the menu if no sub-navigation exists
        }

        return array_merge($navItem, [
            'subnav' => $subNavs,
        ]);
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

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate(
            'teamleader-focus/settings/_edit',
            ['settings' => $this->getSettings()]
        );
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): ?Model
    {
        return new SettingsModel();
    }


    // Private Methods
    // =========================================================================
    /**
     * Registers CP URL rules event
     */
    private function _registerCpUrlRules(): void
    {
        Event::on(UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                // Merge so that settings controller action comes first (important!)
                $event->rules = array_merge(
                    [
                        'teamleader-focus' => ['template' => 'teamleader-focus/companies/_index.twig'],
                        'teamleader-focus/companies' => ['template' => 'teamleader-focus/companies/_index.twig'],
                        'teamleader-focus/companies/<elementId:\d+>' => 'elements/edit',
                        'teamleader-focus/contacts' => ['template' => 'teamleader-focus/contacts/_index.twig'],
                        'teamleader-focus/contacts/<elementId:\d+>' => 'elements/edit',
                        'teamleader-focus/deals' => ['template' => 'teamleader-focus/deals/_index.twig'],
                        'teamleader-focus/deals/<elementId:\d+>' => 'elements/edit',
                        'teamleader-focus/settings' => 'teamleader-focus/settings/edit',
                        'teamleader-focus/teamleader-focus' => 'teamleader-focus/settings/edit',
                    ],
                    $event->rules,
                );
            }
        );
    }

    /**
     * Registers our custom elements
     *
     * @return void
     */
    private function _registerElements(): void
    {
        Event::on(Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = Company::class;
                $event->types[] = Contact::class;
                $event->types[] = Deal::class;
            });
    }

    /**
     * Registers custom FieldLayouts
     *
     * @return void
     */
    private function _registerFieldLayout(): void
    {
        Event::on(
            FieldLayout::class,
            FieldLayout::EVENT_DEFINE_NATIVE_FIELDS,
            function (DefineFieldLayoutFieldsEvent $event) {
                /** @var FieldLayout $fieldLayout */
                $fieldLayout = $event->sender;

                // We only want to provide these options for our company field layouts:
                if ($fieldLayout->type === Company::class) {
                    // Add our custom fields
                    foreach ($this->getCompanies()->createFields() as $field)
                    {
                        $event->fields[] = $field;
                    }
                }

                if ($fieldLayout->type === Contact::class) {
                    // Add our custom fields
                    foreach ($this->getContacts()->createFields() as $field)
                    {
                        $event->fields[] = $field;
                    }
                }

                if ($fieldLayout->type === Deal::class) {
                    // Add our custom fields
                    /*foreach ($this->getDeals()->createFields() as $field)
                    {
                        $event->fields[] = $field;
                    }*/
                }
            }
        );
    }

    /**
     * Registers Formie Event Handlers
     *
     * @return void
     */
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
     * Registers user permissions
     *
     * @return void
     */
    private function _registerUserPermissions(): void
    {
        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function(RegisterUserPermissionsEvent $event) {
                $event->permissions[] = [
                    'heading' => 'Teamleader Focus',
                    'permissions' => [
                        'teamleader-focus:view-companies' => [
                            'label' => Craft::t('teamleader-focus', 'View companies'),
                        ],
                        'teamleader-focus:save-companies' => [
                            'label' => Craft::t('teamleader-focus', 'Edit/Save companies'),
                        ],
                        'teamleader-focus:delete-companies' => [
                            'label' => Craft::t('teamleader-focus', 'Delete companies'),
                        ],
                        'teamleader-focus:view-contacts' => [
                            'label' => Craft::t('teamleader-focus', 'View contacts'),
                        ],
                        'teamleader-focus:save-contacts' => [
                            'label' => Craft::t('teamleader-focus', 'Edit/Save contacts'),
                        ],
                        'teamleader-focus:delete-contacts' => [
                            'label' => Craft::t('teamleader-focus', 'Delete contacts'),
                        ],
                        'teamleader-focus:view-deals' => [
                            'label' => Craft::t('teamleader-focus', 'View deals'),
                        ],
                        'teamleader-focus:save-deals' => [
                            'label' => Craft::t('teamleader-focus', 'Edit/Save deals'),
                        ],
                        'teamleader-focus:delete-deals' => [
                            'label' => Craft::t('teamleader-focus', 'Delete deals'),
                        ],
                        'teamleader-focus:settings' => [
                            'label' => Craft::t('teamleader-focus', 'Access settings'),
                        ],
                    ],
                ];
            }
        );
    }

    /**
     * Registers a custom log target
     *
     * @see LineFormatter::SIMPLE_FORMAT
     */
    private function _registerLogTarget(): void
    {
        if (Craft::getLogger()->dispatcher instanceof Dispatcher) {
            Craft::getLogger()->dispatcher->targets[] = new MonologTarget([
                'name' => 'teamleader-focus',
                'categories' => ['teamleader-focus'],
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
