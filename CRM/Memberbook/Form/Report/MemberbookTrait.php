<?php

use CRM_Altreconomia_ExtensionUtil as E;

trait CRM_Memberbook_MemberbookTrait
{
    protected $can_execute_query = FALSE;

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
        //Civi::log()->debug($sql);
        return $sql;
    }

    protected function MemberBookColumns()
    {
        // Custom columns
        $this->_columns['civicrm_membership']['fields']['sum_qty'] = [
            'title' => E::ts('Quote'),
            'dbAlias' => 'FLOOR(memberbook_line_item.qty)',
            'type' => CRM_Utils_Type::T_INT,
            'required' => TRUE,
            'default' => TRUE,
            'statistics' => ['sum' => E::ts('Quote')],
            'is_statistics' => TRUE,
        ];

        $this->_columns['civicrm_membership']['fields']['sum_line_total'] = [
            'title' => E::ts('Capitale sottoscritto'),
            'dbAlias' => 'memberbook_line_item.line_total',
            'type' => CRM_Utils_Type::T_MONEY,
            'required' => TRUE,
            'default' => TRUE,
            'statistics' => ['sum' => E::ts('Capitale sottoscritto')],
            'is_statistics' => TRUE,
        ];

        $this->_columns['civicrm_membership']['fields']['sum_financial_item'] = [
            'title' => E::ts('Capitale versato'),
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
            'required' => TRUE,
            'default' => TRUE,
            'statistics' => ['sum' => E::ts('Capitale versato')],
            'is_statistics' => TRUE,
        ];

        $this->_columns['civicrm_membership']['fields']['row_number'] = [
            'title' => E::ts('#'),
            'type' => CRM_Utils_Type::T_INT,
            'default' => TRUE,
            'statistics' => ['count' => E::ts('#')],
            'is_statistics' => TRUE,
        ];

        // Order by
        $this->_columns['civicrm_value_dati_gestiona_6']['order_bys']['codice_socio_11'] = [
            'title' => E::ts('Codice socio'),
            'default' => '1',
            'default_weight' => '2',
            'default_order' => 'ASC',
        ];

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

    /**
     * Build where clause.
     */
    public function where()
    {
        parent::where();
        $this->_where .= " and memberbook_price_field_value.membership_type_id = {$this->_aliases['civicrm_membership']}.membership_type_id";
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
        return [
            'civicrm_membership_row_number_count',
            'civicrm_value_dati_gestiona_6_custom_11', // codice socio
            'civicrm_contact_sort_name',
            'civicrm_contact_birth_date',
            'civicrm_value_altri_dati_an_4_custom_7',
            'civicrm_value_dati_fiscali_5_custom_8',
        ];
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

}
