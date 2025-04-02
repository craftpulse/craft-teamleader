<?php

namespace craftpulse\teamleader\elements\conditions;

use Craft;
use craft\elements\conditions\ElementCondition;

/**
 * Deal condition
 */
class DealCondition extends ElementCondition
{
    protected function selectableConditionRules(): array
    {
        return array_merge(parent::conditionRuleTypes(), [
            // ...
        ]);
    }
}
