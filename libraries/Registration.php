<?php

/**
 * Registration class.
 *
 * @category   Apps
 * @package    Registration
 * @subpackage Libraries
 * @author     ClearCenter <developer@clearcenter.com>
 * @copyright  2011 ClearCenter
 * @license    http://www.clearcenter.com/app_license ClearCenter license
 * @link       http://www.clearcenter.com/support/documentation/clearos/registration/
 */

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\registration;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('registration');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\clearcenter\Rest as Rest;
use \clearos\apps\suva\Suva as Suva;
use \clearos\apps\base\Country as Country;

clearos_load_library('base/Configuration_File');
clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('clearcenter/Rest');
clearos_load_library('suva/Suva');
clearos_load_library('base/Country');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Registration for ClearCenter class.
 *
 * @category   Apps
 * @package    Registration
 * @subpackage Libraries
 * @author     ClearCenter <developer@clearcenter.com>
 * @copyright  2011 ClearCenter
 * @license    http://www.clearcenter.com/app_license ClearCenter license
 * @link       http://www.clearcenter.com/support/documentation/clearos/registration/
 */

class Registration extends Rest
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = '/etc/clearos/registration.conf';
    const FILE_REGISTERED_FLAG = '/var/clearos/registration/registered';
    const FOLDER_REGISTRATION = '/var/clearos/registration';
    const REGISTER_NEW = 0;
    const REGISTER_EXISTING = 1;

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Registration constructor.
     */

    function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
        parent::__construct();
    }

    /**
     * Register system to the SDN.
     *
     * @param String $username          account username
     * @param String $password          account password
     * @param int    $registration_type registration type (new install or upgrade/reinstall)
     * @param String $system_name       system name
     * @param int    $system            system ID
     * @param int    $subscription      subscription ID
     *
     * @return Object JSON-encoded response
     *
     * @throws Webservice_Exception
     */

    public function register($username, $password, $registration_type, $system_name, $system, $subscription)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {

            $extras = array (
                'username' => $username, 'password' => $password, 'registration_type' => $registration_type,
                'system_name' => $system_name, 'system' => $system, 'subscription' => $subscription
            );

            $result = $this->request('registration', 'register', $extras);
            $response = json_decode($result);
            if ($response->code == 0) {
                $suva = new Suva();
                $suva->set_device_name($response->device_id);
                $file = new File(self::FILE_REGISTERED_FLAG);
                if (!$file->exists())
                    $file->create('root', 'root', '0644');

                $this->delete_cache();
            }
            return $result;
        } catch (Exception $e) {
            throw new Webservice_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Updates local registration flag.
     *
     * @param boolean $status true if registered
     *
     * @return void
     *
     * @throws Webservice_Exception
     */

    public function set_local_registration_status($status)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {

            $file = new File(self::FILE_REGISTERED_FLAG);
            if (!$file->exists() && $status)
                $file->create('root', 'root', '0644');
            else if ($file->exists() && !$status)
                $file->delete();
        } catch (Exception $e) {
            // Ignore?
        }
    }

    /**
     * Get registration info related to an account on SDN.
     *
     * @param string  $username username
     * @param string  $password password
     *
     * @return Object JSON-encoded response
     * @throws Webservice_Exception
     */

    public function get_registration_info($username = '', $password = '')
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
    
            $extras = array('username' => $username, 'password' => $password);

            $result = $this->request('registration', 'get_registration_info', $extras);

            return $result;
        } catch (Exception $e) {
            throw new Webservice_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Get system registration info related to a device on SDN.
     *
     * @param boolean $realtime set realtime to TRUE to fetch real-time data
     *
     * @return Object JSON-encoded response
     * @throws Webservice_Exception
     */

    public function get_system_info($realtime = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
    
            $cachekey = __CLASS__ . '-' . __FUNCTION__; 

            if (!$realtime && $this->_check_cache($cachekey))
                return $this->cache;
    
            $result = $this->request('registration', 'get_system_info');

            $this->_save_to_cache($cachekey, $result);

            return $result;
        } catch (Exception $e) {
            throw new Webservice_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Check username availability.
     *
     * @param String $username          account username
     *
     * @return Object JSON-encoded response
     * @throws Webservice_Exception
     */

    public function check_username_availability($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
    
            $extras = array('username' => $username);

            $result = $this->request('registration', 'check_username_availability', $extras);

            return $result;
        } catch (Exception $e) {
            throw new Webservice_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Get terms of service.
     *
     *
     * @return Object JSON-encoded response
     * @throws Webservice_Exception
     */

    public function terms_of_service()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $result = $this->request('registration', 'terms_of_service');

            return $result;
        } catch (Exception $e) {
            throw new Webservice_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Create an account on the SDN.
     *
     * @param String $username account username
     * @param String $password account password
     * @param String $email    email
     * @param String $country  country code
     * @param int    $mailer   subscribe to mailing list
     *
     * @return Object JSON-encoded response
     * @throws Webservice_Exception
     */

    public function create_account($username, $password, $email, $country, $mailer)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
    
            $extras = array(
                'username' => $username, 'password' => $password,
                'email' => $email, 'country' => $country, 'mailer' => $mailer
            );

            $result = $this->request('registration', 'create_account', $extras);

            return $result;
        } catch (Exception $e) {
            throw new Webservice_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Returns a list of registration types.
     *
     * @return array
     */

    function get_registration_options()
    {
        clearos_profile(__METHOD__, __LINE__);
            
        $options = Array(
            self::REGISTER_NEW => lang('registration_new'),
            self::REGISTER_EXISTING => lang('registration_existing')
        );
        return $options;
    }

    /**
     * Returns a list of country codes.
     *
     * @return array
     */

    function get_country_options()
    {
        clearos_profile(__METHOD__, __LINE__);
            
        $country = new Country();
        return $country->get_list();
    }

    /**
     * Reset registration.
     *
     * @return void
     * @throws Engine_Exception
     */

    function reset()
    {
        clearos_profile(__METHOD__, __LINE__);
            
        $suva = new Suva();
        $suva->reset_hostkey();
        $this->delete_cache();

        $file = new File(self::FILE_REGISTERED_FLAG);

        if ($file->exists())
            $file->delete();
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Loads configuration files.
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        $configfile = new Configuration_File(self::FILE_CONFIG);

        try {
            $this->config = $configfile->load();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        $this->is_loaded = TRUE;
    }

    /**
     * Generic set routine.
     *
     * @param string $key   key name
     * @param string $value value for the key
     *
     * @return  void
     * @throws Engine_Exception
     */

    function _set_parameter($key, $value)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::FILE_CONFIG, TRUE);

            if (!$file->exists())
                $file->create('webconfig', 'webconfig', '0644');

            $match = $file->replace_lines("/^$key\s*=\s*/", "$key=$value\n");

            if (!$match)
                $file->add_lines("$key=$value\n");
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        $this->is_loaded = FALSE;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for sdn_username
     *
     * @param string $sdn_username SDN Username
     *
     * @return boolean TRUE if sdn_username is valid
     */

    public function validate_sdn_username($sdn_username)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match("/^[A-Za-z0-9]+$/", $sdn_username))
            return lang('registration_sdn_username_is_invalid');
        if (strlen($sdn_username) < 4)
            return lang('registration_sdn_username_min_length') . ' (4).';
    }

    /**
     * Validation routine for sdn_password
     *
     * @param string $sdn_password max instances
     *
     * @return boolean TRUE if sdn_password is valid
     */

    public function validate_sdn_password($sdn_password)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match("/^[A-Za-z0-9\\!\\@\\#\\$\\%\\^\\*\\(\\)\\-\\_\\&]+$/", $sdn_password))
            return lang('registration_sdn_password_is_invalid');
        if (strlen($sdn_password) < 4)
            return lang('registration_sdn_password_min_length') . ' (4).';
    }

    /**
     * Validation routine for system_name
     *
     * @param string $system_name system_name
     *
     * @return boolean TRUE if system_name is valid
     */

    public function validate_system_name($system_name)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match("/^[A-Za-z0-9\\ \\-\\_\\(\\)\\#\\.\\@]+$/", $system_name))
            return lang('registration_system_name_is_invalid');
    }

    /**
     * Validation registration type.
     *
     * @param int $type registration type
     *
     * @return mixed void if type is valid, errmsg otherwise
     */

    public function validate_registration_type($type)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (FALSE)
            return lang('registration_type_is_invalid');
    }

    /**
     * Validation system for registration.
     *
     * @param int $system system
     *
     * @return mixed void if system is valid, errmsg otherwise
     */

    public function validate_system($system)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($system == 0)
            return lang('registration_select_system_required');
    }

    /**
     * Validation subscription for registration.
     *
     * @param int $subscription subscription
     *
     * @return mixed void if subscription is valid, errmsg otherwise
     */

    public function validate_subscription($subscription)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($subscription == 0)
            return lang('registration_select_subscription_required');
    }

    /**
     * Validation routine for email.
     *
     * @param string $email email
     *
     * @return mixed void if email is valid, errmsg otherwise
     */

    public function validate_email($email)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $email))
            return lang('registration_email_invalid');
    }

    /**
     * Validation routine for country.
     *
     * @param string $country country
     *
     * @return string error message if country is invalid
     */

    public function validate_country($country)
    {
        clearos_profile(__METHOD__, __LINE__);

        $country_object = new Country();
        $country_list = $country_object->get_list();

        if (($country != '__') && (! array_key_exists($country, $country_list)))
            return lang('registration_country_invalid');
    }
}
