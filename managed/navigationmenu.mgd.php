<?php

use CRM_Mjwshared_ExtensionUtil as E;

return [
  [
    'name' => 'memberbook_settings',
    'entity' => 'Navigation',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'label' => E::ts('Memberbook Settings'),
        'name' => 'memberbook_settings',
        'url' => 'civicrm/admin/setting/memberbook',
        'permission' => ['administer CiviCRM'],
        'permission_operator' => 'OR',
        'parent_id.name' => 'CiviMember',
        'is_active' => TRUE,
        'has_separator' => 0,
        'weight' => 90,
      ],
      'match' => ['name'],
    ],
  ],
];
