<?php

use CRM_Memberbook_ExtensionUtil as E;

require_once('MemberbookTrait.php');

/**
 * Report for displaying the list of membership contributions, the subscribed and paid-up share capital.
 */
class CRM_Memberbook_Form_Report_MemberbookContributions extends CRM_Report_Form_Member_ContributionDetail
{
    use CRM_Memberbook_MemberbookTrait;

    public function __construct()
    {
        $receipt_date_label = \Civi::settings()->get('memberbook_receipt_date_label') ?? ts('Receipt Date');
        $contribution_shares_label = \Civi::settings()->get('memberbook_qty_lineitem_label') ?? E::ts('Number of shares');
        $total_lineitem_label = \Civi::settings()->get('memberbook_total_lineitem_label') ?? E::ts('Total subscribed');

        $this->_columns = [
            'civicrm_contact' => [
                'dao' => 'CRM_Contact_DAO_Contact',
                'fields' => $this->getBasicContactFields(),
                'filters' => [
                    'sort_name' => [
                        'title' => ts('Donor Name'),
                        'operator' => 'like',
                    ],
                    'id' => [
                        'title' => ts('Contact ID'),
                        'no_display' => TRUE,
                    ],
                ],
                'order_bys' => [
                    'sort_name' => [
                        'title' => ts('Last Name, First Name'),
                        'default_weight' => '3',
                        'default_order' => 'ASC',
                    ],
                ],
                'grouping' => 'contact-fields',
            ],
            'civicrm_email' => [
                'dao' => 'CRM_Core_DAO_Email',
                'fields' => ['email' => NULL],
                'grouping' => 'contact-fields',
            ],
            'civicrm_phone' => [
                'dao' => 'CRM_Core_DAO_Phone',
                'fields' => ['phone' => NULL],
                'grouping' => 'contact-fields',
            ],
            'civicrm_contribution' => [
                'dao' => 'CRM_Contribute_DAO_Contribution',
                'fields' => [
                    'contribution_id' => [
                        'name' => 'id',
                        'no_display' => TRUE,
                        'required' => TRUE,
                    ],
                    'financial_type_id' => ['title' => ts('Financial Type')],
                    'contribution_status_id' => ['title' => ts('Contribution Status')],
                    'payment_instrument_id' => ['title' => ts('Payment Type')],
                    'currency' => [
                        'required' => TRUE,
                        'no_display' => TRUE,
                    ],
                    'trxn_id' => NULL,
                    'receive_date' => ['type' => CRM_Utils_Type::T_DATE,],
                    'receipt_date' => ['type' => CRM_Utils_Type::T_DATE,],
                    'fee_amount' => NULL,
                    'net_amount' => NULL,
                    'total_amount' => NULL,
                    'sum_memberbook_line_qty' => [
                        'title' => $contribution_shares_label,
                        'dbAlias' => "IF(memberbook_line_item.contribution_id = contribution_civireport.id, FLOOR(memberbook_line_item.qty), 0)",
                        'type' => CRM_Utils_Type::T_INT,
                        'required' => FALSE,
                        'default' => TRUE,
                        'statistics' => ['sum' => $contribution_shares_label],
                        'is_statistics' => TRUE,
                    ],
                    'sum_memberbook_line_total' => [
                        'title' => $total_lineitem_label,
                        'dbAlias' => "IF(memberbook_line_item.contribution_id = contribution_civireport.id, memberbook_line_item.line_total, 0)",
                        'type' => CRM_Utils_Type::T_MONEY,
                        'required' => FALSE,
                        'default' => TRUE,
                        'statistics' => ['sum' => $total_lineitem_label],
                        'is_statistics' => TRUE,
                    ],
                ],
                'filters' => [
                    'receive_date' => ['operatorType' => CRM_Report_Form::OP_DATE],
                    'financial_type_id' => [
                        'title' => ts('Financial Type'),
                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                        'options' => CRM_Contribute_BAO_Contribution::buildOptions('financial_type_id', 'search'),
                        'type' => CRM_Utils_Type::T_INT,
                    ],
                    'payment_instrument_id' => [
                        'title' => ts('Payment Type'),
                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                        'options' => CRM_Contribute_BAO_Contribution::buildOptions('payment_instrument_id', 'search'),
                        'type' => CRM_Utils_Type::T_INT,
                    ],
                    'currency' => [
                        'title' => ts('Currency'),
                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                        'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
                        'default' => NULL,
                        'type' => CRM_Utils_Type::T_STRING,
                    ],
                    'contribution_status_id' => [
                        'title' => ts('Contribution Status'),
                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                        'options' => CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'search'),
                        'type' => CRM_Utils_Type::T_INT,
                    ],
                    'total_amount' => ['title' => ts('Contribution Amount')],
                ],
                'order_bys' => [
                    'receipt_date' => [
                        'title' => $receipt_date_label,
                        'default_weight' => '1',
                        'default_order' => 'ASC',
                    ],
                    'receive_date' => [
                        'title' => ts('Contribution Date'),
                        'default_weight' => '2',
                        'default_order' => 'ASC',
                    ],
                ],
                'grouping' => 'contri-fields',
            ],
            'civicrm_membership' => [
                'dao' => 'CRM_Member_DAO_Membership',
                'fields' => [
                    'membership_type_id' => [
                        'title' => ts('Membership Type'),
                        'required' => TRUE,
                        'no_repeat' => TRUE,
                    ],
                    'membership_start_date' => [
                        'title' => ts('Membership Start Date'),
                        'default' => TRUE,
                    ],
                    'membership_end_date' => [
                        'title' => ts('Membership Expiration Date'),
                        'default' => TRUE,
                    ],
                    'join_date' => [
                        'title' => ts('Member Since'),
                        'default' => TRUE,
                    ],
                    'source' => ['title' => ts('Membership Source')],
                ],
                'filters' => [
                    'membership_join_date' => ['operatorType' => CRM_Report_Form::OP_DATE],
                    'membership_start_date' => ['operatorType' => CRM_Report_Form::OP_DATE],
                    'membership_end_date' => ['operatorType' => CRM_Report_Form::OP_DATE],
                    'owner_membership_id' => [
                        'title' => ts('Primary Membership'),
                        'operatorType' => CRM_Report_Form::OP_INT,
                    ],
                    'tid' => [
                        'name' => 'membership_type_id',
                        'title' => ts('Membership Types'),
                        'type' => CRM_Utils_Type::T_INT,
                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                        'options' => CRM_Member_PseudoConstant::membershipType(),
                    ],
                ],
                'grouping' => 'member-fields',
            ],
            'civicrm_membership_status' => [
                'dao' => 'CRM_Member_DAO_MembershipStatus',
                'alias' => 'mem_status',

                'fields' => [
                    'membership_status_name' => [
                        'name' => 'name',
                        'title' => ts('Membership Status'),
                        'default' => TRUE,
                    ],
                ],
                'filters' => [
                    'sid' => [
                        'name' => 'id',
                        'title' => ts('Membership Status'),
                        'type' => CRM_Utils_Type::T_INT,
                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                        'options' => CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'label'),
                    ],
                ],
                'grouping' => 'member-fields',
            ],
        ] + $this->getAddressColumns([
            // These options are only excluded because they were not previously present.
            'order_by' => FALSE,
            'group_by' => FALSE,
        ]);

        $this->_groupFilter = TRUE;
        $this->_tagFilter = TRUE;

        // If we have campaigns enabled, add those elements to both the fields, filters and sorting
        $this->addCampaignFields('civicrm_contribution', FALSE, TRUE);

        $this->_currencyColumn = 'civicrm_contribution_currency';

        CRM_Report_Form::__construct();
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

        $this->traitFrom();

        $this->_from .= " LEFT JOIN civicrm_line_item as memberbook_line_item on memberbook_line_item.entity_id = {$this->_aliases['civicrm_membership']}.id and memberbook_line_item.entity_table = 'civicrm_membership'";

        if (\Civi::settings()->get('memberbook_consider_one_year')) {
            $this->_from .= " and memberbook_line_item.contribution_id = {$this->_aliases['civicrm_contribution']}.id";
        }

        $this->_from .= " LEFT JOIN civicrm_price_field_value as memberbook_price_field_value on memberbook_price_field_value.id = memberbook_line_item.price_field_value_id
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
            $this->_from .= " LEFT JOIN civicrm_entity_financial_trxn eft
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

    public function tempTable($applyLimit = TRUE) {}

    protected function sortColumns(): array
    {
        return array_merge($this->traitSortColumns(), [
            'civicrm_address_street_address',
            'civicrm_address_postal_code',
            'civicrm_address_city',
            'civicrm_address_state_province_id',
            'civicrm_address_country_id',
            'civicrm_address_address_street_address',
            'civicrm_address_address_postal_code',
            'civicrm_address_address_city',
            'civicrm_address_address_state_province_id',
            'civicrm_address_address_country_id',
            'civicrm_contribution_receive_date',
            'civicrm_membership_membership_type_id',
            'civicrm_membership_membership_start_date',
            'civicrm_membership_membership_end_date',
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

    /**
     * Build order by clause.
     * @todo The parent function is messy: this is a copy of CRM_Report_Form::orderBy
     */
    public function orderBy()
    {
        $this->_orderBy = "";
        $this->_sections = [];
        $this->storeOrderByArray();
        if (!empty($this->_orderByArray) && !$this->_rollup == 'WITH ROLLUP') {
            $this->_orderBy = "ORDER BY " . implode(', ', $this->_orderByArray);
        }
        $this->assign('sections', $this->_sections);
    }

    /**
     * Defines grouping clause of the report SQL query
     */
    public function groupBy()
    {
        $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_contribution']}.id";
    }
}
