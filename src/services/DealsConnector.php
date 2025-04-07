<?php

namespace craftpulse\teamleader\services;

use Craft;
use craftpulse\teamleader\elements\Deal;
use craftpulse\teamleader\models\SettingsModel;
use craftpulse\teamleader\Teamleader;
use yii\base\Component;

/**
 * Class DealsConnector
 *
 * @author      CraftPulse
 * @package     Teamleader
 * @since       5.0.0
 *
 */
class DealsConnector extends Component
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
     * @param Deal $element
     * @param bool $isNew
     * @return string|null
     */
    public function sync(Deal $element, bool $isNew): ?string
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
    private function _generatePayload(Deal $element): ?string
    {
        $payload = [
            'context' => 'deals',
            'title' => $element->title,
            'lead' => [
                'customer' => [
                    'type' => $element->companyId ? 'company' : 'contact',
                    'id' => $element->companyId ?: $element->contactId,
                ]
            ],
            // @TODO this probably needs more logic to be tested
            'contact_person_id' => $element->contactId,
        ];

        $endpoint = 'deals.create';

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
