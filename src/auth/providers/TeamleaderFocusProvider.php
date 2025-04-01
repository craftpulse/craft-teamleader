<?php

namespace craftpulse\teamleader\auth\providers;

use craftpulse\teamleader\auth\providers\TeamleaderFocus;
use verbb\auth\base\OAuthProvider;

class TeamleaderFocusProvider extends OAuthProvider
{
    // Public Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return TeamleaderFocus::class;
    }
}
