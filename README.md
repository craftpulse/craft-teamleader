# Teamleader Focus plugin for Craft CMS 5.x

The Teamleader plugin is a powerful tool for integrating Craft CMS with Teamleader Focus, allowing seamless synchronization of contacts, companies, and deals directly from Formie forms.

![Screenshot](./resources/img/teamleader.jpg)

## Requirements

This plugin requires Craft CMS 5.0.0 or later.

# Teamleader Plugin for Craft CMS 5.x

Seamlessly integrate **Teamleader Focus** with **Craft CMS** and **Formie forms** to manage contacts, companies, and deals efficiently.

## Installation

### Standard Craft CMS Installation

1. Open your terminal and navigate to your Craft project:
   ```sh
   cd /path/to/project
   ```
2. Install the plugin via Composer:
   ```sh
   composer require craftpulse/craft-teamleader-focus
   ```
3. Install the plugin:
   ```sh
   craft plugin/install teamleader-focus
   ```
   Alternatively, activate it via **Settings → Plugins** in the Craft Control Panel.

### Installing on DDEV

1. Install the Teamleader plugin:
   ```sh
   ddev composer require craftpulse/craft-teamleader-focus
   ```
2. Install the plugin in Craft CMS:
   ```sh
   ddev craft plugin/install teamleader-focus
   ```

---

## Connecting to Teamleader Focus

1. In **Craft CMS**, go to **Settings → Formie → Integrations**.
2. Locate **Teamleader Focus** and click **Connect**.
3. Authenticate with your **Teamleader API credentials**.
4. Once connected, you can map Formie fields to Teamleader fields.

---

## Using Teamleader in Formie Forms

1. Open any **Formie form**.
2. Navigate to **Integrations → CRM → Teamleader Focus**.
3. Configure your field mappings.
4. Save the form.

---

## Custom Formie Fields

The Teamleader Focus plugin provides custom Formie fields to enhance your forms:

### Client Type Field

The **Client Type** field allows you to differentiate between B2B (Business-to-Business) and B2C (Business-to-Consumer) submissions. This controls the backend logic for creating records in Teamleader Focus.

**How it works:**

- **Company (B2B)**: Creates a contact, company, and deal. The contact is automatically linked to the company.
- **Client (B2C)**: Creates only a contact and deal, skipping company creation entirely.

**Field Settings:**

- **Default Value**: Choose whether "Company" or "Client" is selected by default.
- **Company Label**: Customize the label for the B2B option (default: "Company").
- **Client Label**: Customize the label for the B2C option (default: "Client").
- **Layout**: Display radio buttons vertically or horizontally.

This field is always required and works seamlessly with Formie's built-in **Conditions** feature to show/hide company-related fields based on the selected value.

---

## Mapping Form Fields to Teamleader Focus

### Mapping Options in Form Settings

When configuring Teamleader integration in a Formie form, you will see the following options in the **Form Settings → Integrations → CRM → Teamleader Focus** section:

- **Map to Contacts** – Enables mapping of Formie fields to Teamleader contacts.
- **Map to Companies** – Enables mapping of Formie fields to Teamleader companies.
- **Map to Deals** – Enables mapping of Formie fields to Teamleader deals.
- **Link user to company** – Ensures contacts are linked to a company in Teamleader when enabled.
- **Default Currency** – Select the default currency for deal values (dynamically fetched from Teamleader).

### Important Mapping Notes

⚠️ **Name Field Configuration**: Formie's **Name** field should be configured as **separate fields** (Suffix, First Name, Last Name) to ensure proper mapping to Teamleader.

⚠️ **Address Mapping Requirement**: Teamleader **requires a full address** (`addressLine1`, `postal_code`, `city`, `country`). If any of these fields are missing, **the address data will NOT appear** in Teamleader.

⚠️ **Country Field**: The `country` field accepts both **ISO codes** (e.g., `BE`, `FR`) and **full country labels** (e.g., `Belgium`, `France`). ISO codes are matched case-insensitively. This is useful when using prefilled dropdowns that pass the code rather than the label.

### Contact Fields

| Handle                  | Name                         | Type    | Required |
| :---------------------- | ---------------------------- | ------- | -------- |
| salutation              | Salutation                   | String  | No       |
| first_name              | First Name                   | String  | No       |
| last_name               | Last Name                    | String  | Yes      |
| email                   | Email address                | String  | Yes      |
| mobile_phone            | Mobile number                | String  | No       |
| phone                   | Phone number                 | String  | No       |
| fax                     | Fax number                   | String  | No       |
| addressLine1            | Address                      | String  | No       |
| postal_code             | Postal Code                  | String  | No       |
| city                    | City                         | String  | No       |
| country                 | Country                      | String  | No       |
| language                | Language                     | String  | No       |
| remarks                 | Remarks (Markdown supported) | String  | No       |
| tags                    | Tags                         | String  | No       |
| marketing_mails_consent | Marketing Mails Consent      | Boolean | No       |

