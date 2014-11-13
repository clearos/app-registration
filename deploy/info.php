<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'registration';
$app['version'] = '1.6.7';
$app['release'] = '1';
$app['vendor'] = 'ClearCenter';
$app['packager'] = 'ClearCenter';
$app['license'] = 'Proprietary';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('registration_app_description');
$app['tooltip'] = lang('registration_app_tooltip');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('registration_app_name');
$app['category'] = lang('base_category_system');
$app['subcategory'] = lang('base_subcategory_operating_system');

// Wizard extras
$app['controllers']['registration']['wizard_name'] = lang('registration_app_name');
$app['controllers']['registration']['wizard_description'] = lang('registration_app_description');
$app['controllers']['registration']['inline_help'] = array(
    lang('registration_registering') => lang('registration_registering_help'),
    lang('registration_creating_an_account') => lang('registration_creating_an_account_help'),
);

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_requires'] = array(
    'app-clearcenter-core => 1:1.4.8',
    'app-base-core => 1:1.6.5'
);

$app['core_file_manifest'] = array(
    'registration.conf' => array(
        'target' => '/etc/clearos/registration.conf',
        'mode' => '0644',
        'owner' => 'root',
        'group' => 'root',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
    'app-registration.cron' => array(
        'target' => '/etc/cron.d/app-registration',
        'mode' => '0644',
        'owner' => 'root',
        'group' => 'root',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
    'clearcenter-checkin' => array(
        'target' => '/usr/sbin/clearcenter-checkin',
        'mode' => '0755',
        'owner' => 'root',
        'group' => 'root',
    )
);
$app['core_directory_manifest'] = array(
    '/var/clearos/registration' => array('mode' => '755', 'owner' => 'root', 'group' => 'root')
);
