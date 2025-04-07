<?php

namespace craftpulse\teamleader\controllers;

use Craft;
use craft\errors\MissingComponentException;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use craft\web\UrlManager;

use craftpulse\teamleader\services\Providers as ProviderService;
use craftpulse\teamleader\Teamleader;

use Throwable;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Class SettingsController
 *
 * @author      CraftPulse
 * @package     Teamleader
 * @since       5.0.0
 *
 * @property ProviderService $providers
 */
class SettingsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        $this->requireAdmin();

        return parent::beforeAction($action);
    }

    /**
     * @return Response|null
     * @throws ForbiddenHttpException|Throwable
     */
    public function actionEdit(): ?Response
    {
        // Ensure they have permission to edit the plugin settings
        $currentUser = Craft::$app->getUser()->getIdentity();
        if (!$currentUser->can('teamleader-focus:settings')) {
            throw new ForbiddenHttpException('You do not have permission to edit the Teamleader Focus settings.');
        }
        $general = Craft::$app->getConfig()->getGeneral();
        if (!$general->allowAdminChanges) {
            throw new ForbiddenHttpException('Unable to edit Teamleader Focus plugin settings because admin changes are disabled in this environment.');
        }

        // Edit the plugin settings
        $variables = [];
        $pluginName = 'Teamleader Focus';
        $templateTitle = Craft::t('teamleader-focus', 'Plugin settings');

        $variables['fullPageForm'] = true;
        $variables['pluginName'] = $pluginName;
        $variables['title'] = $templateTitle;
        $variables['docTitle'] = "{$pluginName} - {$templateTitle}";
        $variables['crumbs'] = [
            [
                'label' => $pluginName,
                'url' => UrlHelper::cpUrl('teamleader-focus'),
            ],
            [
                'label' => $templateTitle,
                'url' => UrlHelper::cpUrl('teamleader-focus/plugin'),
            ],
        ];
        $variables['settings'] = Teamleader::$plugin->settings;
        $variables['connected'] = (bool)Teamleader::$plugin->providers->getToken();

        return $this->renderTemplate('teamleader-focus/settings/general/_edit', $variables);
    }

    /**
     * Saves the plugin settings
     * @return Response|null
     * @throws ForbiddenHttpException
     * @throws MethodNotAllowedHttpException
     * @throws NotFoundHttpException
     * @throws Throwable
     * @throws MissingComponentException
     * @throws BadRequestHttpException
     */
    public function actionSave(): ?Response
    {
        // Ensure they have permission to edit the plugin settings
        $currentUser = Craft::$app->getUser()->getIdentity();
        if (!$currentUser->can('teamleader-focus:settings')) {
            throw new ForbiddenHttpException('You do not have permission to edit the Teamleader Focus settings.');
        }
        $general = Craft::$app->getConfig()->getGeneral();
        if (!$general->allowAdminChanges) {
            throw new ForbiddenHttpException('Unable to edit Teamleader Focus plugin settings because admin changes are disabled in this environment.');
        }

        // Save the plugin settings
        $this->requirePostRequest();
        $pluginHandle = Craft::$app->getRequest()->getRequiredBodyParam('pluginHandle');
        $plugin = Craft::$app->getPlugins()->getPlugin($pluginHandle);
        $settings = Craft::$app->getRequest()->getBodyParam('settings', []);

        if ($plugin === null) {
            throw new NotFoundHttpException('Plugin not found');
        }

        if (!Craft::$app->getPlugins()->savePluginSettings($plugin, $settings)) {
            Craft::$app->getSession()->setError(Craft::t('app', "Couldn't save plugin settings."));

            // Send the redirect back to the template
            /** @var UrlManager $urlManager */
            $urlManager = Craft::$app->getUrlManager();
            $urlManager->setRouteParams([
                'plugin' => $plugin,
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Plugin settings saved.'));

        return $this->redirectToPostedUrl();
    }
}
