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
 * Displays the courses within which the user his enrolled, their categories,
 * and their teachers. Courses open to all users are also displayed.
 * Courses are displayed on two columns, one for each term in the year.
 * Teachers can move their courses to select the relevant column.
 *
 * @package    block_mytermcourses
 * @author     Brice Errandonea <brice.errandonea@u-cergy.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * File : lang/en/block_mytermcourses.php
 * English text strings
 *
 */

$string['pluginname'] = 'My term courses';
$string['mytermcourses'] = 'My term courses';
$string['mytermcourses:createcourse'] = 'Create a new course (through block_mytermcourses)';
$string['mytermcourses:addinstance'] = 'Add a new My term courses block';
$string['mytermcourses:myaddinstance'] = 'Add a new My term courses block to the My Moodle page';
$string['changetitle'] = 'Change block title';
$string['bgcolor'] = 'Categories background color';
$string['teacher'] = 'Teacher';
$string['teachers'] = 'Teachers';
$string['useidnumber'] = 'Sort courses by idnumber';
$string['commoncategories'] = 'Common categories';
$string['notenrolled'] = 'You\'re not enrolled in any course';
$string['firstterm'] = 'First term';
$string['secondterm'] = 'Second term';
$string['addcourse'] = 'Add a new course';
$string['myoldcourses'] = 'My old courses';
$string['similarnewcourses'] = 'Already here';
$string['teacheroldcourses'] = 'Courses you gave last year';
$string['adminoldcourses'] = 'Courses you helped last year';
$string['studentoldcourses'] = 'Courses you attended last year';
$string['fetch'] = 'Fetch';
$string['fetchagain'] = 'Fetch again';
$string['cantseecourse'] = 'I can\'t see my course. What can I do ?';
$string['reallyfetchagain'] = 'Do you really want to fetch this course once again ?';
$string['alreadycreated'] = 'Already created';
$string['createagain'] = 'Create again';
$string['reallycreateagain'] = 'Do you really want to create another course for this one ?';
$string['chooseformat'] = 'Please choose a format for your course';
$string['choosecategory'] = 'Please choose a course category';
$string['choosefaculty'] = 'Please choose a faculty';
$string['chooselevel'] = 'Please choose a level';
$string['choosetraining'] = 'Please choose a training';
$string['suggestedcohorts'] = 'Suggested cohorts';
$string['mutualchooseany'] = 'If the course you want to create is common to several of the above trainings,'
        . ' please click any of the relevant trainings';
$string['categorynotfound'] = 'Can\'t find the category you\'re looking for ? You can create a new one.'
        . ' Please don\'t use this too often : it\'s better to create new categories in Apogée rather than here.';
$string['coursenotfound'] = 'Can\'t find the course you\'re looking for ? You can create a new one.'
        . ' Please don\'t use this too often : it\'s better to create new courses in Apogée rather than here.';
$string['createnewcategory'] = 'Create new category';
$string['missingcategoryname'] = 'You must give a name to the category you want to create';
$string['missingcoursename'] = 'You must give a name to the course you want to create';
$string['createwhichcourse'] = 'Which one of these courses do you want to create ?';
$string['categoryplaceholder'] = 'Please choose carefully : you can\'t change this later.';
$string['courseplaceholder'] = '';
$string['creatingcourse'] = 'Creating course';
$string['incategory'] = 'in category';
$string['cohortsare'] = 'Students cohorts are Apogée groups. If you tie a cohort to your course,'
        . ' any student added to the cohort (in Apogée) will be automatically added to the course.';
$string['noknowncohorts'] = 'We don\'t have any information about cohorts you might want to tie to this course.';
$string['choosecohorts'] = 'According to our knowledge, you could be interested in these cohorts.'
        . ' Please check there is no mistake.';
$string['alllevelcohorts'] = 'Can\'t find the cohorts you\'re looking for ?'
        . ' Please click here for all the cohorts available in';
$string['tryenroldemands'] = "Please check you are part of a cohort your teacher linked to this course."
        . " Or search the <a href='course/index.php'>courses list</a>."
        . " Maybe you can request for enrolment in this course (if your teacher enabled this possibility).";
