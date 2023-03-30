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
 * launches the experience with the requested registration
 *
 * @package mod_cmi5launch
 * @copyright  2013 Andrew Downes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once('header.php');


// Trigger Activity launched event.
$event = \mod_cmi5launch\event\activity_launched::create(array(
    'objectid' => $cmi5launch->id,
    'context' => $context,
));
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('cmi5launch', $cmi5launch);
$event->trigger();

//MB
//Might this be a good way? Maybe it can check session or regid numbers for what to display?
//Retrieve registration id (from view.php)
//Is there a way ot send only the regid for certain aus?
$registrationid = required_param('launchform_registration', PARAM_TEXT);
if (empty($registrationid)) {
    echo "<div class='alert alert-error'>".get_string('cmi5launch_regidempty', 'cmi5launch')."</div>";

    // Failed to connect to LRS.
    if ($CFG->debug == 32767) {
        echo "<p>Error attempting to get registration id querystring parameter.</p>";
    }
    die();
}
//If it's 1 than the "Start New Registration" was pushed
elseif($registrationid == 1)
{
	//to bring in functions from class cmi5Connector
	$connectors = new cmi5Connectors;

    //Build url to pass as returnUrl
    $returnUrl = $CFG->wwwroot .'/mod/cmi5launch/view.php'. '?id=' .$cm->id;

    $retrieveUrl = $connectors->getRetrieveUrl();

	//Retrieve launch URL from CMI5 player
	$url = $retrieveUrl($cmi5launch->id, $returnUrl);
	//urlInfo is one big string so
	parse_str($url, $urlInfo);
	//Retrieve registration id from end of parsed URL
	$registrationid = $urlInfo['registration'];
}

// Save a record of this registration to the LRS state API.
$getregistrationdatafromlrsstate = cmi5launch_get_global_parameters_and_get_state(
    "http://cmi5api.co.uk/stateapikeys/registrations"
);
$errorhtml = "<div class='alert alert-error'>".get_string('cmi5launch_notavailable', 'cmi5launch')."</div>";
$lrsrespond = $getregistrationdatafromlrsstate->httpResponse['status'];
//Unable to connect to LRS
if ($lrsrespond != 200 && $lrsrespond != 404) {
    // Failed to connect to LRS.
    echo $errorhtml;
    if ($CFG->debug == 32767) {
        echo "<p>Error attempting to get registration data from State API.</p>";
        echo "<pre>";
        var_dump($getregistrationdatafromlrsstate);
        echo "</pre>";
    }
    die();
}
//Successfully connected to LRS
if ($lrsrespond == 200) {
    $registrationdata = json_decode($getregistrationdatafromlrsstate->content->getContent(), true);
} else {
    $registrationdata = null;
}
$registrationdataetag = $getregistrationdatafromlrsstate->content->getEtag();

$datenow = date("c");

$registrationdataforthisattempt = array(
    $registrationid => array(
        "created" => $datenow,
        "lastlaunched" => $datenow
    )
);

//Getting error on  - Exception - Attempt to modify property "httpResponse" on null
//It's because if this isnull it can't have a property, but it is trying to 
//access property anyway
if (is_null($registrationdata)) {
    // If the error is 404 create a new registration data array.
   /* if ($registrationdata->httpResponse['status'] = 404) {*/
        $registrationdata = $registrationdataforthisattempt;
   /* }*/
} else if (array_key_exists($registrationid, $registrationdata)) {
    // Else if the regsitration exists update the lastlaunched date.
    $registrationdata[$registrationid]["lastlaunched"] = $datenow;
} else { // Push the new data on the end.
    $registrationdata[$registrationid] = $registrationdataforthisattempt[$registrationid];
}

// Sort the registration data by last launched (most recent first).
uasort($registrationdata, function($a, $b) {
    return strtotime($b['lastlaunched']) - strtotime($a['lastlaunched']);
});

// TODO: Currently this is re-PUTting all of the data - it may be better just to POST the new data.
// This will prevent us sorting, but sorting could be done on output.
$saveresgistrationdata = cmi5launch_get_global_parameters_and_save_state(
    $registrationdata,
    "http://cmi5api.co.uk/stateapikeys/registrations",
    $registrationdataetag
);


$lrsrespond = $saveresgistrationdata->httpResponse['status'];
if ($lrsrespond != 204) {
    // Failed to connect to LRS.
    echo $errorhtml;
    if ($CFG->debug == 32767) {
        echo "<p>Error attempting to set registration data to State API.</p>";
        echo "<pre>";
        var_dump($saveresgistrationdata);
        echo "</pre>";
    }
    die();
}

$langpreference = array(
    "languagePreference" => cmi5launch_get_moodle_langauge()
);

$saveagentprofile = cmi5launch_get_global_parameters_and_save_agentprofile($langpreference, "CMI5LearnerPreferences");

$lrsrespond = $saveagentprofile->httpResponse['status'];
if ($lrsrespond != 204) {
    // Failed to connect to LRS.
    echo $errorhtml;
    if ($CFG->debug == 32767) {
        echo "<p>Error attempting to set learner preferences to Agent Profile API.</p>";
        echo "<pre>";
        var_dump($saveagentprofile);
        echo "</pre>";
    }
    die();
}
/*
Moodle used to send a launched statement to LRS. This is no longer needed as CMI%
player handles the tracking. - MB 1/27/23

$savelaunchedstatement = cmi5_launched_statement($registrationid);

$lrsrespond = $savelaunchedstatement->httpResponse['status'];
if ($lrsrespond != 204) {
    // Failed to connect to LRS.
    echo $errorhtml;
    if ($CFG->debug == 32767) {
        echo "<p>Error attempting to send 'launched' statement.</p>";
        echo "<pre>";
        var_dump($savelaunchedstatement);
        echo "</pre>";
    }
    die();
}
*/
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

header("Location: ". cmi5launch_get_launch_url($registrationid));

exit;