<?php

namespace craftpulse\teamleader\auth\providers;

use craftpulse\teamleader\auth\clients\TeamleaderFocus as TeamleaderFocusClient;
use verbb\auth\base\ProviderTrait;
use verbb\auth\models\Token;

/**
 * Class TeamleaderFocus
 *
 * @author      CraftPulse
 * @package     Teamleader
 * @since       5.0.0
 *
 */
class TeamleaderFocus extends TeamleaderFocusClient
{
    // Traits
    // =========================================================================

    use ProviderTrait;

    // Public Methods
    // =========================================================================

    /**
     * @param Token|null $token
     * @return string|null
     */
    public function getBaseApiUrl(?Token $token): ?string
    {
        return 'https://api.focus.teamleader.eu/';
    }
}
