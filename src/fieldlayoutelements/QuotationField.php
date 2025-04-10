<?php

namespace craftpulse\teamleader\fieldlayoutelements;

use Craft;
use craft\fieldlayoutelements\BaseNativeField;
use craft\base\ElementInterface;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\web\assets\tablesettings\TableSettingsAsset;
use craft\web\assets\timepicker\TimepickerAsset;
use craftpulse\teamleader\elements\Company;
use InvalidArgumentException;

class QuotationField extends BaseNativeField
{
    // Public Properties
    // =========================================================================
    /**
     * @var string|null $name
     */
    public ?string $name = null;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (($element->quotations ?? null) == null) {
            return '<p>'.Craft::t('teamleader-focus', 'No quotations in this deal').'</p>';
        }

        $html = '<table style="width:100%;">';
        $html .= '<tr>';
        $html .= '<th>Title</th>';
        $html .= '<th>Amount</th>';
        $html .= '<th>Product</th>';
        $html .= '<th>Link</th>';
        $html .= '</tr>';

        foreach($element->quotations as $quotation) {
            $html .= '<tr>';
            $html .= '<td>'.$quotation->title.'</td>';
            $html .= '<td>'.$quotation->totalTaxInclusiveAmount.' '.$quotation->currency.'</td>';
            $html .= '<td><a href="'.$quotation->product->getCpEditUrl().'" title="Visit webpage" rel="noopener" target="_blank" aria-label="View">'.$quotation->product->title.'</a></td>';
            $html .= '<td><a href="'.$quotation->getCpEditUrl().'" title="Visit webpage" rel="noopener" target="_blank" aria-label="View">View</a></td>';
            $html .= '</tr>';
        }

        $html .= '</table>';

        return $html;
    }
}
