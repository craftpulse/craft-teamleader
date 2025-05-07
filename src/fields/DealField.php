<?php

namespace craftpulse\teamleader\fields;

use Craft;
use craft\fields\BaseRelationField;
use craftpulse\teamleader\elements\Deal;

class DealField extends BaseRelationField
{
    public static function displayName(): string
    {
        return Craft::t('app', 'Deals');
    }


    public static function icon(): string
    {
        return 'envelope-open-text';
    }

    public static function elementType(): string
    {
        return Deal::class;
    }
}