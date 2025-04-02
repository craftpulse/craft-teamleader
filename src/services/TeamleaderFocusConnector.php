<?php

namespace craftpulse\teamleader\services;

use craft\base\Element;
use yii\base\Component;

use verbb\auth\base\OAuthProviderTrait;

/**
 * Class TeamleaderFocusConnector
 *
 * @author      CraftPulse
 * @package     Teamleader
 * @since       5.0.0
 *
 */
class TeamleaderFocusConnector extends Component
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
        return $this->OAuthRequest($method, $endpoint, [
            $contentType => $payload,
        ]);
    }
}
