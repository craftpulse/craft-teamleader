<?php
/**
 * Teamleader plugin for Craft CMS
 *
 * This plugin integrates with teamleader focus to generate and manage deals, and comes with a Formie integration.
 *
 * @link      https://craft-pulse.com
 * @copyright Copyright (c) 2025 CraftPulse
 */

namespace craftpulse\teamleader\integrations\formie;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use craftpulse\teamleader\auth\providers\TeamleaderFocus as TeamleaderFocusProvider;

use Illuminate\Support\Collection;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

use verbb\formie\base\Integration;
use verbb\auth\base\OAuthProviderInterface;
use verbb\auth\models\Token;
use verbb\formie\base\Crm;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use yii\base\Exception;

/**
 * Class TeamleaderFocus
 *
 * @author      CraftPulse
 * @package     Teamleader
 * @since       5.0.0
 *
 */
class TeamleaderFocus extends Crm implements OAuthProviderInterface
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Teamleader Focus');
    }

    public static function getOAuthProviderClass(): string
    {
        return TeamleaderFocusProvider::class;
    }

    public static function supportsOAuthConnection(): bool
    {
        return true;
    }

    // Properties
    // =========================================================================

    /**
     * @var bool
     */
    public bool $mapToContacts = false;
    /**
     * @var bool
     */
    public bool $mapToCompanies = false;
    /**
     * @var bool
     */
    public bool $mapToDeals = false;
    /**
     * @var bool
     */
    public bool $linkToCompany = false;
    /**
     * @var string|null
     */
    public ?string $dealTitle = null;
    /**
     * @var string|null
     */
    public ?string $userId = null;
    /**
     * @var string|null
     */
    public ?string $companyId = null;

    /**
     * @var array|null
     */
    public ?array $contactsFieldMapping = null;
    /**
     * @var array|null
     */
    public ?array $companiesFieldMapping = null;
    /**
     * @var array|null
     */
    public ?array $dealsFieldMapping = null;

    public bool $appendTags = false;

    // Public Methods
    // =========================================================================
    /**
     * @return string
     */
    public function getIconUrl(): string
    {
        return Craft::$app->getAssetManager()->getPublishedUrl("@craftpulse/teamleader/icon-mask.svg", true);
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'This is a Teamleader Focus lead creation integration.');
    }

    /**
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    public function getSettingsHtml(): string
    {
        $settings = $this->getSettingsHtmlVariables();

        return Craft::$app->getView()->renderTemplate('teamleader-focus/integrations/formie/_plugin-settings', $settings);
    }

    /**
     * @param $form
     * @return string
     * @throws Exception
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function getFormSettingsHtml($form): string
    {
        $formSettings = $this->getFormSettingsHtmlVariables($form);

        return Craft::$app->getView()->renderTemplate('teamleader-focus/integrations/formie/_form-settings', $formSettings);
    }

    /**
     * @return string
     */
    public function getApiDomain(): string
    {
        return "https://api.focus.teamleader.eu/";
    }

    /**
     * @param Token|null $token
     * @return string|null
     */
    public function getBaseApiUrl(?Token $token): ?string
    {
        return "https://api.focus.teamleader.eu/";
    }

    /**
     * @return array
     */
    public function getOAuthProviderConfig(): array
    {
        $config = parent::getOAuthProviderConfig();
        $config['domain'] = $this->getApiDomain();
        $config['baseApiUrl'] = $this->getApiDomain();
        $config['clientId'] = $this->getClientId();
        $config['clientSecret'] = $this->getClientSecret();
        $config['redirectUri'] = $this->getRedirectUri();

        return $config;
    }

    private function _getExistingTags(string $id): array
    {
        $response = $this->request('POST', "contacts.info", [
            'json' => [
                'id' => $id,
            ],
        ]);

        Craft::warning("DEBUG: response: " . Json::encode($response));

        return $response['data']['tags'] ?? [];
    }

    /**
     * @param Submission $submission
     * @return bool
     * @throws IntegrationException
     */
    public function sendPayload(Submission $submission): bool
    {
        try {
            $contactValues = $this->getFieldMappingValues($submission, $this->contactsFieldMapping, 'contacts');
            $companyValues = $this->getFieldMappingValues($submission, $this->companiesFieldMapping, 'companies');
            $dealsValues = $this->getFieldMappingValues($submission, $this->dealsFieldMapping, 'deals');

            if ($this->mapToContacts) {
                $contactPayload = $this->_prepPayload($contactValues, 'contacts');
                $endpoint = 'contacts.add';

                // First check if we already have a user with the primary email address attached.

                $filterPayload = [
                    'filter' => [
                        'email' => [
                            'type' => 'primary',
                            'email' => $contactValues['email'],
                        ],
                    ]
                ];

                $response = $this->deliverPayload($submission, 'contacts.list', $filterPayload);
                $currentUser = Collection::make($response['data'])->first();

                // Make sure we send a "contacts.update" request if we have an actual response id.
                if(!empty($currentUser['id'])) {
                    // Pull out stuff for later
                    $tags = ArrayHelper::remove($contactValues, 'tags');

                    $endpoint = 'contacts.update';
                    $contactPayload['id'] = $currentUser['id'];
                    $this->userId = $currentUser['id'];

                    // Process any tags, we need to fetch them first, then add or delete them.
                    if ($tags) {
                        if ($this->appendTags) {
                            Craft::warning("DEBUG 1: Fetching tags to append");
                            $existingTags = $this->_getExistingTags($this->userId);
                            Craft::warning("DEBUG 2: Existing tags: " . Json::encode($existingTags));
                            $tags = array_merge($tags, $existingTags);
                            Craft::warning("DEBUG 3: All tags to append: " . Json::encode($tags));
                        }

                        $contactPayload['tags'] = $tags;
                    }
                }

                $response = $this->deliverPayload($submission, $endpoint, $contactPayload);

                if ($response === false) {
                    return true;
                }

                if($endpoint === 'contacts.add') {
                    $this->userId = $response['data']['id'] ?? null;

                    if (is_null($this->userId)) {
                        Integration::error($this, Craft::t('formie', 'Missing return “id” {response}. Sent payload {payload}', [
                            'response' => Json::encode($response),
                            'payload' => Json::encode($contactValues),
                        ]), true);

                        return false;
                    }
                } else {
                    if (!empty($response)) {
                        Integration::error($this, Craft::t('formie', 'Invalid response {response} Sent payload {payload}', [
                            'response' => Json::encode($response),
                            'payload' => Json::encode($contactValues),
                        ]), true);

                        return false;
                    }
                }
            }

            if ($this->mapToCompanies) {
                $companyPayload = $this->_prepPayload($companyValues, 'companies');
                $endpoint = 'companies.add';

                // First check if we already have a user with the primary email address attached.

                $filterPayload = [
                    'filter' => [
                        'vat_number' => $this->_formatVatNumber($companyValues['vat_number']),
                    ]
                ];

                $response = $this->deliverPayload($submission, 'companies.list', $filterPayload);
                $currentCompany = Collection::make($response['data'])->first();

                // Make sure we send a "contacts.update" request if we have an actual response id.
                if(!empty($currentCompany['id'])) {
                    $endpoint = 'companies.update';
                    $companyPayload['id'] = $currentCompany['id'];
                    $this->companyId = $currentCompany['id'];
                }

                $response = $this->deliverPayload($submission, $endpoint, $companyPayload);

                if ($response === false) {
                    return true;
                }

                if($endpoint === 'companies.add') {
                    $this->companyId = $response['data']['id'] ?? null;

                    if (is_null($this->companyId)) {
                        Integration::error($this, Craft::t('formie', 'Missing return “id” {response}. Sent payload {payload}', [
                            'response' => Json::encode($response),
                            'payload' => Json::encode($contactValues),
                        ]), true);

                        return false;
                    }
                } else {
                    if (!empty($response)) {
                        Integration::error($this, Craft::t('formie', 'Invalid response {response} Sent payload {payload}', [
                            'response' => Json::encode($response),
                            'payload' => Json::encode($contactValues),
                        ]), true);

                        return false;
                    }
                }
            }

            if ($this->mapToDeals && ($this->userId || $this->companyId)) {

                $options = [
                    'contact_person_id' => $this->userId ?? '',
                    'company_id' => $this->companyId ?? '',
                ];

                $dealPayload = $this->_prepPayload($dealsValues, 'deals', $options);

                $response = $this->deliverPayload($submission, 'deals.create', $dealPayload);

                if ($response === false) {
                    return true;
                }

                $dealId = $response['data']['id'] ?? null;

                if (is_null($dealId)) {
                    Integration::error($this, Craft::t('formie', 'Missing return “id” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($dealsValues),
                    ]), true);

                    return false;
                }
            }
        } catch (Exception $error) {
            Integration::apiError($this, $error);

            return false;
        }

        return true;
    }

    /**
     * @return IntegrationFormSettings
     * @throws IntegrationException
     */
    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            if ($this->mapToContacts) {
                $fields = $this->_fetchCustomFields('contact');

                $settings['contacts'] = array_merge([
                    new IntegrationField([
                        'handle' => 'salutation',
                        'name' => Craft::t('formie', 'Salutation'),
                    ]),
                    new IntegrationField([
                        'handle' => 'first_name',
                        'name' => Craft::t('formie', 'First Name'),
                    ]),
                    new IntegrationField([
                        'handle' => 'last_name',
                        'name' => Craft::t('formie', 'Last Name'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'email',
                        'name' => Craft::t('formie', 'Email address'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'mobile_phone',
                        'name' => Craft::t('formie', 'Mobile number'),
                    ]),
                    new IntegrationField([
                        'handle' => 'phone',
                        'name' => Craft::t('formie', 'Phone number'),
                    ]),
                    // @TODO - build support for repeater fields, since Teamleader Focus supports multiple addresses in an array
                    new IntegrationField([
                        'handle' => 'addressLine1',
                        'name' => Craft::t('formie', 'Address'),
                    ]),
                    new IntegrationField([
                        'handle' => 'postal_code',
                        'name' => Craft::t('formie', 'Postal Code'),
                    ]),
                    new IntegrationField([
                        'handle' => 'city',
                        'name' => Craft::t('formie', 'City'),
                    ]),
                    new IntegrationField([
                        'handle' => 'country',
                        'name' => Craft::t('formie', 'Country'),
                    ]),
                    new IntegrationField([
                        'handle' => 'language',
                        'name' => Craft::t('formie', 'Language'),
                    ]),
                    new IntegrationField([
                        'handle' => 'marketing_mails_consent',
                        'name' => Craft::t('formie', 'Marketing Mails Consent'),
                        'type' => 'boolean',
                    ]),
                    new IntegrationField([
                        'handle' => 'tags',
                        'name' => Craft::t('formie', 'Tags'),
                        'type' => 'array',
                    ]),
                    new IntegrationField([
                        'handle' => 'remarks',
                        'name' => Craft::t('formie', 'Remarks'),
                    ]),
                ], $this->_getCustomFields($fields));
            }

            if ($this->mapToCompanies) {
                $fields = $this->_fetchCustomFields('company');

                $settings['companies'] = array_merge([
                    new IntegrationField([
                        'handle' => 'company_name',
                        'name' => Craft::t('formie', 'Company Name'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'email',
                        'name' => Craft::t('formie', 'Email address'),
                        'required' => true,
                    ]),
                    // @TODO - build support for repeater fields, since Teamleader Focus supports multiple addresses in an array
                    new IntegrationField([
                        'handle' => 'addressLine1',
                        'name' => Craft::t('formie', 'Address'),
                    ]),
                    new IntegrationField([
                        'handle' => 'postal_code',
                        'name' => Craft::t('formie', 'Postal Code'),
                    ]),
                    new IntegrationField([
                        'handle' => 'city',
                        'name' => Craft::t('formie', 'City'),
                    ]),
                    new IntegrationField([
                        'handle' => 'country',
                        'name' => Craft::t('formie', 'Country'),
                    ]),
                    new IntegrationField([
                        'handle' => 'mobile_phone',
                        'name' => Craft::t('formie', 'Mobile number'),
                    ]),
                    new IntegrationField([
                        'handle' => 'phone',
                        'name' => Craft::t('formie', 'Phone number'),
                    ]),
                    new IntegrationField([
                        'handle' => 'vat_number',
                        'name' => Craft::t('formie', 'VAT Number'),
                    ]),
                    new IntegrationField([
                        'handle' => 'national_identification_number',
                        'name' => Craft::t('formie', 'National Identification Number'),
                    ]),
                    new IntegrationField([
                        'handle' => 'website',
                        'name' => Craft::t('formie', 'Website'),
                    ]),
                    new IntegrationField([
                        'handle' => 'language',
                        'name' => Craft::t('formie', 'Language'),
                    ]),
                    new IntegrationField([
                        'handle' => 'marketing_mails_consent',
                        'name' => Craft::t('formie', 'Marketing Mails Consent'),
                        'type' => 'boolean',
                    ]),
                ], $this->_getCustomFields($fields));
            }

            if ($this->mapToDeals) {
                $fields = $this->_fetchCustomFields('sale');

                $settings['deals'] = array_merge([], $this->_getCustomFields($fields));
            }
        } catch (Exception $error) {
            Integration::apiError($this, $error);
        }

        return new IntegrationFormSettings($settings);
    }

    /**
     * @param string $context
     * @return array|null
     */
    private function _fetchCustomFields(string $context): ?array {
        $filters = [
            'filter' => [
                'context' => $context,
            ]
        ];

        $response = $this->request('POST', 'customFieldDefinitions.list', $filters);
        $customFields = $response['data'];

        if (empty($customFields)) {
            return null;
        } else {
            return Collection::make($customFields)->filter(fn($field) => $field['context'] === $context)->toArray();
        }
    }

    /**
     * @param mixed $fields
     * @return array
     */
    private function _getCustomFields(mixed $fields): array
    {
        $customFields = [];

        foreach ($fields as $field) {
            $type = $field['type'] ?? null;

            if (!$type) {
                continue;
            }

            $customFields[] = new IntegrationField([
                'handle' => $field['id'],
                'name' => $field['label'],
                'type' => $this->_convertFieldType($type),
                'sourceType' => $type,
            ]);
        }

        return $customFields;
    }

    /**
     * @param string $fieldType
     * @return string
     */
    private function _convertFieldType(string $fieldType): string
    {
        $fieldTypes= [
            'multi_select' => IntegrationField::TYPE_ARRAY,
            'date' => IntegrationField::TYPE_DATE,
            'money' => IntegrationField::TYPE_FLOAT,
            'auto_increment' => IntegrationField::TYPE_NUMBER,
            'integer' => IntegrationField::TYPE_NUMBER,
            'number' => IntegrationField::TYPE_NUMBER,
            'boolean' => IntegrationField::TYPE_BOOLEAN,
            'telephone' => IntegrationField::TYPE_PHONE,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    /**
     * @param array $fields
     * @param string $context
     * @param array $options
     * @return array
     */
    private function _prepPayload(array $fields, string $context, array $options = []): array
    {
        $payload = $fields;
        $payload['context'] = $context;

        if (in_array($context, ['contacts', 'companies'])) {
            if(isset($payload['email'])) {
                $payload['emails'][] = [
                    'type' => 'primary',
                    'email' => $payload['email'],
                ];
                unset($payload['email']);
            }

            if(isset($payload['phone'])) {
                $payload['telephones'][] = [
                    'type' => 'phone',
                    'number' => $payload['phone'],
                ];
                unset($payload['phone']);
            }

            if(isset($payload['mobile_phone'])) {
                $payload['telephones'][] = [
                    'type' => 'phone',
                    'number' => $payload['mobile_phone'],
                ];
                unset($payload['phone']);
            }

            if(isset($payload['address'])) {
                $address = $this->_generateAddressObject($payload['address']);
                if($address) {
                    $payload['addresses'][] = $address;
                }
            }

            if(isset($payload['company_name'])) {
                $payload['name'] = $payload['company_name'];
                unset($payload['company_name']);
            }

            return $payload;
        }

        if ($context === 'deals') {
            $payload['lead'] = [
                'customer' => [
                    'type' => $this->companyId ? 'company' : 'contact',
                    'id' => $this->companyId ?: $this->userId,
                ],
                'contact_person_id' => $this->userId ?: '',
            ];
            $payload['title'] = $this->dealTitle;
        }

        return $payload;
    }

    /**
     * @param array $fields
     * @return array|null
     */
    private function _generateAddressObject(array $fields): ?array {
        // All fields need to be there, otherwise we won't generate it.
        $payload = $fields;
        $required_fields = ['addressLine1', 'postal_code', 'city', 'country'];
        $missing_values = array_diff($required_fields, array_keys($fields));

        if (!empty($missing_values)) {
            $address = [
                'type' => 'primary',
                'address' => [
                    'line_1' => $payload['addressLine1'],
                    'postal_code' => $payload['postal_code'],
                    'city' => $payload['city'],
                    'country' => $payload['country'],
                ]
            ];

            return $address;
        } else {
            return null;
        }
    }

    /**
     * @param string $vatNumber
     * @return bool|string
     */
    private function _formatVatNumber(string $vatNumber): bool|string
    {
        // Extract first two and ensure it's valid A-Z
        $countryCode = strtoupper(substr($vatNumber, 0, 2));

        // Ensure the country code is valid (basic check: two uppercase letters)
        if (!preg_match('/^[A-Z]{2}$/', $countryCode)) {
            return false; // Invalid country code
        }

        // Extract the numerical part and remove non-numeric characters
        $vatNumber = preg_replace('/[^0-9]/', '', substr($vatNumber, 2));

        // Ensure it has at least 8 and at most 12 digits (common VAT length range in EU)
        if (strlen($vatNumber) < 8 || strlen($vatNumber) > 12) {
            return false; // Invalid format
        }

        // Format the VAT number according to common EU formats
        if (strlen($vatNumber) === 9) {
            $formattedNumber = substr($vatNumber, 0, 3) . '.' . substr($vatNumber, 3, 3) . '.' . substr($vatNumber, 6, 3);
        } elseif (strlen($vatNumber) === 10) {
            $formattedNumber = substr($vatNumber, 0, 4) . '.' . substr($vatNumber, 4, 3) . '.' . substr($vatNumber, 7, 3);
        } else {
            $formattedNumber = wordwrap($vatNumber, 3, '.', true); // General formatting
        }

        return $countryCode . ' ' . $formattedNumber;
    }
}
