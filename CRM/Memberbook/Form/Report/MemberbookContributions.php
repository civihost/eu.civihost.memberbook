<?php

use CRM_Altreconomia_ExtensionUtil as E;

require_once('MemberbookTrait.php');

/**
 * Report for displaying the list of membership contributions, the subscribed and paid-up share capital.
 */
class CRM_Memberbook_Form_Report_MemberbookContributions extends CRM_Report_Form_Member_ContributionDetail
{
    use CRM_Memberbook_MemberbookTrait;

    public function __construct()
    {
        parent::__construct();

        $this->_columns += $this->getAddressColumns([
            // These options are only excluded because they were not previously present.
            'order_by' => FALSE,
            'group_by' => FALSE,
        ]);

        $this->_columns['civicrm_contribution']['group_bys']['id'] = [
            'title' => ts('Contribution'),
            'default' => TRUE,
        ];

        $this->_columns['civicrm_contact']['fields']['birth_date'] = [
            'title' => ts('Birth Date'),
        ];

        $this->_columns['civicrm_contact']['fields']['sort_name'] = [
            'title' => ts('Contact Name')
        ];

        $this->_columns['civicrm_contribution']['fields']['receive_date'] = [
            'title' => E::ts('Data operazione'),
            'type' => CRM_Utils_Type::T_DATE,
        ];

        $this->_columns['civicrm_contract']['order_bys']['sort_name'] = [
            'title' => ts('Last Name, First Name'),
            'default' => '0',
            'default_weight' => '2',
            'default_order' => 'ASC',
        ];

        $this->_columns['civicrm_contribution']['order_bys']['receive_date'] = [
            'title' => E::ts('Data operazione'),
            'default' => '0',
            'default_weight' => '2',
            'default_order' => 'ASC',
        ];

        $this->MemberBookColumns();
    }

    public function from(): void
    {
        $this->setFromBase('civicrm_contribution');
        $this->_from .= "
              {$this->_aclFrom}
              INNER JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
                      ON ({$this->_aliases['civicrm_contribution']}.contact_id = {$this->_aliases['civicrm_contact']}.id)
              LEFT JOIN civicrm_membership {$this->_aliases['civicrm_membership']}
                      ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_membership']}.contact_id)
              LEFT JOIN civicrm_membership_status {$this->_aliases['civicrm_membership_status']}
                          ON {$this->_aliases['civicrm_membership_status']}.id =
                             {$this->_aliases['civicrm_membership']}.status_id";

        $this->_from .= " LEFT JOIN civicrm_line_item as memberbook_line_item on memberbook_line_item.contribution_id = {$this->_aliases['civicrm_contribution']}.id
            LEFT JOIN civicrm_price_field_value as memberbook_price_field_value on memberbook_price_field_value.id = memberbook_line_item.price_field_value_id
            LEFT JOIN civicrm_price_field as memberbook_price_field on memberbook_price_field.id = memberbook_line_item.price_field_id
            ";

        // include contribution note
        if (
            !empty($this->_params['fields']['contribution_note']) ||
            !empty($this->_params['note_value'])
        ) {
            $this->_from .= "
            LEFT JOIN civicrm_note {$this->_aliases['civicrm_note']}
                      ON ( {$this->_aliases['civicrm_note']}.entity_table = 'civicrm_contribution' AND
                           {$this->_aliases['civicrm_contribution']}.id = {$this->_aliases['civicrm_note']}.entity_id )";
        }

        //for contribution batches
        if (
            !empty($this->_params['fields']['batch_id']) ||
            !empty($this->_params['bid_value'])
        ) {
            $this->_from .= "
        LEFT JOIN civicrm_entity_financial_trxn eft
          ON eft.entity_id = {$this->_aliases['civicrm_contribution']}.id AND
            eft.entity_table = 'civicrm_contribution'
        LEFT JOIN civicrm_entity_batch {$this->_aliases['civicrm_batch']}
          ON ({$this->_aliases['civicrm_batch']}.entity_id = eft.financial_trxn_id
          AND {$this->_aliases['civicrm_batch']}.entity_table = 'civicrm_financial_trxn')";
        }

        $this->joinAddressFromContact();
        $this->joinPhoneFromContact();
        $this->joinEmailFromContact();
    }

    public function tempTable($applyLimit = TRUE)
    {
    }

    protected function sortColumns(): array
    {
        return array_merge($this->traitSortColumns(), [
            'civicrm_address_address_street_address',
            'civicrm_address_address_postal_code',
            'civicrm_address_address_city',
            'civicrm_address_address_state_province_id',
            'civicrm_address_address_country_id',
        ]);
    }

    public function whereClause(&$field, $op, $value, $min, $max)
    {
        switch ($field['name']) {
            case 'active_in_year':
                if ($value) {
                    return "(YEAR({$this->_aliases['civicrm_contribution']}.receive_date) = {$value})";
                }
            case 'membership_type_id':
                $this->can_execute_query = TRUE;
            default:
                return parent::whereClause($field, $op, $value, $min, $max);
        }
    }
}
