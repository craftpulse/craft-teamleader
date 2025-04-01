<?php

namespace craftpulse\teamleader\models;

use Craft;
use craft\base\Model;
use craft\base\PluginInterface;

use League\OAuth2\Client\Token\AccessToken;

use DateTime;

/**
 * Class Token
 *
 * @author      CraftPulse
 * @package     Teamleader
 * @since       5.0.0
 *
 */
class SettingsModel extends Model
{
    // Public Properties
    // =========================================================================

    public ?int $id = null;
    public ?string $accessToken = null;
    public ?string $secret = null;
    public ?string $expires = null;
    public ?string $refreshToken = null;
    public ?string $resourceOwnerId = null;
    public array $values = [];
    public ?DateTime $dateCreated = null;
    public ?DateTime $dateUpdated = null;
    public ?string $uid = null;

    // Private Properties
    // =========================================================================

    private ?AccessToken $_token = null;

    // Public Methods
    // =========================================================================

    public function setToken(AccessToken $token): void
    {
        $this->_token = $token;
    }

    public function getToken(): ?AccessToken
    {
        if ($this->_token) {
            return $this->_token;
        }

        return $this->_token = new AccessToken(
            array_merge($this->values, [
              'access_token' => $this->accessToken,
              'refresh_token' => $this->refreshToken,
              'expires' => $this->expires,
              'resource_owner_id' => $this->resourceOwnerId,
            ])
        );
    }
}
