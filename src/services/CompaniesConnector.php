<?php

namespace craftpulse\teamleader\services;

use Craft;

use craftpulse\teamleader\elements\Company;
use craftpulse\teamleader\Teamleader;
use craftpulse\teamleader\models\SettingsModel;

use yii\base\Component;
use yii\base\ExitException;

/**
 * Class CompaniesConnector
 *
 * @author      CraftPulse
 * @package     Teamleader
 * @since       5.0.0
 *
 */
class CompaniesConnector extends Component
{
    // Private Properties
    // =========================================================================

    /**
     * @var SettingsModel
     */
    private SettingsModel $settings;
    private bool $connected;

    // Public Methods
    // =========================================================================

    /**
     * @return void
     */
    public function init(): void
    {
        $this->settings = Teamleader::$plugin->settings;
        $this->connected = (bool)Teamleader::$plugin->providers->getToken();
    }

    /**
     * @param Company $element
     * @param bool $isNew
     * @return string|null
     */
    public function sync(Company $element, bool $isNew): ?string
    {

        if ($this->connected && $this->settings->clientId && $this->settings->clientSecret) {
            if (!$isNew) {
                $teamleaderId = $this->_generatePayload($element);

                if ($teamleaderId) {
                    return $teamleaderId;
                }
            }
        }

        return null;
    }

    // Private Methods
    // =========================================================================
    private function _generatePayload(Company $element): ?string
    {
        $payload = [
            'context' => 'companies',
            'name' => $element->title,
            'marketingMailsConsent' => $element->marketingMailsConsent,
            'emails' => $element->emails,
            'nationalIdentificationNumber' => $element->nationalIdentificationNumber,
            'telephones' => $element->telephones,
            // @TODO: maybe this can be a validator from the get go?
            'vatNumber' => Teamleader::$plugin->teamleaderConnector->formatVatNumber($element->vatNumber),
            // @TODO: check if we need a validator
            'website' => $element->website,
            // @TODO: map custom fields -> teamleaderConnector
        ];

        $endpoint = 'companies.add';

        if ($element->teamleaderId) {
            $endpoint = 'companies.update';
            $payload['id'] = $element->teamleaderId;
        }

        $response = Teamleader::$plugin->teamleaderConnector->deliverPayload($element, $endpoint, $payload);

        if ($response) {
            // This id needs to be saved in the element
            return $response['data']['id'] ?? null;
        }

        return null;
    }
}
