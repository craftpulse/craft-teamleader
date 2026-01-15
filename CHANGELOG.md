# Release Notes for Teamleader

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
