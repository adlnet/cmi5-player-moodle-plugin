<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Helper class for sessions -MB
 *
 * @copyright  2023 Megan Bohland
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_cmi5launch\local;
defined('MOODLE_INTERNAL') || die();

use mod_cmi5launch\local\cmi5_connectors;
use mod_cmi5launch\local\session;


class session_helpers {

    public function cmi5launch_get_create_session() {
        return [$this, 'cmi5launch_create_session'];
    }

    public function cmi5launch_get_update_session() {
        return [$this, 'cmi5launch_update_sessions'];
    }

    public function cmi5launch_get_retrieve_sessions_from_db() {
        return [$this, 'cmi5launch_retrieve_sessions_from_db'];
    }

    public function cmi5launch_get_convert_session() {
        return [$this, 'cmi5launch_session_for_db'];
    }

    /**
     * Gets updated session information from CMI5 player
     * @param mixed $sessionid - the session id
     * @param mixed $cmi5id - cmi5 instance id
     * @return session
     */

     // MB, maybe here? whenever a session is updated check the grades?
    public function cmi5launch_update_sessions($sessionid, $cmi5id) {

        global $CFG, $DB, $cmi5launch;

        $connector = new cmi5_connectors;
        $getsessioninfo = $connector->cmi5launch_get_session_info();
        $progress = new progress;
        $getprogress = $progress->cmi5launch_get_retrieve_statements();

        //Yeah, lets put the proress update her too., too combine

        // Get the session from DB with session id.
        $session = $this->cmi5launch_retrieve_sessions_from_db($sessionid);

        // Get updates from lrs as well
        $session = $getprogress($session->registrationid, $cmi5launch->id, $session);
    
        // Get updates from cmi5player
        // This is sessioninfo from CMI5 player.
        $sessioninfo = $getsessioninfo($sessionid, $cmi5id);
/*
        echo"<br>";
        echo "Ok, what is session INBOFFOFO here?";
        var_dump($sessioninfo);
        echo "<br>";;
     */
        // Update session.
        foreach ($sessioninfo as $key => $value) {
            // We don't want to overwrite id.

            //Will making it lowercase help? 
            // This seemed to solve the issue with DB thank oodness. 
           // $key = mb_convert_case($key, MB_CASE_LOWER, "UTF-8");
            if (property_exists($session, $key ) && $key != 'id' && $key != 'sessionid') {
                // If it's an array, encode it so it can be saved to DB.
                if (is_array($value)) {
                    $value = json_encode($value);
                }

                if(is_string($key)){
                    // MAKE IT LOWERCASE? IS THAT ENOUH?
                    $key = mb_convert_case($key, MB_CASE_LOWER, "UTF-8");
                   // $newsession->$smallkey = ($value);
                }
                //Can we see if property is a strin and then convert that>?

/*
                echo "Ok, what is value here?";
                var_dump($value);

                echo "<br>";
                echo "Ok, what is key here?";
                var_dump($key);
                echo "<br>";
*/
              //  $key = mb_convert_case($key, MB_CASE_LOWER, "UTF-8");
                $session->$key = $value;
            }
        }


        // Now update to table.
        $DB->update_record('cmi5launch_sessions', $session);

        return $session;
    }


    /**
     * Creates a session record in DB
     * @param mixed $sessionid - the session id
     * @param mixed $launchurl - the launch url
     * @param mixed $launchmethod - the launch method
     * @return void
     */
    public function cmi5launch_create_session($sessionid, $launchurl, $launchmethod) {

        global $DB, $CFG, $cmi5launch, $USER;

        $table = "cmi5launch_sessions";
// Well, maybe this is the problem, its not making them riiiht

        // Make a new record to save.
        $newrecord = new \stdClass();
        // Because of many nested properties, needs to be done manually.
        $newrecord->sessionid = $sessionid;
        $newrecord->launchurl = $launchurl;
        $newrecord->tenantname = $USER->username;
        $newrecord->launchmethod = $launchmethod;

        // Save record to table.
        $DB->insert_record($table, $newrecord, true);
    }

    /**
     * Retrieves session from DB
     * @param mixed $sessionid - the session id
     * @return session
     */
    public function cmi5launch_retrieve_sessions_from_db($sessionid) {

        global $DB, $CFG;

        $check = $DB->record_exists('cmi5launch_sessions', ['sessionid' => $sessionid], '*', IGNORE_MISSING);

        // If check is negative, the record does not exist. Throw error.
        if (!$check) {

            echo "<p>Error attempting to get session data from DB. Check session id.</p>";
            echo "<pre>";
            var_dump($sessionid);
            echo "</pre>";

        } else {

            $sessionitem = $DB->get_record('cmi5launch_sessions',  array('sessionid' => $sessionid));

            $session = new session($sessionitem);

        }

        // Return new session object.
        return $session;
    }

        // Constructs sessions with lowercase values to work with DB. Is fed array and where array key matches property, sets the property.
    public function cmi5launch_session_for_db($statement)
    {


        //make a new session object
        $newsession = new \stdClass();
        foreach ($statement as $key => $value) {

            if (!$key == 'id' && !$key == 'sessionid') {
                $smallkey = mb_convert_case($key, MB_CASE_LOWER, "UTF-8");
                $newsession->$smallkey = ($value);
            }
            $newsession->id = $statement['id'];
            $newsession->sessionid = $statement['sessionid'];
        }
    }
}
