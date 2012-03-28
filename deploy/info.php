<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'registration';
$app['version'] = '1.0.10';
$app['release'] = '1';
$app['vendor'] = 'ClearCenter';
$app['packager'] = 'ClearCenter';
$app['license'] = 'Proprietary';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('registration_app_description');
$app['tooltip'] = lang('registration_app_tooltip');
$app['inline_help'] =  array(
    lang('registration_registering') => lang('registration_registering_help'),
    lang('registration_creating_an_account') => lang('registration_creating_an_account_help'),
);

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('registration_app_name');
$app['category'] = lang('base_category_system');
$app['subcategory'] = lang('base_subcategory_settings');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_requires'] = array(
    'app-clearcenter',
);

$app['core_file_manifest'] = array(
   'registration.conf' => array(
        'target' => '/etc/clearos/registration.conf',
        'mode' => '0644',
        'owner' => 'root',
        'group' => 'root',
        'config' => TRUE,
        'config_params' => 'noreplace',
    )
);
$app['core_directory_manifest'] = array(
   '/var/clearos/registration' => array('mode' => '755', 'owner' => 'root', 'group' => 'root')
);
