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
 * File : block_mytermcourses.php
 * Block class definition
 */

require_once($CFG->dirroot.'/blocks/mytermcourses/lib.php');

use core_completion\progress;

class block_mytermcourses extends block_base {

    const COURSECAT_SHOW_COURSES_COLLAPSED = 10;
    const COURSECAT_SHOW_COURSES_AUTO = 15; /* will choose between collapsed and expanded automatically */
    const COURSECAT_SHOW_COURSES_EXPANDED = 20;
    const COURSECAT_SHOW_COURSES_EXPANDED_WITH_CAT = 30;

    public function init() {

        $this->title = get_string('mytermcourses', 'block_mytermcourses');
    }

    public function get_content() {
        global $CFG, $DB;


        if ($this->content !== null) {

            return $this->content;
        }

        $sitecontext = context_system::instance();

        $this->content = new stdClass;
        $this->content->text = '';
        $lastyear = ($CFG->thisyear - 1).'-'.$CFG->thisyear;
        $this->content->text .= "<a href='$CFG->wwwroot/blocks/mytermcourses/oldcourses.php'>"
                . "<button id='oldcoursesbutton' class='btn btn-success' style='margin:5px'>"
                .get_string('pluginname', 'block_mytermcourses').' '."$lastyear</button></a>&nbsp;&nbsp";

        if (has_capability('block/mytermcourses:createcourse', $sitecontext)) {

            $this->content->text .= "<a href='$CFG->wwwroot/blocks/mytermcourses/addcourse.php'>"
                    . "<button id='addcoursebutton' class='btn btn-info' style='margin:5px'>"
                    .get_string('addcourse', 'block_mytermcourses')."</button></a>&nbsp;&nbsp;";
        } else {

            $this->content->text .= "<button class='btn btn-secondary' style='margin:5px'"
                    . " onclick='block_mytermcourses_notfound()'>".get_string('cantseecourse', 'block_mytermcourses').
                    "</button>&nbsp;&nbsp;";
            $this->content->text .= "<div style='display:none' id='notfounddiv'>"
                    .get_string('tryenroldemands', 'block_mytermcourses')."</div>";
        }

        $this->content->text .= '<br><br>';

        $courses = $this->getusercourses();

        if (!$courses) {

            $this->content->text .= get_string('notenrolled', 'block_mytermcourses');
        } else {

            // User's course categories.
            $categoriesid = array();
            foreach ($courses as $course) {

                if (!in_array($course->category, $categoriesid)) {

                    array_push($categoriesid, $course->category);
                }
            }

            $categories = $this->sortcategories($categoriesid);

            // Display categories and courses.
            foreach ($categories as $category) {

                $categorycodeparts = explode('-', $category->idnumber);
                if ($categorycodeparts[0] == $CFG->yearprefix) {

                    $yearcategory = true;
                    $nextyear = $CFG->thisyear + 1;
                    $categoryprefix = "$CFG->thisyear-$nextyear / ";
                } else if ($categorycodeparts[0] == $CFG->previousyearprefix) {

                    $yearcategory = true;
                    $categoryprefix = "$CFG->previousyear-$CFG->thisyear / ";
                } else {

                    $yearcategory = false;
                    $categoryprefix = "";
                }

                $commoncategoriessettings = get_config('mytermcourses', 'Common_categories');

                if ($commoncategoriessettings) {

                    $commoncategoriesid = explode(';', $commoncategoriessettings);

                    if ($yearcategory && !in_array($category->id, $commoncategoriesid)) {

                        if ($categorycodeparts[0] == $CFG->yearprefix) {

                            $bgcolor = '#731472';
                        } else {

                            $bgcolor = '#28A745';
                        }
                    } else {

                        $bgcolor = '#A56E9D';
                    }

                    $style = "font-weight:bold;padding:5px;padding-left:10px;color:white;background-color:$bgcolor;"
                            . "width:100%";
                } else {

                    if ($yearcategory) {

                        if ($categorycodeparts[0] == $CFG->yearprefix) {

                            $bgcolor = '#731472';
                        } else {

                            $bgcolor = '#28A745';
                        }
                    } else {

                        $bgcolor = '#A56E9D';
                    }

                    $style="font-weight:bold;padding:5px;padding-left:10px;color:white;"
                            . "background-color:$bgcolor;width:100%";
                }

                $category = $DB->get_record('course_categories', array('id' => $category->id));
                $this->content->text .= "<p style='$style'>$categoryprefix$category->name</p>";
                $this->displaycourses($courses, $category);
            }
        }

        $this->content->footer = '';
        return $this->content;
    }

    private function getusercourses() {

        global $DB;

        $courses = enrol_get_my_courses('summary, summaryformat', 'fullname ASC');
        $courseids = array();

        foreach ($courses as $course) {

            $courseids[] = $course->id;
        }

        reset($courses);

        // Common categories.

        $commoncategoriessettings = get_config('mytermcourses', 'Common_categories');

        if ($commoncategoriessettings) {

            $commoncategoriesid = explode(';', $commoncategoriessettings);
            $commoncategories = $this->sortcategories($commoncategoriesid);
            foreach ($commoncategories as $commoncategory) {

                if (isset($commoncategory->id)) {

                    $commoncourses = $DB->get_records('course', array('category' => $commoncategory->id));
                    if (!$commoncourses) {

                        continue;
                    }

                    foreach ($commoncourses as $commoncourse) {

                        // Only get courses with guest access enabled.
                        $guestenrol = $DB->get_record('enrol', array('enrol' => 'guest', 'courseid' => $commoncourse->id));

                        if ($guestenrol->status) { // status = 1 means 'disabled method'.

                            continue;
                        }

                        if (!in_array($commoncourse->id, $courseids)) {

                            $courses[] = $commoncourse;
                        }
                    }
                }
            }
        }

        return $courses;
    }

