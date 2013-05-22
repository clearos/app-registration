<?php

/**
 * Software updates controller.
 *
 * @category   apps
 * @package    registration
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/software_updates/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \Exception as Exception;
use \clearos\apps\base\Yum_Busy_Exception as Yum_Busy_Exception;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Software updates controller.
 *
 * @category   apps
 * @package    registration
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/software_updates/
 */

class Extras extends ClearOS_Controller
{
    /**
     * Updates controller.
     *
     * @return view
     */

    function handle_install()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Load dependencies
        //------------------

        $this->load->library('registration/Software_Extras');

        // Grab data
        //----------

        try {
            $data['complete'] = $this->software_extras->is_complete();

            if ($data['complete']) {
                $data['details'] = '...';
            } else {
                $status = $this->software_extras->get_install_status();

                if ($status['busy'] || $status['wc_busy']) {
                    $data['details'] = $status['details'];
                } else {
                    $data['details'] = 'Starting...'; // FIXME: translate
                    $this->software_extras->run_install();
                }
            }

            $data['code'] = 0;
        } catch (\Exception $e) {
            $data['code'] =  clearos_exception_code($e);
            $data['error_message'] = clearos_exception_message($e);
        }

        // Return status message
        //----------------------

        $this->output->set_header("Content-Type: application/json");
        $this->output->set_output(json_encode($data));

    }
}
