<?php
/**
 * Teamleader plugin for Craft CMS
 *
 * @link      https://craft-pulse.com
 * @copyright Copyright (c) 2025 CraftPulse
 */

namespace craftpulse\teamleader\auth\providers;

use craftpulse\teamleader\auth\clients\TeamleaderFocus as TeamleaderFocusClient;

use verbb\auth\base\ProviderTrait;
use verbb\auth\models\Token;

/**
 * Class TeamleaderFocus
 *
 * verbb/auth wrapper around the League OAuth2 client. Used by Formie to wire
 * the provider into the integration.
 *
 * @author CraftPulse
 * @since  5.0.0
 */
class TeamleaderFocus extends TeamleaderFocusClient
{
    // Traits
    // =========================================================================

    use ProviderTrait;

    // Public Methods
    // =========================================================================

    /**
     * @author CraftPulse
     */
    public function getBaseApiUrl(?Token $token): ?string
    {
        return self::API_BASE_URL;
    }
}
