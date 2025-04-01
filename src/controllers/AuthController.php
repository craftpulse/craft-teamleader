<?php

namespace craftpulse\teamleader\controllers;

use Craft;
use craft\errors\MissingComponentException;
use craft\web\Controller;

use craftpulse\teamleader\services\Providers as ProviderService;

use craftpulse\teamleader\Teamleader;
use yii\base\ExitException;
use yii\log\Logger;
use yii\web\BadRequestHttpException;
use yii\web\Response;

use verbb\auth\Auth;
use verbb\auth\helpers\Session;

use Throwable;

/**
 * Class AuthController
 *
 * @author      CraftPulse
 * @package     Teamleader
 * @since       5.0.0
 *
 * @property ProviderService $providers
 */
class AuthController extends controller
{
    // Protected Properties
    // =========================================================================

    protected array|int|bool $allowAnonymous = ['connect', 'callback'];


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

    /**
     * @throws ExitException
     * @throws BadRequestHttpException|Throwable
     */
    public function actionConnect(): ?Response
    {
        $provider = Teamleader::$plugin->providers->createProvider();

        try {
            // Redirect to the provider platform to login and authorise
            return Auth::getInstance()->getOAuth()->connect('teamleader-focus', $provider);
        } catch (Throwable $error) {
            $error = Craft::t('teamleader-focus', 'Unable to authorize with Teamleader Focus: “{message}” {file}:{line}',
            [
                'message' => $error->getMessage(),
                'file' => $error->getFile(),
                'line' => $error->getLine(),
            ]);

            Teamleader::$plugin->log($error, [], Logger::LEVEL_ERROR);

            Craft::$app->getSession()->setFlash('teamleader-focus-error', $error);

            return $this->asFailure(Craft::t('teamleader-focus', 'Unable to authorize with Teamleader Focus.'));
        }


    }

    /**
     * @throws Throwable
     * @throws MissingComponentException
     */
    public function actionCallback(): ?Response
    {
        // Restore the session data that we saved before authorization redirection from the cache back to session
        Session::restoreSession($this->request->getParam('state'));

        // Get both the origin (failure) and redirect (success) URLs
        $origin = Session::get('origin');
        $redirect = Session::get('redirect');

        $provider = Teamleader::$plugin->providers->createProvider();

        try {
            // Fetch the access token
            $token = Auth::getInstance()->getOAuth()->callback('teamleader-focus', $provider);

            if (!$token) {
                Session::setError('teamleader-focus', Craft::t('teamleader-focus', 'Unable to fetch token.'));

                return $this->redirect($origin);
            }

            // Save the auth token
            $token->reference = 'teamleader-focus';
            Auth::getInstance()->getTokens()->upsertToken($token);

        } catch (Throwable $error) {
            // Check if there are any meaningful errors returned from providers
            $message = implode(', ', array_filter([$e->getMessage(), $this->request->getParam('error'), $this->request->getParam('error_description')]));

            $error = Craft::t('teamleader-focus', 'Unable to process callback for Teamleader Focus: “{message}” {file}:{line}',
                [
                    'message' => $message,
                    'file' => $error->getFile(),
                    'line' => $error->getLine(),
                ]);

            Teamleader::$plugin->log($error, [], Logger::LEVEL_ERROR);

            Craft::$app->getSession()->setFlash('teamleader-focus-error', $error);

            return $this->redirect($origin);
        }

        Session::setNotice('teamleader-focus', Craft::t('teamleader-focus', 'Teamleader Focus connected.'), true);

        return $this->redirect($redirect);
    }

    public function actionDisconnect(): ?Response
    {
        // Delete all tokens for this integration
        Auth::getInstance()->getTokens()->deleteTokenByOwnerReference('teamleader-focus', 'teamleader-focus');

        return $this->asSuccess(Craft::t('teamleader-focus', 'Teamleader Focus disconnected.'));
    }
}
