<?php

/**
 * Javascript helper for Registration.
 *
 * @category   apps
 * @package    registration
 * @subpackage javascript
 * @author     ClearCenter <developer@clearcenter.com>
 * @copyright  2011 ClearCenter
 * @license    http://www.clearcenter.com/app_license ClearCenter license
 * @link       http://www.clearcenter.com/support/documentation/clearos/registration/
 */

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('registration');
clearos_load_language('base');

///////////////////////////////////////////////////////////////////////////////
// J A V A S C R I P T
///////////////////////////////////////////////////////////////////////////////

header('Content-Type: application/x-javascript');

echo "
var reg_info_ok = false;
var reg_default_name = '" . lang('registration_my_server') . "';
var my_systems = new Array();
var my_subscriptions = new Array();
var eval_request = -1;
var purchase_request = -2;

$(document).ready(function() {
    // Default fields to hide
    //-----------------------

    $('#subscription_field').hide();

    $('#registration_loading_box').show();
    $('#registration_summary').hide();

    reg_default_name = $('#system_name').val();
    if ($('#registration_type').val() == 0)
        $('#system_field').hide();

    $('#theme_wizard_nav_next').hide();
    $('#wizard_nav_next').click(function() {
        window.location = '/app/base/wizard/next_step';
        return;
    });

    // Get SDN and Registration info
    //------------------------------

    get_sdn_info();
    get_registration_info();

    // Form change events
    //-------------------

    $('#registration_type').change(function(event) {
        if ($('#registration_type').val() > 0) {
            $('#system_field').show();
            $('#validate_system_name').remove();
            $('#system').after('<input type=\'hidden\' id=\'validate_system\' name=\'validate_system\' value=\'1\'>');
        } else {
            $('#system_field').hide();
            $('#system_name').after('<input type=\'hidden\' id=\'validate_system_name\' name=\'validate_system_name\' value=\'1\'>');
            $('#validate_system').remove();
        }
        get_registration_info();
        check_system_info();
    });

    $('#email').css('width', 250);

    toggle_mailer();

    $('#mailer').change(function(event) {
        toggle_mailer();
    });

    $('#subscription').change(function(event) {
        if ($('#subscription').val() == 0) {
            $('#subscription_details').remove();
        } else if ($('#subscription').val() == eval_request) {
            $('#subscription_details').remove();
            window.open(my_subscriptions[$('#subscription').val()].url);
            return true;
        } else if ($('#subscription').val() == purchase_request) {
            $('#subscription_details').remove();
            window.open(my_subscriptions[$('#subscription').val()].url);
            return true;
        } else {
            // Dump info about subscription
            display_subscription_info();
        }
    });

    $('#new_account_username').blur(function(event) {
        check_username_availability();
    });

    $('#sdn_form_username').blur(function(event) {
        get_registration_info();
    });

    $('#sdn_form_password').blur(function(event) {
        get_registration_info();
    });

    $('#system').change(function(event) {
        check_system_info();
    });

    $('a.view_tos').click(function (e) {
        e.preventDefault();
        sdn_terms_of_service();
    });

    $('#refresh').click(function (e) {
        e.preventDefault();
        $('#registration_warning_box').hide();
        $('.theme-validation-error').hide();
        if ($('.theme-validation-error').prev()[0].localName.toLowerCase() == 'br' ) 
            $('.theme-validation-error').prev().remove();
        get_registration_info();
    });
});

/**
 * Sets up mailer dropdown and interest group display.
 *
 * @return NULL
 */

function toggle_mailer() {
    if ($('#mailer').val() == 0) {
        $('#interest_new_release_field').hide();
        $('#interest_new_apps_field').hide();
        $('#interest_betas_field').hide();
        $('#interest_promotions_field').hide();
    } else {
        $('#interest_new_release_field').show();
        $('#interest_new_apps_field').show();
        $('#interest_betas_field').show();
        $('#interest_promotions_field').show();
    }
}

/**
 * Displays subcription info.
 *
 * @return JSON SDN information
 */

