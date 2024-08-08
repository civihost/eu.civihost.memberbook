<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
return [
  [
    'name' => 'CRM_Memberbook_Form_Report_MemberbookContributions',
    'entity' => 'ReportTemplate',
    'params' => [
      'version' => 3,
      'label' => 'MemberbookContributions',
      'description' => 'MemberbookContributions (eu.civihost.memberbook)',
      'class_name' => 'CRM_Memberbook_Form_Report_MemberbookContributions',
      'report_url' => 'memberbook-contributions',
      'component' => 'CiviMember',
    ],
  ],
];
