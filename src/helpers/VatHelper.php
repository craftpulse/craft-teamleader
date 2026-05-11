<?php
/**
 * Teamleader plugin for Craft CMS
 *
 * @link      https://craft-pulse.com
 * @copyright Copyright (c) 2025 CraftPulse
 */

namespace craftpulse\teamleader\helpers;

/**
 * Class VatHelper
 *
 * Normalizes and validates EU VAT numbers for use with the Teamleader Focus API.
 * The API stores and expects raw alphanumeric format (e.g. "BE0899623035").
 *
 * @author CraftPulse
 * @since  5.1.0
 */
class VatHelper
{
    // Const Properties
    // =========================================================================

    /**
     * EU VAT body patterns per country code (after the 2-letter prefix).
     *
     * @see https://ec.europa.eu/taxation_customs/vies/faq.html
     * @var array<string, string>
     */
    private const VAT_PATTERNS = [
        'AT' => '/^U\d{8}$/',
        'BE' => '/^[01]\d{9}$/',
        'BG' => '/^\d{9,10}$/',
        'HR' => '/^\d{11}$/',
        'CY' => '/^\d{8}[A-Z]$/',
        'CZ' => '/^\d{8,10}$/',
        'DK' => '/^\d{8}$/',
        'EE' => '/^\d{9}$/',
        'FI' => '/^\d{8}$/',
        'FR' => '/^[0-9A-Z]{2}\d{9}$/',
        'DE' => '/^\d{9}$/',
        'EL' => '/^\d{9}$/',
        'GR' => '/^\d{9}$/',
        'HU' => '/^\d{8}$/',
        'IE' => '/^(\d[A-Z0-9+*]\d{5}[A-Z]|\d{7}[A-Z]{1,2})$/',
        'IT' => '/^\d{11}$/',
        'LV' => '/^\d{11}$/',
        'LT' => '/^(\d{9}|\d{12})$/',
        'LU' => '/^\d{8}$/',
        'MT' => '/^\d{8}$/',
        'NL' => '/^\d{9}B\d{2}$/',
        'PL' => '/^\d{10}$/',
        'PT' => '/^\d{9}$/',
        'RO' => '/^\d{2,10}$/',
        'SK' => '/^\d{10}$/',
        'SI' => '/^\d{8}$/',
        'ES' => '/^([A-Z]\d{7}[A-Z0-9]|\d{8}[A-Z])$/',
        'SE' => '/^\d{12}$/',
        'XI' => '/^\d{9}$/',
    ];

    // Public Methods
    // =========================================================================

    /**
     * Normalize a VAT number to the raw alphanumeric format expected by the Teamleader Focus API.
     *
     * Strips formatting (spaces, dots, dashes) and validates the body against
     * known EU country patterns. Preserves letters in the body where required
     * (e.g. FR, NL, IE, ES, AT, CY).
     *
     * @param  string       $vatNumber Raw user input (e.g. "BE 0899.623.035", "FR XX 999999999")
     * @return string|false The normalized VAT number (e.g. "BE0899623035") or false if invalid
     *
     * @author CraftPulse
     */
    public static function formatVatNumber(string $vatNumber): string|false
    {
        // Strip common formatting characters (spaces, dots, dashes, slashes)
        $normalized = preg_replace('/[\s.\-\/]/', '', $vatNumber);

        // Extract and validate country code (first 2 characters, uppercased)
        $countryCode = strtoupper(substr($normalized, 0, 2));

        if (!preg_match('/^[A-Z]{2}$/', $countryCode)) {
            return false;
        }

        // Uppercase the body — required for pattern matching (NL has "B", FR has letters, etc.)
        $vatBody = strtoupper(substr($normalized, 2));

        if ($vatBody === '') {
            return false;
        }

        // Validate against known EU pattern if available
        $pattern = self::VAT_PATTERNS[$countryCode] ?? null;

        if ($pattern !== null && !preg_match($pattern, $vatBody)) {
            return false;
        }

        // For unknown country codes, apply a basic alphanumeric length check
        if ($pattern === null && !preg_match('/^[A-Z0-9]{4,15}$/', $vatBody)) {
            return false;
        }

        return $countryCode . $vatBody;
    }
}
