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
 * @property CompaniesConnector $companiesConnector
 * @property Contacts $contacts
 * @property ContactsConnector $contactsConnector
 * @property Deals $deals
 * @property DealsConnector $dealsConnector
 * @property Providers $providers
 * @property TeamleaderFocusConnector $teamleaderConnector
 *
 */
trait ServicesTrait
{
    public static function config(): array
    {
        return [
            'components' => [
                'companies' => Companies::class,
                'companiesConnector' => CompaniesConnector::class,
                'contacts' => Contacts::class,
                'contactsConnector' => ContactsConnector::class,
                'deals' => Deals::class,
                'dealsConnector' => DealsConnector::class,
                'providers' => Providers::class,
                'teamleaderConnector' => TeamleaderFocusConnector::class,
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
     * Returns the CompaniesConnector service
     *
     * @return CompaniesConnector The companies connector service
     * @throws InvalidConfigException
     */
    public function getCompaniesConnector(): CompaniesConnector
    {
        return $this->get('companiesConnector');
    }

    /**
     * Returns the contacts service
     *
     * @return Contacts The contacts service
     * @throws InvalidConfigException
     */
    public function getContacts(): Contacts
    {
        return $this->get('contacts');
    }

    /**
     * Returns the ContactsConnector service
     *
     * @return ContactsConnector The contacts connector service
     * @throws InvalidConfigException
     */
    public function getContactsConnector(): ContactsConnector
    {
        return $this->get('contactsConnector');
    }

    /**
     * Returns the deals service
     *
     * @return Deals The deals service
     * @throws InvalidConfigException
     */
    public function getDeals(): Deals
    {
        return $this->get('deals');
    }

    /**
     * Returns the DealsConnector service
     *
     * @return DealsConnector The deals connector service
     * @throws InvalidConfigException
     */
    public function getDealsConnector(): DealsConnector
    {
        return $this->get('dealsConnector');
    }

    /**
     * Returns the TeamleaderFocusConnector service
     *
     * @return TeamleaderFocusConnector The teamleader focus connector service
     * @throws InvalidConfigException
     */
    public function getTeamleaderFocusConnector(): TeamleaderFocusConnector
    {
        return $this->get('teamleaderFocusConnector');
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
