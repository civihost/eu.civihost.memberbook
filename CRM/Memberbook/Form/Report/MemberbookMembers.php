<?php

use CRM_Memberbook_ExtensionUtil as E;

require_once('MemberbookTrait.php');

/**
 * Report for displaying the list of members and their subscribed and paid-up share capital.
 */
class CRM_Memberbook_Form_Report_MemberbookMembers extends CRM_Report_Form_Member_Detail
{
    use CRM_Memberbook_MemberbookTrait;

    public function __construct()
    {
        parent::__construct();

        $this->_columns['civicrm_membership']['group_bys']['contact_id'] = [
            'title' => ts('Contact'),
            'default' => TRUE,
        ];

        $this->_columns['civicrm_membership']['fields']['membership_type_id']['required'] = FALSE;

        $this->MemberBookColumns();
    }

    public function from(): void
    {
        $this->setFromBase('civicrm_contact');
        $this->_from .= "
         {$this->_aclFrom}
               INNER JOIN civicrm_membership {$this->_aliases['civicrm_membership']}
                          ON {$this->_aliases['civicrm_contact']}.id =
                             {$this->_aliases['civicrm_membership']}.contact_id AND {$this->_aliases['civicrm_membership']}.is_test = 0
               LEFT  JOIN civicrm_membership_status {$this->_aliases['civicrm_membership_status']}
                          ON {$this->_aliases['civicrm_membership_status']}.id =
                             {$this->_aliases['civicrm_membership']}.status_id ";

        $this->joinAddressFromContact();
        $this->joinPhoneFromContact();
        $this->joinEmailFromContact();

        if ($this->isTableSelected('civicrm_contribution')) {
            $this->_from .= "
            LEFT JOIN civicrm_membership_payment cmp
                ON {$this->_aliases['civicrm_membership']}.id = cmp.membership_id
            LEFT JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
                 ON cmp.contribution_id={$this->_aliases['civicrm_contribution']}.id
            LEFT JOIN civicrm_line_item as memberbook_line_item
                ON memberbook_line_item.contribution_id = {$this->_aliases['civicrm_contribution']}.id
            LEFT JOIN civicrm_price_field_value as memberbook_price_field_value
                ON memberbook_price_field_value.id = memberbook_line_item.price_field_value_id
            LEFT JOIN civicrm_price_field as memberbook_price_field
                ON memberbook_price_field.id = memberbook_line_item.price_field_id\n";
        }
        if ($this->isTableSelected('civicrm_contribution_recur')) {
            $this->_from .= <<<HERESQL
            LEFT JOIN civicrm_contribution_recur {$this->_aliases['civicrm_contribution_recur']}
                ON {$this->_aliases['civicrm_membership']}.contribution_recur_id = {$this->_aliases['civicrm_contribution_recur']}.id
HERESQL;
        }

        $this->traitFrom();
    }

    protected function sortColumns(): array
    {
        return $this->traitSortColumns();
    }

    public function whereClause(&$field, $op, $value, $min, $max)
    {
        switch ($field['name']) {
            case 'active_in_year':
                if ($value) {
                    return "(YEAR({$this->_aliases['civicrm_membership']}.start_date) <= {$value} AND
                        ({$this->_aliases['civicrm_membership']}.end_date IS NULL OR YEAR({$this->_aliases['civicrm_membership']}.end_date) >= {$value}))";
                }
            case 'membership_type_id':
                $this->can_execute_query = TRUE;
            default:
                return parent::whereClause($field, $op, $value, $min, $max);
        }
    }
}
