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
 * File : lib.php
 * Functions library.
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/coursecatlib.php');
require_once("$CFG->dirroot/course/lib.php");

function block_mytermcourses_createcourse($coursename, $courseidnumber, $categoryid) {

    global $DB, $USER;

    $firstcourseidnumber = $courseidnumber;
    $courseidnumber = block_mytermcourses_tryidnumber('course', $courseidnumber, 0);
    $courseshortname = block_mytermcourses_tryshortname($coursename, 0);
    $rolelocalstaff = $DB->get_record('role', array('shortname' => 'localstaff'));
    $rolelocalteacher = $DB->get_record('role', array('shortname' => 'localteacher'));
    $roleeditingteacher = $DB->get_record('role', array('shortname' => 'editingteacher'));
    $roleappuiadmin = $DB->get_record('role', array('shortname' => 'appuiadmin'));

    $islocalteacher = $DB->record_exists('role_assignments',
            array('roleid' => $rolelocalteacher->id, 'contextid' => 1, 'userid' => $USER->id));
    if ($islocalteacher) {

        $roleincourse = $roleeditingteacher;
    } else {

        $roleincourse = $roleappuiadmin;
    }

    $coursedata = new stdClass;
    $coursedata->fullname = $coursename;
    $coursedata->shortname = $courseshortname;
    $coursedata->category = $categoryid;
    $coursedata->idnumber = $courseidnumber;
    $coursedata->format = 'topics';

    $newcourse = create_course($coursedata);
    $newcontext = context_course::instance($newcourse->id, MUST_EXIST);

    block_mytermcourses_createsections($newcourse, $newcontext);

    // Current user is enroled in the new course, with appropriate role.
    $enrolmethod = $DB->get_record('enrol', array('enrol' => 'manual', 'courseid' => $newcontext->instanceid));
    $now = time();
    $roleassignment = new stdClass();
    $roleassignment->roleid = $roleincourse->id;
    $roleassignment->contextid = $newcontext->id;
    $roleassignment->userid = $USER->id;
    $roleassignment->timemodified = $now;
    $roleassignment->modifierid = $USER->id;
    $DB->insert_record('role_assignments', $roleassignment);
    $enrolment = new stdClass();
    $enrolment->enrolid = $enrolmethod->id;
    $enrolment->userid = $USER->id;
    $enrolment->timestart = $now;
    $enrolment->timecreated = $now;
    $enrolment->timemodified = $now;
    $enrolment->modifierid = $USER->id;
    $DB->insert_record('user_enrolments', $enrolment);

    return $newcourse;
}

function block_mytermcourses_createsections($newcourse, $newcontext) {

    global $CFG, $DB;

    $specialcategoryidnumber = $CFG->yearprefix."-1";

    $specialcategoryid = $DB->get_record('course_categories', array('idnumber' => $specialcategoryidnumber))->id;

    $specialcontextid = $DB->get_record('context',
            array('contextlevel' => CONTEXT_COURSECAT, 'instanceid' => $specialcategoryid))->id;

    $specialcontexts = array($specialcontextid => array('DESCRIPTION DU COURS', 'PLAN DE COURS', 'FICHES TD',
        'INFORMATIONS GENERALES', 'SUPPORT ET NOTES DE COURS'));
    $newcontextpath = explode('/', $newcontext->path);
    $closestcontextid = 0;

    foreach ($newcontextpath as $parentcontextid) {

        if (array_key_exists($parentcontextid, $specialcontexts)) {

            $closestcontextid = $parentcontextid;
        }
    }

    $now = time();
    $numsectionsoption = new stdClass();
    $numsectionsoption->courseid = $newcourse->id;
    $numsectionsoption->format = 'topics';
    $numsectionsoption->sectionid = 0;
    $numsectionsoption->name = 'numsections';
    if ($closestcontextid) {

        $sectiontitles = $specialcontexts[$closestcontextid];
        $numsectionsoption->value = count($sectiontitles);
        $DB->insert_record('course_format_options', $numsectionsoption);
        $i = 1;

        foreach ($sectiontitles as $sectiontitle) {

            $coursesection = new stdClass();
            $coursesection->course = $newcourse->id;
            $coursesection->section = $i;
            $coursesection->name = $sectiontitle;
            $coursesection->summary = '';
            $coursesection->summaryformat = 1;
            $coursesection->sequence = '';
            $coursesection->visible = 1;
            $coursesection->timemodified = $now;
            $DB->insert_record('course_sections', $coursesection);
            $i++;
        }
    } else {

        $courseconfig = get_config('moodlecourse');
        $numsectionsoption->value = $courseconfig->numsections;
        $DB->insert_record('course_format_options', $numsectionsoption);
        for ($i = 1; $i <= $numsectionsoption->value; $i++) {

            $coursesection = new stdClass();
            $coursesection->course = $newcourse->id;
            $coursesection->section = $i;
            $coursesection->summary = '';
            $coursesection->summaryformat = 1;
            $coursesection->sequence = '';
            $coursesection->visible = 1;
            $coursesection->timemodified = $now;
            $DB->insert_record('course_sections', $coursesection);
        }
    }
}

