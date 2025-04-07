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

    public static function getOAuthProviderClass(): string
    {
        return TeamleaderFocusProvider::class;
    }

    public static function supportsOAuthConnection(): bool
    {
        return true;
    }

    public function getToken(): ?Token
    {
        return $this->token;
    }
}