function display_subscription_info() {
    $('#subscription_details').remove();
    if ($('#subscription').val() == 0)
        return;
    // If sidebar_summary does not exist, use wizard container
    if ($('#sidebar_summary_table').length == 0)
       $('#theme-sidebar-container').append('<div id=\'subscription_details\'</div>');
    else
       $('#sidebar_summary_table').parent().append('<div id=\'subscription_details\'</div>');

    $('#subscription_details').html('<h3>" . lang('registration_subscription_details') . "</h3>' +
    '<table width=\'100%\'>' +
    '<tr>' +
    '<td width=\'65\' valign=\'top\'><b>" . lang('base_description') . "</b></td>' +
    '<td>' + my_subscriptions[$('#subscription').val()].description + '</td>' +
    '</tr>' +
    '<tr>' +
    '<td valign=\'top\'><b>" . lang('registration_serial_number') . "</b></td>' +
    '<td>' + my_subscriptions[$('#subscription').val()].serial_number + '</td>' +
    '</tr>' +
    '<tr>' +
    '<td valign=\'top\'><b>" . lang('registration_expiry') . "</b></td>' +
    '<td>' + $.datepicker.formatDate('MM d, yy', new Date(my_subscriptions[$('#subscription').val()].expire)) + '</td>' +
    '</tr>' + (my_subscriptions[$('#subscription').val()].evaluation == false ? '' : 
    '<tr>' +
    '<td valign=\'top\'><b>" . lang('registration_type') . "</b></td>' +
    '<td>" . lang('registration_evaluation') . "</td>' +
    '</tr>')
    );
}

/**
 * Returns information from the SDN.
 *
 * @return JSON SDN information
 */

function get_sdn_info() {
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: '/app/registration/ajax/get_sdn_info',
        data: 'ci_csrf_token=' + $.cookie('ci_csrf_token'),
        success: function(data) {
            // Check to see if it's registered already
            if (data.device_id != undefined && data.device_id < 0) {
                if ($(location).attr('href').match('.*registration$') != null) {
                    window.location = '/app/registration/register';
                    return;
                }
            }

            if ($(location).attr('href').match('.*registration\/register($|.*$)') != null) {
                // Add SDN organization to differentiate the expected account information
                if (data.sdn_org != undefined) {
                    $('#sdn_form_username_label').html('" . lang('registration_account') . " (' + data.sdn_org + ')');
                    $('#register').val('" . lang('registration_register_system') . "');
                }
                if (data.supported != undefined && data.supported) {
                    $('#subscription_field').show();
                    $('#subscription').after('<input type=\'hidden\' id=\'validate_subscription\' name=\'validate_subscription\' value=\'1\'>');
                } else {
                    $('#subscription_field').hide();
                    $('#validate_subscription').remove();
                }
            } else if ($(location).attr('href').match('.*registration$') != null) {
                get_system_info();
            }
        },
        error: function(xhr, text, err) {
            // Don't display any errors if ajax request was aborted due to page redirect/reload
            if (xhr['abort'] == undefined)
                clearos_dialog_box('error', '" . lang('base_warning') . "', xhr.responseText.toString());
        }
    });
}

/**
 * Registration info.
 */

