<?php
/**
 * Teamleader plugin for Craft CMS
 *
 * @link      https://craft-pulse.com
 * @copyright Copyright (c) 2025 CraftPulse
 */

namespace craftpulse\teamleader\auth\grant;

use League\OAuth2\Client\Grant\AbstractGrant;

/**
 * Class TeamleaderFocusRefreshTokenGrant
 *
 * Custom refresh_token grant for the League OAuth2 client. Teamleader Focus
 * requires client_id and client_secret on refresh requests, which the default
 * grant doesn't include.
 *
 * @author CraftPulse
 * @since  5.0.0
 */
class TeamleaderFocusRefreshTokenGrant extends AbstractGrant
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @author CraftPulse
     */
    public function prepareRequestParameters(array $defaults, array $options): array
    {
        return [
            'grant_type' => $this->getName(),
            'client_id' => $defaults['client_id'],
            'client_secret' => $defaults['client_secret'],
            'refresh_token' => $options['refresh_token'],
        ];
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @author CraftPulse
     */
    protected function getName(): string
    {
        return 'refresh_token';
    }

    /**
     * @inheritdoc
     *
     * @author CraftPulse
     */
    protected function getRequiredRequestParameters(): array
    {
        return [
            'refresh_token',
        ];
    }
}
