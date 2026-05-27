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
use craft\helpers\Json;

use craftpulse\teamleader\auth\clients\TeamleaderFocus as TeamleaderFocusClient;
use craftpulse\teamleader\auth\providers\TeamleaderFocus as TeamleaderFocusProvider;
use craftpulse\teamleader\helpers\ClientTypeHelper;
use craftpulse\teamleader\helpers\CurrencyHelper;
use craftpulse\teamleader\helpers\VatHelper;

use Error;

use Illuminate\Support\Collection;

use Throwable;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

use verbb\auth\base\OAuthProviderInterface;
use verbb\auth\models\Token;
use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use yii\base\Exception;

/**
 * Class TeamleaderFocus
 *
 * Formie CRM integration for Teamleader Focus. Handles contact/company/deal
 * creation and updates, with optional contact-to-company linking.
 *
 * @author CraftPulse
 * @since  5.0.0
 */
class TeamleaderFocus extends Crm implements OAuthProviderInterface
{
    // Const Properties
    // =========================================================================

    /**
     * @var int Cache TTL for currency options (24h). Currency lists rarely change.
     */
    private const CURRENCY_CACHE_TTL = 86400;

    /**
     * @var string The update strategy for custom fields.
     */

    public const CUSTOM_FIELDS_UPDATE_STRATEGY_PARTIAL = 'partial';

    // Public Properties
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
     * @var bool Whether to update custom fields partially for contacts.
     */
    public bool $partialUpdateCustomFieldsContacts = true;

    /**
     * @var bool Whether to update custom fields partially for companies.
     */
    public bool $partialUpdateCustomFieldsCompanies = true;

    /**
     * @var string The default currency for deals when not mapped from a form field.
     */
    public string $defaultCurrency = 'EUR';

    /**
     * @var string|null The title to use for created deals when no form field is mapped.
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


    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @author CraftPulse
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Teamleader Focus');
    }

    /**
     * Returns the OAuth provider class for Teamleader Focus authentication.
     *
     * @author CraftPulse
     */
    public static function getOAuthProviderClass(): string
    {
        return TeamleaderFocusProvider::class;
    }

    /**
     * Indicates whether this integration supports OAuth connection.
     *
     * @author CraftPulse
     */
    public static function supportsOAuthConnection(): bool
    {
        return true;
    }

    // Public Methods
    // =========================================================================

    /**
     * @author CraftPulse
     */
    public function getIconUrl(): string
    {
        return Craft::$app->getAssetManager()->getPublishedUrl('@craftpulse/teamleader/icon-mask.svg', true);
    }

