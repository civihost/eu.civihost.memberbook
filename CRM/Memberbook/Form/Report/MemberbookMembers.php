<?php

use CRM_Altreconomia_ExtensionUtil as E;

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
        parent::from();

        $this->_from .= " LEFT JOIN civicrm_contribution as memberbook_contribution on {$this->_aliases['civicrm_membership']}.contact_id = memberbook_contribution.contact_id
            LEFT JOIN civicrm_line_item as memberbook_line_item on memberbook_line_item.contribution_id = memberbook_contribution.id
            LEFT JOIN civicrm_price_field_value as memberbook_price_field_value on memberbook_price_field_value.id = memberbook_line_item.price_field_value_id
            LEFT JOIN civicrm_price_field as memberbook_price_field on memberbook_price_field.id = memberbook_line_item.price_field_id
            ";
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
