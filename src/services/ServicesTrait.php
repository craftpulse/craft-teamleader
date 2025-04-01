<?php

namespace craftpulse\passwordpolicy\services;

use yii\base\InvalidConfigException;

/**
 * Class Token
 *
 * @author      CraftPulse
 * @package     Teamleader
 * @since       5.0.0
 *
 */
trait ServicesTrait
{
    public static function config(): array
    {
        return [
            'components' => [
                //'passwords' => PasswordService::class,
            ],
        ];
    }

    // Public Methods
    // =========================================================================

    /**
     * Returns the passwords service
     *
     * The service
     * @throws InvalidConfigException
     */
    /*public function getPasswords(): PasswordService
    {
        return $this->get('passwords');
    }*/
}
