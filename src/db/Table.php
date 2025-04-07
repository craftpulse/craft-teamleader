<?php

namespace craftpulse\teamleader\db;

/**
 * Abstract class Table
 *
 * @author      CraftPulse
 * @package     Teamleader
 * @since       5.0.0
 *
 */
abstract class Table
{
    // Public Constants
    // =========================================================================

    public const COMPANIES = "{{%teamleader_focus_companies}}";
    public const COMPANIES_ADDRESSES = "{{%teamleader_focus_companies_addresses}}";
    public const CONTACTS = "{{%teamleader_focus_contacts}}";
    public const CONTACTS_COMPANIES = "{{%teamleader_focus_contacts_companies}}";
    public const DEALS = "{{%teamleader_focus_deals}}";
}
