<?php

$capabilities = array(

    'repository/elis_files:view' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'user' => CAP_ALLOW
        )
    ),
    'repository/elis_files:createsitecontent' => array(

        'riskbitmask' => RISK_XSS| RISK_DATALOSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'manager'=> CAP_ALLOW
        )
    ),

    'repository/elis_files:viewsitecontent' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'manager'=> CAP_ALLOW
        )
    ),

    'repository/elis_files:createsharedcontent' => array(

        'riskbitmask' => RISK_XSS| RISK_DATALOSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager'=> CAP_ALLOW
        )
    ),

    'repository/elis_files:viewsharedcontent' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager'=> CAP_ALLOW
        )
    ),


    'repository/elis_files:createcoursecontent' => array(

        'riskbitmask' => RISK_XSS| RISK_DATALOSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager'=> CAP_ALLOW
        )
    ),

    'repository/elis_files:viewcoursecontent' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager'=> CAP_ALLOW
        )
    ),

    'repository/elis_files:createowncontent' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager'=> CAP_ALLOW
        )
    ),

    'repository/elis_files:viewowncontent' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager'=> CAP_ALLOW
        )
    ),

    'repository/elis_files:createusersetcontent' => array(

        'riskbitmask' => RISK_XSS| RISK_DATALOSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'manager'=> CAP_ALLOW
        )
    ),

    'repository/elis_files:viewusersetcontent' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'manager'=> CAP_ALLOW
        )
    )
);
