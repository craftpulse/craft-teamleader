<?php

namespace craftpulse\teamleader\services;

use craft\base\Element;

use craftpulse\teamleader\Teamleader;
use craftpulse\teamleader\auth\providers\TeamleaderFocus as TeamleaderFocusProvider;

use yii\base\Component;

use verbb\auth\base\OAuthProviderTrait;
use verbb\auth\base\OAuthProviderInterface;
use verbb\auth\models\Token;

/**
 * Class TeamleaderFocusConnector
 *
 * @author      CraftPulse
 * @package     Teamleader
 * @since       5.0.0
 *
 */
class TeamleaderFocusConnector extends Component implements OAuthProviderInterface
{
    // Public Properties
    // =========================================================================

    /**
     * @var Token|null
     */
    public ?Token $token = null;

    // Traits
    // =========================================================================

    use OAuthProviderTrait {
        request as OAuthRequest;
    }

    // Public Methods
    // =========================================================================

    /**
     * @return void
     */
    public function init(): void
    {
        $this->token = Teamleader::$plugin->providers->getToken();
    }

    /**
     * @param Element $element
     * @param string $endpoint
     * @param mixed $payload
     * @param string $method
     * @param string $contentType
     * @return mixed
     */
    public function deliverPayload(Element $element, string $endpoint, mixed $payload, string $method = 'POST', string $contentType = 'json'): mixed
    {
        // Return a JSON response from the provider
        return $this->request($method, $endpoint, [
            $contentType => $payload,
        ]);
    }

    /**
     * @return string
     */
    public static function getOAuthProviderClass(): string
    {
        return TeamleaderFocusProvider::class;
    }

    /**
     * @return bool
     */
    public static function supportsOAuthConnection(): bool
    {
        return true;
    }

    /**
     * @return Token|null
     */
    public function getToken(): ?Token
    {
        return $this->token;
    }

    /**
     * @param string $vatNumber
     * @return bool|string
     */
    public function formatVatNumber(string $vatNumber): bool|string
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
