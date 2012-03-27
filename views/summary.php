<?php

/**
 * Registration summary view.
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
// Infobox
///////////////////////////////////////////////////////////////////////////////

echo "<div id='registration_loading_box' style='display: none'>";

if (!$reset)
    echo infobox_highlight(
        lang('base_status'),
        "<div class='theme-loading-normal'>" . lang('registration_loading_registration_information') ."</div>"
    );

echo "</div>";

///////////////////////////////////////////////////////////////////////////////
// Warning box
///////////////////////////////////////////////////////////////////////////////

echo "<div id='registration_warning_box' style='display: none'>";

echo infobox_warning(
    lang('base_warning'),
    "<div id='registration_warning'></div>"
);

echo "</div>";

///////////////////////////////////////////////////////////////////////////////
// Summary form
///////////////////////////////////////////////////////////////////////////////

echo "<div id='registration_summary' style='display: none'>";

echo form_open('registration', array('autocomplete' => 'off'));
echo form_header(lang('registration_registration'));

echo field_input('status', '', lang('base_status'), TRUE);
echo field_input('reseller', '', lang('registration_reseller'), TRUE);
echo field_input('account', '', lang('base_account'), TRUE);
echo field_input('system_name', '', lang('registration_system_name'), TRUE);
echo field_input('hostname', '', lang('registration_hostname_ip'), TRUE);
echo field_input('hostkey', '', lang('registration_hostkey'), TRUE);
echo field_input('end_of_life', '', lang('registration_end_of_life'), TRUE);
echo field_input('support', '', lang('registration_support'), TRUE);
echo field_input('support_renewal', '', lang('registration_support_renewal'), TRUE);

echo form_footer();
echo form_close();

echo "</div>";