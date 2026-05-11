<?php
/**
 * Teamleader plugin for Craft CMS
 *
 * @link      https://craft-pulse.com
 * @copyright Copyright (c) 2025 CraftPulse
 */

namespace craftpulse\teamleader\auth\clients;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;

/**
 * Class TeamleaderFocusResourceOwner
 *
 * Wraps the resource owner data returned by Teamleader Focus's users.me endpoint.
 *
 * @author CraftPulse
 * @since  5.0.0
 */
class TeamleaderFocusResourceOwner implements ResourceOwnerInterface
{
    // Traits
    // =========================================================================

    use ArrayAccessorTrait {
        getValueByKey as private _arrayAccessorTraitGetValueByKey;
    }

    // Protected Properties
    // =========================================================================

    /**
     * @var array The raw response payload from users.me.
     */
    protected array $response;

    // Public Methods
    // =========================================================================

    /**
     * @author CraftPulse
     */
    public function __construct(array $response = [])
    {
        $this->response = $response;
    }

    /**
     * @inheritdoc
     *
     * @author CraftPulse
     */
    public function getId(): array|int|string|null
    {
        return $this->_getValueByKey('id');
    }

    /**
     * @author CraftPulse
     */
    public function getAccount(): array|string|null
    {
        return $this->_getValueByKey('account', []);
    }

    /**
     * @author CraftPulse
     */
    public function getFirstName(): array|string|null
    {
        return $this->_getValueByKey('first_name', '');
    }

    /**
     * @author CraftPulse
     */
    public function getLastName(): array|string|null
    {
        return $this->_getValueByKey('last_name', '');
    }

    /**
     * @author CraftPulse
     */
    public function getEmail(): array|string|null
    {
        return $this->_getValueByKey('email', '');
    }

    /**
     * @author CraftPulse
     */
    public function getLanguage(): array|string|null
    {
        return $this->_getValueByKey('language', '');
    }

    /**
     * @author CraftPulse
     */
    public function getTelephones(): array|string|null
    {
        return $this->_getValueByKey('telephones', []);
    }

    /**
     * @author CraftPulse
     */
    public function getFunction(): array|string|null
    {
        return $this->_getValueByKey('function', '');
    }

    /**
     * @author CraftPulse
     */
    public function getTimezone(): array|string|null
    {
        return $this->_getValueByKey('time_zone', '');
    }

    /**
     * @inheritdoc
     *
     * @author CraftPulse
     */
    public function toArray(): array
    {
        return $this->response;
    }

    // Private Methods
    // =========================================================================

    /**
     * Wraps ArrayAccessorTrait::getValueByKey with $this->response prefilled.
     *
     * @author CraftPulse
     */
    private function _getValueByKey(string $key, mixed $default = null): mixed
    {
        return $this->_arrayAccessorTraitGetValueByKey(
            $this->response,
            $key,
            $default
        );
    }
}
