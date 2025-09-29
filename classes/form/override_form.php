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

namespace mod_externalassignment\form;

defined('MOODLE_INTERNAL') || die();
use mod_externalassignment\local\assign;
use moodleform;

require_once("$CFG->libdir/formslib.php");

/**
 * definition and validation of the grading form
 *
 * @package   mod_externalassignment
 * @copyright 2024 Marcel Suter <marcel.suter@bzz.ch>
 * @copyright 2024 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class override_form extends moodleform {
    /** @var assign the external assignment */
    protected assign $assign;

    /**
     * override_form constructor.
     * @param $submiturl string the url to submit the form
     * @param $assign assign the assignment object
     * @param $customdata mixed the data entered in the form
     */
    public function __construct($submiturl, $assign, $customdata = null) {
        $this->assign = $assign;
        parent::__construct($submiturl, $customdata);

    }

    /**
     * definition of the override form
     * @return void
     * @throws coding_exception
     */
    public function definition() {
        $mform = $this->_form;
        $mform->addElement(
            'header',
            'extension',
            get_string('grantextension', 'externalassignment')
        );
        $mform->setExpanded('extension');
        $mform->addElement('static', 'selectedusers', get_string('selectedusers', 'externalassignment'), '');
        $count = 0;

        foreach ($this->_customdata->users as $userid => $user) {
            $element = $mform->addElement('hidden', "uid[$count]", $user->get_userid());
            $element->setType(PARAM_INT);
            $mform->addElement(
                'static',
                'fullname' . $count,
                '',
                $user->get_firstname() . ' ' . $user->get_lastname()
            );
            $count++;
        }

        $options = ['optional' => true];
        $mform->addElement(
            'date_time_selector',
            'allowsubmissionsfromdate',
            get_string('allowsubmissionsfromdate', 'externalassignment'),
            $options
        );
        $mform->addHelpButton(
            'allowsubmissionsfromdate',
            'allowsubmissionsfromdate',
            'externalassignment'
        );

        $mform->addElement(
            'date_time_selector',
            'duedate',
            get_string('duedate', 'externalassignment'),
            $options
        );
        $mform->addHelpButton('duedate', 'duedate', 'externalassignment');

        $mform->addElement(
            'date_time_selector',
            'cutoffdate',
            get_string('cutoffdate', 'externalassignment'),
            $options
        );
        $mform->addHelpButton('cutoffdate', 'cutoffdate', 'externalassignment');

        /*$mform->addElement(
            'text',
            'id',
            'course_module_id',
            $this->_customdata->id
        );
        $mform->setType('id', PARAM_INT);

        $mform->addElement(
            'text',
            'externalassignment',
            'extassign_id',
            $this->_customdata->externalassignment
        );
        $mform->setType('externalassignment', PARAM_INT);*/

        $this->add_action_buttons();
        $this->set_data($this->_customdata);
    }

    /**
     * validates the formdata for the override
     * @param $data
     * @param $files
     * @return array  error messages
     * @throws \coding_exception
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        // Ensure that the dates make sense.
        if (!empty($data['allowsubmissionsfromdate']) && !empty($data['cutoffdate'])) {
            if ($data['cutoffdate'] < $data['allowsubmissionsfromdate']) {
                $errors['cutoffdate'] = get_string('cutoffdatefromdatevalidation', 'externalassignment');
            }
        }

        if (!empty($data['allowsubmissionsfromdate']) && !empty($data['duedate'])) {
            if ($data['duedate'] <= $data['allowsubmissionsfromdate']) {
                $errors['duedate'] = get_string('duedateaftersubmissionvalidation', 'externalassignment');
            }
        }

        if (!empty($data['cutoffdate']) && !empty($data['duedate'])) {
            if ($data['cutoffdate'] < $data['duedate'] ) {
                $errors['cutoffdate'] = get_string('cutoffdatevalidation', 'externalassignment');
            }
        }

        // Ensure that at least one setting for the assignment was changed.
        $changed = false;
        $keys = ['duedate', 'cutoffdate', 'allowsubmissionsfromdate'];
        foreach ($keys as $key) {
            if ($data[$key] != $this->assign->{'get_' . $key}()) {
                $changed = true;
                break;
            }
        }

        if (!$changed) {
            $errors['allowsubmissionsfromdate'] = get_string('nooverridedata', 'externalassignment');
        }
        return $errors;
    }
}
