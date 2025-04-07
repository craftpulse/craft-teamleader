<?php

namespace craftpulse\teamleader\fieldlayoutelements;

use Craft;
use craft\base\FieldLayoutElementInterface;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseUiElement;
use yii\base\InvalidConfigException;

class ContactSidebarAction extends BaseUiElement implements FieldLayoutElementInterface
{
    public string $name = 'contactSidebarAction';

    public function getLabel(): ?string
    {
        return Craft::t('app', 'Contact Action');
    }

    public function getHtml(ElementInterface $element, bool $static): string
    {
        return Craft::$app->getView()->renderTemplate('_components/actions/assign-companies/trigger', [
            'element' => $element,
        ]);
    }

    public function isSelectable(): bool
    {
        return true;
    }

    public function isVisible(ElementInterface $element): bool
    {
        return true;
    }

    public function getInputHtml(ElementInterface $element): string
    {
        return $this->getHtml($element, false);
    }

    public function getSidebar(): bool
    {
        return true;
    }
}