function block_mytermcourses_oldcourses($oldcourses, $rolename, $fetchedcourseid, $connection) {

    global $CFG, $DB;

    $html = '';
    $currentidnumber = '';
    $categorystyle = "font-weight:bold;padding:5px;color:white;background-color:gray;width:100%";
    foreach ($oldcourses as $oldcourse) {

        $oldcoursetable = explode(';', $oldcourse);
        $oldcategoryidnumber = $oldcoursetable[0];
        if (!$oldcategoryidnumber) {

            continue;
        }
        $oldcategoryname = $oldcoursetable[1];
        $oldcourseidnumber = $oldcoursetable[2];
        $oldcoursename = $oldcoursetable[3];
        $oldcourseid = $oldcoursetable[4];
        if (isset($oldcoursetable[5])) {

            $oldparentcategoryidnumber = $oldcoursetable[5];
        } else {

            $oldparentcategoryidnumber = '';
        }

        if ($currentidnumber != $oldcategoryidnumber) {

            $currentidnumber = $oldcategoryidnumber;
            $html .= "<br><p style='$categorystyle'>$oldcategoryname</p>";
            //~ $html .= '<strong>'.$oldcategoryname.'</strong>';
        }

        $html .= '<p>';
        $html .= "<a href='https://enp16.u-cergy.fr/course/view.php?id=$oldcourseid' target='_blank'>";
        $html .= $oldcoursename;
        $html .= "</a>&nbsp;&nbsp;";

        if (($rolename == 'editingteacher')||($rolename == 'appuiadmin')) {

            $cantransfer = true;
            $sitecontext = context_system::instance();
            if (!has_capability('block/mytermcourses:createcourse', $sitecontext)) {

                $cantransfer = false;
            }

            if ($rolename == 'editingteacher') {

                //TODO : si le cours est dans l'offre pédagogique cette année et que cet utilisateur ne l'enseigne plus,
                // ne pas permettre le transfert.
            }

            $newcourseidnumber = block_mytermcourses_newidnumber($oldcourseidnumber);

            if ($newcourseidnumber == '') {

                continue;
            }

            $newcategoryidnumber = block_mytermcourses_newidnumber($oldcategoryidnumber);
            $newparentcategoryidnumber = block_mytermcourses_newidnumber($oldparentcategoryidnumber);
            $newcategoryname = block_mytermcourses_newname($oldcategoryname);

            if ($fetchedcourseid && $cantransfer) {

                if ($oldcourseid == $fetchedcourseid) {

                    block_mytermcourses_fetchcourse($connection, $fetchedcourseid, $newcourseidnumber, $newcategoryname,
                            $newcategoryidnumber, $newparentcategoryidnumber);
                }
            }

            $similarnewcourses = block_mytermcourses_newcourses($newcourseidnumber);

            if ($similarnewcourses) {

                if ($connection) {

                    $againconfirm = block_mytermcourses_againconfirm($oldcourseid, $connection);
                }

                $html .= "<button class='btn btn-secondary' onclick='block_mytermcourses_newcourses(\"$newcourseidnumber\")'>".get_string('similarnewcourses', 'block_mytermcourses')."</button>";
                $html .= '&nbsp;';
                $html .= "<button class='btn btn-secondary' onclick='block_mytermcourses_again(\"$newcourseidnumber\")'>".get_string('fetchagain', 'block_mytermcourses')."</button>";
                $html .= "<div style='display:none' id='again$newcourseidnumber'>$againconfirm</div>";
                $html .= "<div style='display:none' id='sim$newcourseidnumber'>$similarnewcourses</div>";
            } else if ($cantransfer) {

                $html .= "<a href='oldcourses.php?fetch=$oldcourseid'>";
                $html .= '<button class="btn btn-secondary">'.get_string('fetch', 'block_mytermcourses').'</button>';
                $html .= "</a>&nbsp;";
            }
        }

        $html .= '</p>';
    }

    return $html;
}

function block_mytermcourses_formatsform() {

    global $CFG;
    $picturesdir = "$CFG->dirroot/blocks/mytermcourses/pictures";
}

function block_mytermcourses_againconfirm($oldcourseid, $connection) {

    $html = '<div>';
    $html .= get_string('reallyfetchagain', 'block_mytermcourses').'&nbsp';
    $html .= "<a href='oldcourses.php?fetch=$oldcourseid'>";
    $html .= '<button class="btn btn-primary">'.get_string('fetchagain', 'block_mytermcourses').'</button>';
    $html .= "</a>&nbsp;";
    $html .= '</div>';
    return $html;
}

function block_mytermcourses_similarcourses($newcourseidnumber) {

    global $DB;
    $html = '';
    $sql = "SELECT id, idnumber, fullname, shortname, visible FROM {course} WHERE idnumber = '$newcourseidnumber' OR"
            . " idnumber LIKE '$newcourseidnumber+%'";
    $newcourses = $DB->get_records_sql($sql);
    return $newcourses;
}