    public function sortcategories($categoriesid) {

        // Changer ici pour définir l'ordre des catégories shouhaitées

        /* Année actuelle en premier, puis catégorie commune du bloc, puis année précédente,
         *  puis espaces personnels, puis espaces collaboratifs, puis le reste
         */

        global $DB, $CFG;

        $categories = array();
        $categoriesorder = array();

        foreach ($categoriesid as $categoryid) {

            $idnumber = $DB->get_field('course_categories', 'idnumber', array('id' => $categoryid));
            $suffix = substr($idnumber, -6);

            if ($suffix == 'COMMON') {

                $categoriesorder[$categoryid] = substr($idnumber, 0, -6);
            } else {

                $categoriesorder[$categoryid] = $idnumber;
            }
        }

        asort($categoriesorder);
        $currentyearcategories = array();
        $blockcommoncategories = array();
        $previousyearcategories = array();
        $blockpreviousyearcommoncategories = array();
        $personalspacecategories = array();
        $collaborativespacecategories = array();
        $othercategories = array();

        $haspersonalspace = false;

        foreach ($categoriesorder as $categoryid => $idnumber) {

            $prefix = substr($idnumber, 0, 5);

            $category = $DB->get_record('course_categories', array('id' => $categoryid));

            $commoncategoriessettings = get_config('mytermcourses', 'Common_categories');

            if ($prefix == $CFG->yearprefix) {

                if ($commoncategoriessettings) {

                    $commoncategoriesid = explode(';', $commoncategoriessettings);

                    if (in_array($category->id, $commoncategoriesid)) {

                        $blockcommoncategories[] = $category;
                    } else {

                        $currentyearcategories[] = $category;
                    }


                } else {

                    $currentyearcategories[] = $category;
                }
            } else if ($prefix == $CFG->previousyearprefix) {

                if ($commoncategoriessettings) {

                    $commoncategoriesid = explode(';', $commoncategoriessettings);

                    if (in_array($category->id, $commoncategoriesid)) {

                        $blockpreviousyearcommoncategories[] = $category;
                    } else {

                        $previousyearcategories[] = $category;
                    }
                } else {

                    $previousyearcategories[] = $category;
                }
            } else if ($idnumber == 'PERSO') {

                $personalspacecategories[] = $category;
            } else if ($idnumber == 'COLLAB') {

                $collaborativespacecategories[] = $category;
            } else {

                $othercategories[] = $category;
            }
        }

        /**
         * S'il y a un espace personnel, les espaces collaboratifs ne seront pas affichés
         * (on ne veut pas d'étudiants dans les espaces collaboratifs).
         */

        foreach ($currentyearcategories as $category) {

            array_push($categories, $category);
        }

        foreach ($blockcommoncategories as $category) {

            array_push($categories, $category);
        }

        foreach ($previousyearcategories as $category) {

            array_push($categories, $category);
        }

        foreach ($blockpreviousyearcommoncategories as $category) {

            array_push($categories, $category);
        }

        foreach ($personalspacecategories as $category) {

            array_push($categories, $category);

            $haspersonalspace = true;
        }

        if (!$haspersonalspace) {

            foreach ($collaborativespacecategories as $category) {

                array_push($categories, $category);
            }
        }

        foreach ($othercategories as $category) {

            array_push($categories, $category);
        }



//            // Ancienne version. A effacer ensuite.
//
//            if (($idnumber == 'COLLAB')||($idnumber == 'PERSO')) {
//
//                if ($lastcategoryid) {
//
//                    $afterlastcategoryid = $categoryid;
//                } else {
//
//                    $lastcategoryid = $categoryid;
//                }
//
//            } else {
//
//                array_push($categories, $category);
//            }
//        }
//        if ($lastcategoryid) {
//
//            $lastcategory = $DB->get_record('course_categories', array('id' => $lastcategoryid));
//            array_push($categories, $lastcategory);
//        }
//        if ($afterlastcategoryid) {
//
//            $afterlastcategory = $DB->get_record('course_categories', array('id' => $afterlastcategoryid));
//            array_push($categories, $afterlastcategory);
//        }

        return $categories;
    }

    public function displaycourses($courses, $category) {

        $this->content->text .= '<div style="overflow:auto">';

        foreach ($courses as $course) {

            if ($course->category == $category->id) {

                $this->content->text .= block_mytermcourses_displaycourse($course);
            }
        }

        $this->content->text .= '</div>';
        $this->content->text .= '<br><br>';
    }

    public function specialization() {

        global $CFG;
        $currentyear = $CFG->thisyear.'-'.($CFG->thisyear + 1);
        $this->title = get_string('pluginname', 'block_mytermcourses')." $currentyear";
    }

    public function has_config() {

        return true;
    }
}

