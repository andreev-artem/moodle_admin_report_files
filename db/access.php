<?php

$report_files_capabilities = array(

    'report/files:view' => array(
        'riskbitmask' => RISK_DATALOSS | RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'admin' => CAP_ALLOW,
        ),
        
        'clonepermissionsfrom' => 'moodle/site:viewreports',
        
    )
);
