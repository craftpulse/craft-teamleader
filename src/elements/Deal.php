<?php

namespace craftpulse\teamleader\elements;

use Craft;
use craft\base\Element;
use craft\behaviors\RevisionBehavior;
use craft\elements\User;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\ElementQueryInterface;
use craft\enums\Color;
use craft\events\DefineHtmlEvent;
use craft\events\DefineMetadataEvent;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\i18n\Formatter;
use craft\models\FieldLayout;
use craft\web\CpScreenResponseBehavior;

use craftpulse\teamleader\db\Table;
use craftpulse\teamleader\elements\conditions\DealCondition;
use craftpulse\teamleader\elements\db\DealQuery;

use craftpulse\teamleader\records\DealRecord;
use DateTime;

use yii\base\InvalidConfigException;
use yii\db\Exception;
use yii\web\Response;

/**
 * Deal element type
 */
class Deal extends Element
{
    // Constant Properties
    // =========================================================================
    public ?float $amount = 0.00;
    public ?string $currency = 'EUR';
    public ?string $reference = null;
    public ?string $summary = null;
    public ?DateTime $dateClosing = null;
    public ?DateTime $dateClosed = null;
    public ?string $webUrl = null;
    public ?int $companyId = null;
    public ?int $contactId = null;
    public ?string $statusKey = null;
    public ?string $phase = null;

    public ?array $companies = null;
    public ?array $contacts = null;

    private ?FieldLayout $fieldLayout = null;

    // Statuses
    // -------------------------------------------------------------------------
    public const STATUS_NEW = 'new';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_MEETING_SCHEDULED = 'meeting_scheduled';
    public const STATUS_QUOTATION_SENT = 'quotation_sent';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REFUSED = 'refused';

    // Public Static Methods
    // =========================================================================
    public static function displayName(): string
    {
        return Craft::t('teamleader-focus', 'Deal');
    }

