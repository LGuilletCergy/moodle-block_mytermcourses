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
$oldyear = optional_param('year', $lastyear, PARAM_TEXT);
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
$title = get_string('pluginname', 'block_mytermcourses')." $oldyear";
$PAGE->set_title($title);
$PAGE->set_pagelayout('standard');
$PAGE->set_heading($title);
$PAGE->navbar->add(get_string('pluginname', 'block_mytermcourses'));
$PAGE->navbar->add($title);

$connection = ssh2_connect('enp16.u-cergy.fr', 22);
ssh2_auth_password($connection, 'enp17', 'Hrso3[(à');

$courselistcommand = "php /var/www/moodle/enp17.php $USER->username";
$courseliststream = ssh2_exec($connection, $courselistcommand);
stream_set_blocking($courseliststream, true);
$courseliststreamout = ssh2_fetch_stream($courseliststream, SSH2_STREAM_STDIO);
$courselist = stream_get_contents($courseliststreamout);
$oldcourses = explode('£§£', $courselist);

$oldteachedcourses = explode('£µ£', $oldcourses[0]);
$oldstudiedcourses = explode('£µ£', $oldcourses[1]);
$oldadmincourses = explode('£µ£', $oldcourses[2]);

$teacheroutput = trim(block_mytermcourses_oldcourses($oldteachedcourses,
        'editingteacher', $fetchedcourseid, $connection));
$studentoutput = trim(block_mytermcourses_oldcourses($oldstudiedcourses, 'student', 0, 0, null));
$adminoutput = trim(block_mytermcourses_oldcourses($oldadmincourses, 'appuiadmin', $fetchedcourseid, $connection));

echo $OUTPUT->header();

if ($teacheroutput) {

    echo '<h3>'.get_string('teacheroldcourses', 'block_mytermcourses').'</h3>';
    echo "$teacheroutput<br>";
}
if ($adminoutput) {

    echo '<h3>'.get_string('adminoldcourses', 'block_mytermcourses').'</h3>';
    echo "$adminoutput<br>";
}
if ($studentoutput) {

    echo '<h3>'.get_string('studentoldcourses', 'block_mytermcourses').'</h3>';
    echo "$studentoutput<br>";
}

echo "<a href='$CFG->wwwroot/my'><button class='btn btn-primary'>".get_string('back')."</button></a>";
echo $OUTPUT->footer();

