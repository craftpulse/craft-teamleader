<?php

namespace craftpulse\teamleader\helpers;

class VatHelper
{
    /**
     * @param string $vatNumber
     * @return string|false
     */
    public static function formatVatNumber(string $vatNumber): string|false
    {
        // Extract first two and ensure it's valid A-Z
        $countryCode = strtoupper(substr($vatNumber, 0, 2));

        // Ensure the country code is valid (basic check: two uppercase letters)
        if (!preg_match('/^[A-Z]{2}$/', $countryCode)) {
            return false; // Invalid country code
        }

        // Extract the numerical part and remove non-numeric characters
        $vatNumber = preg_replace('/[^0-9]/', '', substr($vatNumber, 2));

        // Ensure it has at least 8 and at most 12 digits (common VAT length range in EU)
        if (strlen($vatNumber) < 8 || strlen($vatNumber) > 12) {
            return false; // Invalid format
        }

        // Format the VAT number according to common EU formats
        if (strlen($vatNumber) === 9) {
            $formattedNumber = substr($vatNumber, 0, 3) . '.' . substr($vatNumber, 3, 3) . '.' . substr($vatNumber, 6, 3);
        } elseif (strlen($vatNumber) === 10) {
            $formattedNumber = substr($vatNumber, 0, 4) . '.' . substr($vatNumber, 4, 3) . '.' . substr($vatNumber, 7, 3);
        } else {
            $formattedNumber = wordwrap($vatNumber, 3, '.', true); // General formatting
        }

        return $countryCode . ' ' . $formattedNumber;
    }
}
