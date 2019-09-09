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
 * File : oldcourses.php
 * Access old courses or copy them as new courses.
 */

require_once('../../config.php');
require_once("$CFG->dirroot/blocks/mytermcourses/lib.php");
$lastyear = ($CFG->thisyear - 1).'-'.$CFG->thisyear;
$fetchedcourseid = optional_param('fetch', 0, PARAM_INT);

require_login();

// Header code.
$moodlefilename = '/blocks/mytermcourses/oldcourses.php';
$sitecontext = context_system::instance();

if (!has_capability('block/mytermcourses:createcourse', $sitecontext)) {

    $fetchedcourseid = 0;
}

$PAGE->set_context($sitecontext);
$PAGE->set_url($moodlefilename);
$title = get_string('pluginname', 'block_mytermcourses')." $lastyear";
$PAGE->set_title($title);
$PAGE->set_pagelayout('standard');
$PAGE->set_heading($title);
$PAGE->navbar->add(get_string('pluginname', 'block_mytermcourses'));
$PAGE->navbar->add($title);

$roleteacher = $DB->get_record('role', array('shortname' => 'editingteacher'))->id;
$roleappuiadmin = $DB->get_record('role', array('shortname' => 'appuiadmin'))->id;
//$rolestudent = $DB->get_record('role', array('shortname' => 'student'))->id;

$listoldteachedcourses = list_old_courses_for_role($roleteacher);
$listoldappuiadmincourses = list_old_courses_for_role($roleappuiadmin);
//$listoldstudiedcourses = list_old_courses_for_role($rolestudent);
// Les cours où l'utilisateur est enseignant.

$teacheroutput = trim(block_mytermcourses_oldcourses($listoldteachedcourses,
    'editingteacher', $fetchedcourseid));

echo $OUTPUT->header();

if ($teacheroutput) {

    echo '<h3>'.get_string('teacheroldcourses', 'block_mytermcourses').'</h3>';
    echo "$teacheroutput<br>";
}

// Les cours où l'utilisateur est appui administratif.
$adminoutput = trim(block_mytermcourses_oldcourses($listoldappuiadmincourses,
        'appuiadmin', $fetchedcourseid));

if ($adminoutput) {

    echo '<h3>'.get_string('adminoldcourses', 'block_mytermcourses').'</h3>';
    echo "$adminoutput<br>";
}

//// Les cours où l'utilisateur est étudiant.
//$studentoutput = trim(block_mytermcourses_oldcourses($listoldstudiedcourses, 'student', 0));
//
//if ($studentoutput) {
//
//    echo '<h3>'.get_string('studentoldcourses', 'block_mytermcourses').'</h3>';
//    echo "$studentoutput<br>";
//}

echo "<a href='$CFG->wwwroot/my'><button class='btn btn-primary'>".get_string('back')."</button></a>";
echo $OUTPUT->footer();

function list_old_courses_for_role($roleid) {

    global $USER, $CFG, $DB;

    $sql = "SELECT * FROM {course} WHERE idnumber LIKE '%$CFG->previousyearprefix%' AND id IN "
            . "(SELECT instanceid FROM {context} WHERE contextlevel = ".CONTEXT_COURSE." AND id IN"
            . "(SELECT contextid FROM {role_assignments} WHERE userid = $USER->id AND roleid = $roleid))"
            . "ORDER BY {course}.category";

    $listoldcourses = $DB->get_records_sql($sql);

    return $listoldcourses;
}