function get_registration_info() {
    if ($('#sdn_form_username').val() == undefined || $('#sdn_form_username').val() == '' || $('#sdn_form_password').val() == '')
        return;

    $('#system').hide();
    $('#loading-systems').remove();
    $('#subscription').hide();
    $('#loading-subscriptions').remove();
    $('#system').after(
        '<div class=\'theme-loading-small\' id=\'loading-systems\' style=\'margin: 5px 0px 4px 0px; padding-top: 0px;\'>" . lang('registration_get_system_list') . "</div>'
    );
    $('#subscription').after(
        '<div class=\'theme-loading-small\' id=\'loading-subscriptions\' style=\'margin: 5px 0px 4px 0px; padding-top: 0px;\'>" . lang('registration_get_subscription_list') . "</div>'
    );
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: '/app/registration/ajax/get_registration_info',
        data: 'ci_csrf_token=' + $.cookie('ci_csrf_token') + '&username=' + $('#sdn_form_username').val() + '&password=' + $('#sdn_form_password').val(),
        success: function(data) {
            if (data.code > 0) {
                $('#loading-systems').html(data.errmsg);
                $('#loading-systems').removeClass('theme-loading-small');
                $('#loading-subscriptions').html(data.errmsg);
                $('#loading-subscriptions').removeClass('theme-loading-small');
                // Code 4 is auth error...display some additional help RE: ClearCenter vs. ClearFoundation
                if (data.code == 4 && data.help != undefined) {
                    $('#registration_warning_box').show();
                    $('#registration_warning').html(data.help);
                }
                reg_info_ok = false;
                return;
            } else if (data.code < 0) {
                $('#registration_warning_box').show();
                $('#registration_warning').html(data.errmsg);
                reg_info_ok = false;
                return;
            }

            $('#loading-systems').remove();
            $('#system').show();
            $('#loading-subscriptions').remove();
            $('#subscription').show();

            my_systems.length = 0;
            my_subscriptions.length = 0;
            for (index = 0; index < data.subscriptions.length; index++) {
                var description = data.subscriptions[index].description;
                if (data.subscriptions[index].id == eval_request)
                    description = '" . lang('registration_request_evaluation') . "'; 
                else if (data.subscriptions[index].id == purchase_request)
                    description = '" . lang('registration_request_purchase') . "'; 
                my_subscriptions[data.subscriptions[index].id] = {
                    serial_number: data.subscriptions[index].serial_number,
                    assigned: data.subscriptions[index].assigned,
                    expire: data.subscriptions[index].expire,
                    description: description,
                    purchased: data.subscriptions[index].purchased,
                    url: data.subscriptions[index].url,
                    evaluation: data.subscriptions[index].evaluation
                };
            }
            $('#system')
                .find('option')
                .remove()
                .end()
            ;
            // TODO - IE8 workaround
            //$('#system').append( new Option('" . lang('base_select') . "', 0));
            $('#system').append($('<option value=\"0\">" . lang('base_select') . "</option>'));
            for (index = 0; index < data.systems.length; index++) {
                my_systems[data.systems[index].id] = {
                    subscription_id: data.systems[index].subscription_id,
                    name: data.systems[index].name, supported: data.systems[index].supported
                };
                // TODO - IE8 workaround
                //$('#system').append( new Option(data.systems[index].name, data.systems[index].id));
                $('<option value=\"' + data.systems[index].id + '\">' + data.systems[index].name + '</option>').appendTo($('#system'));
            }
            reg_info_ok = true;
            check_system_info();
        },
        error: function(xhr, text, err) {
            // Don't display any errors if ajax request was aborted due to page redirect/reload
            if (xhr['abort'] == undefined) {
                $('#registration_warning_box').show();
                $('#registration_warning').html(xhr.responseText.toString());
            }
        }
    });
}

function get_system_info() {
    if (!internet_connection) {
        $('#registration_loading_box').hide();
        $('#registration_warning_box').show();
        $('#registration_warning').html('" . lang('registration_offline') . "');
        return;
    }

    $.ajax({
        type: 'GET',
        dataType: 'json',
        url: '/app/registration/ajax/get_system_info',
        success: function(data) {
            if (data.code > 0) {
                // Code 3 == not registered
                if (data.code == 3) {
                    window.location = '/app/registration/register';
                    return;
                }
                $('#registration_loading_box').hide();
                $('#registration_warning_box').show();
                $('#registration_warning').html(data.errmsg);
                return;
            } else if (data.code < 0) {
                $('#registration_loading_box').hide();
                $('#registration_warning_box').show();
                $('#registration_warning').html(data.errmsg);
                return;
            }
            $('#system_name_text').html(data.system_name);
            if (data.reseller != undefined) {
                $('#reseller_field').show();
                $('#reseller_text').html(data.reseller);
            } else {
                $('#reseller_field').hide();
            }
            $('#status_text').html('" . lang('registration_registered') . "');
            $('#account_text').html(data.account);
            $('#hostname_text').html(data.hostname);
            $('#hostkey_text').html(data.hostkey);
            $('#end_of_life_text').html($.datepicker.formatDate('MM d, yy', new Date(data.end_of_life)));
            if (data.support != undefined) {
                $('#support_field').show();
                $('#support_text').html(data.support);
                $('#support_renewal_field').show();
                $('#support_renewal_text').html(data.support_renewal);
                $('#support_renewal_text').html($.datepicker.formatDate('MM d, yy', new Date(data.support_renewal)));
            } else {
                $('#support_field').hide();
                $('#support_renewal_field').hide();
            }

            $('#registration_loading_box').hide();
            $('#registration_summary').show();

            // Check to see if Professional extras have been installed
            get_extras_state();
        },
        error: function(xhr, text, err) {
            // Don't display any errors if ajax request was aborted due to page redirect/reload
            if (xhr['abort'] == undefined) {
                $('#registration_warning_box').show();
                $('#registration_warning').html(xhr.responseText.toString());
            }
        }
    });
}

