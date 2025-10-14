<?php

namespace craftpulse\teamleader\fields;

use Craft;
use craft\fields\BaseRelationField;
use craftpulse\teamleader\elements\Quotation;

class QuotationField extends BaseRelationField
{
    public static function displayName(): string
    {
        return Craft::t('app', 'Quotations');
    }


    public static function icon(): string
    {
        return 'envelope-open-text';
    }

    public static function elementType(): string
    {
        return Quotation::class;
    }
}