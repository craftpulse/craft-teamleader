<?php

namespace craftpulse\teamleader\elements;

use Craft;
use craft\base\Element;
use craft\elements\User;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\UrlHelper;
use craft\web\CpScreenResponseBehavior;
use craftpulse\teamleader\elements\conditions\ContactCondition;
use craftpulse\teamleader\elements\db\ContactQuery;
use yii\web\Response;

/**
 * Contact element type
 */
class Contact extends Element
{
    public static function displayName(): string
    {
        return Craft::t('teamleader-focus', 'Contact');
    }

    public static function lowerDisplayName(): string
    {
        return Craft::t('teamleader-focus', 'contact');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('teamleader-focus', 'Contacts');
    }

    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('teamleader-focus', 'contacts');
    }

    public static function refHandle(): ?string
    {
        return 'contact';
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

    public static function find(): ElementQueryInterface
    {
        return Craft::createObject(ContactQuery::class, [static::class]);
    }

    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(ContactCondition::class, [static::class]);
    }

    protected static function defineSources(string $context): array
    {
        return [
            [
                'key' => '*',
                'label' => Craft::t('teamleader-focus', 'All contacts'),
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
            'uri' => ['label' => Craft::t('app', 'URI')],
            'link' => ['label' => Craft::t('app', 'Link'), 'icon' => 'world'],
            'id' => ['label' => Craft::t('app', 'ID')],
            'uid' => ['label' => Craft::t('app', 'UID')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
            // ...
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'link',
            'dateCreated',
            // ...
        ];
    }

    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            // ...
        ]);
    }

    public function getUriFormat(): ?string
    {
        // If contacts should have URLs, define their URI format here
        return null;
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
        // Define how contacts should be routed when their URLs are requested
        return [
            'templates/render',
            [
                'template' => 'site/template/path',
                'variables' => ['contact' => $this],
            ]
        ];
    }

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

    public function canCreateDrafts(User $user): bool
    {
        return true;
    }

    protected function cpEditUrl(): ?string
    {
        return sprintf('contacts/%s', $this->getCanonicalId());
    }

    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl('contacts');
    }

    public function prepareEditScreen(Response $response, string $containerId): void
    {
        /** @var Response|CpScreenResponseBehavior $response */
        $response->crumbs([
            [
                'label' => self::pluralDisplayName(),
                'url' => UrlHelper::cpUrl('contacts'),
            ],
        ]);
    }

    public function afterSave(bool $isNew): void
    {
        if (!$this->propagating) {
            // todo: update the `contacts` table
        }

        parent::afterSave($isNew);
    }
}
