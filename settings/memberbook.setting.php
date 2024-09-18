<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

use CRM_Memberbook_ExtensionUtil as E;

return [
    'memberbook_code_customfield' => [
        'name' => 'memberbook_code_customfield',
        'type' => 'String',
        'html_type' => 'select',
        'default' => NULL,
        'required' => TRUE,
        'is_domain' => 1,
        'is_contact' => 0,
        'title' => E::ts('Member code custom field'),
        'description' => E::ts(
            'The member code is mandatory for Italian Member Book and usually is a custom field added to Membership or Contribution entity. ' .
            'If you choose the flag "Considers only contributions for one year" the member code should be added to Contribution entity and ' .
            'will be automatically updated by the job according to the receipt date of contribution.'
        ),
        'html_attributes' => [],
        'pseudoconstant' => [
            'callback' => 'CRM_Memberbook_Utils::getCustomFields',
        ],
        'settings_pages' => [
            'memberbook' => [
                'weight' => 10,
            ]
        ],
    ],
    'memberbook_code_label' => [
        'name' => 'memberbook_code_label',
        'type' => 'String',
        'html_type' => 'text',
        'default' => 'Codice socio',
        'required' => TRUE,
        'is_domain' => 1,
        'is_contact' => 0,
        'title' => E::ts('Member code label'),
        'html_attributes' => [],
        'settings_pages' => [
            'memberbook' => [
                'weight' => 15,
            ]
        ],
    ],
    'memberbook_ssn_customfield' => [
        'name' => 'memberbook_ssn_customfield',
        'type' => 'String',
        'html_type' => 'select',
        'default' => NULL,
        'required' => TRUE,
        'is_domain' => 1,
        'is_contact' => 0,
        'title' => E::ts('Member fiscal code field'),
        'html_attributes' => [],
        'pseudoconstant' => [
            'callback' => 'CRM_Memberbook_Utils::getContactCustomFields',
        ],
        'settings_pages' => [
            'memberbook' => [
                'weight' => 20,
            ]
        ],
    ],
    'memberbook_vat_customfield' => [
        'name' => 'memberbook_vat_customfield',
        'type' => 'String',
        'html_type' => 'select',
        'default' => NULL,
        'required' => TRUE,
        'is_domain' => 1,
        'is_contact' => 0,
        'title' => E::ts('Member VAT number field'),
        'html_attributes' => [],
        'pseudoconstant' => [
            'callback' => 'CRM_Memberbook_Utils::getContactCustomFields',
        ],
        'settings_pages' => [
            'memberbook' => [
                'weight' => 30,
            ]
        ],
    ],
    'memberbook_consider_one_year' => [
        'name' => 'memberbook_consider_one_year',
        'type' => 'Boolean',
        'default' => 0,
        'html_type' => 'checkbox',
        'title' => E::ts('Consider only contributions for one year for totals columns in the report'),
        'description' => E::ts('This option is useful for italian Associations because the Member Book must only refer to one year\'s membership contributions. In this case, we suggest adding the member code custom field in the Contribution entity.'),
        'is_domain' => 1,
        'is_contact' => 0,
        'settings_pages' => [
            'memberbook' => [
                'weight' => 40
            ]
        ],
    ],
    'memberbook_shares_label' => [
        'name' => 'memberbook_shares_label',
        'type' => 'String',
        'html_type' => 'text',
        'default' => 'Numero quote',
        'required' => TRUE,
        'is_domain' => 1,
        'is_contact' => 0,
        'title' => E::ts('Number of shares label'),
        'html_attributes' => [],
        'settings_pages' => [
            'memberbook' => [
                'weight' => 45,
            ]
        ],
    ],
    'memberbook_total_subscribed_label' => [
        'name' => 'memberbook_total_subscribed_label',
        'type' => 'String',
        'html_type' => 'text',
        'default' => 'Capitale sottoscritto',
        'required' => TRUE,
        'is_domain' => 1,
        'is_contact' => 0,
        'title' => E::ts('Total subscribed label'),
        'html_attributes' => [],
        'settings_pages' => [
            'memberbook' => [
                'weight' => 50,
            ]
        ],
    ],
    'memberbook_total_paid_label' => [
        'name' => 'memberbook_total_paid_label',
        'type' => 'String',
        'html_type' => 'text',
        'default' => 'Capitale versato',
        'required' => TRUE,
        'is_domain' => 1,
        'is_contact' => 0,
        'title' => E::ts('Total paid label'),
        'html_attributes' => [],
        'settings_pages' => [
            'memberbook' => [
                'weight' => 60,
            ]
        ],
    ],
    'memberbook_receipt_date_label' => [
        'name' => 'memberbook_receipt_date_label',
        'type' => 'String',
        'html_type' => 'text',
        'required' => FALSE,
        'is_domain' => 1,
        'is_contact' => 0,
        'title' => E::ts('Receipt date label'),
        'description' => E::ts('If you choose the flag "Considers only contributions for one year" you can change the label of Receipt Date in the report'),
        'html_attributes' => [],
        'settings_pages' => [
            'memberbook' => [
                'weight' => 70,
            ]
        ],
    ],
];
