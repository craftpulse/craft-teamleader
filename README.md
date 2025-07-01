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

## Mapping Form Fields to Teamleader Focus

### Mapping Options in Form Settings

When configuring Teamleader integration in a Formie form, you will see the following options in the **Form Settings → Integrations → CRM → Teamleader Focus** section:

- **Map to Contacts** – Enables mapping of Formie fields to Teamleader contacts.
- **Map to Companies** – Enables mapping of Formie fields to Teamleader companies.
- **Map to Deals** – Enables mapping of Formie fields to Teamleader deals.
- **Link user to company** – Ensures contacts are linked to a company in Teamleader when enabled.

### Important Mapping Notes

⚠️ **Name Field Configuration**: Formie’s **Name** field should be configured as **separate fields** (Suffix, First Name, Last Name) to ensure proper mapping to Teamleader.

⚠️ **Address Mapping Requirement**: Teamleader **requires a full address** (`addressLine1`, `postal_code`, `city`, `country`). If any of these fields are missing, **the address data will NOT appear** in Teamleader.

### Contact Fields

| Handle                  | Name                    | Type    | Required |
|:------------------------|-------------------------|---------|----------|
| salutation              | Salutation              | String  | No       |
| first_name              | First Name              | String  | No       |
| last_name               | Last Name               | String  | Yes      |
| email                   | Email address           | String  | Yes      |
| mobile_phone            | Mobile number           | String  | No       |
| phone                   | Phone number            | String  | No       |
| addressLine1            | Address                 | String  | No       |
| postal_code             | Postal Code             | String  | No       |
| city                    | City                    | String  | No       |
| country                 | Country                 | String  | No       |
| language                | Language                | String  | No       |
| tags                    | Tags                    | String  | No       |
| remarks                 | Remarks                 | String  | No       |
| marketing_mails_consent | Marketing Mails Consent | Boolean | No       |

### Company Fields

| Handle                         | Name                           | Type    | Required |
|--------------------------------|--------------------------------|---------|----------|
| company_name                   | Company Name                   | String  | Yes      |
| email                          | Email address                  | String  | Yes      |
| addressLine1                   | Address                        | String  | No       |
| postal_code                    | Postal Code                    | String  | No       |
| city                           | City                           | String  | No       |
| country                        | Country                        | String  | No       |
| mobile_phone                   | Mobile number                  | String  | No       |
| phone                          | Phone number                   | String  | No       |
| vat_number                     | VAT Number                     | String  | No       |
| national_identification_number | National Identification Number | String  | No       |
| website                        | Website                        | String  | No       |
| language                       | Language                       | String  | No       |
| marketing_mails_consent        | Marketing Mails Consent        | Boolean | No       |

### Deal Fields

**Custom Fields Only**: The Teamleader integration only supports **custom fields** for Deals, which are dynamically fetched from your Teamleader configuration.

**Deal Title**: This must be configured in the Form Settings under 'Map to Deals' and is required to create a deal in Teamleader. as it is required for identifying the deal in Teamleader.

---

## Syncing Data with Teamleader Focus

- When a user submits a Formie form, **Teamleader Focus** will create contacts, companies, or deals based on the mapped fields.
- You can verify the data in your **Teamleader account**.

---

## Troubleshooting

- **Authentication Issues**: Ensure your **API credentials** are correct.
- **Data Not Syncing**: Double-check your **field mappings** in Formie.

---

**Brought to you by [CraftPulse](https://craft-pulse.com/)**