function block_mytermcourses_newcourses($newcourseidnumber) {

    $newcourses = block_mytermcourses_similarcourses($newcourseidnumber);
    $html = '';

    if ($newcourses) {

        $html .= '<div style="overflow:auto">';
        foreach ($newcourses as $newcourse) {

            $html .= block_mytermcourses_displaycourse($newcourse);
        }

        $html .= '</div>';
        $html .= '<br>';
    }

    return $html;
}

function block_mytermcourses_newname($oldname) {

    global $CFG;

    $oldyear = 'Y2017-';
    $newyear = $CFG->yearprefix.'-';
    $length = strlen($oldyear);
    $oldyearposition = strpos($oldname, $oldyear);

    if ($oldyearposition === false) {

        $newname = $oldname;
    } else {

        $newname = substr_replace($oldname, $newyear, $oldyearposition, $length);
    }

    return $newname;
}

function block_mytermcourses_newidnumber($oldidnumber) {

    global $CFG;

    if (strpos($oldidnumber, '+')) {

        $idnumbertable = explode('+', $oldidnumber);
        $oldidnumber = $idnumbertable[0];
    }
    if (substr($oldidnumber, 0, 6) == 'Y2017-') {

        $newidnumber = substr_replace($oldidnumber, $CFG->yearprefix, 0, 5);

        if ((substr($newidnumber, -1, 1) == 'A') && (substr($newidnumber, -3, 1) == '-')) {

            $newidnumber .= 'U';
        }
    } else if (substr($oldidnumber, 0, 6) == 'COLLAB') {

        $newidnumber = $oldidnumber;
    } else {

        $newidnumber = '';
    }
    return $newidnumber;
}

/**
 * $parentfield is idnumber if fetching an old course or id if creating aside pedagooffer.
 * $parentvalue is the value of this parent category's field.
 */
function block_mytermcourses_createcategory($name, $idnumber, $parentfield, $parentvalue) {

    global $DB;

    $parentcategory = $DB->get_record('course_categories', array($parentfield => $parentvalue));

    if ($parentcategory) {

        $categorydata = array('name' => $name, 'idnumber' => $idnumber, 'parent' => $parentcategory->id, 'visible' => 1);
        $newcategory = coursecat::create($categorydata);

        return $newcategory;
    } else {

        return null;
    }
}

function block_mytermcourses_idnumbercounter($nature, $parentid) {

    global $CFG, $DB;

    $blockdir = "$CFG->dirroot/blocks/mytermcourses";
    $parentcategory = $DB->get_record('course_categories', array('id' => $parentid));

    if ($nature == 'category') {

        $counterfile = "$blockdir/vetcompteur";
        $separator = 'V';
        $grandparentcategory = $DB->get_record('course_categories', array('id' => $parentcategory->parent));
    } else {

        $counterfile = "$blockdir/elpcompteur";
        $separator = 'E';
        $parentidnumbertable = explode('-', $parentcategory->idnumber);
        $ufrcode = substr($parentidnumbertable[1], 0, 1);
    }
    $next = system("cat $counterfile");
    $command = "echo '".($next+1)."' > $counterfile";
    system($command);

    if ($nature == 'category') {

        $idnumber = $grandparentcategory->idnumber.$CFG->thisyear.$separator.$next;
    } else {

        $idnumber = $parentcategory->idnumber.'-'.$ufrcode.$CFG->thisyear.$separator.$next;
    }

    return $idnumber;
}

function block_mytermcourses_fetchcourse($connection, $fetchedcourseid, $newcourseidnumber,
        $newcategoryname, $newcategoryidnumber, $newparentcategoryidnumber) {

    global $CFG, $DB;

    $unmarkcommand = "php /var/www/moodle/enp17unmark.php $fetchedcourseid";
    ssh2_exec($connection, $unmarkcommand);
    $backupcommand = "cd /var/www/moodle && moosh -n course-backup -f "
            . "/var/movingcourses/course$fetchedcourseid.mbz $fetchedcourseid";
    ssh2_exec($connection, $backupcommand);
    $fetchcommand = "scp enp17@enp16.u-cergy.fr:/var/movingcourses/course$fetchedcourseid.mbz /var/enp16courses";
    $retry = 1;
    $tries = 0;

    while ($retry && $tries < 50) {    // Retry fetching if it didn't work, but not more than 50 times.

        system($fetchcommand, $retry);
        $tries++;
    }

    $newcategory = $DB->get_record('course_categories', array('idnumber' => $newcategoryidnumber));

    if (!$newcategory) {

        $newcategory = block_mytermcourses_createcategory($newcategoryname, $newcategoryidnumber,
                'idnumber', $newparentcategoryidnumber);
    }
    if (!$newcategory) {

        echo "<h3>Erreur : impossible de créer la catégorie de cours.</h3>";
        exit;
    }

    $restorecommand = "cd $CFG->dirroot && moosh -n course-restore "
            . "/var/enp16courses/course$fetchedcourseid.mbz $newcategory->id";
    $restoreoutput = system($restorecommand);
    $restoretable = explode("New course ID for ", $restoreoutput);
    $errorstring = "<h3>Une erreur s'est produite. Les indications ci-dessus (s'il y en a) peuvent aider "
            . "le Service d'ingénierie pédagogique à résoudre ce problème. S'il n'y en a pas, essayez de recharger "
            . "cette page : il arrive que ça fonctionne la deuxième fois.</h3>";

    if ($restoretable) {

        if (isset($restoretable[1])) {

            block_mytermcourses_preparerestoredcourse($restoretable, $newcourseidnumber);
        } else {

            block_mytermcourses_error($errorstring);
        }
    } else {

        block_mytermcourses_error($errorstring);
    }
}

