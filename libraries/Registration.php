<?php

/**
 * Registration class.
 *
 * @category   apps
 * @package    registration
 * @subpackage libraries
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
use \clearos\apps\suva\Suva as Suva;
use \clearos\apps\base\Country as Country;
use \clearos\apps\tasks\Cron as Cron;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\clearcenter\Rest as Rest;
use \clearos\apps\mode\Mode_Engine as Mode_Engine;
use \clearos\apps\mode\Mode_Factory as Mode_Factory;

clearos_load_library('base/Configuration_File');
clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('suva/Suva');
clearos_load_library('base/Country');
clearos_load_library('tasks/Cron');
clearos_load_library('base/Shell');
clearos_load_library('clearcenter/Rest');
clearos_load_library('mode/Mode_Engine');
clearos_load_library('mode/Mode_Factory');

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
 * @category   apps
 * @package    registration
 * @subpackage libraries
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
    const FILE_AUDIT = 'audit.json';
    const FOLDER_REGISTRATION = '/var/clearos/registration';
    const COMMAND_CAT = '/bin/cat';
    const COMMAND_INSTALL_EXTRAS = '/usr/clearos/apps/registration/deploy/install-extras';
    const REGISTER_NEW = 0;
    const REGISTER_EXISTING = 1;
    const CODE_SYSTEM_REGISTERED = 0;
    const CODE_SYSTEM_NOT_REGISTERED = 3;

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
     * @param String $environment       environment
     *
     * @return Object JSON-encoded response
     *
     * @throws Webservice_Exception
     */

    public function register($username, $password, $registration_type, $system_name, $system, $subscription, $environment)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {

            $extras = array (
                'username' => $username, 'password' => $password, 'registration_type' => $registration_type,
                'system_name' => $system_name, 'system' => $system, 'subscription' => $subscription, 'environment' => $environment
            );

            $result = $this->request('registration', 'register', $extras);
            $response = json_decode($result);
            if ($response->code == 0) {
                $suva = new Suva();
                $suva->set_device_name($response->device_id);
                $this->set_local_registration_status(TRUE);
                $this->_install_extras();
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

            $response = json_decode($result);

            // Only set local registration flag if code is specific
            if ($response->code == self::CODE_SYSTEM_NOT_REGISTERED)
                $this->set_local_registration_status(FALSE);
            elseif ($response->code == self::CODE_SYSTEM_REGISTERED)
                $this->set_local_registration_status(TRUE);

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
     * @param String  $username  account username
     * @param String  $password  account password
     * @param String  $email     email
     * @param String  $country   country code
     * @param boolean $mailer    subscribe to mailing list
     * @param int     $interests interest groups
     *
     * @return Object JSON-encoded response
     * @throws Webservice_Exception
     */

    public function create_account($username, $password, $email, $country, $mailer, $interests)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
    
            $extras = array(
                'username' => $username, 'password' => $password,
                'email' => $email, 'country' => $country, 'mailer' => $mailer, 'interests' => $interests
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
        $suva->set_device_name(0);
        $this->delete_cache();

        $this->set_local_registration_status(FALSE);
    }

    /**
     * Registration check-in.
     *
     * @return void
     * @throws Engine_Exception
     */

    function check_in()
    {
        clearos_profile(__METHOD__, __LINE__);
            
        if (!$this->is_loaded)
            $this->_load_config();

        try {
            $cron = new Cron();

            $app = 'app-registration';

            $schedule = NULL;
            if ($cron->exists_configlet($app))
                $schedule = $cron->get_configlet($app);
            
            while (TRUE) {
                $dow = rand(0, 6);
                if ($dow != date('w'))
                    break;
            }
            if ($schedule == NULL || preg_match('/0 0 \* \* \*.*/', $schedule)) {
                // Randomize future check-ins
                $cron_entry = rand(0,59) . ' ' . rand(0,23) . ' * * ' . $dow . 
                    " root /usr/sbin/clearcenter-checkin >/dev/null 2>&1";
                $cron->delete_configlet($app);
                $cron->add_configlet($app, $cron_entry);
                // Let's send the webservice call at a randomized time in the future
                // to prevent bottlenecks
                return;
            }
        } catch (Exception $e) {
            // Don't really care
        }

        try {

            // Device ID
            //----------
            $suva = new Suva();
            $extras['device_id'] = $suva->get_device_name();

            // Mode
            //-----
            if (clearos_library_installed('mode/Mode_Engine')) {
                try {
                    $mode_object = Mode_Factory::create();
                    $extras['mode'] = $mode_object->get_mode();
                } catch (\Exception $e) {
                    // Not really worried about
                }
            }

            // Uptime
            //-------
            $shell = new Shell();
            $shell->execute(self::COMMAND_CAT, '/proc/uptime', FALSE);
            $line = $shell->get_last_output_line();
            if (preg_match('/([0-9.]+)\s+([0-9.])/', $line, $match))
                $extras['uptime'] = $match[1];

            // Locale and Version Info always get sent up in Rest calls

            // Opt-Out data
            //-------------
            try {
                $file = new File(CLEAROS_TEMP_DIR . '/' . self::FILE_AUDIT);
                if ($file->exists()) {
                    $audit = json_decode($file->get_contents());
                    if ($audit != NULL) {
                        // Users
                        if (!isset($this->config['exclude_user']) || !$this->config['exclude_user'])
                            $extras['user'] = $audit->users->weekly;
                        // Unique IP
                        if (!isset($this->config['exclude_ip']) || !$this->config['exclude_ip'])
                            $extras['ip4'] = $audit->ip4->weekly;
                        // Unique MAC
                        if (!isset($this->config['exclude_mac']) || !$this->config['exclude_mac'])
                            $extras['mac'] = $audit->mac->weekly;
                    }
                }
            } catch (\Exception $e) {
                // Not really worried about
            }
            
            $result = $this->request('registration', 'check_in', $extras);
            $response = json_decode($result);
            if ($response->code != 0)
                throw new Engine_Exception($response->errmsg, CLEAROS_ERROR);
        } catch (\Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Intall extra packages.
     *
     * After a system is registered, new repos for paid apps are available.
     * This makes it possible to install additional features.
     *
     * @return void
     */

    protected function _install_extras()
    {
        clearos_profile(__METHOD__, __LINE__);

        $options['background'] = TRUE;

        $shell = new Shell();
        $shell->execute(self::COMMAND_INSTALL_EXTRAS, '', TRUE, $options);
    }

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
            return lang('base_username_invalid');

        if (strlen($sdn_username) < 4)
            return lang('base_username_too_short') . ' (4).';
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
            return lang('base_password_is_invalid');

        if (strlen($sdn_password) < 4)
            return lang('base_password_too_short') . ' (4).';
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
            return lang('base_email_address_invalid');
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
            return lang('base_country_invalid');
    }

    /**
     * Validation routine for environment.
     *
     * @param string $environment environment
     *
     * @return string error message if environment is invalid
     */

    public function validate_environment($environment)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($environment == '0' || !preg_match('/\w+/', $environment))
            return lang('registration_environment_invalid');
    }

    /**
     * Validation routine for mailer.
     *
     * @param string $mailer mailer
     *
     * @return string error message if mailer is invalid
     */

    public function validate_mailer($mailer)
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Validation routine for interest group.
     *
     * @param string $interest interest
     *
     * @return string error message if interest group is invalid
     */

    public function validate_interest_new_release($interest)
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Validation routine for interest group.
     *
     * @param string $interest interest
     *
     * @return string error message if interest group is invalid
     */

    public function validate_interest_new_apps($interest)
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Validation routine for interest group.
     *
     * @param string $interest interest
     *
     * @return string error message if interest group is invalid
     */

    public function validate_interest_betas($interest)
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Validation routine for interest group.
     *
     * @param string $interest interest
     *
     * @return string error message if interest group is invalid
     */

    public function validate_interest_promotions($interest)
    {
        clearos_profile(__METHOD__, __LINE__);
    }
}
