<?php

namespace craftpulse\teamleader\helpers;

use Craft;
use craft\base\Element;
use craft\base\Event;
use craft\events\DefineElementEditorHtmlEvent;
use craft\helpers\Html;
use craft\helpers\UrlHelper;

use craftpulse\teamleader\Teamleader;
use craftpulse\teamleader\elements\Company;
use craftpulse\teamleader\elements\Contact;
use craftpulse\teamleader\elements\Deal;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Exception;

class ElementSidebarHelper
{
    // Public constants
    // =========================================================================
    /**
     * @event DefineHtmlEvent
     */
    public const EVENT_DEFINE_SIDEBAR_HTML = 'defineSidebarHtml';

    /**
     * @event DefineHtmlEvent
     */
    public const EVENT_DEFINE_META_FIELDS_HTML = 'defineMetaFieldsHtml';

    /**
     * @ const string[]
     */
    public const ELIGIBLE_ELEMENT_TYPES = [
        Company::class,
        Contact::class,
        Deal::class,
    ];

    // Public Methods
    // =========================================================================

    /**
     * Returns the HTML for the sidebar.
     *
     * @param Element $element
     * @param string $type
     * @return string
     * @throws Exception
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Throwable
     */
    public static function getSidebarHtml(Element $element, string $type = 'teamleader'): string
    {
        $user = Craft::$app->getUser()->getIdentity();

        if (!$user->can('teamleader-focus:view-sidebar-panel')) {
            return '';
        }

        $connected = (bool)Teamleader::$plugin->providers->getToken();

        if (empty(Teamleader::$plugin->settings->clientId) && empty(Teamleader::$plugin->settings->clientSecret) && !$connected) {
            return '';
        }

        $html = Html::beginTag('fieldset', ['class' => 'teamleader-focus-element-sidebar']) .
            Html::tag('legend', 'Teamleader Focus Status', ['class' => 'h6']) .
            Html::tag('div', $type === 'teamleader' ? self::metaFieldsHtmlTeamleader($element) : self::metaFieldsHtmlDeals($element), ['class' => 'meta']) .
            Html::endTag('fieldset');

        $event = new DefineElementEditorHtmlEvent([
            'element' => $element,
            'html' => $html,
        ]);
        Event::trigger(self::class, self::EVENT_DEFINE_SIDEBAR_HTML, $event);

        return $event->html;
    }

    /**
     * @param Element $element
     * @return string
     * @throws Exception
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private static function metaFieldsHtmlTeamleader(Element $element): string
    {
        // Need logic here to create this currently based on the element, we need an element with the data from our database

        $html = Craft::$app->getView()->renderTemplate('teamleader-focus/_components/_teamleader-sidebar', [
            'synced' => true,
            'syncToActionUrl' => UrlHelper::actionUrl('teamleader-focus/sync/sync-to', $element),
            'syncFromActionUrl' => UrlHelper::actionUrl('teamleader-focus/sync/sync-from', $element),
        ]);

        $event = new DefineElementEditorHtmlEvent([
            'element' => $element,
            'html' => $html,
        ]);

        Event::trigger(self::class, self::EVENT_DEFINE_META_FIELDS_HTML, $event);

        return $event->html;
    }

    /**
     * @param Element $element
     * @return string
     * @throws Exception
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private static function metaFieldsHtmlDeals(Element $element): string
    {
        // Need logic here to create this currently based on the element, we need an element with the data from our database

        $html = Craft::$app->getView()->renderTemplate('teamleader-focus/_components/_deals-sidebar', [
            'variable' => true,
        ]);

        $event = new DefineElementEditorHtmlEvent([
            'element' => $element,
            'html' => $html,
        ]);

        Event::trigger(self::class, self::EVENT_DEFINE_META_FIELDS_HTML, $event);

        return $event->html;
    }

}
