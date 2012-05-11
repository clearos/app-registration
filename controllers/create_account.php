<?php

/**
 * Create account controller.
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

class Create_Account extends ClearOS_Controller
{
    /**
     * Create Account default controller
     *
     * @return view
     */

    function index()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->lang->load('registration');
        $this->load->library('registration/Registration');

        $data['vendor'] = $this->session->userdata['sdn_org'];

        // Set validation rules
        //---------------------
         
        $check_username = ($this->input->post('new_account_username') === FALSE) ? FALSE : TRUE;
        if ($check_username)
            $this->form_validation->set_policy('new_account_username', 'registration/Registration', 'validate_sdn_username', TRUE);
        $this->form_validation->set_policy('new_account_password', 'registration/Registration', 'validate_sdn_password', TRUE);
        $this->form_validation->set_policy('new_account_password_confirm', 'registration/Registration', 'validate_sdn_password', TRUE);
        $this->form_validation->set_policy('email', 'registration/Registration', 'validate_email', TRUE);
        $this->form_validation->set_policy('country', 'registration/Registration', 'validate_country', TRUE);
        $form_ok = $this->form_validation->run();

        // Check for password match
        if ($form_ok) {
            if ($this->input->post('new_account_password') != $this->input->post('new_account_password_confirm')) {
                $form_ok = FALSE;
                $this->page->set_message(lang('registration_password_mismatch'), 'warning');
            }
        }
        // Handle form submit
        //-------------------

        if (($this->input->post('create') && $form_ok)) {
            try {
                $response = json_decode(
                    $this->registration->create_account(
                        $this->input->post('new_account_username'),
                        $this->input->post('new_account_password'),
                        $this->input->post('email'),
                        $this->input->post('country'),
                        (boolean)$this->input->post('mailer')
                    )
                );
                if ($response->code == 0) {
                    $this->page->set_message(lang('registration_account_created'), 'info');
                    $this->session->set_userdata('account_created', $this->input->post('new_account_username'));
                    redirect('/registration/register/' . $this->input->post('new_account_username'));
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

        $data['mailer'] = TRUE;
        $data['country'] = 'US';
        $data['country_options'] = $this->registration->get_country_options();

        $this->page->view_form('registration/create_account', $data, lang('registration_registration'));
    }
}
