<?php

namespace craftpulse\teamleader\services;

use Craft;
use craft\base\Component;
use craft\helpers\App;
use craft\helpers\UrlHelper;
use craftpulse\teamleader\auth\providers\TeamleaderFocusProvider;
use craftpulse\teamleader\models\SettingsModel;
use craftpulse\teamleader\Teamleader;

use verbb\auth\Auth;
use verbb\auth\models\Token;

/**
 * Class Providers
 *
 * @author      CraftPulse
 * @package     Teamleader
 * @since       5.0.0
 *
 */
class Providers extends Component
{
    // Private Properties
    // =========================================================================

    /**
     * @var SettingsModel
     */
    private SettingsModel $settings;

    // Public Methods
    // =========================================================================

    /**
     * @return void
     */
    public function init(): void
    {
        $this->settings = Teamleader::$plugin->settings;
    }

    /**
     * @return TeamleaderFocusProvider
     */
    public function createProvider(): TeamleaderFocusProvider
    {
        return New TeamleaderFocusProvider([
            'clientId' => App::parseEnv($this->settings->clientId),
            'clientSecret' => App::parseEnv($this->settings->clientSecret),
            'redirectUri' => UrlHelper::actionUrl('teamleader-focus/auth/callback'),
        ]);
    }

    public function getToken(): ?Token
    {
        $token = Auth::getInstance()->getTokens()->getTokenByOwnerReference('teamleader-focus', 'teamleader-focus');

        if (!empty($token)) {
            return $token;
        }

        return null;
    }
}
