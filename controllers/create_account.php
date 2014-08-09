<?php

/**
 * Create account controller.
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
        $this->lang->load('base');
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
        $this->form_validation->set_policy('mailer', 'registration/Registration', 'validate_mailer', FALSE);
        $this->form_validation->set_policy('mailer', 'registration/Registration', 'validate_interest_new_release', FALSE);
        $this->form_validation->set_policy('mailer', 'registration/Registration', 'validate_interest_new_apps', FALSE);
        $this->form_validation->set_policy('mailer', 'registration/Registration', 'validate_interest_betas', FALSE);
        $this->form_validation->set_policy('mailer', 'registration/Registration', 'validate_interest_promotions', FALSE);
        $form_ok = $this->form_validation->run();

        // Check for password match
        if ($form_ok) {
            if ($this->input->post('new_account_password') != $this->input->post('new_account_password_confirm')) {
                $form_ok = FALSE;
                $this->page->set_message(lang('base_password_and_verify_do_not_match'), 'warning');
            }
        }

        // Handle form submit
        //-------------------

        if (($this->input->post('create') && $form_ok)) {
            try {
                $interests = 0;
                if ((boolean)$this->input->post('interest_new_release'))
                    $interests += 1;
                if ((boolean)$this->input->post('interest_new_apps'))
                    $interests += 2;
                if ((boolean)$this->input->post('interest_betas'))
                    $interests += 4;
                if ((boolean)$this->input->post('interest_promotions'))
                    $interests += 8;
                $response = json_decode(
                    $this->registration->create_account(
                        $this->input->post('new_account_username'),
                        $this->input->post('new_account_password'),
                        $this->input->post('email'),
                        $this->input->post('country'),
                        (boolean)$this->input->post('mailer'),
                        $interests
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
    if ($this->input->post('create')) {
        $data['interest_new_release'] = (boolean)$this->input->post('interest_new_release');
        $data['interest_new_apps'] = (boolean)$this->input->post('interest_new_apps');
        $data['interest_betas'] = (boolean)$this->input->post('interest_betas');
        $data['interest_promotions'] = (boolean)$this->input->post('interest_promotions');
    } else {
        $data['interest_new_release'] = TRUE;
        $data['interest_new_apps'] = TRUE;
        $data['interest_betas'] = TRUE;
        $data['interest_promotions'] = TRUE;
    }
    $data['country_options'] = $this->registration->get_country_options();

        $this->page->view_form('registration/create_account', $data, lang('registration_registration'));
    }
}
