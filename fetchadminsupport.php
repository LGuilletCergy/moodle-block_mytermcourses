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
 * Initially developped for :
 * Université de Cergy-Pontoise
 * 33, boulevard du Port
 * 95011 Cergy-Pontoise cedex
 * FRANCE
 *
 * Displays the user's courses for the current term. Courses open to all users are also displayed.
 * Teachers can add courses.
 *
 * @package    block_mytermcourses
 * @author     Brice Errandonea <brice.errandonea@u-cergy.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * File : fetchadminsupport.php
 * Script pour récupérer la liste des appuis administratifs dans chacun des cours d'une certaine categorie sur une autre plateforme et inscrire les mêmes dans les cours correspondants sur cette plateforme-ci.
 */

require_once('../../config.php');
require_once("$CFG->dirroot/blocks/mytermcourses/lib.php");

$topidnumber = required_param('code', PARAM_TEXT);
$topcategory = $DB->get_record('course_categories', array('idnumber' => "$CFG->yearprefix-$topidnumber"));

require_login();
$sitecontext = context_system::instance();
if (!has_capability('block/mytermcourses:fetcholdcategory', $sitecontext)) {
	header("Location: $CFG->wwwroot/my/index.php");
}

// Header code.
$moodlefilename = '/blocks/mytermcourses/fetchadminsupport.php';
$PAGE->set_context($sitecontext);
$PAGE->set_url($moodlefilename);
$title = "Récupération des appuis administratifs d'une ancienne categorie";
$PAGE->set_title($title);
$PAGE->set_pagelayout('standard');
$PAGE->set_heading($title);
$PAGE->navbar->add(get_string('pluginname', 'block_mytermcourses'));
$PAGE->navbar->add($title);

$connection = ssh2_connect('enp16.u-cergy.fr', 22);
ssh2_auth_password($connection, 'enp17', 'Hrso3[(à');
$courselistcommand = "php /var/www/moodle/enp17adminsupport.php $topidnumber";
$courseliststream = ssh2_exec($connection, $courselistcommand);
stream_set_blocking($courseliststream, true);
$courseliststreamout = ssh2_fetch_stream($courseliststream, SSH2_STREAM_STDIO);
$courselist = stream_get_contents($courseliststreamout);
$updatedcourselist = str_replace('Y'.($CFG->thisyear - 1).'-', 'Y'.$CFG->thisyear.'-', $courselist);
$oldcourses = explode('£µ£', $updatedcourselist);

//~ print_object($oldcourses); exit;

echo $OUTPUT->header();
$i = 0;
$j = 0;
foreach ($oldcourses as $oldcourse) {
	$oldcoursearray = explode(';', $oldcourse);
	if (count($oldcoursearray)) {
	    $idnumber = array_shift($oldcoursearray);
        $newcourse = $DB->get_record('course', array('idnumber' =>  $idnumber));
        if ($newcourse) {
			enroladminsupports($newcourse, $oldcoursearray);
            $j++;
		}
	}
	$i++;
}
echo "<h3>$i cours dans la liste, $j traités.</h3>";
echo $OUTPUT->footer();

function enroladminsupports($course, $usernames) {
    global $DB, $USER;
    $now = time();
    $coursecontext = $DB->get_record('context', array('instanceid' => $course->id, 'contextlevel' => 50));
    $sitecontextid = 1;
    $manualenrol = $DB->get_record('enrol', array('enrol' => 'manual', 'courseid' => $course->id));
    foreach ($usernames as $username) {
		$user = $DB->get_record('user', array('username' => $username));
		if ($user) {
			//On vérifie que cet utilisateur fait bien partie du personnel de l'établissement.
			$staff = $DB->get_record('role_assignments', array('contextid' => $sitecontextid, 'userid' => $user->id, 'roleid' => 11));
			//role 11 : Personnel de l'établissement
			//role 12 : Appui administratif			
			if ($staff) {
				$userenrolment = $DB->get_record('user_enrolments', array('enrolid' => $manualenrol->id, 'userid' => $user->id));
			    if (!$userenrolment) {
				    $userenrolment = new stdClass();
				    $userenrolment->status = 0;
				    $userenrolment->enrolid = $manualenrol->id;
				    $userenrolment->userid = $user->id;
				    $userenrolment->timestart = $now;
				    $userenrolment->timeend = 0;
				    $userenrolment->modifierid = $USER->id;
				    $userenrolment->timecreated = $now;
				    $userenrolment->timemodified = $now;
				    $userenrolment->id = $DB->insert_record('user_enrolments', $userenrolment);
				    print_object($userenrolment);
			    }
			    $adminsupport = $DB->get_record('role_assignments', array('contextid' => $coursecontext->id, 'userid' => $user->id, 'roleid' => 12));
			    if (!$adminsupport) {
				    $adminsupport = new stdClass();
				    $adminsupport->roleid = 12;
				    $adminsupport->contextid = $coursecontext->id;
				    $adminsupport->userid = $user->id;
				    $adminsupport->timemodified = $now;
				    $adminsupport->modifierid = $USER->id;
				    $adminsupport->id = $DB->insert_record('role_assignments', $adminsupport);
				    print_object($adminsupport);
			    }
		    }
	    }
	}
    //~ exit;
}