function block_mytermcourses_error($errorstring) {

    global $OUTPUT;
    echo $OUTPUT->header();
    echo $errorstring;
    echo $OUTPUT->footer();
    exit;
}

function block_mytermcourses_tryidnumber($table, $triedidnumber, $i) {

    global $DB;
    $newidnumber = $triedidnumber;

    if ($i) {

        $newidnumber .= "+$i";
    }

    $already = $DB->record_exists($table, array('idnumber' => $newidnumber));

    if ($already) {

        return block_mytermcourses_tryidnumber($table, $triedidnumber, $i + 1);
    } else {

        return $newidnumber;
    }
}

function block_mytermcourses_tryshortname ($coursename, $i) {

    global $DB;

    $newshortname = $coursename;

    if ($i) {

        $newshortname .= "_$i";
    }

    $already = $DB->record_exists('course', array('shortname' => $newshortname));

    if ($already) {

        return block_mytermcourses_tryshortname($newshortname, $i + 1);
    } else {

        return $newshortname;
    }
}

function block_mytermcourses_preparerestoredcourse($restoretable, $newcourseidnumber) {

    global $DB;

    $titleandids = explode(':', $restoretable[1]);
    $idtable = explode(' ', $titleandids[1]);
    $first = substr($idtable[1], 0, 1);

    if ($first == '_') {

        $restoredcourseid = $idtable[2];
    } else {

        $restoredcourseid = $idtable[1];
    }

    $restoredcourse = $DB->get_record('course', array('id' => $restoredcourseid));
    $restoredcourse->shortname = $restoredcourse->fullname;
    $restoredcourse->idnumber = block_mytermcourses_tryidnumber('course', $newcourseidnumber, 0);
    $restoredcourse->groupmode = 1;
    $restoredcourse->enablecompletion = 1;
    $DB->update_record('course', $restoredcourse);
    //~ $DB->set_field('course', 'idnumber', $newcourseidnumber, array('id' => $restoredcourseid));
    block_mytermcourses_enrolcreator($restoredcourseid);
    // Set coursedisplay topics format option to 0 if it's not already set.
    $coursedisplayset = $DB->record_exists('course_format_options',
            array('courseid' => $restoredcourseid, 'format' => 'topics', 'name' => 'coursedisplay'));

    if (!$coursedisplayset) {

        $option = new stdClass();
        $option->courseid = $restoredcourseid;
        $option->format = 'topics';
        $option->sectionid = 0;
        $option->name = 'coursedisplay';
        $option->value = 0;
        $DB->insert_record('course_format_options', $option);
    }

    //TODO : rediriger plutôt vers la page d'affectation des cohortes.
    $courseurl = new moodle_url('/course/view.php', array('id' => $restoredcourseid));
    redirect($courseurl);
}

function block_mytermcourses_enrolcreator($courseid) {

    global $DB, $USER;

    $now = time();
    $manualenrol = $DB->get_record('enrol', array('courseid' => $courseid, 'enrol' => 'manual'));
    $creatorenrolment = new stdClass();
    $creatorenrolment->enrolid = $manualenrol->id;
    $creatorenrolment->userid = $USER->id;
    $creatorenrolment->timestart = $now;
    $creatorenrolment->timeend = 0;
    $creatorenrolment->modifierid = $USER->id;
    $creatorenrolment->timecreated = $now;
    $creatorenrolment->timemodified = $now;
    $DB->insert_record('user_enrolments', $creatorenrolment);
    $role = $DB->get_record('role', array('shortname' => 'editingteacher'));
    $coursecontext = context_course::instance($courseid);
    role_assign($role->id, $USER->id, $coursecontext->id);
}

