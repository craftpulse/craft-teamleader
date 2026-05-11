# Release Notes for Teamleader

## 5.2.2 - unreleased
### Security
- Removed public `$companyId`, `$dealId`, and `$userId` properties from the Formie integration class — these held per-submission state and could risk cross-submission data leakage if Formie hydrated an integration from a stored representation. IDs are now scoped to local variables in `sendPayload()` and passed as parameters to `_prepPayload()`. ([#14](https://github.com/craftpulse/craft-teamleader-focus/issues/14))

### Fixed
- Fixed `sendPayload()` and `fetchFormSettings()` catching only `yii\base\Exception`, letting `\RuntimeException`, GuzzleHttp network errors, and `IdentityProviderException` propagate uncaught. Catches now broaden to `\Exception|\Error` and route through Formie's normal error handling. ([#13](https://github.com/craftpulse/craft-teamleader-focus/issues/13))
- Fixed `_prepPayload()` unconditionally overwriting the mapped deal title with the static `dealTitle` setting. Mapped form values are now respected; the static setting is the fallback when the mapped value is empty. ([#18](https://github.com/craftpulse/craft-teamleader-focus/issues/18))
- Fixed `_fetchCustomFields()` crashing when the API response omits the `data` key (e.g. 204 responses, partial errors). ([#20](https://github.com/craftpulse/craft-teamleader-focus/issues/20))
- Fixed companies error logs encoding `$contactValues` instead of `$companyValues`, making company-related API failures impossible to debug. ([#20](https://github.com/craftpulse/craft-teamleader-focus/issues/20))
- Fixed `getCurrencyOptions()` making a live API call on every CP page render. Now cached for 24h on success only — API failures fall back to defaults without polluting the cache. ([#21](https://github.com/craftpulse/craft-teamleader-focus/issues/21))

### Changed
- Removed `declare(strict_types=1)` from `TeamleaderFocusRefreshTokenGrant` per Craft CMS plugin conventions. ([#12](https://github.com/craftpulse/craft-teamleader-focus/issues/12))
- Extracted `API_BASE_URL` to a `public const` on the auth client class. Integration class and OAuth provider now reference the single source of truth. ([#21](https://github.com/craftpulse/craft-teamleader-focus/issues/21))
- Added `defineRules()` to the Formie integration: `dealTitle` is required when `mapToDeals` is enabled; `defaultCurrency` is validated as a 3-char string. Existing saved integrations with `mapToDeals=true` and an empty `dealTitle` will fail validation on next save in the CP. ([#21](https://github.com/craftpulse/craft-teamleader-focus/issues/21))
- Loosened composer PHP constraint from `^8.2.0` to `^8.2`. ([#21](https://github.com/craftpulse/craft-teamleader-focus/issues/21))
- Section headers, `@author` annotations, and PSR-12 formatting brought in line with Craft CMS plugin conventions across the codebase. ([#16](https://github.com/craftpulse/craft-teamleader-focus/issues/16), [#17](https://github.com/craftpulse/craft-teamleader-focus/issues/17), [#19](https://github.com/craftpulse/craft-teamleader-focus/issues/19))

## 5.2.1 - 2026-04-02
### Fixed
- Fixed country field only accepting labels (e.g., "Belgium") — now also accepts ISO codes (e.g., "BE") from prefilled dropdowns, with case-insensitive matching. ([#8](https://github.com/craftpulse/craft-teamleader-focus/issues/8)) - Thanks [@ishetnogferre](https://github.com/ishetnogferre)
- Fixed tags field only accepting arrays — now also normalizes comma-, semicolon-, and pipe-separated strings from hidden fields. ([#10](https://github.com/craftpulse/craft-teamleader-focus/issues/10)) - Thanks [@ishetnogferre](https://github.com/ishetnogferre)
- Fixed empty values in tag arrays not being filtered out, matching the string path behavior.
- Fixed `VatHelper::formatVatNumber()` stripping letters from non-Belgian EU VAT numbers (FR, NL, IE, ES, etc.) and applying Belgian-specific dot formatting. Now validates against per-country EU patterns and outputs raw alphanumeric format as expected by the Teamleader Focus API.
- Fixed `IdentityProviderException` being thrown with an empty error message in OAuth client `checkResponse`.

### Changed
- Removed unused `$options` parameter from `_prepPayload()` and dead `$options` variable in `sendPayload()`.
- Added explicit `private` visibility to OAuth/API URL constants in auth client (PHP 8.2).
- Removed unused static `$plugin` property from main plugin class — use inherited `::getInstance()` instead.

## 5.2.0 - 2026-01-18
### Added
- Added "Tags" integration field for Contacts
- Added "Tags" integration field for Companies
- Added "Append Tags" option for Contacts to preserve existing tags during updates (uses `contacts.tag` endpoint)
- Added "Append Tags" option for Companies to preserve existing tags during updates (uses `companies.tag` endpoint)
- Added "Remarks" integration field for Contacts
- Added "Remarks" integration field for Companies
- Added "Summary" integration field for Deals
- Added `fax` field mapping for both contacts and companies.
- Added `currency` field mapping for deals - allows mapping currency from a form field.
- Added `Default Currency` setting for deals - configurable fallback when currency is not mapped from a form field.
- Added `CurrencyHelper` class for formatting currency options from Teamleader Focus API.
- Added `Client Type` custom Formie field for B2B/B2C workflow differentiation.
- B2B (Company) requests create contact + company + deal with linking.
- B2C (Client) requests create contact + deal only, skipping company creation.
- Field displays as radio buttons with configurable labels and default value.
- Added `ClientTypeHelper` for detecting client type from form submissions.

### Changed
- Renamed Deals "Extra Information" field to "Summary" with correct API handle

### Fixed
- Fixed Deals field using incorrect API handle `remarks` instead of `summary`
- Fixed `linkToCompany` toggle having no effect - contacts are now properly linked to companies via the `contacts.linkToCompany` API endpoint when both entities exist and the setting is enabled.
- Removed `mobile_phone` field from companies mapping - Teamleader Focus API only supports `phone` and `fax` for companies.

## 5.1.1 - 2026-01-15
### Fixed
- Fixed custom fields not being sent in the correct API format (now properly structured as `custom_fields` array)
- Fixed `contact_person_id` sending empty string instead of being omitted when not applicable
- Fixed `contact_person_id` now only included when customer is a company and a contact person exists
- Fixed mobile phone type using `'phone'` instead of `'mobile'` for the telephone type
- Removed `context` from API payload (internal use only, not an API field)

## 5.1.0 - 2026-01-13
### Added
- Added "Remarks" integration field for creating remarks via forms
- Added "estimated_value" integration field for pushing amounts to Teamleader Focus

### Changed
- Refactored VAT number formatting into a reusable helper function
- Made email on companies optional to match Teamleader Focus API Specs

### Fixed
- Fixed context filters not properly limiting API field results
- Fixed custom fields not saving correctly to Teamleader Focus
- Fixed address generation not conforming to Teamleader Focus API specs
- Fixed mobile_phone mapping incorrectly unsetting `phone` instead of `mobile_phone`
- Fixed PHPStan return type in VatHelper::formatVatNumber()

## 5.0.2 - 2025-03-10
### Fixed
- Fixed the path of the icon-mask to `teamleader`, using an alias looks to the namespace, not the folder structure or plugin handle

## 5.0.1 - 2025-02-26
### Fixed
- Fixed the template path of the settings templates to `teamleader-focus` as the plugin had to be renamed

## 5.0.0 - 2025-02-24
- Initial Release
