<?php

namespace craftpulse\teamleader\elements;

use Craft;
use craft\base\Element;
use craft\elements\User;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\ElementQueryInterface;
use craft\events\DefineHtmlEvent;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\web\CpScreenResponseBehavior;
use craftpulse\teamleader\elements\conditions\QuotationCondition;
use craftpulse\teamleader\elements\db\QuotationQuery;
use craftpulse\teamleader\records\QuotationRecord;
use yii\web\Response;

/**
 * Quotation element type
 */
class Quotation extends Element
{
    public const STATUS_NEW = 'new';
    public const STATUS_QUOTATION_SENT = 'quotation_sent';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REFUSED = 'refused';

    public ?DateTime $dateExpiry = null;
    public ?string $currency = 'EUR';
    public array|string $discounts = [];
    public float $purchasePrice = 0.00;
    public ?string $phase = null;
    public float $taxAmount = 0.00;
    public float $taxRate = 0.00;
    public float $taxableAmount = 0.00;
    public float $totalTaxExclusiveAmount = 0.00;
    public float $totalTaxInclusiveAmount = 0.00;
    public array|string $quotationLines = [];

    public ?array $deals = null;

    private ?FieldLayout $fieldLayout = null;



    public static function displayName(): string
    {
        return Craft::t('teamleader-focus', 'Quotation');
    }

    public static function lowerDisplayName(): string
    {
        return Craft::t('teamleader-focus', 'quotation');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('teamleader-focus', 'Quotations');
    }

    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('teamleader-focus', 'quotations');
    }

    public static function refHandle(): ?string
    {
        return 'quotation';
    }

    public static function trackChanges(): bool
    {
        return true;
    }

    public static function hasTitles(): bool
    {
        return true;
    }

    public static function hasUris(): bool
    {
        return true;
    }

    public static function isLocalized(): bool
    {
        return false;
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_NEW => ['label' => Craft::t('teamleader-focus', 'New'), 'color' => 'gray'],
            self::STATUS_QUOTATION_SENT => ['label' => Craft::t('teamleader-focus', 'Quotation Sent'), 'color' => 'pink'],
            self::STATUS_ACCEPTED => ['label' => Craft::t('teamleader-focus', 'Accepted'), 'color' => 'green'],
            self::STATUS_REFUSED => ['label' => Craft::t('teamleader-focus', 'Refused'), 'color' => 'red'],
        ];
    }

    public static function find(): ElementQueryInterface
    {
        return Craft::createObject(QuotationQuery::class, [static::class]);
    }

    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(QuotationCondition::class, [static::class]);
    }

    protected static function defineSources(string $context): array
    {
        return [
            [
                'key' => '*',
                'label' => Craft::t('teamleader-focus', 'All quotations'),
            ],
        ];
    }

    protected static function defineActions(string $source): array
    {
        // List any bulk element actions here
        return [];
    }

    protected static function includeSetStatusAction(): bool
    {
        return true;
    }

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

    protected static function defineTableAttributes(): array
    {
        return [
            'slug' => ['label' => Craft::t('app', 'Slug')],
            'id' => ['label' => Craft::t('app', 'ID')],
            'uid' => ['label' => Craft::t('app', 'UID')],
            'deals' => ['label' => Craft::t('teamleader-focus', 'Deals')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
            'dateExpiry' => ['label' => Craft::t('app', 'Date Expiry')],
            // ...
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'dateCreated',
            'dateExpiry',
            'deals',
            // ...
        ];
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

//        $rules[] = [['deals'], 'required'];

        $rules[] = [[
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

    public function getUriFormat(): ?string
    {
        // If quotations should have URLs, define their URI format here
        return null;
    }

    public function getSidebarHtml(bool $static): string
    {
        $components = [];

        $components[] = Craft::$app->getView()->renderTemplate('teamleader-focus/_components/_quotations-sidebar', [
            'element' => $this,
            'dealType' => Deal::class,
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
     * @inheritdoc
     */
    public function canView(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('teamleader-focus:view-deals');
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

    public function canCreateDrafts(User $user): bool
    {
        return true;
    }

    protected function cpEditUrl(): ?string
    {
        return sprintf('teamleader-focus/quotations/%s', $this->getCanonicalId());
    }

    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl('teamleader-focus/quotations');
    }

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
            $record->quotationLines = $this->quotationLines;

            $record->save(false);
        }

        parent::afterSave($isNew);
    }
}
