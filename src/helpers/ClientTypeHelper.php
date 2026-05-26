<?php
/**
 * Teamleader plugin for Craft CMS
 *
 * @link      https://craft-pulse.com
 * @copyright Copyright (c) 2025 CraftPulse
 */

namespace craftpulse\teamleader\helpers;

use craftpulse\teamleader\fields\formie\ClientType;

use verbb\formie\elements\Submission;

/**
 * Class ClientTypeHelper
 *
 * Resolves the client type (B2B/B2C) from a Formie submission by locating the
 * ClientType field on the form. Defaults to B2B when no ClientType field exists.
 *
 * @author CraftPulse
 * @since  5.2.0
 */
class ClientTypeHelper
{
    // Public Methods
    // =========================================================================

    /**
     * Get the client type from a submission.
     *
     * Searches the submission's form for a ClientType field and returns its value.
     * Defaults to 'company' (B2B) if no ClientType field is found.
     *
     * @param  Submission $submission The form submission
     * @return string     The client type ('company' or 'contact')
     *
     * @author CraftPulse
     */
    public static function getClientType(Submission $submission): string
    {
        $form = $submission->getForm();

        if (!$form) {
            return ClientType::TYPE_COMPANY;
        }

        foreach ($form->getCustomFields() as $field) {
            if ($field instanceof ClientType) {
                $value = $submission->getFieldValue($field->handle);

                return $value === ClientType::TYPE_CONTACT
                    ? ClientType::TYPE_CONTACT
                    : ClientType::TYPE_COMPANY;
            }
        }

        return ClientType::TYPE_COMPANY;
    }

    /**
     * Check if the submission is a B2B (company) request.
     *
     * @author CraftPulse
     */
    public static function isCompanyRequest(Submission $submission): bool
    {
        return self::getClientType($submission) === ClientType::TYPE_COMPANY;
    }

    /**
     * Check if the submission is a B2C (contact-only) request.
     *
     * @author CraftPulse
     */
    public static function isContactRequest(Submission $submission): bool
    {
        return self::getClientType($submission) === ClientType::TYPE_CONTACT;
    }
}
