<?php

namespace craftpulse\teamleader\elements;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\elements\Entry;
use craft\elements\User;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\ElementQueryInterface;
use craft\events\DefineHtmlEvent;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\web\CpScreenResponseBehavior;
use craftpulse\teamleader\elements\conditions\QuotationCondition;
use craftpulse\teamleader\elements\db\QuotationQuery;
use craftpulse\teamleader\records\DealQuotationRecord;
use craftpulse\teamleader\records\QuotationRecord;
use yii\web\Response;
use craft\db\Table as CraftTable;

/**
 * Quotation element type
 */
class Quotation extends Element
{
    // Statuses
    // -------------------------------------------------------------------------
    public const STATUS_NEW = 'new';
    public const STATUS_QUOTATION_SENT = 'quotation_sent';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REFUSED = 'refused';

    // Constant Properties
    // =========================================================================
    /**
     * @var DateTime|null
     */
    public ?DateTime $dateExpiry = null;
    /**
     * @var string|null
     */
    public ?string $currency = 'EUR';
    /**
     * @var array|string
     */
    public array|string $discounts = [];
    /**
     * @var float
     */
    public float $purchasePrice = 0.00;
    /**
     * @var string|null
     */
    public ?string $phase = null;
    /**
     * @var float
     */
    public float $taxAmount = 0.00;
    /**
     * @var float
     */
    public float $taxRate = 0.00;
    /**
     * @var float
     */
    public float $taxableAmount = 0.00;
    /**
     * @var float
     */
    public float $totalTaxExclusiveAmount = 0.00;
    /**
     * @var float
     */
    public float $totalTaxInclusiveAmount = 0.00;
    /**
     * @var array|string
     */
    public array|string $quotationLines = [];

    /**
     * @var array|null
     */
    public ?int $dealId = null;

    /**
     * @var null|Deal
     */
    public Deal|string|null $deal = null;

    /**
     * @var array|null
     */
    public ?int $elementId = null;

    /**
     * @var null|string|Element
     */
    public Element|string|null $element = null;

    public ?array $elementTypes = null;

    /**
     * @var FieldLayout|null
     */
    private ?FieldLayout $fieldLayout = null;

    // Public Static Methods
    // =========================================================================
    /**
     * @return string
     */
    public static function displayName(): string
    {
        return Craft::t('teamleader-focus', 'Quotation');
    }

    /**
     * @return string
     */
    public static function lowerDisplayName(): string
    {
        return Craft::t('teamleader-focus', 'quotation');
    }

