<?php

/**
 * @file
 * This file declares a managed database record of type "Job".
 */

return [
    0 =>
    [
        'name' => 'AssignMemberCode',
        'entity' => 'Job',
        'params' => [
            'version' => 3,
            'name' => 'MemberBook: Member Code Assignment',
            'description' => 'Calculate and assign the membership code to contributions',
            'run_frequency' => 'Daily',
            'api_entity' => 'Job',
            'api_action' => 'assign_member_code',
            'parameters' => '',
        ],
    ],
];
