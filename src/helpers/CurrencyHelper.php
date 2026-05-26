<?php
/**
 * Teamleader plugin for Craft CMS
 *
 * @link      https://craft-pulse.com
 * @copyright Copyright (c) 2025 CraftPulse
 */

namespace craftpulse\teamleader\helpers;

/**
 * Class CurrencyHelper
 *
 * Formats currency options for the Teamleader Focus integration.
 *
 * @author CraftPulse
 * @since  5.2.0
 */
class CurrencyHelper
{
    // Const Properties
    // =========================================================================

    /**
     * @var array Default fallback currencies if the API call fails.
     */
    private const DEFAULT_CURRENCIES = [
        ['label' => 'EUR - Euro', 'value' => 'EUR'],
    ];

    // Public Methods
    // =========================================================================

    /**
     * Format currency response data into options array.
     *
     * @param  array $data Raw API response data from currencies.exchangeRates
     * @return array Formatted options for select fields
     *
     * @author CraftPulse
     */
    public static function formatCurrencyOptions(array $data): array
    {
        $currencies = [];

        foreach ($data as $currency) {
            $currencies[] = [
                'label' => $currency['code'] . ' - ' . $currency['name'],
                'value' => $currency['code'],
            ];
        }

        return !empty($currencies) ? $currencies : self::DEFAULT_CURRENCIES;
    }

    /**
     * Get the default fallback currencies.
     *
     * @author CraftPulse
     */
    public static function getDefaultCurrencies(): array
    {
        return self::DEFAULT_CURRENCIES;
    }
}
