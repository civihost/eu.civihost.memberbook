<?php

/**
 * Job.assign_member_code API specification
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_job_assign_member_code_spec(&$spec)
{
    $spec['year'] = [
        'description'  => 'The membership year. If not specified is the current year',
        'api.required' => 0,
    ];
}

/**
 * Job.assign_member_code API
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @see civicrm_api3_create_success
 *
 * @throws CRM_Core_Exception
 */
function civicrm_api3_job_assign_member_code($params)
{
    if (!\Civi::settings()->get('memberbook_consider_one_year')) {
        return civicrm_api3_create_success(ts('The flag "Considers only contributions for one year" is not checked. For this reason, nothing must be done.'));
    }

    $code_customfield = CRM_Memberbook_Utils::getSettingCustomField('memberbook_code_customfield');
    if (!$code_customfield) {
        return civicrm_api3_create_success(ts('The Member code custom field has not been set. For this reason, nothing must be done.'));
    }

    $values = [];
    if (isset($params['year'])) {
        $year = $params['year'];
    } else {
        $year = date('Y');
    }

    $lineItems = \Civi\Api4\LineItem::get(TRUE)
        ->addSelect('contribution_id', 'contribution.receipt_date', 'contribution.receive_date')
        ->addJoin('Contribution AS contribution', 'LEFT', ['contribution_id', '=', 'contribution.id'])
        ->addWhere('contribution.is_test', '=', 0)
        ->addWhere('entity_table', '=', 'civicrm_membership')
        ->addWhere('YEAR(contribution.receipt_date)', '=', $year)
        ->addWhere('contribution.contribution_status_id', '=', 1)
        ->addWhere('contribution.receipt_date', 'IS NOT NULL')
        ->addGroupBy('contribution_id')
        ->execute();

    $counter = 0;
    foreach ($lineItems as $lineItem) {
        $counter++;
        \Civi\Api4\Contribution::update(TRUE)
            ->addValue($code_customfield['group_name'] . '.' . $code_customfield['name'], $counter)
            ->addWhere('id', '=', $lineItem['contribution_id'])
            ->execute();
    }

    // If records were processed ..
    if ($counter) {
        return civicrm_api3_create_success(
            ts(
                '%1 contribution record(s) were processed.',
                [
                    1 => $counter,
                ]
            )
        );
    }
    // No records processed
    return civicrm_api3_create_success(ts('No contribution records were processed.'));
}
