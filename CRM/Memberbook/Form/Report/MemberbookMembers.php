<?php

use CRM_Altreconomia_ExtensionUtil as E;

/**
 * Overrides the Membership Detail report for FIAB:
 * - membership year filter
 * - payment lookup (for the year)
 *
 * See CRM/Report/Form/Member/Detail.php
 * and CRM/Report/Form.php to override the relevant bits
 */

class CRM_Memberbook_Form_Report_MemberbookMembers extends CRM_Report_Form_Member_Detail
{

    public function buildQuery($applyLimit = TRUE)
    {
        $sql = parent::buildQuery($applyLimit);
        $sql = str_replace("(SELECT SQL_CALC_FOUND_ROWS", "(SELECT", $sql);
        //Civi::log()->debug($sql);
        return $sql;
    }

    public function __construct()
    {
        parent::__construct();

        $this->_columns['civicrm_membership']['group_bys']['contact_id'] = [
            'title' => ts('Contact'),
            'default' => TRUE,
        ];

        $this->_columns['civicrm_membership']['fields']['membership_type_id']['required'] = FALSE;

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

    public function preProcess(): void
    {
        //$this->assign('reportTitle', E::ts('Abbonamenti per rivista'));
        parent::preProcess();
    }

    public function select()
    {
        parent::select();
        $this->_select = str_replace("COUNT({$this->_aliases['civicrm_membership']}.row_number) as", "null as ", $this->_select);
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

    /**
     * Build where clause.
     */
    public function where()
    {
        parent::where();
        $this->_where .= " and memberbook_price_field_value.membership_type_id = {$this->_aliases['civicrm_membership']}.membership_type_id";
    }

    /**
     * Override to add handling for autorenew status.
     */
    public function whereClause(&$field, $op, $value, $min, $max)
    {
        switch ($field['name']) {
            case 'active_in_year':
                if ($value) {
                    return "(YEAR({$this->_aliases['civicrm_membership']}.start_date) <= {$value} AND
                        ({$this->_aliases['civicrm_membership']}.end_date IS NULL OR YEAR({$this->_aliases['civicrm_membership']}.end_date) >= {$value}))";
                }
            default:
                return parent::whereClause($field, $op, $value, $min, $max);
        }
    }

    public function alterDisplay(&$rows): void
    {
        parent::alterDisplay($rows);
        $entryFound = FALSE;
        ////Civi::log()->debug('rows' . print_r($rows, true));
        //$magazine_type = $this->getSubmitValue('magazine_type_value');
        //
        $cnt = 0;
        foreach ($rows as $rowNum => $row) {
            $cnt++;
            //    if (array_key_exists('civicrm_membership_membership_type_id', $row)) {
            //        $url = CRM_Utils_System::url("civicrm/contact/view", 'reset=1&cid=' . $row['civicrm_contact_id'], $this->_absoluteUrl);
            //        $rows[$rowNum]['civicrm_membership_membership_type_id'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $row['civicrm_membership_membership_type_id'], 'name', 'id');
            //        $rows[$rowNum]['civicrm_membership_membership_type_id_link'] = $url;
            //        $entryFound = TRUE;
            //    }
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


            //    if (array_key_exists('civicrm_contact_sort_name', $row)) {
            //        $url = CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $row['civicrm_contact_id'] . '&selectedChild=member', $this->_absoluteUrl);
            //        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
            //        $entryFound = TRUE;
            //    }
            //    if (array_key_exists('civicrm_address_address_name', $row)) {
            //        $rows[$rowNum]['civicrm_address_address_name'] = $this->addressName($row);
            //        $entryFound = TRUE;
            //    }
            //    if (array_key_exists('civicrm_membership_line_1', $row)) {
            //        $address_name = $this->addressName($row);
            //        if ($row['civicrm_contact_custom_contact_type'] === 'Organization') {
            //            $greatings = 'Spett.le ' . $address_name;
            //            //$greatings = 'Spett.le ' . $row['civicrm_contact_custom_organization_name'];
            //        } else {
            //            //$greatings = 'Gentile ' . trim($row['civicrm_contact_custom_first_name'] . ' ' . $row['civicrm_contact_custom_last_name']);
            //            $greatings = 'Gentile ' . $address_name;
            //        }
            //        $rows[$rowNum]['civicrm_membership_line_1'] = $greatings;
            //        $entryFound = TRUE;
            //    }
            //    if (array_key_exists('civicrm_membership_line_2', $row)) {
            //        if ($magazine_type === 'last') {
            //            $rows[$rowNum]['civicrm_membership_line_2'] = self::LINE_2_LAST;
            //        } elseif ($magazine_type === 'next-to-last') {
            //            $rows[$rowNum]['civicrm_membership_line_2'] = self::LINE_2_NEXT_TO_LAST;
            //        }
            //        $entryFound = TRUE;
            //    }
            //
            // skip looking further in rows, if first row itself doesn't have the column we need
            if (!$entryFound) {
                break;
            }
        }
    } //

    //protected function addressName($row)
    //{
    //    $address_name = $row['civicrm_address_address_name'];
    //    if (empty($address_name)) {
    //        if ($row['civicrm_contact_custom_contact_type'] === 'Organization') {
    //            $address_name = $row['civicrm_contact_custom_organization_name'];
    //        } else {
    //            $address_name = trim($row['civicrm_contact_custom_first_name'] . ' ' . $row['civicrm_contact_custom_last_name']);
    //        }
    //    }
    //    return $address_name;
    //}

    /**
     * Modify column headers.
     */
    public function modifyColumnHeaders()
    {
        // Re-order the columns in a custom order defined below.
        $sortArray = [
            'civicrm_membership_row_number_count',
            'civicrm_value_dati_gestiona_6_custom_11', // codice socio
            'civicrm_contact_sort_name',
            'civicrm_contact_birth_date',
            'civicrm_value_altri_dati_an_4_custom_7',
            'civicrm_value_dati_fiscali_5_custom_8',
        ];
        // Only re-order selected columns.
        $sortArray = array_flip(array_intersect_key(array_flip($sortArray), $this->_columnHeaders));

        // Re-ordering.
        $this->_columnHeaders = array_merge(array_flip($sortArray), $this->_columnHeaders);
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
}
