<?php

/**
 * Registration controller.
 *
 * @category   Apps
 * @package    Registration
 * @subpackage Controllers
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
 * @category   Apps
 * @package    Registration
 * @subpackage Controllers
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

    function index($reset = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->load->library('registration/Registration');
        $this->lang->load('registration');
        $data = array();
        if ($reset != null)
            $data['reset'] = TRUE;

        $this->page->view_form('registration/summary', $data, lang('registration_registration'));
    }

    /**
     * Register recontroller
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
                        $this->input->post('subscription')
                    )
                );
                if ($response->code == 0) {
                    redirect('/registration');
                } else {
                    $this->page->set_message($response->errmsg);
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
        $this->page->set_message(lang('registration_reset') . '<span style=\'padding: 5 0 5 10;\'>' . anchor_ok('/app/registration') . '</span>', 'info');
        $this->index(true);
    }

    /**
     * Redirects for wizard navigation.
     *
     * A helper for javascript for sending the "next" button to the 
     * next page in the wizard.
     *
     * @return redirect
     */

    function wizard_redirect()
    {
        redirect($this->session->userdata('wizard_redirect'));
    }
}