function check_system_info() {

    // Don't do any further checks if we haven't grabbed SDN info
    if (!reg_info_ok)
        return;

    if ($('#system').val() == 0) {
        $('#subscription').attr('disabled', false);
        $('#system_name').attr('disabled', false);
    }

    // If an upgrade/re-install, disable system name...it is inherited from previously registered system reg
    if ($('#registration_type').val() > 0) {
        //$('#system').show(); TODO - Need this?
        $('#system').attr('disabled', false);
        // Disable name field...it be inherited
        $('#system_name').attr('disabled', true);
        if ($('#system').val() == 0)
            $('#system_name').val('');
        else
            $('#system_name').val(my_systems[$('#system').val()].name);

        // If subscription req'd...list only ones that are unassigned if system upgrade does not already have a license
        if ($('#subscription_field').is(':visible')) {
            // Remove all options from list
            $('#subscription')
                .find('option')
                .remove()
                .end()
            ;
            if ($('#system').val() != 0 && my_systems[$('#system').val()].supported) {
                // Subscription is inherted from upgrade/reinstall

                // Add inherited
                // TODO - IE8 workaround
                //new Option(my_subscriptions[my_systems[$('#system').val()].subscription_id].description,
                //my_systems[$('#system').val()].subscription_id)
                $('#subscription').append(
                    $('<option value=\"' + my_systems[$('#system').val()].subscription_id  + '\">' +
                    my_subscriptions[my_systems[$('#system').val()].subscription_id].description  + '</option>')
                );

                // Update display
                display_subscription_info();
                // Exit function
                return;
            }
            // Add back 'Select' default
            $('#subscription option[value=\'0\']').remove();
            // TODO - IE8 workaround
            //$('#subscription').append( new Option('" . lang('base_select') . "', 0));
            $('#subscription').append($('<option value=\"0\">" . lang('base_select') . "</option>'));
            for (id in my_subscriptions) {
                // If no system has been selected, don't list any
                if ($('#system').val() == 0)
                    continue;
                if (!my_subscriptions[id].assigned) {
                    // TODO - IE8 workaround
                    //$('#subscription').append( new Option(my_subscriptions[id].description, id));
                    $('#subscription').append($('<option value=\"' + id + '\">' + my_subscriptions[id].description + '</option>'));
                }
            }
            $('#subscription').attr('disabled', false);
        } else {
            $('#subscription').attr('disabled', true);
        }
    } else {
        $('#system_name').val(reg_default_name);
        // If subscription req'd...list only ones that are unassigned
        if ($('#subscription_field').is(':visible')) {
            $('#subscription')
                .find('option')
                .remove()
                .end()
            ;
            // Add back 'Select' default
            $('#subscription option[value=\'0\']').remove();
            //$('#subscription').append( new Option('" . lang('base_select') . "', 0));
            $('#subscription').append($('<option value=\"0\">" . lang('base_select') . "</option>'));
            for (id in my_subscriptions) {
                if (!my_subscriptions[id].assigned) {
                    // Only tack on serial number identifier if subscription
                    // IE8 Workaround
                    //$('#subscription').append( new Option(my_subscriptions[id].description + ' (' + my_subscriptions[id].serial_number.substr(0, 4) + '...)', id));
                    if (id > 0)
                        $('#subscription').append($('<option value=\"' + id + '\">' + my_subscriptions[id].description +
                            ' (' + my_subscriptions[id].serial_number.substr(0, 4) + '...)</option>'));
                    else
                        $('#subscription').append($('<option value=\"' + id + '\">' + my_subscriptions[id].description + '</option>'));
                }
            }
        } else {
            $('#subscription').attr('disabled', true);
        }
        $('#system').hide();
        $('#system').attr('disabled', true);
        $('#system_name').attr('disabled', false);
        // See if we can save a step by preventing duplicate names which must be unique
        for (id in my_systems) {
            if ($('#system_name').val() == my_systems[id].name)
                $('#system_name').val(my_systems[id].name + '-' + Math.floor(Math.random() * 100));
        }
    }
    display_subscription_info();
}

