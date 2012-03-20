<?php

/**
 * Registration create account view.
 *
 * @category   Apps
 * @package    Registration
 * @subpackage Views
 * @author     ClearCenter <developer@clearcenter.com>
 * @copyright  2011 ClearCenter
 * @license    http://www.clearcenter.com/Company/terms.html ClearSDN license
 * @link       http://www.clearcenter.com/support/documentation/clearos/registration/
 */

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('base');
$this->lang->load('registration');

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////
// anchor_custom('/app/registration/create_account', lang('base_reset'), 'low'),

$buttons = array(
    form_submit_custom('create', lang('registration_create_account'), 'high'), 
    anchor_cancel('/app/registration/register', 'low')
);

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open('registration/create_account', array('autocomplete' => 'off'));
echo form_header(lang('registration_create_account'));

///////////////////////////////////////////////////////////////////////////////
// Form fields and buttons
///////////////////////////////////////////////////////////////////////////////

echo field_input('new_account_username', $new_account_username, lang('registration_account_username'), FALSE);
echo field_password('new_account_password', $new_account_password, lang('base_password'), FALSE);
echo field_password('new_account_password_confirm', $new_account_password_confirm, lang('registration_password_confirm'), FALSE);
echo field_input('email', $email, lang('registration_email'), FALSE);
echo field_dropdown('country', $country_options, $country, lang('registration_country'), FALSE);
echo field_checkbox('mailer', $mailer, lang('registration_mailer'), FALSE);

echo field_button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