function block_mytermcourses_displaycourse($course) {

    global $CFG;

    $coursewidth = 280;
    $courseheight = 160;
    $coursestyle = 'border:1px solid gray;margin:10px;float:left;padding:10px;border-radius:5px;overflow:hidden';
    $courseurl = $CFG->wwwroot.'/course/view.php?id='.$course->id;

    if ($course->visible) {

        $hiddencolor = '';
    } else {

        $hiddencolor = 'color:gray';
    }

    $html = "<div style='width:$coursewidth;height:$courseheight;$coursestyle' class='coursecard'>";
    $html .= "<div style='overflow:hidden'>";
    $coursename = $course->fullname;
    $html .= "<a style='font-weight:bold;font-size:16;$hiddencolor' href='$courseurl'>$coursename</a>";
    $html .= "<div style='font-size:10;margin:5px;margin-left:5px;float:right'>$course->idnumber</div>";
    $imageheight = '80px';
    $imagewidth = '110px';
    $html .= "<div style='text-align:center;max-width:$imagewidth;max-height:$imageheight;float:left;margin:10px'>";
    $html .= block_mytermcourses_courseimage($course, $imagewidth, $imageheight);
    $html .= "</div>";
    $html .= "<div style='float:right'>";
    $html .= block_mytermcourses_coursecontacts($course);
    $html .= "</div>";
    $html .= '</div></div>';
    return $html;
}

