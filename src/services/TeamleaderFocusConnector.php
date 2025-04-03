<?php

namespace craftpulse\teamleader\services;

use Craft;
use craft\base\Element;
use craftpulse\teamleader\auth\providers\TeamleaderFocus as TeamleaderFocusProvider;
use yii\base\Component;

use verbb\auth\base\OAuthProviderTrait;
use verbb\auth\base\OAuthProviderInterface;

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
    // Traits
    // =========================================================================

    use OAuthProviderTrait {
        request as OAuthRequest;
    }

    // Public Methods
    // =========================================================================

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

    public static function getOAuthProviderClass(): string
    {
        return TeamleaderFocusProvider::class;
    }

    public static function supportsOAuthConnection(): bool
    {
        return true;
    }
}
