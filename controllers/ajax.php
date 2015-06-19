<?php

/**
 * AJAX controller for Registration.
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

use \clearos\apps\network\Network_Status as Network_Status;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * JSON controller.
 *
 * @category   apps
 * @package    registration
 * @subpackage controllers
 * @author     ClearCenter <developer@clearcenter.com>
 * @copyright  2011 ClearCenter
 * @license    http://www.clearcenter.com/app_license ClearCenter license
 * @link       http://www.clearcenter.com/support/documentation/clearos/registration/
 */

class Ajax extends ClearOS_Controller
{
    /**
     * Ajax default controller
     *
     * @return string
     */

    function index()
    {
        echo "These aren't the droids you're looking for...";
    }

    /**
     * Ajax get account information controller
     *
     * @return JSON
     */

    function get_account_info()
    {
        clearos_profile(__METHOD__, __LINE__);

        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');

        try {
            $this->load->library('registration/Registration');
            echo $this->registration->get_account_info($this->input->post('password')) ? $this->input->post('password') : NULL; 
        } catch (Exception $e) {
            echo json_encode(Array('code' => clearos_exception_code($e), 'errmsg' => clearos_exception_message($e)));
        }
    }

    /**
     * Ajax check for authentication controller
     *
     * @return JSON
     */

    function is_authenticated()
    {
        clearos_profile(__METHOD__, __LINE__);

        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');

        try {
            $this->load->library('registration/Registration');
            echo $this->registration->is_authenticated(
                ($this->input->post('username')) ? $this->input->post('username') : NULL,
                ($this->input->post('password')) ? $this->input->post('password') : NULL,
                ($this->input->post('email')) ? $this->input->post('email') : NULL
            );
        } catch (Exception $e) {
            echo json_encode(Array('code' => clearos_exception_code($e), 'errmsg' => clearos_exception_message($e)));
        }
    }

    /**
     * Ajax call to fetch registration info from SDN account
     *
     * @return JSON
     */

    function get_registration_info()
    {
        clearos_profile(__METHOD__, __LINE__);

        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');

        try {
            $this->load->library('registration/Registration');
            echo $this->registration->get_registration_info(
                $this->input->post('username'),
                $this->input->post('password')
            );
        } catch (Exception $e) {
            echo json_encode(Array('code' => clearos_exception_code($e), 'errmsg' => clearos_exception_message($e)));
        }
    }

    /**
     * Ajax get SDN information controller
     *
     * @return JSON
     */

    function get_sdn_info()
    {
        clearos_profile(__METHOD__, __LINE__);

        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');

        try {
            $this->load->library('registration/Registration');
            $result = $this->registration->get_sdn_info(TRUE);
            $response = json_decode($result);
            // Better sync up local registation status
            if ($response->code == 0 && $response->device_id > 0)
                $this->registration->set_local_registration_status(TRUE);
            elseif ($response->code == 0 && $response->device_id <= 0)
                $this->registration->set_local_registration_status(FALSE);
            echo $result;
        } catch (Exception $e) {
            echo json_encode(Array('code' => clearos_exception_code($e), 'errmsg' => clearos_exception_message($e)));
        }
    }

    /**
     * Ajax get system information controller
     *
     * @return JSON
     */

    function get_system_info()
    {
        clearos_profile(__METHOD__, __LINE__);

        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');

        try {
            sleep(2);
            $this->load->library('network/Network_Status');
            $status = $this->network_status->get_connection_status();

            if ($status == Network_Status::STATUS_OFFLINE) {
                $data['network_code'] = 2;
                echo json_encode($data);
            } else if ($status == Network_Status::STATUS_ONLINE_NO_DNS) {
                $data['network_code'] = 1;
                echo json_encode($data);
            } else {
                $data['network_code'] = 0;
                $this->load->library('registration/Registration');
                echo $this->registration->get_system_info();
            }
        } catch (Exception $e) {
            echo json_encode(Array('code' => clearos_exception_code($e), 'errmsg' => clearos_exception_message($e)));
        }
    }

    /**
     * Ajax check username availability
     *
     * @return JSON
     */

    function check_username_availability()
    {
        clearos_profile(__METHOD__, __LINE__);

        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');

        try {
            $this->load->library('registration/Registration');
            echo $this->registration->check_username_availability($this->input->post('username'));
        } catch (Exception $e) {
            echo json_encode(Array('code' => clearos_exception_code($e), 'errmsg' => clearos_exception_message($e)));
        }
    }

    /**
     * Ajax terms of service
     *
     * @return JSON
     */

    function terms_of_service()
    {
        clearos_profile(__METHOD__, __LINE__);

        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');

        try {
            $this->load->library('registration/Registration');
            echo $this->registration->terms_of_service();
        } catch (Exception $e) {
            echo json_encode(Array('code' => clearos_exception_code($e), 'errmsg' => clearos_exception_message($e)));
        }
    }

    /**
     * Ajax acknowledge subscription notice
     *
     * @return JSON
     */

    function acknowledge_subscription_notice($id = 0)
    {
        clearos_profile(__METHOD__, __LINE__);

        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');

        try {
            $this->load->library('registration/Registration');
            echo $this->registration->acknowledge_subscription_notice($id);
        } catch (Exception $e) {
            echo json_encode(Array('code' => clearos_exception_code($e), 'errmsg' => clearos_exception_message($e)));
        }
    }
}
