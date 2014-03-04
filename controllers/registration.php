<?php

/**
 * Registration controller.
 *
 * @category   apps
 * @package    registration
 * @subpackage controllers
 * @author     ClearCenter <developer@clearcenter.com>
 * @copyright  2011 ClearCenter
 * @license    http://www.clearcenter.com/app_license ClearCenter license
 * @link       http://www.clearcenter.com/support/documentation/clearos/registration/
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

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
 * @copyright  2011 ClearCenter
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
        $this->lang->load('registration');
        $data = array();

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
        $this->load->library('base/Product');
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
        $validate_system_name = ($this->input->post('validate_system_name')) === FALSE ? FALSE : TRUE;
        $this->form_validation->set_policy('system_name', 'registration/Registration', 'validate_system_name', $validate_system_name);
        $validate_system = ($this->input->post('validate_system')) === FALSE ? FALSE : TRUE;
        $this->form_validation->set_policy('system', 'registration/Registration', 'validate_system', $validate_system);
        $validate_subscription = ($this->input->post('validate_subscription')) === FALSE ? FALSE : TRUE;
        $this->form_validation->set_policy('subscription', 'registration/Registration', 'validate_subscription', $validate_subscription);
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
                        $this->input->post('environment')
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
        $data['environment_options'] = array(
            0 => lang('base_select'),
            'home' => lang('registration_home'),
            'soho' => lang('registration_soho'),
            'smb' => lang('registration_smb'),
            'business' => lang('registration_business'),
            'smb_multi' => lang('registration_smb_multi'),
            'edu' => lang('registration_edu'),
            'nfp' => lang('registration_nfp'),
            'gov' => lang('registration_gov'),
            'other' => lang('registration_other')
        );

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
}
