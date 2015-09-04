<?php

/**
 * Registration updating libary in progress view.
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
// Infobox
///////////////////////////////////////////////////////////////////////////////

$options['buttons']  = array(
    anchor_custom('/app/registration/abort_update', lang('registration_abort_update'), 'high')
);
echo infobox_info(
    lang('registration_updating'),
    loading('normal', lang('base_loading_software_update')),
    $options
);

