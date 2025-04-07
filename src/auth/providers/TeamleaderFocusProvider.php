<?php

namespace craftpulse\teamleader\auth\providers;

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
