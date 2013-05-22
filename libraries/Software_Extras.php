<?php

/**
 * Software extras class.
 *
 * @category   apps
 * @package    registration
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2013 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/registration/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

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

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\OS as OS;
use \clearos\apps\base\Software as Software;
use \clearos\apps\base\Yum as Yum;

clearos_load_library('base/Engine');
clearos_load_library('base/OS');
clearos_load_library('base/Software');
clearos_load_library('base/Yum');

// Exceptions
//-----------

use \Exception as Exception;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Software extras class.
 *
 * @category   apps
 * @package    registration
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2013 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/registration/
 */

class Software_Extras extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // M E M B E R S
    ///////////////////////////////////////////////////////////////////////////////

    protected $packages = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Software extras constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->packages = array(
            'app-account-synchronization',
            'app-performance-tuning',
            'app-professional-reports',
        );
    }

    /**
     * Installs packages.
     *
     * @param array $list list of packages
     *
     * @return void
     */

    public function run_install()
    {
        clearos_profile(__METHOD__, __LINE__);

        foreach ($this->packages as $package)
            clearos_log('registration', 'requesting package: ' . $package);

        try {
            $yum = new Yum();
            $yum->install($this->packages);
        } catch (Exception $e) {
            // Not fatal
        }
    }

    /**
     * Returns state of install.
     *
     * @return void
     */

    public function is_complete()
    {
        clearos_profile(__METHOD__, __LINE__);

        $os = new OS();

        if (!preg_match('/Professional/', $os->get_name()))
            return TRUE;

        $all_installed = TRUE;

        foreach ($this->packages as $package) {
            $software = new Software($package);
            if (!$software->is_installed())
                $all_installed = FALSE;
        }

        return $all_installed;
    }

    /**
     * Returns status of running install.
     *
     * @return array status information
     */

    public function get_install_status()
    {
        clearos_profile(__METHOD__, __LINE__);

        $yum = new Yum();

        return $yum->get_status();
    }
}
