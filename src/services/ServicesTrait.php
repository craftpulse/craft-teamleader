<?php
/**
 * teamleader plugin for Craft CMS
 *
 * @link      https://craft-pulse.com
 * @copyright Copyright (c) 2025 CraftPulse
 */

namespace craftpulse\teamleader\services;

use yii\base\InvalidConfigException;

/**
 * @author    CraftPulse
 * @package   teamleader
 *
 * @property Companies $companies
 * @property Providers $providers
 */
trait ServicesTrait
{
    public static function config(): array
    {
        return [
            'components' => [
                'companies' => Companies::class,
                'providers' => Providers::class,
            ]
        ];
    }

    // Public Methods
    // =========================================================================

    /**
     * Returns the companies service
     *
     * @return Companies The companies service
     * @throws InvalidConfigException
     */
    public function getCompanies(): Companies
    {
        return $this->get('companies');
    }

    /**
     * Returns the companies service
     *
     * @return Providers The companies service
     * @throws InvalidConfigException
     */
    public function getProviders(): Providers
    {
        return $this->get('providers');
    }

}
