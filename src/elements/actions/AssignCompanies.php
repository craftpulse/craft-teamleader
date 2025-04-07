<?php

namespace craftpulse\teamleader\elements\actions;

use Craft;
use craft\base\ElementAction;

/**
 * Assign Companies element action
 */
class AssignCompanies extends ElementAction
{
    public static function displayName(): string
    {
        return Craft::t('teamleader-focus', 'Assign Companies');
    }

    public function getTriggerHtml(): ?string
    {
//        Craft::$app->getView()->registerJsWithVars(fn($type) => <<<JS
//            (() => {
//                new Craft.ElementActionTrigger({
//                    type: $type,
//
//                    // Whether this action should be available when multiple elements are selected
//                    bulk: true,
//
//                    // Return whether the action should be available depending on which elements are selected
//                    validateSelection: (selectedItems, elementIndex) => {
//                      return true;
//                    },
//
//                    // Uncomment if the action should be handled by JavaScript:
//                    // activate: (selectedItems, elementIndex) => {
//                    //   elementIndex.setIndexBusy();
//                    //   const ids = elementIndex.getSelectedElementIds();
//                    //   // ...
//                    //   elementIndex.setIndexAvailable();
//                    // },
//                });
//            })();
//        JS, [static::class]);

        return Craft::$app->getView()->renderTemplate('teamleader-focus/_components/actions/assign-companies/trigger');
    }

    public function performAction(Craft\elements\db\ElementQueryInterface $query): bool
    {
        $elements = $query->all();
        // ...
    }
}
