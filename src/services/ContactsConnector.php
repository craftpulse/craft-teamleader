<?php

namespace craftpulse\teamleader\services;

use Craft;

use craftpulse\teamleader\elements\Contact;
use craftpulse\teamleader\Teamleader;
use craftpulse\teamleader\models\SettingsModel;

use yii\base\Component;

/**
 * Class ContactsConnector
 *
 * @author      CraftPulse
 * @package     Teamleader
 * @since       5.0.0
 *
 */
class ContactsConnector extends Component
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
     * @param Contact $element
     * @param bool $isNew
     * @return string|null
     */
    public function sync(Contact $element, bool $isNew): ?string
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
    private function _generatePayload(Contact $element): ?string
    {
        $payload = [
            'context' => 'contacts',
            'name' => $element->title,
            'marketingMailsConsent' => $element->marketingMailsConsent,
            'emails' => $element->emails,
            'first_name' => $element->firstName,
            'last_name' => $element->lastName,
            'telephones' => $element->telephones,
            'salutation' => $element->salutation,
            'language' => $element->language,
            // @TODO: map custom fields -> teamleaderConnector
        ];

        $endpoint = 'contacts.add';

        if ($element->teamleaderId) {
            $endpoint = 'contacts.update';
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