    /**
     * @author CraftPulse
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'This is a Teamleader Focus lead creation integration.');
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     *
     * @author CraftPulse
     */
    public function getSettingsHtml(): string
    {
        $settings = $this->getSettingsHtmlVariables();

        return Craft::$app->getView()->renderTemplate('teamleader-focus/integrations/formie/_plugin-settings', $settings);
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     *
     * @author CraftPulse
     */
    public function getFormSettingsHtml($form): string
    {
        $formSettings = $this->getFormSettingsHtmlVariables($form);

        return Craft::$app->getView()->renderTemplate('teamleader-focus/integrations/formie/_form-settings', $formSettings);
    }

    /**
     * @author CraftPulse
     */
    public function getApiDomain(): string
    {
        return TeamleaderFocusClient::API_BASE_URL;
    }

    /**
     * @author CraftPulse
     */
    public function getBaseApiUrl(?Token $token): ?string
    {
        return TeamleaderFocusClient::API_BASE_URL;
    }

    /**
     * Build the config array passed to the OAuth provider constructor.
     *
     * clientId / clientSecret / redirectUri are also set by parent
     * (verbb/auth OAuthProviderTrait), but we re-set them defensively:
     * this plugin uses a custom OAuth client (TeamleaderFocusClient) and
     * the OAuth path is hard to debug when something silently drops a key.
     * The duplication costs nothing at runtime and survives upstream drift.
     *
     * @author CraftPulse
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
     * @inheritdoc
     *
     * @author CraftPulse
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [
            ['dealTitle'],
            'required',
            'when' => fn($model) => $model->mapToDeals,
        ];

        $rules[] = [['defaultCurrency'], 'string', 'length' => 3];

        return $rules;
    }

    /**
     * @throws IntegrationException
     *
     * @author CraftPulse
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

            // Pull tags out of the value array so the main payload doesn't carry them.
            $tags = $this->_normalizeTagsValue($contactValues['tags'] ?? []);
            unset($contactValues['tags']);

            $companyTags = $this->_normalizeTagsValue($companyValues['tags'] ?? []);
            unset($companyValues['tags']);

            $userId = null;
            $companyId = null;

            if ($this->mapToContacts) {
                $contactPayload = $this->_prepPayload($contactValues, 'contacts');
                $endpoint = 'contacts.add';

                // Look up existing contact by primary email.
                $response = $this->deliverPayload($submission, 'contacts.list', [
                    'filter' => [
                        'email' => [
                            'type' => 'primary',
                            'email' => $contactValues['email'] ?? null,
                        ],
                    ],
                ]);
                $currentUser = Collection::make($response['data'] ?? [])->first();

                if (!empty($currentUser['id'])) {
                    $endpoint = 'contacts.update';
                    $contactPayload['id'] = $currentUser['id'];
                    if ($this->partialUpdateCustomFieldsContacts) {
                        $contactPayload['custom_fields_update_strategy'] = self::CUSTOM_FIELDS_UPDATE_STRATEGY_PARTIAL;
                    }
                    $userId = $currentUser['id'];
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
                    $userId = $response['data']['id'] ?? null;

                    if ($userId === null) {
                        Integration::error($this, Craft::t('formie', 'Missing return “id” {response}. Sent payload {payload}', [
                            'response' => Json::encode($response),
                            'payload' => Json::encode($contactValues),
                        ]), true);

                        return false;
                    }
                } elseif (!empty($response)) {
                    Integration::error($this, Craft::t('formie', 'Invalid response {response} Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($contactValues),
                    ]), true);

                    return false;
                }

                // Append-mode: push tags via the dedicated endpoint so existing
                // tags are preserved (no fetch-merge needed).
                if ($endpoint === 'contacts.update' && $this->appendContactTags && !empty($tags)) {
                    $this->deliverPayload($submission, 'contacts.tag', [
                        'id' => $userId,
                        'tags' => $tags,
                    ]);
                }
            }

            if ($this->mapToCompanies && $isCompanyRequest && !empty($companyValues)) {
                $companyPayload = $this->_prepPayload($companyValues, 'companies');
                $endpoint = 'companies.add';

                // First check if we already have a user with the primary email address attached.
                // @TODO - we can make this a lot fancier to update stuff - need to check some Craft CMS templates to make it prettier these settings

                // only do this if we have an actual VAT number - to save an API call.
                // create an enum for types to make mapToCompanies or mapToContacts dynamically?
                if (isset($companyPayload['vat_number'])) {
                    $response = $this->deliverPayload($submission, 'companies.list', [
                        'filter' => [
                            'vat_number' => VatHelper::formatVatNumber($companyValues['vat_number']),
                        ],
                    ]);
                    $currentCompany = Collection::make($response['data'] ?? [])->first();

                    if (!empty($currentCompany['id'])) {
                        $endpoint = 'companies.update';
                        $companyPayload['id'] = $currentCompany['id'];
                        if ($this->partialUpdateCustomFieldsCompanies) {
                            $companyPayload['custom_fields_update_strategy'] = self::CUSTOM_FIELDS_UPDATE_STRATEGY_PARTIAL;
                        }
                        $companyId = $currentCompany['id'];
                    }
                }

                // Handle tags based on endpoint and appendCompanyTags setting
                if (!empty($companyTags)) {
                    if ($endpoint === 'companies.add') {
                        // For new companies, always include tags in the payload, they're new tags too.
                        $companyPayload['tags'] = $companyTags;
                    } elseif (!$this->appendCompanyTags) {
                        // For existing companies with appendCompanyTags=false, include in payload to OVERWRITE all tags
                        // We will use another endpoint (companies.tag) if it's companies.update - for performance reasons (2 API calls over 3)
                        $companyPayload['tags'] = $companyTags;
                    }
                }

                $response = $this->deliverPayload($submission, $endpoint, $companyPayload);

                if ($response === false) {
                    return true;
                }

                if ($endpoint === 'companies.add') {
                    $companyId = $response['data']['id'] ?? null;

                    if ($companyId === null) {
                        Integration::error($this, Craft::t('formie', 'Missing return “id” {response}. Sent payload {payload}', [
                            'response' => Json::encode($response),
                            'payload' => Json::encode($companyValues),
                        ]), true);

                        return false;
                    }
                } elseif (!empty($response)) {
                    Integration::error($this, Craft::t('formie', 'Invalid response {response} Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($companyValues),
                    ]), true);

                    return false;
                }

                if ($endpoint === 'companies.update' && $this->appendCompanyTags && !empty($companyTags)) {
                    $this->deliverPayload($submission, 'companies.tag', [
                        'id' => $companyId,
                        'tags' => $companyTags,
                    ]);
                }
            }

            // Link contact to company if enabled and both IDs exist
            if ($this->linkToCompany && $userId && $companyId && $isCompanyRequest) {
                // Get company info with related contacts
                $response = $this->deliverPayload($submission, 'companies.info', [
                    'id' => $companyId,
                    'includes' => ['related_contacts'],
                ]);

                // Check related_contacts items for the contact id on id
                $relatedContacts = $response['data']['related_contacts'] ?? [];
                $alreadyLinked = false;
                foreach ($relatedContacts as $relatedContact) {
                    if ($relatedContact['id'] === $userId) {
                        $alreadyLinked = true;
                        break;
                    }
                }

                if (!$alreadyLinked) {
                    $linkPayload = [
                        'id' => $userId,
                        'company_id' => $companyId,
                    ];

                    $this->deliverPayload($submission, 'contacts.linkToCompany', $linkPayload);
                }
            }

            if ($this->mapToDeals && ($userId || $companyId)) {
                $dealPayload = $this->_prepPayload($dealsValues, 'deals', $userId, $companyId);

                $response = $this->deliverPayload($submission, 'deals.create', $dealPayload);

                if ($response === false) {
                    return true;
                }

                $dealId = $response['data']['id'] ?? null;

                if ($dealId === null) {
                    Integration::error($this, Craft::t('formie', 'Missing return “id” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($dealsValues),
                    ]), true);

                    return false;
                }
            }
        } catch (Exception | Error $error) {
            Integration::apiError($this, $error);

            return false;
        }

        return true;
    }

    /**
     * @throws IntegrationException
     *
     * @author CraftPulse
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
        } catch (Exception | Error $error) {
            Integration::apiError($this, $error);
        }

        return new IntegrationFormSettings($settings);
    }

    /**
     * Fetch currency options from Teamleader Focus API, cached for 24h on success.
     * On API failure we return defaults without caching, so the next page load retries
     * the API rather than pinning the fallback for the full TTL.
     *
     * @author CraftPulse
     */
    public function getCurrencyOptions(): array
    {
        $cache = Craft::$app->getCache();
        $cacheKey = 'teamleader-focus:currencies:' . ($this->id ?? 'unsaved');

        $cached = $cache->get($cacheKey);

        if ($cached !== false) {
            return $cached;
        }

        try {
            $response = $this->request('POST', 'currencies.exchangeRates', [
                'json' => ['base' => 'EUR'],
            ]);

            $options = CurrencyHelper::formatCurrencyOptions($response['data'] ?? []);
            $cache->set($cacheKey, $options, self::CURRENCY_CACHE_TTL);

            return $options;
        } catch (Throwable $e) {
            return CurrencyHelper::getDefaultCurrencies();
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * Normalize tags from Formie: process arrays and single comma-separated strings.
     *
     * @return array<int, string>
     *
     * @author CraftPulse
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
     * Fetch custom field definitions for the given context (contact/company/sale).
     *
     * @author CraftPulse
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
            ],
        ];

        $response = $this->request('POST', 'customFieldDefinitions.list', ['json' => $options]);

        $customFields = $response['data'] ?? [];

        if (empty($customFields)) {
            return [];
        }

        return Collection::make($customFields)
            ->filter(fn($field) => $field['context'] === $context)
            ->toArray();
    }

