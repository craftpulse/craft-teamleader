<?php

namespace craftpulse\teamleader\elements;

use Craft;
use craft\base\Element;
use craft\elements\Address;
use craft\elements\db\AddressQuery;
use craft\elements\ElementCollection;
use craft\elements\User;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\ElementQueryInterface;
use craft\fieldlayoutelements\TextField;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\web\CpScreenResponseBehavior;
use craftpulse\teamleader\elements\actions\AssignCompanies;
use craftpulse\teamleader\elements\conditions\ContactCondition;
use craftpulse\teamleader\elements\db\ContactQuery;
use craftpulse\teamleader\fieldlayoutelements\ContactSidebarAction;
use craftpulse\teamleader\records\CompanyRecord;
use craftpulse\teamleader\records\ContactCompanyRecord;
use craftpulse\teamleader\records\ContactRecord;
use craftpulse\teamleader\services\ServicesTrait;
use craftpulse\teamleader\Teamleader;
use Illuminate\Support\Collection;
use yii\base\ExitException;
use yii\base\InvalidConfigException;
use yii\db\Exception;
use yii\web\Response;

/**
 * Contact element type
 */
class Contact extends Element
{
    // Constant Properties
    // =========================================================================
    public ?bool $marketingMailsConsent = false;
    public ?string $firstName = '';
    public ?string $language = null;
    public ?string $lastName = '';
    public ?string $salutation = null;
    public array|string $emails = [];
    public array|string $telephones = [];
    public ?int $teamleaderId = null;
    public array|Collection|string $companies = [];

    private ?FieldLayout $fieldLayout = null;
    private array|Collection $_companyContacts = [];
    private array|Collection $_companies = [];

    // Public Static Methods
    // =========================================================================
    /**
     * @return string
     */
    public static function displayName(): string
    {
        return Craft::t('teamleader-focus', 'Contacts');
    }

    /**
     * @return string
     */
    public static function lowerDisplayName(): string
    {
        return Craft::t('teamleader-focus', 'contact');
    }

