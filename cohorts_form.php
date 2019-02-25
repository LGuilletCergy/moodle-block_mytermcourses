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
 * @author     Brice Errandonea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * File : cohorts_form.php
 * Defines the form where a course creator selects cohorts for his course.
 *
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

require_once("$CFG->libdir/formslib.php");

class block_mytermcourses_cohorts_form extends moodleform {
	public function definition() {
		global $CFG, $COURSE, $DB, $USER;

        $courseidnumber = $COURSE->idnumber;
        $coursecategory = $DB->get_record('course_categories', array('id' => $COURSE->category));
        $levelcategory =  $DB->get_record('course_categories', array('id' => $coursecategory->parent));
        if (strpos($courseidnumber, '+')) {
			$idnumberparts = explode('+', $courseidnumber);
			$courseidnumber = $idnumberparts[0];			
		}
		$idnumberparts2 = explode('-', $courseidnumber);
		$courseelpcode = $idnumberparts2[2];
        $availablecoursecohorts = $DB->get_records('local_cohortmanager_info', array('codeelp' => "$CFG->yearprefix-$courseelpcode"));

        $mform =& $this->_form;
        $mform->addElement('header', 'generalheader', get_string('suggestedcohorts', 'block_mytermcourses'));
        //~ $mform->addElement('static', 'cohortdef', '', '<p style="text-align:justify">'.get_string('cohortsare', 'block_mytermcourses').'</p>');
        if ($availablecoursecohorts) {
			$mform->addElement('static', 'somecohorts', '', get_string('choosecohorts', 'block_mytermcourses'));
		} else {
			$mform->addElement('static', 'nocohort', '', get_string('noknowncohorts', 'block_mytermcourses'));
		}

        $mform->addElement('hidden', 'id', $COURSE->id);
        $mform->setType('id', PARAM_INT);

        foreach ($availablecoursecohorts as $availablecoursecohort) {			
            $cohortlocalname = $DB->get_field('local_cohortmanager_names', 'cohortname', array('cohortid' => $availablecoursecohort->cohortid));
            if (!$cohortlocalname) {
				$cohortlocalname = $DB->get_field('cohort', 'name', array('id' => $availablecoursecohort->cohortid));
			}
            $cohortcode = $DB->get_field('cohort', 'idnumber', array('id' => $availablecoursecohort->cohortid));
            if ($availablecoursecohort->teacherid == $USER->id) {
                $checked = 1;
            } else {
                $checked = 0;
            }
            $boxid = "cohort$availablecoursecohort->cohortid";
            $mform->addElement('advcheckbox', $boxid, $cohortcode, $cohortlocalname);
            $mform->setType($boxid, PARAM_INT);
            $mform->setDefault($boxid, $checked);
        }
        $this->add_action_buttons();
	}       
}
