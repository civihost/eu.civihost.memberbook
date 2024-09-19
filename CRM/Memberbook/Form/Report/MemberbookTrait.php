<?php

use CRM_Memberbook_ExtensionUtil as E;

trait CRM_Memberbook_MemberbookTrait
{
    protected $can_execute_query = FALSE;
    protected ?array $code_customfield;
    protected ?array $ssn_customfield;
    protected ?array $vat_customfield;

    public function buildQuery($applyLimit = TRUE)
    {
        $sql = parent::buildQuery($applyLimit);
        if (!$this->can_execute_query) {
            $sql = 'select 0 where 1=0';
            $message = 'You must choose one or more membership types from the filters tab before running this report';
            $title = 'Choose one or more membership types';
            CRM_Core_Session::setStatus($message, $title, $type = 'error', $options = array('expires' => 0));
        }
        $sql = str_replace("(SELECT SQL_CALC_FOUND_ROWS", "(SELECT", $sql);
        return $sql;
    }

    protected function MemberBookColumns()
    {
        $this->code_customfield = CRM_Memberbook_Utils::getSettingCustomField('memberbook_code_customfield');
        $this->ssn_customfield = CRM_Memberbook_Utils::getSettingCustomField('memberbook_ssn_customfield');
        $this->vat_customfield = CRM_Memberbook_Utils::getSettingCustomField('memberbook_vat_customfield');

        $total_subscribed_label = \Civi::settings()->get('memberbook_total_subscribed_label') ?? E::ts('Total subscribed');
        $total_paid_label = \Civi::settings()->get('memberbook_total_paid_label') ?? E::ts('Total paid');
        $member_code_label = \Civi::settings()->get('memberbook_code_label') ?? E::ts('Member code');
        $total_shares_label = \Civi::settings()->get('memberbook_total_shares_label') ?? E::ts('Total number of shares');

        // Custom columns
        if ($this->ssn_customfield && $this->vat_customfield) {
            $customField = \Civi\Api4\CustomField::get(TRUE)
                ->addSelect("GROUP_CONCAT(label SEPARATOR ' - ')")
                ->addWhere('id', 'IN', [$this->ssn_customfield['id'], $this->vat_customfield['id']])
                ->execute()
                ->first();
            // add a single column for the 2 fields
            $ssn_alias = str_replace('civicrm_', '', $this->vat_customfield['table_name']) . "_civireport.{$this->vat_customfield['column_name']}";
            $vat_alias = str_replace('civicrm_', '', $this->ssn_customfield['table_name']) . "_civireport.{$this->ssn_customfield['column_name']}";

            $this->_columns['civicrm_contact']['fields']['ssn_vat'] = [
                'title' => $customField['GROUP_CONCAT:label'],
                'dbAlias' => "IF({$ssn_alias} IS NULL, {$vat_alias}, IF({$vat_alias} IS NULL, {$ssn_alias}, CONCAT({$ssn_alias}, ' - ', {$vat_alias})))",
                'type' => CRM_Utils_Type::T_STRING,
                'required' => FALSE,
                'default' => FALSE,
            ];

            if (!isset($this->_columns[$this->vat_customfield['table_name']])) {
                $this->_columns[$this->vat_customfield['table_name']] = [
                    'dao' => 'CRM_Contact_DAO_Contact',
                    'extends' => 'Individual',
                    'grouping' => $this->vat_customfield['table_name'],
                ];
            }

            if (!isset($this->_columns[$this->ssn_customfield['table_name']])) {
                exit();
                $this->_columns[$this->ssn_customfield['table_name']] = [
                    'dao' => 'CRM_Contact_DAO_Contact',
                    'extends' => 'Individual',
                    'grouping' => $this->ssn_customfield['table_name'],
                ];
            }
        }

        $this->_columns['civicrm_membership']['fields']['sum_qty'] = [
            'title' => $total_shares_label,
            'dbAlias' => 'FLOOR(memberbook_line_item.qty)',
            'type' => CRM_Utils_Type::T_INT,
            'required' => FALSE,
            'default' => TRUE,
            'statistics' => ['sum' => $total_shares_label],
            'is_statistics' => TRUE,
        ];

        $this->_columns['civicrm_membership']['fields']['sum_line_total'] = [
            'title' => $total_subscribed_label,
            'dbAlias' => 'memberbook_line_item.line_total',
            'type' => CRM_Utils_Type::T_MONEY,
            'required' => FALSE,
            'default' => TRUE,
            'statistics' => ['sum' => $total_subscribed_label],
            'is_statistics' => TRUE,
        ];

        $this->_columns['civicrm_membership']['fields']['sum_financial_item'] = [
            'title' => $total_paid_label,
            'dbAlias' => "
                    (select sum(ft.total_amount) from civicrm_entity_financial_trxn as eft
                        left outer join civicrm_financial_trxn as ft on ft.id = eft.financial_trxn_id
                        left outer join civicrm_contribution as sc on eft.entity_id = sc.id
                        where
                        eft.entity_id = memberbook_line_item.contribution_id
                        and sc.id in (select  sl.contribution_id from civicrm_line_item as sl
                                left outer join civicrm_price_field_value as spfv on spfv.id = sl.price_field_value_id
                            where
                                sl.contribution_id = sc.id
                                and spfv.membership_type_id = membership_civireport.membership_type_id
                        )
                        -- and sc.contribution_status_id = contribution_civireport.contribution_status_id
                        and eft.entity_table = 'civicrm_contribution'
                        and ft.is_payment = 1
                    )
                ",
            'type' => CRM_Utils_Type::T_MONEY,
            'required' => FALSE,
            'default' => TRUE,
            'statistics' => ['sum' => $total_paid_label],
            'is_statistics' => TRUE,
        ];

        $this->_columns['civicrm_membership']['fields']['row_number'] = [
            'title' => E::ts('#'),
            'type' => CRM_Utils_Type::T_INT,
            'default' => TRUE,
            'statistics' => ['count' => E::ts('#')],
            'is_statistics' => TRUE,
        ];

        // Change some column titles according to settings
        if (\Civi::settings()->get('memberbook_receipt_date_label')) {
            $this->_columns['civicrm_contribution']['fields']['receipt_date']['title'] = \Civi::settings()->get('memberbook_receipt_date_label');
        }

        // Order by
        if ($this->code_customfield) {
            $this->_columns[$this->code_customfield['table_name']]['order_bys'][$this->code_customfield['column_name']] = [
                'title' => $member_code_label,
                'default' => '1',
                'default_weight' => '2',
                'default_order' => 'ASC',
            ];
        }

        // Custom filters
        $this->_columns['civicrm_membership']['filters']['active_in_year'] = [
            'name' => 'active_in_year',
            'title' => E::ts('Anno'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_INT,
            'weight' => '1',

        ];

        // Defaults
        $this->setDefaults([
            'fields' => ['active_in_year' => 1],
            'active_in_year_op' => 'eq',
        ]);
    }

    public function select()
    {
        parent::select();
        $this->_select = str_replace("COUNT({$this->_aliases['civicrm_membership']}.row_number) as", "null as ", $this->_select);
    }

    public function traitFrom(): void
    {
        if ($this->ssn_customfield && !isset($this->_params['fields']['custom_' . $this->ssn_customfield['id']])) {
            $tableAlias = str_replace('civicrm_', '', $this->ssn_customfield['table_name']) . '_civireport';
            $this->_from .= " LEFT JOIN {$this->ssn_customfield['table_name']} as {$tableAlias} on {$tableAlias}.entity_id = {$this->_aliases['civicrm_contact']}.id";
        }
        if ($this->vat_customfield && !isset($this->_params['fields']['custom_' . $this->vat_customfield['id']])) {
            $tableAlias = str_replace('civicrm_', '', $this->vat_customfield['table_name']) . '_civireport';
            $this->_from .= " LEFT JOIN {$this->vat_customfield['table_name']} as {$tableAlias} on {$tableAlias}.entity_id = {$this->_aliases['civicrm_contact']}.id";
        }
    }

    /**
     * Build where clause.
     */
    public function where()
    {
        parent::where();
        $this->_where .= " and memberbook_price_field_value.membership_type_id = {$this->_aliases['civicrm_membership']}.membership_type_id";
    }

    public function alterDisplay(&$rows): void
    {
        parent::alterDisplay($rows);
        $entryFound = FALSE;
        $cnt = 0;
        foreach ($rows as $rowNum => $row) {
            $cnt++;
            if (array_key_exists('civicrm_membership_row_number_count', $row)) {
                $rows[$rowNum]['civicrm_membership_row_number_count'] = $cnt;
                $entryFound = TRUE;
            }
            if (array_key_exists('civicrm_membership_sum_financial_item_sum', $row)) {
                $rows[$rowNum]['civicrm_membership_sum_financial_item_sum'] = min(
                    $rows[$rowNum]['civicrm_membership_sum_financial_item_sum'],
                    $rows[$rowNum]['civicrm_membership_sum_line_total_sum'] ?? $rows[$rowNum]['civicrm_membership_sum_financial_item_sum']
                );
                $entryFound = TRUE;
            }

            // skip looking further in rows, if first row itself doesn't have the column we need
            if (!$entryFound) {
                break;
            }
        }
    }

    /**
     * @param $value
     * @param array $row
     * @param $selectedfield
     * @param $criteriaFieldName
     *
     * @return array
     */
    protected function alterStateProvinceID($value, &$row, $selectedfield, $criteriaFieldName)
    {
        $states = CRM_Core_PseudoConstant::stateProvinceAbbreviation($value, FALSE);
        if (!is_array($states)) {
            return $states;
        }
    }

    /**
     * @param $value
     * @param array $row
     * @param $selectedField
     * @param $criteriaFieldName
     *
     * @return array
     */
    protected function alterCountryID($value, &$row, $selectedField, $criteriaFieldName)
    {
        $countries = CRM_Core_PseudoConstant::countryIsoCode($value, FALSE);
        if (!is_array($countries)) {
            return $countries;
        }
    }

    /**
     * Calculate grant total.
     *
     * @param array $rows
     *
     * @return bool
     */
    public function grandTotal(&$rows)
    {
        $this->rollupRow = $rows[count($rows) - 1];

        foreach ($this->_columnHeaders as $fld => $val) {
            if (!in_array($fld, $this->_statFields)) {
                if (!$this->_grandFlag) {
                    $this->rollupRow[$fld] = ts('Grand Total');
                    $this->_grandFlag = TRUE;
                } else {
                    $this->rollupRow[$fld] = '';
                }
            }
        }
        foreach ($this->_statFields as $fieldLabel => $fieldName) {
            $function = end(explode('_', $fieldName));
            switch ($function) {
                case 'count':
                    $this->rollupRow[$fieldName] = count(array_column($rows, $fieldName));
                    break;
                case 'sum':
                    $this->rollupRow[$fieldName] = array_sum(array_column($rows, $fieldName));
                    break;
            }
        }

        $this->assign('grandStat', $this->rollupRow);
        return TRUE;
    }

    protected function traitSortColumns(): array
    {
        $orderby = [
            'civicrm_membership_row_number_count',
        ];

        if ($this->code_customfield) {
            $orderby[] = $this->code_customfield['table_name'] . '_custom_' . $this->code_customfield['id'];
        }

        $orderby[] = 'civicrm_contact_sort_name';
        $orderby[] = 'civicrm_contact_birth_date';

        if ($this->ssn_customfield) {
            $orderby[] = $this->ssn_customfield['table_name'] . '_custom_' . $this->ssn_customfield['id'];
        }
        if ($this->vat_customfield) {
            $orderby[] = $this->vat_customfield['table_name'] . '_custom_' . $this->vat_customfield['id'];
        }
        if ($this->ssn_customfield && $this->vat_customfield) {
            $orderby[] = 'civicrm_contact_ssn_vat';
        }
        return $orderby;
    }

    /**
     * Modify column headers.
     */
    public function modifyColumnHeaders()
    {
        // Re-order the columns in a custom order defined below.
        $sortArray = $this->sortColumns();

        // Only re-order selected columns.
        $sortArray = array_flip(array_intersect_key(array_flip($sortArray), $this->_columnHeaders));

        // Re-ordering.
        $this->_columnHeaders = array_merge(array_flip($sortArray), $this->_columnHeaders);
    }

    /**
     * Do AlterDisplay processing on Address Fields.
     *  If there are multiple address field values then
     *  on basis of provided separator the code values are translated into respective labels
     *
     * @param array $row
     * @param array $rows
     * @param int $rowNum
     * @param string|null $baseUrl
     * @param string|null $linkText
     * @param string $separator
     *
     * @return bool
     */
    public function alterDisplayAddressFields(&$row, &$rows, &$rowNum, $baseUrl, $linkText, $separator = ',') {}
}
