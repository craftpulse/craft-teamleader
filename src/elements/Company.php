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
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\web\CpScreenResponseBehavior;
use craftpulse\teamleader\elements\conditions\CompanyCondition;
use craftpulse\teamleader\elements\db\CompanyQuery;
use craftpulse\teamleader\records\CompanyRecord;
use yii\base\ExitException;
use yii\db\Exception;
use yii\web\Response;

/**
 * Company element type
 */
class Company extends Element
{
    // Traits
    // =========================================================================

    // Constant Properties
    // =========================================================================
    /**
     * @var bool|null
     */
    public ?bool $marketingMailsConsent = null;
    /**
     * @var string|null
     */
    public ?string $nationalIdentificationNumber = null;
    /**
     * @var string|null
     */
    public ?string $vatNumber = null;
    /**
     * @var string|null
     */
    public ?string $website = null;
    /**
     * @var array
     */
    public array $emails = [];
    /**
     * @var array
     */
    public array $telephones = [];
    public ElementCollection $addresses;
    /**
     * @var string
     */
    public string $name = '';

    /**
     * @var null|FieldLayout Field layout
     */
    private ?FieldLayout $fieldLayout = null;


    // Public Static Methods
    // =========================================================================
    /**
     * @return string
     */
    public static function displayName(): string
    {
        return Craft::t('teamleader-focus', 'Company');
    }

    /**
     * @return string
     */
    public static function lowerDisplayName(): string
    {
        return Craft::t('teamleader-focus', 'company');
    }

    /**
     * @return string
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('teamleader-focus', 'Companies');
    }

    /**
     * @return string
     */
    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('teamleader-focus', 'companies');
    }

    /**
     * @return string|null
     */
    public static function refHandle(): ?string
    {
        return 'company';
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
     * @throws \yii\base\InvalidConfigException
     */
    public static function find(): ElementQueryInterface
    {
        return Craft::createObject(CompanyQuery::class, [static::class]);
    }

    /**
     * @return ElementConditionInterface
     * @throws \yii\base\InvalidConfigException
     */
    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(CompanyCondition::class, [static::class]);
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
                'label' => Craft::t('teamleader-focus', 'All companies'),
            ],
        ];
    }

    /**
     * @param string $source
     * @return array
     */
    protected static function defineActions(string $source): array
    {
        // List any bulk element actions here
        return [];
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
        return array_merge(parent::defineRules(), [
            // ...
        ]);
    }

    protected function cpEditUrl(): ?string
    {
        return sprintf('teamleader-focus/companies/%s', $this->getCanonicalId());
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
        // Define how companies should be routed when their URLs are requested
        return [
            'templates/render',
            [
                'template' => 'site/template/path',
                'variables' => ['company' => $this],
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
                $companyRecord = new CompanyRecord();
                $companyRecord->id = $this->id;
            } else {
                $companyRecord = CompanyRecord::findOne($this->id);
            }

            $companyRecord->fieldLayoutId = $this->fieldLayout->id;
            $companyRecord->name = $this->title;

            $companyRecord->save(false);
        }

        //Craft::dd($this->emails);

        parent::afterSave($isNew);
    }

    /**
     * Gets the addresses.
     *
     * @return ElementCollection<Address>
     */
    public function getAddresses(): ElementCollection
    {
        if (!isset($this->addresses)) {
            if (!$this->id) {
                /** @var ElementCollection<Address> */
                return ElementCollection::make();
            }

            $this->addresses = $this->createAddressQuery()
                ->andWhere(['fieldId' => null])
                ->collect();
        }

        return $this->addresses;
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

    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl('teamleader-focus/companies');
    }

    public function prepareEditScreen(Response $response, string $containerId): void
    {
        /** @var Response|CpScreenResponseBehavior $response */
        $response->crumbs([
            [
                'label' => self::pluralDisplayName(),
                'url' => UrlHelper::cpUrl('teamleader-focus/companies'),
            ],
        ]);
    }

    /**
     * @param bool $isNew
     * @return bool
     */
    public function beforeSave(bool $isNew): bool
    {
        $this->emails = !empty($this->emails) ? Json::encode($this->emails) : [];
        $this->telephones = !empty($this->telephones) ? Json::encode($this->telephones) : [];

        return parent::beforeSave($isNew);
    }

    /**
     * @return void
     */
    public function afterFind(): void
    {
        $this->addresses = new ElementCollection();

        $this->emails = $this->emails ?: [];
        $this->telephones = $this->telephones ?: [];
    }


    /**
     * @return array|string[]
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'emails',
            'telephones',
        ]);
    }

    /**
     * @return string|null
     */
    public function getUriFormat(): ?string
    {
        // If companies should have URLs, define their URI format here
        return null;
    }

    public function init(): void
    {
        parent::init();

        $this->getAddresses();
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
        return $user->can('teamleader-focus:view-companies');
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
        return $user->can('teamleader-focus:save-companies');
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
        return $user->can('teamleader-focus:save-companies');
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
        return $user->can('teamleader-focus:delete-companies');
    }

    // Private Methods
    // =========================================================================
    private function createAddressQuery(): AddressQuery
    {
        // @TODO: add owner to only get current elements
        return Address::find()
//            ->owner($this)
            ->orderBy(['id' => SORT_ASC]);
    }

}