function block_mytermcourses_courseimage($courserecord, $imagewidth, $imageheight) {

    global $CFG;

    $course = new course_in_list($courserecord);
    $content = '';
    // display course overview files
    $contentimages = $contentfiles = '';

    foreach ($course->get_course_overviewfiles() as $file) {

        $isimage = $file->is_valid_image();
        $url = file_encode_url("$CFG->wwwroot/pluginfile.php",
                '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);

        if ($isimage) {

            $contentimages .= html_writer::tag('div',
                    html_writer::empty_tag('img',
                            array('src' => $url, 'style' => "max-width:$imagewidth;max-height:$imageheight")),
                    array('class' => 'courseimage'));
        } else {

            $image = $this->output->pix_icon(file_file_icon($file, 24), $file->get_filename(), 'moodle');
            $filename = html_writer::tag('span', $image, array('class' => 'fp-icon')).
                    html_writer::tag('span', $file->get_filename(), array('class' => 'fp-filename'));
            $contentfiles .= html_writer::tag('span', html_writer::link($url, $filename),
                    array('class' => 'coursefile fp-filename-icon'));
        }
    }
    $content .= $contentimages. $contentfiles;
    return $content;
}

function block_mytermcourses_coursecontacts2($courserecord) {

    $course = new course_in_list($courserecord);
    $content = '';

    if ($course->has_course_contacts()) {

        $coursecontacts = $course->get_course_contacts();
        $nbcontacts = count($coursecontacts);

        if ($nbcontacts > 1) {

            $s = 's';
        } else {

            $s = '';
        }
        $currentrole = '';

        foreach ($coursecontacts as $userid => $coursecontact) {

            if ($coursecontact['rolename'] != $currentrole) {

                $currentrole = $coursecontact['rolename'];
                $content .= '<br>'.$coursecontact['rolename'].$s.' : ';
            } else {

                $content .= ', ';
            }

            $name = html_writer::link(new moodle_url('/user/view.php',
                    array('id' => $userid, 'course' => SITEID)), $coursecontact['username']);
            $content .= $name;
        }
    }

    return $content;

}

function block_mytermcourses_coursecontacts($courserecord) {

    $course = new course_in_list($courserecord);
    $content = '<div style="font-size:10;margin:2">';

    if ($course->has_course_contacts()) {

        $coursecontacts = $course->get_course_contacts();
        $courseteachers = array();

        foreach ($coursecontacts as $coursecontact) {

            if ($coursecontact['role']->shortname == 'editingteacher') {

                $courseteachers[] = $coursecontact;
            }
        }

        $nbteachers = count($courseteachers);

        if ($nbteachers > 1) {

            $s = 's';
        } else {

            $s = '';
        }

        $content .= '<span style="font-weight:bold">'.$coursecontact['rolename'].$s.' :</span>';
        $content .= '<ul>';
        $numteacher = 1;

        foreach ($courseteachers as $courseteacher) {

            $name = html_writer::link(new moodle_url('/user/view.php',
                    array('id' => $courseteacher['user']->id, 'course' => SITEID)), $courseteacher['username']);
            if ($numteacher == 5) {

                $content .= '<li>etc.</li>';
                break;
            } else {

                $content .= "<li>$name</li>";
                $numteacher++;
            }
        }

        $content .= '</ul>';
    }

    $content .= '</div>';
    return $content;
}

function block_mytermcourses_coursesummary($courserecord) {

    //TODO : gérer chelper
    $course = new course_in_list($courserecord);
    $content = '';
    // display course summary

    if ($course->has_summary()) {

        //~ $content .= html_writer::start_tag('div', array('class' => 'summary', 'style' => 'font-size:11'));
        $chelper = new coursecat_helper();
        //~ $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_EXPANDED);
        $chelper->set_show_courses(1);
        $content .= $chelper->get_course_formatted_summary($course,
                array('overflowdiv' => true, 'noclean' => true, 'para' => false));
        //~ $content .= html_writer::end_tag('div'); // .summary
    }

    var_dump($content);
    return $content;
}

function block_mytermcourses_availablevets() {

    global $CFG, $USER;

    $myvets = array();
    $filename = '/home/referentiel/dokeos_elp_ens.xml';

    if (filesize($filename) > 0) {

        $xmldoc = new DOMDocument();
        $xmldoc->load($filename);
        $xpathvar = new Domxpath($xmldoc);
        $vetquery = '//Structure_diplome[Teacher[@StaffUID="'.$USER->username.'"]]';
        $xmlvets = $xpathvar->query($vetquery);

        foreach ($xmlvets as $xmlvet) {

            $vetcode = $xmlvet->getAttribute('Etape');
            $myvets[$vetcode] = new stdClass();
            $myvets[$vetcode]->vetcodeyear = "$CFG->yearprefix-$vetcode";
            $myvets[$vetcode]->vetname = $xmlvet->getAttribute('libelle_long_version_etape');
            $myvets[$vetcode]->courses = block_mytermcourses_availablecourses($xmlvet, $myvets[$vetcode]->vetcodeyear);
        }
    }
    return $myvets;
}

function block_mytermcourses_availablecourses($xmlvet, $vetcodeyear) {

    global $USER;

    $vetcourses = array();
    $xmlteachers = $xmlvet->childNodes;

    foreach ($xmlteachers as $xmlteacher) {

        if ($xmlteacher->nodeType !== 1 ) {
            continue;
        }

        $xmlteacheruid = $xmlteacher->getAttribute('StaffUID');

        if ($xmlteacheruid == $USER->username) {

            $xmlcourses = $xmlteacher->childNodes;

            foreach ($xmlcourses as $xmlcourse) {

                if ($xmlcourse->nodeType !== 1) {

                        continue;
                }
                $coursecode = $xmlcourse->getAttribute('element_pedagogique');
                $vetcourses[$coursecode] = new stdClass();
                $vetcourses[$coursecode]->coursecodeyear = "$vetcodeyear-$coursecode";
                $vetcourses[$coursecode]->coursename = $xmlcourse->getAttribute('libelle_long_element_pedagogique');
            }
        }
    }

    return $vetcourses;
}

function block_mytermcourses_availablegroups($xmlcourse, $vetcodeyear) {

    $coursegroups = array();
    $xmlgroups = $xmlcourse->childNodes;
    //TODO
    return $coursegroups;
}

function block_mytermcourses_showavailablecourses($availablevet) {

    global $DB;

    $coursecategory = $DB->get_record('course_categories', array('idnumber' => $availablevet->vetcodeyear));

    foreach ($availablevet->courses as $availablecourse) {

        $alreadycourses = block_mytermcourses_similarcourses($availablecourse->coursecodeyear);
        echo "<div style='text-align:left'>";
        echo "<div  style='float:left;margin-right:30px'>";
        echo "<span style='font-weight:bold'>$availablecourse->coursename</span>";
        echo "&nbsp; &nbsp;<span style='font-size:10'>($availablecourse->coursecodeyear)</span></p>";
        echo "</div>";

        if ($alreadycourses) {

            $alreadycoursesgroup = '<div style="overflow:auto">';

            foreach ($alreadycourses as $alreadycourse) {

                $alreadycoursesgroup .= block_mytermcourses_displaycourse($alreadycourse);
            }

            $alreadycoursesgroup .= '</div>';
            $againconfirm = '<div style="text-align:center">'.get_string('reallycreateagain', 'block_mytermcourses')
                    .block_mytermcourses_creationform($coursecategory->id, $availablecourse->coursecodeyear,
                            $availablecourse->coursename).'</div>';

            echo "<button class='btn btn-secondary' ".
                    "onclick='block_mytermcourses_newcourses(\"$availablecourse->coursecodeyear\")'>".
                    get_string('alreadycreated', 'block_mytermcourses')."</button>";
            echo '&nbsp;';
            echo "<button class='btn btn-secondary' "
            ."onclick='block_mytermcourses_again(\"$availablecourse->coursecodeyear\")'>".
                    get_string('createagain', 'block_mytermcourses')."</button>";
            echo '<br><br>';
            echo "<div style='display:none' id='again$availablecourse->coursecodeyear'>$againconfirm</div>";
            echo "<div style='display:none' id='sim$availablecourse->coursecodeyear'>$alreadycoursesgroup</div>";

        } else {

            echo block_mytermcourses_creationform($coursecategory->id,
                    $availablecourse->coursecodeyear, $availablecourse->coursename);
        }

        echo "</div>";
    }
}

function block_mytermcourses_creationform($categoryid, $courseidnumber, $coursename) {

    $html = "<form action='addcourse.php'>";
    $html .= "<input type='hidden' name='category' value='$categoryid'>";
    $html .= "<input type='hidden' name='idnumber' value='$courseidnumber'>";
    $html .= '<input type="hidden" name="coursename" value="'.$coursename.'">';
    $html .= "<input type='hidden' name='sesskey' value='".sesskey()."'>";
    $html .= "<input type='submit' class='btn btn-secondary' value='".get_string('create')."'>";
    $html .= "</form>";
    return $html;
}

function block_mytermcourses_choosecategory($upcategory) {

	global $CFG, $DB, $OUTPUT;

	$paramskip = optional_param('skip', 0, PARAM_INT);
	$parentcategory = $DB->get_record('course_categories', array('id' => $upcategory->parent));
	if ($paramskip) {

            if ($parentcategory) {

                $backurl = "addcourse.php?category=$parentcategory->parent";
            } else {

                $backurl = "$CFG->wwwroot/my/index.php";
            }
	} else {

            $backurl = "addcourse.php?category=$upcategory->parent";
	}

	$categories = $DB->get_records('course_categories', array('parent' => $upcategory->id), 'name');

	if ($categories) {

            $firstcategory = current($categories);
	    $maincoursecategory = $DB->get_record('course_categories', array('idnumber' => $CFG->yearprefix));

            if (count($categories) == 1) { // S'il n'y a qu'une catégorie, on y va tout de suite.

                header("Location: addcourse.php?category=$firstcategory->id&skip=1");
                //~ $url = new moodle_url('/blocks/mytermcourses/addcourse.php',
                // array('category' => $firstcategory->id, 'skip' => 1));
                //~ redirect($url);
            }

            $haschildren = $DB->record_exists('course_categories', array('parent' => $firstcategory->id));
            echo $OUTPUT->header();

            $specialcategoryidnumber = $CFG->yearprefix."-1";

            $specialcategoryid = $DB->get_record('course_categories',
                    array('idnumber' => $specialcategoryidnumber))->id;

	    if ($upcategory->id == $specialcategoryid) {

                echo "<p><span style='font-weight:bold;color:red'>Attention : Les cours sont créés par"
                . " les gestionnaires administratifs de l'UFR Droit. ".
                        "Nous remercions les enseignants de se rapprocher de leur gestionnaire pédagogique"
                        . " pour la création d'un cours.</span></p>";
	    }

	    echo "<p><a href='$backurl'>".get_string('back')."</a></p>";

	    if ($upcategory->id == $maincoursecategory->id) {

                $heading = get_string('choosefaculty', 'block_mytermcourses');
            } else if ($haschildren) {

                $heading = get_string('chooselevel', 'block_mytermcourses');
            } else {

                $heading = get_string('choosetraining', 'block_mytermcourses');
            }

            echo $OUTPUT->heading($heading);

            if (!$haschildren) {

                echo "<p>".get_string('mutualchooseany', 'block_mytermcourses')."</p>";
            }
            foreach ($categories as $category) {

                echo "<a class='btn btn-secondary' style='margin:10px' href='addcourse.php?category=$category->id'>"
                        . "$category->name</a><br>";
            }

            if (!$haschildren) {

                // VET page.
                block_mytermcourses_notfoundform('category', $upcategory->id);
            }

	} else {

            // ELP page.
            echo $OUTPUT->header();
            echo "<p><a href='$backurl'>".get_string('back')."</a></p>";
            echo $OUTPUT->heading(get_string('createwhichcourse', 'block_mytermcourses'));
            echo "<div style='text-align:center'>";
            $categorystyle = "text-align:left;font-weight:bold;padding:5px;color:white;background-color:gray;width:100%";
            echo "<br><p style='$categorystyle'>$upcategory->name";
            echo "&nbsp; &nbsp;<span style='font-size:10'>($upcategory->idnumber)</span></p>";
            echo "</div>";
            block_mytermcourses_choosecourse($upcategory);
            block_mytermcourses_notfoundform('course', $upcategory->id);
	}
}

function block_mytermcourses_notfoundform($nature, $parentid) {

    echo '<br><br>';
    echo "<div style='background-color:whitesmoke;padding:10px;border-radius:5px;text-align:center;"
    . "font-weight:bold;color:blue'>";
    echo "<p>".get_string($nature.'notfound', 'block_mytermcourses')."</p>";
    echo "<form action='addcourse.php'>";
    echo "<input type='hidden' name='".$nature."parent' value='$parentid'>";
    echo "<input type='hidden' name='sesskey' value='".sesskey()."'>";
    echo "<label for='new".$nature."name'>".'<span style="font-weight:bold">'.
            get_string('name').' : </span>'."</label>&nbsp;&nbsp;";
    echo "<input type='text' size='80' name='new".$nature."name' placeholder='".
            get_string($nature.'placeholder', 'block_mytermcourses')."'>&nbsp;&nbsp;";
    echo "<input type='submit' class='btn btn-secondary' value='".
            get_string('create')."'>";
    echo "</form>";
    echo "</div>";
}

function block_mytermcourses_choosecourse($category) {

    global $DB;
    $separatorposition = strpos($category->idnumber, '-');
    $categorycodefirstchar = substr($category->idnumber, $separatorposition+1, 1);
    $offercourses = $DB->get_records('local_pedagooffer', array('categoryid' => $category->id), 'name');
    foreach ($offercourses as $offercourse) {

        $coursecodefirstchar = substr($offercourse->codeelp, 0, 1);

        if ($coursecodefirstchar != $categorycodefirstchar) {

                continue; // Ne pas afficher les cours hors composantes à l'intérieur des composantes.
        }

        $coursecodeyear = "$category->idnumber-$offercourse->codeelp";
        $alreadycourses = block_mytermcourses_similarcourses($coursecodeyear);
        echo "<div style='text-align:left'>";
        echo "<div  style='float:left;margin-right:30px'>";
        echo "<span style='font-weight:bold'>$offercourse->name</span>";
        echo "&nbsp; &nbsp;<span style='font-size:10'>($offercourse->codeelp)</span></p>";
        echo "</div>";

        if ($alreadycourses) {

            $alreadycoursesgroup = '<div style="overflow:auto">';

            foreach ($alreadycourses as $alreadycourse) {

                $alreadycoursesgroup .= block_mytermcourses_displaycourse($alreadycourse);
            }

            $alreadycoursesgroup .= '</div>';
            $againconfirm = '<div style="text-align:center">'.get_string('reallycreateagain', 'block_mytermcourses')
                .block_mytermcourses_creationform($category->id, $coursecodeyear, $offercourse->name).'</div>';

            echo "<button class='btn btn-secondary' onclick='block_mytermcourses_newcourses(\"$coursecodeyear\")'>"
                .get_string('alreadycreated', 'block_mytermcourses')."</button>";
            echo '&nbsp;';
            echo "<button class='btn btn-secondary' onclick='block_mytermcourses_again(\"$coursecodeyear\")'>".
                    get_string('createagain', 'block_mytermcourses')."</button>";
            echo '<br><br>';
            echo "<div style='display:none' id='again$coursecodeyear'>$againconfirm</div>";
            echo "<div style='display:none' id='sim$coursecodeyear'>$alreadycoursesgroup</div>";
        } else {

            echo block_mytermcourses_creationform($category->id, $coursecodeyear, $offercourse->name);
        }
        echo "</div>";
    }
}

function block_mytermcourses_readoldcourseline($oldcoursearray, $parentcategoryid) {

    global $DB, $CFG;

    $nbcells = count($oldcoursearray);

    if ($nbcells == 2) {

        // Course
        $courseidnumber = array_pop($oldcoursearray);
        $coursename = array_pop($oldcoursearray);
        $already = $DB->get_record('course', array('idnumber' => $courseidnumber));

        if ($already) {

            echo "Le cours $already->fullname ($courseidnumber) existe déjà.<br>";
        } else {

            echo "Création du cours $coursename ($courseidnumber) dans la catégorie $parentcategoryid.<br>";
            block_mytermcourses_createcourse($coursename, $courseidnumber, $parentcategoryid);
        }

        echo '<br>';
    } else {

        // Category

        $categoryidnumber = array_pop($oldcoursearray);
        $categoryname = array_pop($oldcoursearray);

        if ($categoryidnumber == $CFG->yearprefix.'-1A') {

            $categoryidnumber = $CFG->yearprefix.'-1AU';
        }

        $already = $DB->get_record('course_categories', array('idnumber' => $categoryidnumber));

        if ($already) {

            echo "La catégorie $already->name ($categoryidnumber) existe déjà.<br>";
            $categoryid = $already->id;
        } else {

            echo "Création de la catégorie $categoryname ($categoryidnumber) dans la catégorie $parentcategoryid.<br>";
            $newcategory = block_mytermcourses_createcategory($categoryname, $categoryidnumber, 'id', $parentcategoryid);
            $categoryid = $newcategory->id;
        }

        block_mytermcourses_readoldcourseline($oldcoursearray, $categoryid);
    }
}

?>

<script>
function block_mytermcourses_newcourses(idnumber) {

    coursesdiv = document.getElementById('sim' + idnumber);
    visible = coursesdiv.style.display;

    if (visible == 'none') {

        coursesdiv.style.display = 'block';
    } else {

        coursesdiv.style.display = 'none';
    }
}

function block_mytermcourses_again(idnumber) {

    confirmdiv = document.getElementById('again' + idnumber);
    visible = confirmdiv.style.display;

    if (visible == 'none') {

        confirmdiv.style.display = 'block';
    } else {

        confirmdiv.style.display = 'none';
    }
}

function block_mytermcourses_availables() {

    step1div = document.getElementById('step1');
    step2div = document.getElementById('step2');
    availablesdiv = document.getElementById('availables');

    if (step1div) {

        step1div.style.display = 'none';
    }
    if (step2div) {

        step2div.style.display = 'none';
    }

    availablesdiv.style.display = 'block';
}

function block_mytermcourses_notfound() {

    notfounddiv = document.getElementById('notfounddiv');
    visible = notfounddiv.style.display;

    if (visible == 'none') {

        notfounddiv.style.display = 'block';
    } else {

        notfounddiv.style.display = 'none';
    }
}
</script>

<?php
