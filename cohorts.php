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
 * File : cohorts.php
 * Page where a course creator selects cohorts to populate his newly created course.
 *
 */

require_once('../../config.php');
require_once("$CFG->dirroot/blocks/mytermcourses/lib.php");
require_once("$CFG->dirroot/blocks/mytermcourses/cohorts_form.php");
require_once("$CFG->dirroot/cohort/lib.php");
require_once("$CFG->libdir/formslib.php");

$courseid = required_param('id', PARAM_INT);

// Header code.
$moodlefilename = '/blocks/mytermcourses/cohorts.php';
$sitecontext = context_system::instance();
$coursecontext = context_course::instance($courseid);
$PAGE->set_context($coursecontext);
$PAGE->set_url($moodlefilename);
$course = $DB->get_record('course', array('id' => $courseid));
$title = get_string('creatingcourse', 'block_mytermcourses');
$PAGE->set_title($title);
$PAGE->set_pagelayout('standard');
$PAGE->set_heading($title);

require_login($course);
$PAGE->navbar->add($title);
require_capability('block/mytermcourses:createcourse', $sitecontext);

$courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
$category = $DB->get_record('course_categories', array('id' => $course->category));
$levelcategory = $DB->get_record('course_categories', array('id' => $category->parent));

$mform = new block_mytermcourses_cohorts_form();

if ($mform->is_cancelled()) {

    redirect($courseurl);
} else if ($submitteddata = $mform->get_data()) {

    $studentroleid = $DB->get_record('role', array('shortname' => 'student'))->id;
    $cohortplugin = enrol_get_plugin('cohort');

    foreach ($submitteddata as $datakey => $datavalue) {

        $keyprefix = substr($datakey, 0, 6);
        if (($keyprefix == 'cohort') && $datavalue) {

            $cohortid = substr($datakey, 6);
            $cohort = $DB->get_record('cohort', array('id' => $cohortid));
            $group = new stdClass();
            $group->name = $cohort->name;
            $group->idnumber = $cohort->idnumber;
            $group->courseid = $courseid;
            $groupid = groups_create_group($group);
            $cohortplugin->add_instance($course,
                    array('customint1' => $cohortid, 'roleid' => $studentroleid, 'customint2' => $groupid));
            $trace = new null_progress_trace();
            enrol_cohort_sync($trace, $course->id);
            $trace->finished();
        }
    }

    redirect($courseurl);
} else {

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('creatingcourse', 'block_mytermcourses').' '.$course->fullname);
    echo '<p>'.get_string('incategory', 'block_mytermcourses').' '.$category->name.'</p>';

    echo '<p style="text-align:justify">'.get_string('cohortsare', 'block_mytermcourses').'</p>';
    $mform->display();
    echo "<p style='text-align:justify'><a href='$CFG->wwwroot/local/cohortmanager/viewinfo.php?"
            . "contextid=$coursecontext->id&origin=course'>".get_string('alllevelcohorts', 'block_mytermcourses').
            " $levelcategory->name</a></p>";
}

echo $OUTPUT->footer();