**Custom Fields**: The Teamleader integration supports **custom fields** for Contacts, which are dynamically fetched from your Teamleader configuration.

### Company Fields

| Handle                         | Name                           | Type    | Required |
| ------------------------------ | ------------------------------ | ------- | -------- |
| company_name                   | Company Name                   | String  | Yes      |
| email                          | Email address                  | String  | No       |
| addressLine1                   | Address                        | String  | No       |
| postal_code                    | Postal Code                    | String  | No       |
| city                           | City                           | String  | No       |
| country                        | Country                        | String  | No       |
| phone                          | Phone number                   | String  | No       |
| fax                            | Fax number                     | String  | No       |
| vat_number                     | VAT Number                     | String  | Yes      |
| national_identification_number | National Identification Number | String  | No       |
| website                        | Website                        | String  | No       |
| language                       | Language                       | String  | No       |
| remarks                        | Remarks (Markdown supported)   | String  | No       |
| tags                           | Tags                           | String  | No       |
| marketing_mails_consent        | Marketing Mails Consent        | Boolean | No       |

**Custom Fields**: The Teamleader integration supports **custom fields** for Companies, which are dynamically fetched from your Teamleader configuration.

**Note**: Companies do not support `mobile_phone` in the Teamleader API. Use `phone` or `fax` instead.

### Deal Fields

| Handle          | Name       | Type   | Required |
| --------------- | ---------- | ------ | -------- |
| title           | Deal Title | String | Yes      |
| estimated_value | Deal Value | Float  | No       |
| currency        | Currency   | String | No       |
| summary         | Summary    | String | No       |

**Custom Fields**: The Teamleader integration supports **custom fields** for Deals, which are dynamically fetched from your Teamleader configuration.

**Deal Title**: This must be configured in the Form Settings under 'Map to Deals' and is required to create a deal in Teamleader.

**Currency**: Can be mapped from a form field or falls back to the **Default Currency** setting. Currencies are dynamically fetched from Teamleader's exchange rates API.

---

### Tag Handling Options

When updating existing contacts or companies, you can control how tags are handled:

- **Append Contact Tags** – When enabled, new tags are added to existing contact tags instead of replacing them. This makes an additional API call.
- **Append Company Tags** – When enabled, new tags are added to existing company tags instead of replacing them. This makes an additional API call.

When disabled (default), tags in the form submission will **overwrite** all existing tags on the contact or company.

**Tag Input Formats**: Tags can be provided as an array (e.g., from checkboxes or radio buttons) or as a delimited string from a hidden/text field. Supported delimiters are commas (`,`), semicolons (`;`), and pipes (`|`). For example, `"vip,premium,corporate"` and `"vip;premium|corporate"` are both valid.

---

### Custom fields handling

When updating existing contact or companies, custom fields can be configured to:

- **Partially update**: Only the provided value will be updated and the remaining custom fields will be unchanged.
- **Replace**: The entire collection of custom fields is replaced, missing values are removed from Teamleader

This behaviour is configurable for both **contact** and **company** separately and **enabled** by default.
More information about this behaviour can be found in the Teamleader API docs: [updating collections](https://developer.focus.teamleader.eu/docs/general-principles#collections).

---

## B2B vs B2C Workflow

The plugin supports different workflows based on the type of submission:

### B2B (Business-to-Business)

When the **Client Type** field is set to "Company":

1. A **contact** is created or updated
2. A **company** is created or updated
3. The contact is **linked to the company** (if "Link user to company" is enabled)
4. A **deal** is created and associated with the company

### B2C (Business-to-Consumer)

When the **Client Type** field is set to "Client":

1. A **contact** is created or updated
2. Company creation is **skipped entirely**
3. A **deal** is created and associated with the contact

This allows you to use a single form for both business and individual customers, with the appropriate records created in Teamleader Focus.

---

## Syncing Data with Teamleader Focus

- When a user submits a Formie form, **Teamleader Focus** will create contacts, companies, or deals based on the mapped fields.
- You can verify the data in your **Teamleader account**.

---

## Troubleshooting

- **Authentication Issues**: Ensure your **API credentials** are correct.
- **Data Not Syncing**: Double-check your **field mappings** in Formie.
- **Mobile Phone Not Appearing on Companies**: The Teamleader API only supports `phone` and `fax` for companies, not `mobile_phone`.
- **Currency Not Applied**: Ensure the currency code matches Teamleader's supported currencies (e.g., "EUR", "USD", "GBP").

---

**Brought to you by [CraftPulse](https://craft-pulse.com/)**
