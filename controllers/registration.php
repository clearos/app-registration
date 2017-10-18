<?php

/**
 * Registration controller.
 *
 * @category   apps
 * @package    registration
 * @subpackage controllers
 * @author     ClearCenter <developer@clearcenter.com>
 * @copyright  2011-2015 ClearCenter
 * @license    http://www.clearcenter.com/app_license ClearCenter license
 * @link       http://www.clearcenter.com/support/documentation/clearos/registration/
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\base\Install_Wizard as Install_Wizard;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Registration controller.
 *
 * @category   apps
 * @package    registration
 * @subpackage controllers
 * @author     ClearCenter <developer@clearcenter.com>
 * @copyright  2011-2015 ClearCenter
 * @license    http://www.clearcenter.com/app_license ClearCenter license
 * @link       http://www.clearcenter.com/support/documentation/clearos/registration/
 */

class Registration extends ClearOS_Controller
{
    /**
     * Registration default controller
     *
     * @return view
     */

    function index()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->load->library('registration/Registration');
        $this->load->library('base/Install_Wizard');
        $this->load->library('base/Script', Install_Wizard::SCRIPT_UPGRADE);
        $this->lang->load('registration');
        $data = array();

        if ($this->script->is_running()) {
            // If wizard update is running still, just put on hold
            redirect('/registration/updating');
            return;
        }
        $this->page->view_form('registration/summary', $data, lang('registration_registration'));
    }

    /**
     * Register controller
     *
     * @param String $username username
     *
     * @return view
     */

    function register($username = '')
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->lang->load('registration');
        $this->load->library('registration/Registration');
        $this->load->library('network/Hostname');

        $data['vendor'] = $this->session->userdata['sdn_org'];
        $data['system_name'] = ucfirst(preg_replace('/\..*/', '', strtolower($this->hostname->get())));

        if ($this->input->post('reset'))
            redirect('/registration');

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('sdn_form_username', 'registration/Registration', 'validate_sdn_username', TRUE);
        $this->form_validation->set_policy('sdn_form_password', 'registration/Registration', 'validate_sdn_password', TRUE);
        $this->form_validation->set_policy('registration_type', 'registration/Registration', 'validate_registration_type', TRUE);
        $validate_system_name = $this->input->post('system_name');
        if (!empty($validate_system_name))
            $this->form_validation->set_policy('system_name', 'registration/Registration', 'validate_system_name', TRUE);
        $validate_system = $this->input->post('system');
        if (!empty($validate_system))
            $this->form_validation->set_policy('system', 'registration/Registration', 'validate_system', TRUE);
        $validate_subscription = $this->input->post('subscription');
        if (!empty($validate_subscription))
            $this->form_validation->set_policy('subscription', 'registration/Registration', 'validate_subscription', TRUE);
        $this->form_validation->set_policy('environment', 'registration/Registration', 'validate_environment', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('register') && $form_ok)) {
            try {
                $response = json_decode(
                    $this->registration->register(
                        $this->input->post('sdn_form_username'),
                        $this->input->post('sdn_form_password'),
                        $this->input->post('registration_type'),
                        $this->input->post('system_name'),
                        $this->input->post('system'),
                        $this->input->post('subscription'),
                        $this->input->post('environment'),
                        $this->input->post('unit')
                    )
                );
                if ($response->code == 0) {
                    redirect('/registration');
                } else {
                    $data['errmsg'] = $response->errmsg;
                }
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        if (empty($username) && $this->session->userdata['account_created'])
            $data['sdn_form_username'] = $this->session->userdata['account_created'];
        else
            $data['sdn_form_username'] = $username;

        $data['registration_type_options'] = $this->registration->get_registration_options();
        $data['system_options'] = array(0 => lang('base_select'));
        $data['subscription_options'] = array(0 => lang('base_select'));
        $data['environment_options'] = $this->registration->get_environment_options();

        $this->page->view_form('registration/register', $data, lang('registration_registration'));
    }

    /**
     * Registration reset controller
     *
     * @return view
     */

    function reset()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->load->library('registration/Registration');
        clearos_load_language('registration');

        $this->registration->reset();
        $this->page->set_message(lang('registration_reset'), 'info');
        redirect('/registration/register');
    }

    /**
     * Registration updating controller
     *
     * @return view
     */

    function updating()
    {
        clearos_profile(__METHOD__, __LINE__);

        clearos_load_language('registration');

        $this->page->view_form('registration/updating_library', $data, lang('registration_updating'));
    }

    /**
     * Abort software update
     *
     * @return view
     */

    function abort_update()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->load->library('base/Install_Wizard');
        $this->install_wizard->abort_update_script();
        redirect('/registration');
    }
}
