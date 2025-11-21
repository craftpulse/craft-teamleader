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
use craft\elements\Entry as EntryElement;

use craftpulse\teamleader\Teamleader;
use craftpulse\teamleader\auth\providers\TeamleaderFocus as TeamleaderFocusProvider;
use craftpulse\teamleader\elements\Company as TLCompany;
use craftpulse\teamleader\elements\Contact as TLContact;
use craftpulse\teamleader\elements\Deal as TLDeal;

use Illuminate\Support\Collection;
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
use yii\log\Logger;

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

    /**
     * @return string
     */
    public static function getOAuthProviderClass(): string
    {
        return TeamleaderFocusProvider::class;
    }

    /**
     * @return bool
     */
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
     * @var string|null
     */
    public ?string $dealId = null;

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
            $contactValues = $this->getFieldMappingValues($submission, $this->contactsFieldMapping, 'contacts');
            $companyValues = $this->getFieldMappingValues($submission, $this->companiesFieldMapping, 'companies');
            $dealsValues = $this->getFieldMappingValues($submission, $this->dealsFieldMapping, 'deals');


            if ($this->mapToCompanies && !($submission->unknownVATNumber)) {
                $companyPayload = $this->_prepPayload($companyValues, 'companies');
                $endpoint = 'companies.add';




                // First check if we already have a user with the primary email address attached.

                
                $filterPayload = [
                    'filter' => [
                        'vat_number' => Teamleader::$plugin->teamleaderConnector->formatVatNumber($companyValues['vat_number']),
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

                
                $compElement = new TLCompany();
                $compElement->title = $submission->companyName;
                $compElement->teamleaderId = $this->companyId;
                $compElement->vatNumber = $submission->vatNumber;
                if(Craft::$app->elements->saveElement($compElement)) {
 
                } else {
                    throw new \Exception("Couldn't save new Company: " . print_r($compElement->getErrors(), true));
                }


            }


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
                    $endpoint = 'contacts.update';
                    $contactPayload['id'] = $currentUser['id'];
                    $this->userId = $currentUser['id'];
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

                                
                $contactElement = new TLContact();
                $contactElement->firstName = $submission->firstName;
                $contactElement->lastName = $submission->lastName;
                $contactElement->teamleaderId = $this->userId;
                if(Craft::$app->elements->saveElement($contactElement)) {

                } else {
                    throw new \Exception("Couldn't save new Contact: " . print_r($contactElement->getErrors(), true));
                }
            }

            if ($this->mapToDeals && ($this->userId || $this->companyId)) {

                $options = [
                    'contact_person_id' => $this->userId ?? '',
                    'company_id' => $this->companyId ?? '',
                    'vat_unknown' => $submission->unknownVATNumber,
                ];


                $dealPayload = $this->_prepPayload($dealsValues, 'deals', $options);

                $response = $this->deliverPayload($submission, 'deals.create', $dealPayload);

                if ($response === false) {
                    return true;
                }

                $this->dealId = $response['data']['id'] ?? null;

                if (is_null($this->dealId)) {
                    Integration::error($this, Craft::t('formie', 'Missing return “id” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($dealsValues),
                    ]), true);

                    return false;
                }

                $dealElement = new TLDeal();
                $dealElement->title = $submission->teamleaderTitle;
                $dealElement->teamleaderId = $this->dealId;
                if(isset($compElement)) {
                $dealElement->companyId = $compElement->id;
                }
                $dealElement->contactId = $contactElement->id;
                if(Craft::$app->elements->saveElement($dealElement)) {

                } else {
                    throw new \Exception("Couldn't save new Deal: " . print_r($dealElement->getErrors(), true));
                }

                $offer = new EntryElement();
                $offer->siteId = $submission->siteId;
                $offer->typeId = 101; // HARDCODED FOR NOW
                $offer->sectionId = 44; // HARDCODED FOR NOW
                $offer->title = $submission->teamleaderTitle;
                $offer->setFieldValue('deal', [$dealElement->id]);
                if(Craft::$app->elements->saveElement($offer)) {

                } else {
                    throw new \Exception("Couldn't save new Offer: " . print_r($offer->getErrors(), true));
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
     * @throws \Throwable
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
                        'handle' => 'mobile_phone',
                        'name' => Craft::t('formie', 'Mobile number'),
                    ]),
                    new IntegrationField([
                        'handle' => 'phone',
                        'name' => Craft::t('formie', 'Phone number'),
                    ]),
                    // @TODO this should be an option if VAT is required or not
                    new IntegrationField([
                        'handle' => 'vat_number',
                        'name' => Craft::t('formie', 'VAT Number'),
                        'required' => false,
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
                        'handle' => 'remarks',
                        'name' => Craft::t('formie', 'Extra Information'),
                        'required' => false,
                    ]),
                ], $this->_getCustomFields($fields));
            }
        } catch (Exception $error) {
            Integration::apiError($this, $error);
        }

        return new IntegrationFormSettings($settings);
    }

    /**
     * @param string $context
     * @return array|null
     * @throws \Throwable
     */
    private function _fetchCustomFields(string $context): ?array {
        $options = [
            "filter" => [
                "context" => $context,
            ],
            "page" => [
                "size" => 100,
            ]
        ];

        $response = $this->request('POST', 'customFieldDefinitions.list', ['json' => $options]);

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

            $payload = $this->_consolidateCustomFields($payload);

            return $payload;
        }

        if ($context === 'deals') {
 
            $payload['lead'] = [
                'customer' => [
                    'type' => ($this->companyId) ? 'company' : 'contact',
                    'id' => $this->companyId ?: $this->userId,
                ],
            ];

            if(!$options['vat_unknown']) {
                $payload['lead']['contact_person_id'] = $this->userId ?: '';
            }

            $payload['responsible_user_id'] = '2259ab68-5394-08b0-a845-5b3c71fbae5f'; // Stephanie Le Clef
            $payload['source_id'] = '15596014-44b1-0c0d-9c50-590a1f76d4ea'; // via offertegenerator Team Masters

            if(isset($payload['estimated_value'])) {
                $amount = $payload['estimated_value'];
                $payload['estimated_value'] = [
                    'amount' => floatval($amount),
                    'currency' => 'EUR',
                ];
            }

            
            if(!isset($payload['title']) || !($payload['title']) || trim($payload['title']) == '') {
                $payload['title'] = $this->dealTitle;
            }

            $tbconcept = Collection::make($payload['4a7243f4-3df6-0f2e-a54d-b31b33e9a4ad'])->map(function (mixed $value, string $key) {
                $arr = Collection::make($value['teambuildingName'])->first();
                return $arr['title'];
            });

            $payload['4a7243f4-3df6-0f2e-a54d-b31b33e9a4ad'] = $tbconcept->toArray();

        }

        $payload = $this->_consolidateCustomFields($payload);

        return $payload;
    }

    private function _consolidateCustomFields(array $payload): ?array {
        $payloadCollected = Collection::make($payload);
        $customFields = $payloadCollected->filter(function (mixed $value, string $key) {
            return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $key) !== 1 ? false : true;
        });
        $nonCustomFields = $payloadCollected->filter(function (mixed $value, string $key) {
            return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $key) !== 1 ? true : false;
        });

        $fixedCustom = $customFields->map(function (mixed $value, string $key) {
            $arr = [];
            $arr[] = [
                'id' => $key,
                'value' => $value
            ];
            return $arr;
        })->values()->flatten(1);

        $outputPayload = $nonCustomFields->toArray();
        $outputPayload['custom_fields'] = $fixedCustom->toArray();

        return $outputPayload;
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
            return [
                'type' => 'primary',
                'address' => [
                    'line_1' => $payload['addressLine1'],
                    'postal_code' => $payload['postal_code'],
                    'city' => $payload['city'],
                    'country' => $payload['country'],
                ]
            ];
        } else {
            return null;
        }
    }

}