    /**
     * Transform raw Teamleader custom field definitions into IntegrationField objects.
     *
     * @author CraftPulse
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
     * Map a Teamleader custom field type to Formie's IntegrationField type.
     *
     * @author CraftPulse
     */
    private function _convertFieldType(string $fieldType): string
    {
        return match ($fieldType) {
            'multi_select' => IntegrationField::TYPE_ARRAY,
            'date' => IntegrationField::TYPE_DATE,
            'money' => IntegrationField::TYPE_FLOAT,
            'auto_increment', 'integer', 'number' => IntegrationField::TYPE_NUMBER,
            'boolean' => IntegrationField::TYPE_BOOLEAN,
            'telephone' => IntegrationField::TYPE_PHONE,
            default => IntegrationField::TYPE_STRING,
        };
    }

    /**
     * Transform field mapping values into a Teamleader API payload for the given context.
     *
     * For the 'deals' context, $userId and $companyId are needed to wire up the
     * lead's customer reference and optional contact_person_id.
     *
     * @author CraftPulse
     */
    private function _prepPayload(array $fields, string $context, ?string $userId = null, ?string $companyId = null): array
    {
        $payload = $fields;
        $customFields = $this->_prepCustomFields($payload);

        if (!empty($customFields)) {
            $payload['custom_fields'] = $customFields;
        }

        if ($context === 'contacts' && isset($payload['mobile_phone'])) {
            $payload['telephones'][] = [
                'type' => 'mobile',
                'number' => $payload['mobile_phone'],
            ];
            unset($payload['mobile_phone']);
        }

        if (in_array($context, ['contacts', 'companies'], true)) {
            if (isset($payload['email'])) {
                $payload['emails'][] = [
                    'type' => 'primary',
                    'email' => $payload['email'],
                ];
                unset($payload['email']);
            }

            if (isset($payload['phone'])) {
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

            if (isset($payload['company_name'])) {
                $payload['name'] = $payload['company_name'];
                unset($payload['company_name']);
            }

            return $payload;
        }

        if ($context === 'deals') {
            $payload['lead'] = [
                'customer' => [
                    'type' => $companyId ? 'company' : 'contact',
                    'id' => $companyId ?: $userId,
                ],
            ];

            if ($companyId && $userId) {
                $payload['lead']['contact_person_id'] = $userId;
            }

            if (isset($payload['estimated_value'])) {
                $currency = $payload['currency'] ?? $this->defaultCurrency;
                unset($payload['currency']);

                $payload['estimated_value'] = [
                    'amount' => $payload['estimated_value'],
                    'currency' => $currency,
                ];
            }

            // Prefer the mapped form value when it has content; fall back to the static
            // setting. !empty() check catches both unset and empty-string mapped values —
            // an empty title would be rejected by the Teamleader API.
            $payload['title'] = !empty($payload['title']) ? $payload['title'] : $this->dealTitle;
        }

        return $payload;
    }

    /**
     * Extract custom fields from the payload and transform them to Teamleader API format.
     *
     * Teamleader expects custom fields as:
     *   "custom_fields": [
     *     { "id": "uuid", "value": "value" },
     *     ...
     *   ]
     *
     * @param  array $fields Reference to the fields array (will be modified to remove custom fields)
     * @return array The custom_fields array in Teamleader format
     *
     * @author CraftPulse
     */
    private function _prepCustomFields(array &$fields): array
    {
        $customFields = [];

        foreach ($fields as $key => $value) {
            if (!str_starts_with($key, 'custom:')) {
                continue;
            }

            unset($fields[$key]);

            if ($value === null || $value === '') {
                continue;
            }

            $customFields[] = [
                'id' => str_replace('custom:', '', $key),
                'value' => $value,
            ];
        }

        return $customFields;
    }

    /**
     * Build a Teamleader address object from address fields. Returns null when
     * required fields are missing or the country can't be resolved to an ISO code.
     *
     * @author CraftPulse
     */
    private function _generateAddressObject(array $fields): ?array
    {
        $requiredFields = ['addressLine1', 'postal_code', 'city', 'country'];
        $missingValues = array_diff($requiredFields, array_keys($fields));

        if ($missingValues) {
            return null;
        }

        $countriesISOList = Collection::make(Craft::$app->getAddresses()->getCountryList());
        $upperValue = strtoupper($fields['country']);

        // Accept ISO codes directly; otherwise look up by label.
        $country = $countriesISOList->has($upperValue)
            ? $upperValue
            : $countriesISOList->flip()->get($fields['country']);

        if (!$country) {
            Integration::error($this, Craft::t('formie', 'Missing country code {country}. Sent payload {payload}', [
                'country' => $fields['country'],
                'payload' => Json::encode($fields),
            ]), true);

            return null;
        }

        return [
            'type' => 'primary',
            'address' => [
                'line_1' => $fields['addressLine1'],
                'postal_code' => $fields['postal_code'],
                'city' => $fields['city'],
                'country' => $country,
            ],
        ];
    }
}
