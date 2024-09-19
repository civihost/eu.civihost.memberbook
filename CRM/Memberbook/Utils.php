<?php

use CRM_Memberbook_ExtensionUtil as E;


class CRM_Memberbook_Utils
{
    public static function getCustomFields($extends = []): array
    {
        if (count($extends) == 0) {
            $extends = ['Contact', 'Individual', 'Organization', 'Household', 'Contribution', 'Membership'];
        }
        $options = array('' => E::ts('None'));
        $customGroups = self::getCustomGroups($extends);

        if (!empty($customGroups)) {
            $customFields = \Civi\Api4\CustomField::get(TRUE)
                ->addSelect('id', 'label', 'column_name', 'custom_group_id', 'name')
                ->addWhere('custom_group_id', 'IN', array_keys($customGroups))
                ->addWhere('is_active', '=', TRUE)
                ->execute();
            foreach ($customFields as $customField) {
                $customGroup = $customGroups[$customField['custom_group_id']];
                $options[
                    $customField['id'] . '|' .
                    $customGroup['extends'] . '|' .
                    $customGroup['table_name'] . '|' .
                    $customField['column_name'] . '|' .
                    $customGroup['name'] . '|' .
                    $customField['name']
                ] = $customGroup['title'] . ' (' . $customGroup['extends'] . ') - ' . $customField['label'];
                //$options[$customGroup['id']] = $customGroup['title'] . ' (' . $customGroup['extends'] . ') - ' . $customField['label'];
            }
        }

        return $options;
    }

    public static function getContactCustomFields($extends = []): array
    {
        return self::getCustomFields(['Contact', 'Individual', 'Organization', 'Household']);
    }

    protected static function getCustomGroups($extends): array
    {
        $groups = [];
        $customGroups = \Civi\Api4\CustomGroup::get(FALSE)
            ->addSelect('id', 'table_name', 'extends', 'title', 'name')
            ->addWhere('extends', 'IN', $extends)
            ->addWhere('is_active', '=', TRUE)
            ->execute();
        foreach ($customGroups as $customGroup) {
            $groups[$customGroup['id']] = $customGroup;
        }

        return $groups;
    }

    public static function getSettingCustomField($name)
    {
        $value = \Civi::settings()->get($name);
        if (!$value) {
            return null;
        }
        if (strpos($value, '|') !== FALSE) {
            $a = explode('|', $value);
            $value = [
                'id' => $a[0],
                'extends' => $a[1],
                'table_name' => $a[2],
                'column_name' => $a[3],
                'group_name' => $a[4],
                'name' => $a[5],
            ];
        }
        return $value;
    }
}
