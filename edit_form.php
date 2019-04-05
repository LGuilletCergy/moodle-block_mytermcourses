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
 * File : edit_form.php
 *
 * Block configuration form.
 *
 */

class block_mytermcourses_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_changetitle', get_string('changetitle', 'block_mytermcourses'));
        $mform->setDefault('config_changetitle', $this->block->title);
        $mform->setType('config_changetitle', PARAM_RAW);

        $mform->addElement('selectyesno', 'config_idnumber', get_string('useidnumber', 'block_mytermcourses'));
        $mform->addHelpButton('config_idnumber', 'useidnumber', 'block_mytermcourses');
        $mform->setDefault('config_idnumber', $this->config->idnumber);

        $mform->addElement('text', 'config_common', get_string('commoncategories', 'block_mytermcourses'));
        $mform->setDefault('config_common', $this->config->common);
        $mform->addHelpButton('config_common', 'commoncategories', 'block_mytermcourses');
        $mform->setType('config_common', PARAM_RAW);
    }
}
