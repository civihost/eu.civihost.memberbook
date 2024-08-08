<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
return [
  [
    'name' => 'CRM_Memberbook_Form_Report_MemberbookMembers',
    'entity' => 'ReportTemplate',
    'params' => [
      'version' => 3,
      'label' => 'MemberbookMembers',
      'description' => 'MemberbookMembers (eu.civihost.memberbook)',
      'class_name' => 'CRM_Memberbook_Form_Report_MemberbookMembers',
      'report_url' => 'memberbook-members',
      'component' => 'CiviMember',
    ],
  ],
];