function check_username_availability() {
    var username = $('#new_account_username').val();
    $('#new_account_username').hide();
    $('#checking_username').remove();
    $('#new_account_username').after('<div class=\'theme-loading-small\' id=\'checking_username\' style=\'padding-top: 0;\'>" . lang('registration_checking_username_availability') . "</div>');
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: '/app/registration/ajax/check_username_availability',
        data: 'ci_csrf_token=' + $.cookie('ci_csrf_token') + '&username=' + username,
        success: function(data) {
            if (data.code == 0) {
                $('#new_account_username').show();
                $('#checking_username').html('');
                $('#checking_username').removeClass('theme-loading-small');
                $('#checking_username').addClass('form-input-ok');
            } else if (data.code > 0) {
                $('#new_account_username').show();
                $('#checking_username').html('');
                $('#checking_username').removeClass('theme-loading-small');
                $('#checking_username').addClass('form-input-fail');
            } else if (data.code < 0) {
                clearos_dialog_box('errmsg', '" . lang('base_warning') . "', data.errmsg);
            }
        },
        error: function(xhr, text, err) {
            // Don't display any errors if ajax request was aborted due to page redirect/reload
            if (xhr['abort'] == undefined) {
                $('#registration_warning_box').show();
                $('#registration_warning').html(xhr.responseText.toString());
            }
        }
    });
}

function sdn_terms_of_service() {
    $('#sdn_terms_of_service_dialog').remove();
    $('#theme-page-container').append('<div id=\"sdn_terms_of_service_dialog\" title=\"" . lang('registration_terms_of_service') . "\">' +
        '<div id=\'tos-text\' style=\'margin-top: 100;\'>' +
        '  <div class=\'theme-loading-small\' id=\'tos-loading\'>" . lang('base_loading') . "</div>' +
        '</div>' +
      '</div>'
    );
    $('#sdn_terms_of_service_dialog').dialog({
        autoOpen: true,
        bgiframe: true,
        modal: true,
        resizable: false,
        draggable: false,
        closeOnEscape: false,
        hide: 'fade',
        height: 450,
        width: 800,
        buttons: {
            '" . lang('base_close') . "': function() {
                $(this).dialog('close');
            }
        }
    });
    $('.ui-dialog-titlebar-close').hide();
    $.ajax({
        type: 'GET',
        dataType: 'json',
        url: '/app/registration/ajax/terms_of_service',
        success: function(data) {
            if (data.code == 0) {
                $('#tos-loading').remove();
                $('#tos-text').css('margin-top', 10);
                $('#tos-text').css('text-align', 'left');
                $('#tos-text').html(data.text);
            } else {
                $('#sdn_terms_of_service_dialog').dialog('close');
                clearos_dialog_box('errmsg', '" . lang('base_warning') . "', data.errmsg);
            }
        },
        error: function(xhr, text, err) {
            $('#sdn_terms_of_service_dialog').dialog('close');
            // Don't display any errors if ajax request was aborted due to page redirect/reload
            clearos_dialog_box('errmsg', '" . lang('base_warning') . "', xhr.responseText.toString());
        }
    });
}

function show_extras(payload) {
    // Wizard: enable next button 
    if (payload.complete) {
        $('#theme_wizard_nav_next').show();
        $('#registration_extras_details').html('Installation complete'); // FIXME translate
    } else {
        $('#registration_extras_details').html('<span class=\'theme-loading-small\'>' + payload.details + '</span>');
        $('#theme_wizard_nav_next').hide();
        $('#registration_extras').show();
    }
}

function get_extras_state() {
    $.ajax({
        type: 'GET',
        dataType: 'json',
        url: '/app/registration/extras/handle_install',
        data: '',
        success: function(json) {
            show_extras(json);
            window.setTimeout(get_extras_state, 3000);
        },
        error: function(xhr, text, err) {
            window.setTimeout(get_extras_state, 3000);
        }
    });
}

";

// vim: syntax=javascript ts=4
