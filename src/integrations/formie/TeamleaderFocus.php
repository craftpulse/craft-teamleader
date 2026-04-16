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
use craftpulse\teamleader\helpers\ClientTypeHelper;
use craftpulse\teamleader\helpers\CurrencyHelper;
use craftpulse\teamleader\helpers\VatHelper;

use Illuminate\Support\Collection;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

use verbb\formie\base\Integration;
use verbb\auth\base\OAuthProviderInterface;
use verbb\auth\models\Token;
use verbb\formie\base\Crm;
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
 */
class TeamleaderFocus extends Crm implements OAuthProviderInterface
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Teamleader Focus');
    }

    /**
     * Returns the OAuth provider class for Teamleader Focus authentication.
     *
     * @return string
     */
    public static function getOAuthProviderClass(): string
    {
        return TeamleaderFocusProvider::class;
    }

    /**
     * Indicates whether this integration supports OAuth connection.
     *
     * @return bool
     */
    public static function supportsOAuthConnection(): bool
    {
        return true;
    }

    // Properties
    // =========================================================================

    /**
     * @var bool Whether to link the contact to the company.
     */
    public bool $linkToCompany = false;

    /**
     * @var bool Whether to map form submissions to Teamleader Focus contacts.
     */
    public bool $mapToContacts = false;

    /**
     * @var bool Whether to map form submissions to Teamleader Focus companies.
     */
    public bool $mapToCompanies = false;

    /**
     * @var bool Whether to map form submissions to Teamleader Focus deals.
     */
    public bool $mapToDeals = false;

    /**
     * @var string|null The Teamleader company ID after creation or lookup.
     */
    public ?string $companyId = null;

    /**
     * @var string|null The Teamleader deal ID after creation.
     */
    public ?string $dealId = null;

    /**
     * @var string|null The Teamleader user/contact ID after creation or lookup.
     */
    public ?string $userId = null;

    /**
     * @var string The default currency for deals when not mapped from a form field.
     */
    public string $defaultCurrency = 'EUR';

    /**
     * @var string|null The title to use for created deals.
     */
    public ?string $dealTitle = null;

    /**
     * @var array|null Field mapping configuration for contacts.
     */
    public ?array $contactsFieldMapping = null;

    /**
     * @var array|null Field mapping configuration for companies.
     */
    public ?array $companiesFieldMapping = null;

    /**
     * @var array|null Field mapping configuration for deals.
     */
    public ?array $dealsFieldMapping = null;

    /**
     * @var bool Whether to append new tags to existing contact tags instead of replacing them.
     *           When enabled, makes an additional API call to fetch existing tags before update.
     */
    public bool $appendContactTags = false;

    /**
     * @var bool Whether to append new tags to existing company tags instead of replacing them.
     *           When enabled, makes an additional API call to fetch existing tags before update.
     */
    public bool $appendCompanyTags = false;

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

    /**
     * @param Submission $submission
     * @return bool
     * @throws IntegrationException
     */
    public function sendPayload(Submission $submission): bool
    {
        try {
            $isCompanyRequest = ClientTypeHelper::isCompanyRequest($submission);

            $contactValues = $this->getFieldMappingValues($submission, $this->contactsFieldMapping, 'contacts');
            $companyValues = $isCompanyRequest
                ? $this->getFieldMappingValues($submission, $this->companiesFieldMapping, 'companies')
                : [];
            $dealsValues = $this->getFieldMappingValues($submission, $this->dealsFieldMapping, 'deals');

            // Make sure we take the tags from Formie, but unset them, so we don't override them by mistake.
            $tags = $this->_normalizeTagsValue($contactValues['tags'] ?? []);
            unset($contactValues['tags']);

            $companyTags = $this->_normalizeTagsValue($companyValues['tags'] ?? []);
            unset($companyValues['tags']);

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
                if (!empty($currentUser['id'])) {
                    $endpoint = 'contacts.update';
                    $contactPayload['id'] = $currentUser['id'];
                    $this->userId = $currentUser['id'];
                }

                // Handle tags based on endpoint and appendContactTags setting
                if (!empty($tags)) {
                    if ($endpoint === 'contacts.add') {
                        // For new contacts, always include tags in the payload, they're new tags too.
                        $contactPayload['tags'] = $tags;
                    } elseif (!$this->appendContactTags) {
                        // For existing contacts with appendContactTags=false, include in payload to OVERWRITE all tags
                        // We will use another endpoint (contacts.tag) if it's contact.update - for performance reasons (2 API calls over 3)
                        $contactPayload['tags'] = $tags;
                    }
                }

                $response = $this->deliverPayload($submission, $endpoint, $contactPayload);

                if ($response === false) {
                    return true;
                }

                if ($endpoint === 'contacts.add') {
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

                // This ADDS tags without removing existing ones - no need to fetch existing tags first
                if ($endpoint === 'contacts.update' && $this->appendContactTags && !empty($tags)) {
                    $tagPayload = [
                        'id' => $this->userId,
                        'tags' => $tags,
                    ];

                    $this->deliverPayload($submission, 'contacts.tag', $tagPayload);
                }
            }

            if ($this->mapToCompanies && $isCompanyRequest && !empty($companyValues)) {
                $companyPayload = $this->_prepPayload($companyValues, 'companies');
                $endpoint = 'companies.add';

                // First check if we already have a user with the primary email address attached.
                // @TODO - we can make this a lot fancier to update stuff - need to check some Craft CMS templates to make it prettier these settings

                // only do this if we have an actual VAT number - to save an API call.
                // create an enum for types to make mapToCompanies or mapToContacts dynamically?
                if(isset($companyPayload['vat_number'])) {
                    $filterPayload = [
                        'filter' => [
                            'vat_number' => VatHelper::formatVatNumber($companyValues['vat_number']),
                        ],
                        // Include the custom fields to preserve them when updating.
                        'includes' => ['custom_fields'],
                    ];

                    $response = $this->deliverPayload($submission, 'companies.list', $filterPayload);
                    $currentCompany = Collection::make($response['data'])->first();

                    // Make sure we send a "contacts.update" request if we have an actual response id.
                    if (!empty($currentCompany['id'])) {
                        $endpoint = 'companies.update';
                        $companyPayload['id'] = $currentCompany['id'];
                        $this->companyId = $currentCompany['id'];

                        // See if we need custom fields
                        $existingCustomFields = $currentCompany['custom_fields'] ?? [];

                        if (!empty($existingCustomFields)) {
                            // Get the custom field ids from the form payload
                            $formCustomFieldIds = array_column($companyPayload['custom_fields'], 'id');

                            foreach ($existingCustomFields as $companyCustomField) {
                                // The API returns the custom field id in the definition object
                                $companyCustomFieldId = $companyCustomField['definition']['id'];

                                if (!in_array($companyCustomFieldId, $formCustomFieldIds, true)) {
                                    $companyPayload['custom_fields'][] = [
                                        'id' => $companyCustomFieldId,
                                        'value' => $companyCustomField['value'],
                                    ];
                                }
                            }
                        }
                    }
                }

                // Handle tags based on endpoint and appendCompanyTags setting
                if (!empty($companyTags)) {
                    if ($endpoint === 'companies.add') {
                        // For new companies, always include tags in the payload, they're new tags too.
                        $companyPayload['tags'] = $companyTags;
                    } elseif (!$this->appendCompanyTags) {
                        // For existing contacts with appendCompanyTags=false, include in payload to OVERWRITE all tags
                        // We will use another endpoint (companies.tag) if it's companies.update - for performance reasons (2 API calls over 3)
                        $companyPayload['tags'] = $companyTags;
                    }
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

                // This ADDS tags without removing existing ones - no need to fetch existing tags first
                if ($endpoint === 'companies.update' && $this->appendCompanyTags && !empty($companyTags)) {
                    $tagPayload = [
                        'id' => $this->companyId,
                        'tags' => $companyTags,
                    ];

                    $this->deliverPayload($submission, 'companies.tag', $tagPayload);
                }
            }

            // Link contact to company if enabled and both IDs exist
            if ($this->linkToCompany && $this->userId && $this->companyId && $isCompanyRequest) {
                $linkPayload = [
                    'id' => $this->userId,
                    'company_id' => $this->companyId,
                ];

                $this->deliverPayload($submission, 'contacts.linkToCompany', $linkPayload);
            }

            if ($this->mapToDeals && ($this->userId || $this->companyId)) {
                $dealPayload = $this->_prepPayload($dealsValues, 'deals');

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
                    new IntegrationField([
                        'handle' => 'fax',
                        'name' => Craft::t('formie', 'Fax'),
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
                        'handle' => 'remarks',
                        'name' => Craft::t('formie', 'Remarks (Markdown supported)'),
                    ]),
                    new IntegrationField([
                        'handle' => 'tags',
                        'name' => Craft::t('formie', 'Tags'),
                    ]),
                    new IntegrationField([
                        'handle' => 'marketing_mails_consent',
                        'name' => Craft::t('formie', 'Marketing Mails Consent'),
                        'type' => 'boolean',
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
                        'required' => false,
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
                        'handle' => 'phone',
                        'name' => Craft::t('formie', 'Phone number'),
                    ]),
                    new IntegrationField([
                        'handle' => 'fax',
                        'name' => Craft::t('formie', 'Fax'),
                    ]),
                    new IntegrationField([
                        'handle' => 'vat_number',
                        'name' => Craft::t('formie', 'VAT Number'),
                        'required' => true,
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
                        'handle' => 'remarks',
                        'name' => Craft::t('formie', 'Remarks (Markdown supported)'),
                    ]),
                    new IntegrationField([
                        'handle' => 'tags',
                        'name' => Craft::t('formie', 'Tags'),
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

                $settings['deals'] = array_merge([
                    new IntegrationField([
                        'handle' => 'title',
                        'name' => Craft::t('formie', 'Deal Title'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'estimated_value',
                        'name' => Craft::t('formie', 'Deal Value'),
                        'required' => false,
                    ]),
                    new IntegrationField([
                        'handle' => 'summary',
                        'name' => Craft::t('formie', 'Summary'),
                        'required' => false,
                    ]),
                    new IntegrationField([
                        'handle' => 'currency',
                        'name' => Craft::t('formie', 'Currency'),
                    ]),
                ], $this->_getCustomFields($fields));
            }
        } catch (Exception $error) {
            Integration::apiError($this, $error);
        }

        return new IntegrationFormSettings($settings);
    }

    /**
     * Fetch currency options from Teamleader Focus API.
     *
     * @return array
     */
    public function getCurrencyOptions(): array
    {
        try {
            $response = $this->request('POST', 'currencies.exchangeRates', [
                'json' => ['base' => 'EUR']
            ]);

            return CurrencyHelper::formatCurrencyOptions($response['data'] ?? []);
        } catch (Throwable $e) {
            return CurrencyHelper::getDefaultCurrencies();
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * Normalize tags from Formie: process arrays and single comma-separated strings.
     *
     * @param mixed $value
     * @return array<int, string>
     */
    private function _normalizeTagsValue(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return array_values(array_filter($value, fn(mixed $item) => $item !== null && $item !== ''));
        }

        if (is_string($value)) {
            $parts = array_map(trim(...), preg_split('/[,;|]/', $value));

            return array_values(array_filter($parts, fn(string $s) => $s !== ''));
        }

        return [];
    }
    
    /**
     * @param string $context
     * @return array
     */
    private function _fetchCustomFields(string $context): array
    {
        $options = [
            'filter' => [
                'context' => $context,
            ],
            // @TODO create setting.
            'page' => [
                'size' => 100,
            ]
        ];

        $response = $this->request('POST', 'customFieldDefinitions.list', ['json' => $options]);
        $customFields = $response['data'];

        if (empty($customFields)) {
            return [];
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
                'handle' => 'custom:' . $field['id'],
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
        $fieldTypes = [
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
     * @return array
     */
    private function _prepPayload(array $fields, string $context): array
    {
        $payload = $fields;

        $customFields = $this->_prepCustomFields($payload);

        if (!empty($customFields)) {
            $payload['custom_fields'] = $customFields;
        }

        if ($context === 'contacts') {
            if (isset($payload['mobile_phone'])) {
                $payload['telephones'][] = [
                    'type' => 'mobile',
                    'number' => $payload['mobile_phone'],
                ];
                unset($payload['mobile_phone']);
            }
        }

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

            if (isset($payload['fax'])) {
                $payload['telephones'][] = [
                    'type' => 'fax',
                    'number' => $payload['fax'],
                ];
                unset($payload['fax']);
            }

            $addressSource = $payload['address'] ?? (isset($payload['addressLine1']) ? $payload : null);

            if ($addressSource && $address = $this->_generateAddressObject($addressSource)) {
                    $payload['addresses'][] = $address;
                    unset($payload['addressLine1'], $payload['postal_code'], $payload['city'], $payload['country']);
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
            ];

            if ($this->companyId && $this->userId) {
                $payload['lead']['contact_person_id'] = $this->userId;
            }

            if (isset($payload['estimated_value'])) {
                $currency = $payload['currency'] ?? $this->defaultCurrency;
                unset($payload['currency']);

                $payload['estimated_value'] = [
                    'amount' => $payload['estimated_value'],
                    'currency' => $currency,
                ];
            }

            $payload['title'] = $this->dealTitle;
        }

        return $payload;
    }

    /**
     * Extract custom fields from the payload and transform them to Teamleader API format
     *
     * Teamleader expects custom fields as:
     * "custom_fields": [
     *   { "id": "uuid", "value": "value" },
     *   ...
     * ]
     *
     * @param array $fields Reference to the fields array (will be modified to remove custom fields)
     * @return array The custom_fields array in Teamleader format
     */
    private function _prepCustomFields(array &$fields): array
    {
        $customFields = [];

        foreach ($fields as $key => $value) {
            if (str_starts_with($key, 'custom:')) {
                unset($fields[$key]);

                if ($value === null || $value === '') {
                    continue;
                }

                $customFields[] = [
                    'id' => str_replace('custom:', '', $key),
                    'value' => $value,
                ];
            }
        }

        return $customFields;
    }

    /**
     * @param array $fields
     * @return array|null
     */
    private function _generateAddressObject(array $fields): ?array {
        // All fields need to be there, otherwise we won't generate it.
        $required_fields = ['addressLine1', 'postal_code', 'city', 'country'];
        $missing_values = array_diff($required_fields, array_keys($fields));

        if ($missing_values) {
            return null;
        }

        $countriesISOList = Collection::make(Craft::$app->getAddresses()->getCountryList());
        $upperValue = strtoupper($fields['country']);

        // If the value is already a valid ISO code, use it directly. Otherwise, look it up by label.
        $country = $countriesISOList->has($upperValue)
            ? $upperValue
            : $countriesISOList->flip()->get($fields['country']);

        // If country is not found, return error.
        if (!$country) {
            Integration::error($this, Craft::t('formie', 'Missing country code {country}. Sent payload {payload}', [ 'country' => $fields['country'], 'payload' => Json::encode($fields) ]), true);

            return null;
        }

        return [
            'type' => 'primary',
            'address' => [
                'line_1' => $fields['addressLine1'],
                'postal_code' => $fields['postal_code'],
                'city' => $fields['city'],
                'country' => $country,
            ]
        ];
    }
}
