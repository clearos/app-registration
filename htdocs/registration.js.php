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

    // Wizard previous/next button handling
    //-------------------------------------

    $('#wizard_nav_next').on('click', function(e) {
        if ($('#wizard_next_showstopper').length == 0) {
            // Allow to go to next step
        } else {
            e.preventDefault();
            clearos_modal_infobox_open('wizard_next_showstopper');
        }
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
        $('.theme-validation-error').hide();
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

    var info = '<div id=\'registration-subscription-details\'>' +
        '<h3>" . lang('registration_subscription_details') . "</h3>' +
        '<div class=\'row theme-registration-detail\'>' +
        '    <div class=\'col-lg-4 theme-field\'>" . lang('base_description') . "</div>' +
        '    <div class=\'col-lg-8\'>' + my_subscriptions[$('#subscription').val()].description + '</div>' +
        '</div>' +
        '<div class=\'row theme-registration-detail\'>' +
        '    <div class=\'col-lg-4 theme-field\'>" . lang('registration_serial_number') . "</div>' +
        '    <div class=\'col-lg-8\'>' + my_subscriptions[$('#subscription').val()].serial_number + '</div>' +
        '</div>' +
        '<div class=\'row theme-registration-detail\'>' +
        '    <div class=\'col-lg-4 theme-field\'>" . lang('registration_expiry') . "</div>' +
        '    <div class=\'col-lg-8\'>' + $.datepicker.formatDate('MM d, yy', new Date(my_subscriptions[$('#subscription').val()].expire)) + '</div>' +
        '</div>' +
        (my_subscriptions[$('#subscription').val()].evaluation == false ? '' :
        '<div class=\'row theme-registration-detail\'>' +
        '    <div class=\'col-lg-4 theme-field\'>" . lang('registration_type') . "</div>' +
        '    <div class=\'col-lg-8\'>" . lang('registration_evaluation') . "</div>' +
        '</div>') +
        '</div>'
    ;
    if ($('#inline-help-hook').length != 0)
       $('#inline-help-hook').html(info);
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
            if (xhr['abort'] == undefined) {
                var options = new Object();
                options.type = 'warning';
                clearos_dialog_box('data_err1', '" . lang('base_warning') . "', xhr.responseText.toString(), options);
            }
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

    var sys_options = new Object();
    sys_options.id = 'loading-systems';
    sys_options.form_control = true;
    sys_options.text = '" . lang('registration_get_system_list') . "';
    $('#system').after(clearos_loading(sys_options));

    var sub_options = new Object();
    sub_options.id = 'loading-subscriptions';
    sub_options.form_control = true;
    sub_options.text = '" . lang('registration_get_subscription_list') . "';
    $('#subscription').after(clearos_loading(sub_options));
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: '/app/registration/ajax/get_registration_info',
        data: 'ci_csrf_token=' + $.cookie('ci_csrf_token') + '&username=' + $('#sdn_form_username').val() + '&password=' + $('#sdn_form_password').val(),
        success: function(data) {
            if (data.code > 0) {
                $('#loading-systems').html(data.errmsg);
                $('#loading-subscriptions').html(data.errmsg);
                // Code 4 is auth error...display some additional help RE: ClearCenter vs. ClearFoundation
                if (data.code == 4 && data.help != undefined) {
                    var options = new Object();
                    options.type = 'warning';
                    clearos_dialog_box('auth_err2', '" . lang('base_warning') . "', data.help, options);
                }
                reg_info_ok = false;
                return;
            } else if (data.code < 0) {
                var options = new Object();
                options.type = 'warning';
                clearos_dialog_box('data_err3', '" . lang('base_warning') . "', data.errmsg, options);
                reg_info_ok = false;
                return;
            }

            $('#loading-systems').remove();
            $('#system').show();
            $('#loading-subscriptions').remove();
            $('#subscription').show();

            my_systems = [];
            my_subscriptions = [];
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
                var options = new Object();
                options.type = 'warning';
                clearos_dialog_box('data_err4', '" . lang('base_warning') . "', xhr.responseText.toString(), options);
            }
        }
    });
}

function get_system_info() {
    if (!internet_connection) {
        var options = new Object();
        options.type = 'warning';
        clearos_dialog_box('data_err5', '" . lang('base_warning') . "', '" . lang('registration_offline') . "', options);
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
                var options = new Object();
                options.type = 'warning';
                clearos_dialog_box('data_err6', '" . lang('base_warning') . "', data.errmsg, options);
                return;
            } else if (data.code < 0) {
                $('#registration_loading_box').hide();
                var options = new Object();
                options.type = 'warning';
                clearos_dialog_box('data_err7', '" . lang('base_warning') . "', data.errmsg, options);
                return;
            }
            $('#system_name_text').html(data.system_name);
            if (data.reseller != undefined) {
                $('#reseller_field').show();
                $('#reseller_text').html(data.reseller);
            } else {
                $('#reseller_field').hide();
            }
            $('#wizard_next_showstopper').remove();
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
                var options = new Object();
                options.type = 'warning';
                clearos_dialog_box('data_err8', '" . lang('base_warning') . "', xhr.responseText.toString(), options);
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
    $('.theme-validation-error').html('');
    $('#checking_username').remove();
    var options = new Object();
    options.text = '" . lang('registration_checking_username_availability') . "';
    options.id = 'checking_username';
    $('#new_account_username').after(clearos_loading(options));
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: '/app/registration/ajax/check_username_availability',
        data: 'ci_csrf_token=' + $.cookie('ci_csrf_token') + '&username=' + username,
        success: function(data) {
            if (data.code == 0) {
                $('#new_account_username').show();
                $('#checking_username').html('');
            } else if (data.code > 0) {
                $('#new_account_username').show();
                $('#checking_username').html('" . lang('registration_non_unique_username') . "');
                $('#checking_username').addClass('theme-validation-error');
            } else if (data.code < 0) {
                clearos_dialog_box('errmsg', '" . lang('base_warning') . "', data.errmsg);
            }
        },
        error: function(xhr, text, err) {
            // Don't display any errors if ajax request was aborted due to page redirect/reload
            if (xhr['abort'] == undefined) {
                var options = new Object();
                options.type = 'warning';
                clearos_dialog_box('data_err9', '" . lang('base_warning') . "', xhr.responseText.toString(), options);
            }
        }
    });
}

function sdn_terms_of_service() {
    clearos_modal_infobox_open('sdn_tos');
    $.ajax({
        type: 'GET',
        dataType: 'json',
        url: '/app/registration/ajax/terms_of_service',
        success: function(data) {
            if (data.code == 0) {
                $('#tos_content').html(data.text);
            } else {
                clearos_modal_infobox_close('sdn_tos');
                clearos_dialog_box('errmsg', '" . lang('base_warning') . "', data.errmsg);
            }
        },
        error: function(xhr, text, err) {
            clearos_modal_infobox_close('sdn_tos');
            // Don't display any errors if ajax request was aborted due to page redirect/reload
            clearos_dialog_box('errmsg', '" . lang('base_warning') . "', xhr.responseText.toString());
        }
    });
}

function show_extras(payload) {
    // Wizard: enable next button 
    if (payload.complete) {
        $('#registration_extras_details').html('Installation complete'); // FIXME translate
    } else {
        $('#registration_extras_details').html(clearos_loading('normal', payload.details));
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
