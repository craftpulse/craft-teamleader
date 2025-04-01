<?php
/**
 * teamleader plugin for Craft CMS
 *
 * @link      https://craft-pulse.com
 * @copyright Copyright (c) 2025 CraftPulse
 */

namespace craftpulse\teamleader\services;

use craftpulse\teamleader\services\Redirects;
use craftpulse\teamleader\services\Routes;
use craftpulse\teamleader\services\Statistics;

/**
 * @author    CraftPulse
 * @package   teamleader
 *
 * @property Redirects $redirects
 * @property Routes $routes
 * @property Statistics $statistics
 */
trait ServicesTrait
{
    public static function config(): array
    {
        return [
            'components' => [
                'companies' => Companies::class,
            ]
        ];
    }

    // Public Methods
    // =========================================================================


    /**
     * Returns the routes service
     *
     * @return Companies The routes service
     * @throws InvalidConfigException
     */
    public function getCompanies(): Companies
    {
        return $this->get('companies');
    }
}
