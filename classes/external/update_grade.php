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

namespace mod_externalassignment\external;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("$CFG->dirroot/lib/externallib.php");

use core\check\performance\debugging;
use dml_exception;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use invalid_parameter_exception;
use mod_externalassignment\local\assign;
use mod_externalassignment\local\grade;

/**
 * webservice to update the externalgrade and externalfeedback
 *
 * @package   mod_externalassignment
 * @copyright 2023 Marcel Suter <marcel@ghwalin.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_grade extends external_api {
    /**
     * creates the return structure
     * @return external_single_structure
     */
    public static function execute_returns() {
        return
            new external_single_structure([
                'type' => new external_value(PARAM_TEXT, 'info, warning, error'),
                'name' => new external_value(PARAM_TEXT, 'the name of this warning'),
                'message' => new external_value(PARAM_TEXT, 'warning message'),
            ]);
    }

    /**
     * Update grades from an external system
     * @param $assignmentname  String the name of the external assignment
     * @param $username  String the external username
     * @param $points float the number of points
     * @param $max  float the maximum points from tests
     * @param $externallink  string the url of the students repo
     * @param $feedback  string the feedback as json-structure
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function execute(
        string $assignmentname,
        string $username,
        float  $points,
        float  $max,
        string $externallink,
        string $feedback
    ): array {
        $params = self::validate_parameters(
            self::execute_parameters(),
            [
                'assignment_name' => $assignmentname,
                'user_name' => $username,
                'points' => $points,
                'max' => $max,
                'externallink' => $externallink,
                'feedback' => $feedback,
            ]
        );
        $externalusername = self::customfieldid_username();
        $userid = self::get_user_id($params['user_name'], $externalusername);
        if (!empty($userid)) {
            $assignment = self::read_assignment($assignmentname, $userid);
            if (empty($assignment->get_id())) {
                echo 'ERROR: no assignment ' . $params['assignment_name'] . ' found';
                return self::generate_warning(
                    'error',
                    'no_assignment',
                    'No assignment with name "' . $params['assignment_name'] . '" found. Contact your teacher.'
                );
            } else {
                self::update_grades($assignment, $userid, $params);
            }
        } else {
            echo 'ERROR: no username ' . $params['user_name'] . ' found';
            return self::generate_warning(
                'error',
                'no_user',
                'No Moodle user found with username "' . $params['user_name'] . '" Update your Moodle profile.'
            );
        }

        return self::generate_warning(
            'info',
            'success',
            'Update successful'
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters(
            [
                'assignment_name' => new external_value(
                    PARAM_TEXT,
                    'name of the assignment'
                ),
                'user_name' => new external_value(
                    PARAM_TEXT,
                    'username of the github user'
                ),
                'points' => new external_value(
                    PARAM_FLOAT,
                    'the points for grading'
                ),
                'max' => new external_value(
                    PARAM_FLOAT,
                    'the maximum points'
                ),
                'externallink' => new external_value(
                    PARAM_TEXT,
                    'the url of the student repository',
                    0,
                    ''
                ),
                'feedback' => new external_value(
                    PARAM_TEXT,
                    'the feedback for this grade',
                    0,
                    '[]'
                ),
            ]
        );
    }

    /**
     * returns the id of the custom field for the external username
     * @throws dml_exception
     */
    private static function customfieldid_username(): int {
        global $DB;
        $customfield = $DB->get_record(
            'user_info_field',
            ['shortname' => get_config('mod_externalassignment', 'external_username')],
            'id,shortname');
        return $customfield->id;
    }

    /**
     * returns the moodle userid by the external username
     * @param string $username the external username
     * @param int $fieldid the id of the custom field for external username
     * @return int  the moodle-userid
     * @throws dml_exception
     */
    private static function get_user_id(string $username, int $fieldid): ?int {
        global $DB;
        $query = 'SELECT userid' .
            '  FROM {user_info_data}' .
            ' WHERE fieldid=:fieldid' .
            '   AND data=:ghusername';
        $user = $DB->get_record_sql(
            $query,
            [
                'fieldid' => $fieldid,
                'ghusername' => $username,
            ]
        );
        if (!empty($user)) {
            return $user->userid;
        } else {
            return null;
        }
    }

    /**
     * reads the assignment data
     * @param string $assignmentname
     * @param int $userid
     * @return assign
     * @throws dml_exception
     */
    private static function read_assignment(string $assignmentname, int $userid): assign {

        $assignment = new assign(null);
        $assignment->load_db_external($assignmentname, $userid);
        return $assignment;
    }

    /**
     * Generates the warning messages to be sent to the caller
     * @param string $type
     * @param string $name
     * @param string $message
     * @return string[]
     */
    private static function generate_warning(string $type, string $name, string $message): array {
        return [
            'type' => $type,
            'name' => $name,
            'message' => $message,
        ];
    }

    /**
     * updates the grade for a programming assignment
     * @param assign $assignmentid
     * @param int $userid
     * @param array $params
     * @return void
     * @throws dml_exception
     */
    private static function update_grades(assign $assignment, int $userid, array $params): void {
        global $CFG, $DB;
        $grade = new grade(null);
        $grade->load_db($assignment->get_id(), $userid);
        $grade->set_userid($userid);
        $points = $params['points'] * $assignment->get_externalgrademax() / $params['max'];
        $grade->set_externalgrade($points);
        $feedback = urldecode($params['feedback']);
        $grade->set_externalfeedback(format_text($feedback, FORMAT_MARKDOWN));
        $grade->set_externallink($params['externallink']);
        if (empty($grade->get_id())) {
            $DB->insert_record('externalassignment_grades', $grade->to_stdclass());
        } else {
            $DB->update_record('externalassignment_grades', $grade->to_stdclass());
        }

        $gradevalues = new \stdClass();
        $gradevalues->userid = $userid;
        $gradevalues->rawgrade = floatval($grade->get_externalgrade()) + floatval($grade->get_manualgrade());

        require_once($CFG->dirroot . '/lib/gradelib.php');
        grade_update(
            'mod/externalassignment',
            $assignment->get_course(),
            'mod',
            'externalassignment',
            $assignment->get_id(),
            0,
            $gradevalues
        );

    }

    /**
     * reads the grade using the assignment-name and userid
     *
     * @param int $userid
     * @param int $coursemoduleid
     * @return object|null
     * @throws dml_exception
     */
    private static function read_grade(int $coursemoduleid, int $userid): ?object {
        global $DB;

        $data = $DB->get_record(
            'externalassignment_grades',
            [
                'userid' => $userid,
                'externalassignment' => $coursemoduleid,
            ]
        );
        if (!$data) {
            return null;
        }
        return $data;
    }
}
