# Release Notes for Teamleader

## 5.1.0.1 - 2026-01-12

### Fixed
- Fixed PHPStan return type in VatHelper::formatVatNumber()

## 5.1.0 - 2026-01-12
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

## 5.0.2 - 2025-03-10
### Fixed
- Fixed the path of the icon-mask to `teamleader`, using an alias looks to the namespace, not the folder structure or plugin handle

## 5.0.1 - 2025-02-26
### Fixed
- Fixed the template path of the settings templates to `teamleader-focus` as the plugin had to be renamed

## 5.0.0 - 2025-02-24
- Initial Release
