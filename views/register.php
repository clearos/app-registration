<?php

/**
 * Registration register view.
 *
 * @category   apps
 * @package    registration
 * @subpackage views
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
// Warning box
///////////////////////////////////////////////////////////////////////////////

echo "<div id='registration_warning_box'" . (isset($errmsg) ? "" : " style='display: none'") . ">";

echo infobox_warning(
    lang('base_warning'),
    "<div id='registration_warning'>" . (isset($errmsg) ? $errmsg : "") . "</div>"
);

echo "</div>";

///////////////////////////////////////////////////////////////////////////////
// Form handler
///////////////////////////////////////////////////////////////////////////////
// form_submit_custom('reset', lang('base_reset')), 

$buttons = array(
    form_submit_custom('register', lang('registration_register_system'), 'high', array('id' => 'register')), 
    form_submit_custom('refresh', lang('registration_refresh_form'), 'high', array('id' => 'refresh')), 
);

// Don't show "create account" if one was just created
if (! $this->session->userdata('account_created'))
    $buttons[] = anchor_custom('/app/registration/create_account', lang('registration_create_account'), 'low');

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////
// TODO: merge custom CSS

echo form_open('registration/register', array('autocomplete' => 'off'));
echo form_header(lang('registration_registration'));

echo field_input('sdn_form_username', $sdn_form_username, lang('registration_account') . ($vendor ? ' (' . $vendor . ')' : ''), FALSE);
echo field_password('sdn_form_password', $sdn_form_password, lang('base_password'), FALSE);
echo field_dropdown('registration_type', $registration_type_options, $registration_type, lang('registration_type'), FALSE);
echo field_dropdown('system', $system_options, 0, lang('registration_system_list'), FALSE);
echo field_dropdown('subscription', $subscription_options, 0, lang('registration_subscription_list'), FALSE);
echo field_input('system_name', $system_name, lang('registration_system_name'), FALSE);
echo field_dropdown('environment', $environment_options, $environment, lang('registration_environment'), FALSE);
echo field_info('terms_of_service', 
    lang('registration_terms_of_service'), 
    sprintf(lang('registration_terms_of_service_blurb'),
    '<b>' . lang('registration_register_system') . '</b>', 
    '<a href=\'#\' class=\'view_tos highlight-link\'>' . lang('registration_terms_of_service') . '</a>')
);

echo field_button_set($buttons);

echo form_footer();
echo form_close();