    /**
     * @return string
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('teamleader-focus', 'Contacts');
    }

    /**
     * @return string
     */
    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('teamleader-focus', 'contacts');
    }

    /**
     * @return string|null
     */
    public static function refHandle(): ?string
    {
        return 'contact';
    }

    /**
     * @return bool
     */
    public static function trackChanges(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public static function hasTitles(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public static function hasUris(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public static function isLocalized(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public static function hasStatuses(): bool
    {
        return true;
    }

    /**
     * @return ElementQueryInterface
     * @throws InvalidConfigException
     */
    public static function find(): ElementQueryInterface
    {
        return Craft::createObject(ContactQuery::class, [static::class]);
    }

    /**
     * @return ElementConditionInterface
     * @throws InvalidConfigException
     */
    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(ContactCondition::class, [static::class]);
    }

    // Protected Static Methods
    // =========================================================================
    /**
     * @param string $context
     * @return array[]
     */
    protected static function defineSources(string $context): array
    {
        return [
            [
                'key' => '*',
                'label' => Craft::t('teamleader-focus', 'All contacts'),
            ],
        ];
    }

    /**
     * @param string $source
     * @return array
     */
    protected static function defineActions(string $source): array
    {
        return [
            AssignCompanies::class
        ];
    }

    /**
     * @return bool
     */
    protected static function includeSetStatusAction(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            [
                'label' => Craft::t('app', 'Date Created'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'ID'),
                'orderBy' => 'elements.id',
                'attribute' => 'id',
            ],
            // ...
        ];
    }

    /**
     * @return array[]
     */
    protected static function defineTableAttributes(): array
    {
        return [
            'slug' => ['label' => Craft::t('app', 'Slug')],
            'id' => ['label' => Craft::t('app', 'ID')],
            'vatNumber' => ['label' => Craft::t('teamleader-focus', 'VAT Number')],
            'uid' => ['label' => Craft::t('app', 'UID')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
            // ...
        ];
    }

    /**
     * @param string $source
     * @return string[]
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'dateCreated',
            // ...
        ];
    }

    // Protected Methods
    // =========================================================================
    /**
     * @return array
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [[
            'emails',
            'firstName',
            'language',
            'lastName',
            'marketingMailsConsent',
            'salutation',
            'telephones',
            'companies',
        ], 'safe'];

        return $rules;
    }

    /**
     * Returns element metadata that should be shown within the editor sidebar.
     *
     * @return array The data, with keys representing the labels. The values can either be strings or callables.
     * If a value is `false`, it will be omitted.
     * @since 3.7.0
     */
    protected function metadata(): array
    {
        return [];
    }

    protected function cpEditUrl(): ?string
    {
        return sprintf('teamleader-focus/contacts/%s', $this->getCanonicalId());
    }

    /**
     * @return array
     */
    protected function previewTargets(): array
    {
        $previewTargets = [];
        $url = $this->getUrl();
        if ($url) {
            $previewTargets[] = [
                'label' => Craft::t('app', 'Primary {type} page', [
                    'type' => self::lowerDisplayName(),
                ]),
                'url' => $url,
            ];
        }
        return $previewTargets;
    }

    /**
     * @return array|string|null
     */
    protected function route(): array|string|null
    {
        // Define how contacts should be routed when their URLs are requested
        return [
            'templates/render',
            [
                'template' => 'site/template/path',
                'variables' => ['contact' => $this],
            ]
        ];
    }

    // Public Methods
    // =========================================================================
    /**
     * @inheritdoc
     * @throws Exception|ExitException
     */
    public function afterSave(bool $isNew): void
    {
        if (!$this->propagating) {
            if ($isNew) {
                $contactRecord = new ContactRecord();
                $contactRecord->id = $this->id;
            } else {
                $contactRecord = ContactRecord::findOne($this->id);
            }

            $contactRecord->fieldLayoutId = $this->fieldLayout->id;

            //fields
           $contactRecord->emails = $this->emails;
           $contactRecord->marketingMailsConsent = $this->marketingMailsConsent;
           $contactRecord->firstName = $this->firstName;
           $contactRecord->lastName = $this->lastName;
           $contactRecord->salutation = $this->salutation;
           $contactRecord->telephones = $this->telephones;
           $contactRecord->language = $this->language;

//           $contactRecord->teamleaderId = $this->teamleaderId;

            $success = $contactRecord->save(false);

            if ($success) {
                $companyRelations = Teamleader::$plugin->getContacts()->getContactsCompaniesByContactId($this->id);
                $idsToDelete = $companyRelations->map(function($value) {return $value['companyId'];})->toArray();

                if (!is_string($this->companies)) {
                    foreach ($this->companies as $company) {
                        if (!$companyRelations->first(function($value) use ($company) {
                            return $value['companyId'] == $company;
                        })) {
                            // add
                            $contactCompany = new ContactCompanyRecord();
                            $contactCompany->companyId = $company;
                            $contactCompany->contactId = $this->id;

                            $contactCompany->save(false);
                        }

                        // Search value and delete
                        if (($key = array_search($company, $idsToDelete)) !== false) {
                            unset($idsToDelete[$key]);
                        }
                        //                    $companyRelations = $companyRelations->filter(function($value) use ($company){return $value['companyId'] != $company;});
                        //                    Craft::dd($companyRelations->reject(
                        //                        function($entry) use ($company){
                        //                            $entry['companyId'] == $company;
                        //                        }));
                        //                    $companyRelations = $companyRelations->filter(
                        //                        function($entry) use ($company){
                        //                            $entry['id'] != $company;
                        //                        });
                    }
                }

                foreach ($idsToDelete as $itemToDelete) {
                    $company = ContactCompanyRecord::find()
                        ->andWhere(['companyId' => $itemToDelete])
                        ->one();

                    if ($company) {
                        $company->delete(); // Delete the company
                    }
                }
            }
        }

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     * @return FieldLayout|null
     */
    public function getFieldLayout(): ?FieldLayout
    {
        if ($this->fieldLayout !== null) {
            return $this->fieldLayout;
        }

        $this->fieldLayout = Craft::$app->getFields()->getLayoutByType(self::class);

        return $this->fieldLayout;
    }

    /**
     * @inheritdoc
     */
    public function afterValidate(): void
    {
//        $scenario = $this->getScenario();
//
//        if ($scenario === self::SCENARIO_LIVE) {
//            $companyElements = $this->getFieldLayout()->getAllElements();
//            foreach ($companyElements as $companyElement) {
//                if ($companyElements->required) {
//                    (new RequiredValidator())->validateAttribute($this, $companyElements->attribute);
//                }
//            }
//        }
    }

    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl('teamleader-focus/contacts');
    }

    public function prepareEditScreen(Response $response, string $containerId): void
    {
        /** @var Response|CpScreenResponseBehavior $response */
        $response->crumbs([
            [
                'label' => self::pluralDisplayName(),
                'url' => UrlHelper::cpUrl('teamleader-focus/contacts'),
            ],
        ]);
    }

    /**
     * @inheritdoc
     * @since 1.0.0
     */
    public function hasRevisions(): bool
    {
        return false;
    }

    /**
     * @param bool $isNew
     * @return bool
     */
    public function beforeSave(bool $isNew): bool
    {
//        $this->emails = !empty($this->emails) ? Json::encode($this->emails) : [];
//        $this->telephones = !empty($this->telephones) ? Json::encode($this->telephones) : [];
        $this->title = $this->firstName . ' ' . $this->lastName;

        return parent::beforeSave($isNew);
    }

    /**
     * @return string|null
     */
    public function getUriFormat(): ?string
    {
        // If contacts should have URLs, define their URI format here
        return null;
    }

    public function init(): void
    {
        parent::init();

        if ($this->id) {
            $this->_companyContacts = Teamleader::$plugin->getContacts()->getContactsCompaniesByContactId($this->id);

            if ($this->_companyContacts) {
                $this->_companies = Teamleader::$plugin->getCompanies()->getCompaniesByIds($this->_companyContacts->map(function($relation){return $relation['companyId'];})->all());
            }
        }
    }

    /**
     * @param User $user
     * @return bool
     */
    public function canView(User $user): bool
    {
        if ($user->admin) {
            return true; // Admins can always view
        }

        if (parent::canView($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('teamleader-focus:view-contacts');
    }

    /**
     * @param User $user
     * @return bool
     */
    public function canSave(User $user): bool
    {
        if ($user->admin) {
            return true; // Admins can always view
        }

        if (parent::canSave($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('teamleader-focus:save-contacts');
    }

    /**
     * @param User $user
     * @return bool
     */
    public function canDuplicate(User $user): bool
    {
        if ($user->admin) {
            return true; // Admins can always view
        }

        if (parent::canDuplicate($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('teamleader-focus:save-contacts');
    }

    /**
     * @param User $user
     * @return bool
     */
    public function canDelete(User $user): bool
    {
        if ($user->admin) {
            return true; // Admins can always view
        }

        if (parent::canSave($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('teamleader-focus:delete-contacts');
    }

    public function getCompanies(): array
    {
        return $this->_companies;
    }

    public function getArray($handle): array
    {
        if ($this[$handle]) {
            $data = Json::decode($this[$handle]);

            if ($data == '') return [];

            return Json::decode($this[$handle]);
        }

        return [];
    }

}