    /**
     * @return string
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('teamleader-focus', 'Quotations');
    }

    /**
     * @return string
     */
    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('teamleader-focus', 'quotations');
    }

    /**
     * @return string|null
     */
    public static function refHandle(): ?string
    {
        return 'quotation';
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
     * @return array[]
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_NEW => ['label' => Craft::t('teamleader-focus', 'New'), 'color' => 'gray'],
            self::STATUS_QUOTATION_SENT => ['label' => Craft::t('teamleader-focus', 'Quotation Sent'), 'color' => 'pink'],
            self::STATUS_ACCEPTED => ['label' => Craft::t('teamleader-focus', 'Accepted'), 'color' => 'green'],
            self::STATUS_REFUSED => ['label' => Craft::t('teamleader-focus', 'Refused'), 'color' => 'red'],
        ];
    }

    /**
     * @return ElementQueryInterface
     * @throws \yii\base\InvalidConfigException
     */
    public static function find(): ElementQueryInterface
    {
        return Craft::createObject(QuotationQuery::class, [static::class]);
    }

    /**
     * @return ElementConditionInterface
     * @throws \yii\base\InvalidConfigException
     */
    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(QuotationCondition::class, [static::class]);
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
                'label' => Craft::t('teamleader-focus', 'All quotations'),
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
            'slug' => Craft::t('app', 'Slug'),
            'uri' => Craft::t('app', 'URI'),
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
            'dealId' => ['label' => Craft::t('teamleader-focus', 'Deal')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
            'dateExpiry' => ['label' => Craft::t('app', 'Date Expiry')],
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
            'dateExpiry',
            'deal',
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

        $rules[] = [['deal','element'], 'required', 'on' => self::SCENARIO_LIVE];

        $rules[] = [[
            'deal',
            'element',
            'phase',
            'currency',
            'discounts',
            'purchasePrice',
            'taxAmount',
            'taxRate',
            'taxableAmount',
            'totalTaxExclusiveAmount',
            'totalTaxInclusiveAmount',
            'quotationLines'
        ], 'safe'];

        return $rules;
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
        // Define how quotations should be routed when their URLs are requested
        return [
            'templates/render',
            [
                'template' => 'site/template/path',
                'variables' => ['quotation' => $this],
            ]
        ];
    }

    /**
     * @return string|null
     */
    protected function cpEditUrl(): ?string
    {
        return sprintf('teamleader-focus/quotations/%s', $this->getCanonicalId());
    }


    // Public Methods
    // =========================================================================
    /**
     * @return void
     * @throws \yii\base\InvalidConfigException
     */
    public function init(): void
    {
        parent::init();

        if ($this->id && $this->dealId) {
            $this->deal = Deal::findOne($this->dealId);
        }

        if ($this->id && $this->elementId) {
            $el = (new Query())->from(CraftTable::ELEMENTS)->where(['id' => $this->elementId])->one();

            if ($el) {
                $this->element = Craft::$app->getElements()->createElementQuery($el['type'])
                    ->id($el['id'])
                    ->one();
            }
        }
    }

    /**
     * @return string|null
     */
    public function getUriFormat(): ?string
    {
        // If quotations should have URLs, define their URI format here
        return null;
    }

    /**
     * @param bool $static
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \yii\base\Exception
     */
    public function getSidebarHtml(bool $static): string
    {
        $components = [];

        $components[] = Craft::$app->getView()->renderTemplate('teamleader-focus/_components/_quotations-sidebar', [
            'element' => $this,
            'dealType' => Deal::class,
            'elementType' => Entry::class,
            'status' => $this->getStatus(),
        ]);

        // Fire a defineSidebarHtml event
        $event = new DefineHtmlEvent([
            'html' => implode("\n", $components),
        ]);
        $this->trigger(self::EVENT_DEFINE_SIDEBAR_HTML, $event);
        return $event->html;
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
    public function canView(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('teamleader-focus:view-quotations');
    }

    /**
     * @inheritdoc
     */
    public function canSave(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('teamleader-focus:save-quotations');
    }

    /**
     * @inheritdoc
     */
    public function canDuplicate(User $user): bool
    {
        if (parent::canDuplicate($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('teamleader-focus:view-quotations');
    }

    /**
     * @inheritdoc
     */
    public function canDelete(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('teamleader-focus:delete-quotations');
    }

    /**
     * @param User $user
     * @return bool
     */
    public function canCreateDrafts(User $user): bool
    {
        return true;
    }

    /**
     * @return string|null
     */
    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl('teamleader-focus/quotations');
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        $status = parent::getStatus();
        if ($status !== self::STATUS_ENABLED) {
            return $status;
        }

        if (is_null($this->phase)) {
            return self::STATUS_NEW;
        }

        switch ($this->phase) {
            case self::STATUS_NEW:
                return self::STATUS_NEW;
            case self::STATUS_QUOTATION_SENT:
                return self::STATUS_QUOTATION_SENT;
            case self::STATUS_ACCEPTED:
                return self::STATUS_ACCEPTED;
            case self::STATUS_REFUSED:
                return self::STATUS_REFUSED;
            default:
                return self::STATUS_NEW;
        }
    }

    /**
     * @param Response $response
     * @param string $containerId
     * @return void
     */
    public function prepareEditScreen(Response $response, string $containerId): void
    {
        /** @var Response|CpScreenResponseBehavior $response */
        $response->crumbs([
            [
                'label' => self::pluralDisplayName(),
                'url' => UrlHelper::cpUrl('teamleader-focus/quotations'),
            ],
        ]);
    }

    /**
     * @param bool $isNew
     * @return void
     * @throws \yii\db\Exception
     */
    public function afterSave(bool $isNew): void
    {
        if (!$this->propagating) {
            if ($isNew) {
                $record = new QuotationRecord();
                $record->id = $this->id;
            } else {
                $record = QuotationRecord::findOne($this->id);
            }

//            $teamleaderId = Teamleader::$plugin->quotesConnector->sync($this, $isNew);
//
//            if ($teamleaderId) {
//                $contactRecord->teamleaderId = $teamleaderId;
//            }

            $record->fieldLayoutId = $this->fieldLayout->id;

            if ($this->deal) {
                $this->dealId = $this->deal->id ?? $this->deal;
            }

            if ($this->element) {
                $this->elementId = $this->element->id ?? $this->element;
            }

            $record->dealId = $this->dealId;
            $record->elementId = $this->elementId;
            $record->currency = $this->currency;
            $record->discounts = $this->discounts;
            $record->purchasePrice = $this->purchasePrice;
            $record->phase = $this->phase;
            $record->taxAmount = $this->taxAmount;
            $record->taxRate = $this->taxRate;
            $record->taxableAmount = $this->taxableAmount;
            $record->totalTaxExclusiveAmount = $this->totalTaxExclusiveAmount;
            $record->totalTaxInclusiveAmount = $this->totalTaxInclusiveAmount;
            $record->quotationLines = $this->quotationLines;

            $success = $record->save(false);

            if ($success) {
                $relation = DealQuotationRecord::find()->where(['quotationId' => $this->id])->one();

                if (is_null($relation)) {
                    $relation = new DealQuotationRecord();
                    $relation->quotationId = $this->id;
                }

                $relation->dealId = $this->dealId;
                $relation->save();
            }
        }

        parent::afterSave($isNew);
    }
}