    public static function lowerDisplayName(): string
    {
        return Craft::t('teamleader-focus', 'deal');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('teamleader-focus', 'Deals');
    }

    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('teamleader-focus', 'deals');
    }

    public static function refHandle(): ?string
    {
        return 'deal';
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
        return true;
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_NEW => ['label' => Craft::t('teamleader-focus', 'New'), 'color' => 'gray'],
            self::STATUS_CONTACTED => ['label' => Craft::t('teamleader-focus', 'Contacted'), 'color' => 'yellow'],
            self::STATUS_MEETING_SCHEDULED => ['label' => Craft::t('teamleader-focus', 'Meeting Scheduled'), 'color' => 'orange'],
            self::STATUS_QUOTATION_SENT => ['label' => Craft::t('teamleader-focus', 'Quotation Sent'), 'color' => 'pink'],
            self::STATUS_ACCEPTED => ['label' => Craft::t('teamleader-focus', 'Accepted'), 'color' => 'green'],
            self::STATUS_REFUSED => ['label' => Craft::t('teamleader-focus', 'Refused'), 'color' => 'red'],
        ];
    }

    /**
     * @throws InvalidConfigException
     */
    public static function find(): ElementQueryInterface
    {
        return Craft::createObject(DealQuery::class, [static::class]);
    }

    /**
     * @throws InvalidConfigException
     */
    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(DealCondition::class, [static::class]);
    }

    // Protected Static Methods
    // =========================================================================
    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context): array
    {
        return [
            [
                'key' => '*',
                'label' => Craft::t('teamleader-focus', 'All deals'),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source): array
    {
        // List any bulk element actions here
        return [];
    }

    /**
     * @inheritdoc
     */
    protected static function includeSetStatusAction(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        return [
            'slug' => ['label' => Craft::t('app', 'Slug')],
            'uri' => ['label' => Craft::t('app', 'URI')],
            'link' => ['label' => Craft::t('app', 'Link'), 'icon' => 'world'],
            'id' => ['label' => Craft::t('app', 'ID')],
            'uid' => ['label' => Craft::t('app', 'UID')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
            // ...
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'link',
            'dateCreated',
            // ...
        ];
    }

    // Protected Methods
    // =========================================================================
    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [[
            'title',
            'phase',
            'amount',
        ], 'safe'];

        return $rules;
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    protected function route(): array|string|null
    {
        // Define how deals should be routed when their URLs are requested
        return [
            'templates/render',
            [
                'template' => 'site/template/path',
                'variables' => ['deal' => $this],
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    protected function cpEditUrl(): ?string
    {
        return sprintf('teamleader-focus/deals/%s', $this->getCanonicalId());
    }

    // Public Methods
    // =========================================================================
    public function init(): void
    {
        parent::init();

        if ($this->id && $this->companyId) {
            $this->company = Company::findOne($this->companyId);
        }
    }
    /**
     * @inheritdoc
     */
    public function getUriFormat(): ?string
    {
        // If deals should have URLs, define their URI format here
        return null;
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
        return $user->can('teamleader-focus:save-deals');
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
        return $user->can('teamleader-focus:view-deals');
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
        return $user->can('teamleader-focus:delete-deals');
    }

    /**
     * @inheritdoc
     */
    public function canCreateDrafts(User $user): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl('teamleader-focus/deals');
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
            case self::STATUS_CONTACTED:
                return self::STATUS_CONTACTED;
            case self::STATUS_MEETING_SCHEDULED:
                return self::STATUS_MEETING_SCHEDULED;
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


    public function getMetadata(): array
    {
        $metadata = $this->metadata();

        // Fire a 'defineMetadata' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_METADATA)) {
            $event = new DefineMetadataEvent(['metadata' => $metadata]);
            $this->trigger(self::EVENT_DEFINE_METADATA, $event);
            $metadata = $event->metadata;
        }

        $formatter = Craft::$app->getFormatter();

        $html = array_merge($metadata, [
            Craft::t('app', 'Created at') => $this->dateCreated && !$this->getIsUnpublishedDraft()
                ? $formatter->asDatetime($this->dateCreated, Formatter::FORMAT_WIDTH_SHORT)
                : false,
            Craft::t('app', 'Updated at') => $this->dateUpdated && !$this->getIsUnpublishedDraft()
                ? $formatter->asDatetime($this->dateUpdated, Formatter::FORMAT_WIDTH_SHORT)
                : false,
            Craft::t('app', 'Notes') => function() {
                if ($this->getIsRevision()) {
                    $revision = $this;
                } elseif ($this->getIsCanonical() || $this->isProvisionalDraft) {
                    $element = $this->getCanonical(true);
                    $revision = $element->getCurrentRevision();
                }
                if (!isset($revision)) {
                    return false;
                }
                /** @var RevisionBehavior $behavior */
                $behavior = $revision->getBehavior('revision');
                if ($behavior->revisionNotes === null || $behavior->revisionNotes === '') {
                    return false;
                }
                return Html::encode($behavior->revisionNotes);
            },
        ]);

        return $html;
    }


    public function getSidebarHtml(bool $static): string
    {
        $components = [];

//        $metaFieldsHtml = $this->metaFieldsHtml($static);
//        if ($metaFieldsHtml !== '') {
//            $components[] = Html::tag('div', $metaFieldsHtml, ['class' => 'meta']) .
//                Html::tag('h2', Craft::t('app', 'Metadata'), ['class' => 'visually-hidden']);
//        }

//        $components[] = Html::beginTag('div') .
//            Html::tag('legend', Craft::t('app', 'Status'), ['class' => 'h6']) .
//            Cp::selectizeHtml([
//                'id' => $this->id,
//                'name' => 'Test',
//                'label' => Craft::t('app', 'Test'),
//                'options' => [
//                    [
//                        'label' => 'test',
//                        'value' => 'test'
//                    ]
//                ],
//                'value' => 'test',
//                'disabled' => false,
//            ]) .
//            Html::endTag('div');

        $components[] = Craft::$app->getView()->renderTemplate('teamleader-focus/_components/_deals-sidebar', [
            'element' => $this,
            'companyConfig' => [
                'allowAdd' => true,
                'allowRemove' => true,
                'elements' => $this->company ?? [],
                'elementType' => Company::class,
                'name' => 'companies',
                'criteria' => [
                    'siteId' => Craft::$app->sites->currentSite->id,
                ],
                'limit' => 1,
                'viewMode' => 'list',
                'showCardsInGrid' => false,
            ],
            'contactConfig' => [
                'allowAdd' => true,
                'allowRemove' => true,
                'elements' => $this->contact ?? [],
                'elementType' => Contact::class,
                'name' => 'contacts',
                'criteria' => [
                    'siteId' => Craft::$app->sites->currentSite->id,
                ],
                'limit' => 1,
                'viewMode' => 'list',
                'showCardsInGrid' => false,
            ],
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
     */
    public function prepareEditScreen(Response $response, string $containerId): void
    {
        /** @var Response|CpScreenResponseBehavior $response */
        $response->crumbs([
            [
                'label' => self::pluralDisplayName(),
                'url' => UrlHelper::cpUrl('teamleader-focus/deals'),
            ],
        ]);
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

    public function beforeSave(bool $isNew): bool
    {
        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function afterSave(bool $isNew): void
    {
        if (!$this->propagating) {
            if ($isNew) {
                $dealRecord = new DealRecord();
                $dealRecord->id = $this->id;
            } else {
                $dealRecord = DealRecord::findOne($this->id);
            }

            $dealRecord->fieldLayoutId = $this->fieldLayout->id;
            $dealRecord->amount = $this->amount;
            $dealRecord->currency = $this->currency;
            $dealRecord->phase = $this->phase;

//            Craft::dd($this->phase);

            $dealRecord->save(false);
        }

        parent::afterSave($isNew);
    }
}
