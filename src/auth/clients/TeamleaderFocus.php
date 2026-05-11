<?php
/**
 * Teamleader plugin for Craft CMS
 *
 * @link      https://craft-pulse.com
 * @copyright Copyright (c) 2025 CraftPulse
 */

namespace craftpulse\teamleader\auth\clients;

use craftpulse\teamleader\auth\grant\TeamleaderFocusRefreshTokenGrant;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

/**
 * Class TeamleaderFocus
 *
 * League OAuth2 provider for Teamleader Focus. Owns the OAuth + API base URL
 * constants used across the plugin.
 *
 * @author CraftPulse
 * @since  5.0.0
 */
class TeamleaderFocus extends AbstractProvider
{
    // Traits
    // =========================================================================

    use BearerAuthorizationTrait;

    // Const Properties
    // =========================================================================

    /**
     * @var string Base URL for the Teamleader Focus OAuth2 endpoints. Only consumed inside this class.
     */
    private const OAUTH_BASE_URL = 'https://focus.teamleader.eu/oauth2/';

    /**
     * @var string Base URL for the Teamleader Focus API. Referenced from the
     *             Formie integration and the OAuth provider — single source of truth.
     */
    public const API_BASE_URL = 'https://api.focus.teamleader.eu/';

    // Public Methods
    // =========================================================================

    /**
     * @author CraftPulse
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);

        $this->getGrantFactory()->setGrant('refresh_token', new TeamleaderFocusRefreshTokenGrant());
    }

    /**
     * @inheritdoc
     *
     * @author CraftPulse
     */
    public function getBaseAuthorizationUrl(): string
    {
        return self::OAUTH_BASE_URL . 'authorize';
    }

    /**
     * @inheritdoc
     *
     * @author CraftPulse
     */
    public function getBaseAccessTokenUrl(array $params): string
    {
        return self::OAUTH_BASE_URL . 'access_token';
    }

    /**
     * @inheritdoc
     *
     * @author CraftPulse
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return self::API_BASE_URL . 'users.me';
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @author CraftPulse
     */
    protected function getDefaultScopes(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     *
     * @author CraftPulse
     */
    protected function getAuthorizationHeaders($token = null): array
    {
        if (!$token instanceof AccessToken) {
            return [];
        }

        return ['Authorization' => 'Bearer ' . $token->getToken()];
    }

    /**
     * @inheritdoc
     *
     * @author CraftPulse
     */
    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if (!in_array($response->getStatusCode(), [200, 201, 204], true)) {
            throw new IdentityProviderException(
                'Teamleader API error: ' . $response->getStatusCode(),
                $response->getStatusCode(),
                $response->getBody()->getContents()
            );
        }
    }

    /**
     * @inheritdoc
     *
     * @author CraftPulse
     */
    protected function createResourceOwner(array $response, AccessToken $token): TeamleaderFocusResourceOwner
    {
        if (!isset($response['data'])) {
            throw new \RuntimeException('Unexpected response from the resource owner call');
        }

        return new TeamleaderFocusResourceOwner($response['data']);
    }
}
