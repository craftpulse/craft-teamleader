<?php

namespace craftpulse\teamleader\controllers;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;

use craftpulse\teamleader\auth\providers\TeamleaderFocusProvider;

use yii\web\Response;

use verbb\auth\Auth;

/**
 * Class AuthController
 *
 * @author      CraftPulse
 * @package     Teamleader
 * @since       5.0.0
 *
 */
class AuthController extends controller
{
    // Protected Properties
    // =========================================================================

    protected array|int|bool $allowAnonymous = ['login', 'callback'];

    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        // Don't require CSRF validation for callback requests
        if ($action->id === 'callback') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionLogin(): Response
    {
        $provider = New TeamleaderFocusProvider([
            'client_id' => 'client_id',
            'client_secret' => 'client_secret',
            'redirectUri' => UrlHelper::actionUrl('tbd'),
        ]);

        // Redirect to the provider platform to login and authorise
        return Auth::getInstance()->getOAuth()->connect('teamleader-focus', $provider);
    }

    public function actionCallback(): Response
    {

    }
}
