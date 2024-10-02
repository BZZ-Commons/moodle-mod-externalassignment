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
     * Update grades and feedback from an external system
     *
     * @param $assignmentname  string the name of the external assignment
     * @param $username  string the external username
     * @param $points float the number of points
     * @param $max  float the maximum points from tests
     * @param $externallink  string the url of the students repo
     * @param $feedback  string the feedback as json-structure
     * @return array info, warning or error messages
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

        // get the userid by the external username
        $externalusername = self::customfieldid_username();
        $results = [];
        $users = explode(',',$params['user_name']);
        echo 'users: ' . print_r($users, true);
        foreach ($users as $user) {
            $userid = self::get_user_id($user, $externalusername);

            if (empty($userid)) {
                echo 'ERROR: no username ' . $params['user_name'] . ' found';
                $results = self::generate_warning(
                    $results,
                    'error',
                    'no_user',
                    'No Moodle user found with username "' . $user . '": Update your Moodle profile.'
                );
                break;
            }

            // get the assignment with the specified name
            $assignment = self::read_assignment($assignmentname, $userid);
            if (empty($assignment->get_id())) {
                echo 'ERROR: no assignment ' . $params['assignment_name'] . ' found';
                $results = self::generate_warning(
                    $results,
                    'error',
                    'no_assignment',
                    'No matching assignment found. Contact your teacher.\n' .
                    '  * assignmentname "' . $params['assignment_name'] . '"\n' .
                    '  * username "' . $user . '"'
                );
                break;
            }

            // check if the assignment is overdue
            $override = $assignment->get_students()[$userid]->get_override();
            if (empty($override) || $override == 0) {
                $cutoffdate = $assignment->get_cutoffdate();
            } else {
                $cutoffdate = $override->get_cutoffdate();
            }
            if ($cutoffdate !=0 && $cutoffdate < time()) {
                echo 'WARNING: the assignment is overdue, points/feedback not updated';
                $results = self::generate_warning(
                    $results,
                    'warning',
                    'overdue',
                    'The assignment is overdue, points/feedback not updated'
                );
                break;
            }

            // update the grade
            self::update_grades($assignment, $userid, $params);
            $results = self::generate_warning(
                $results,
                'info',
                'success',
                'Update successful'
            );
        }

        return self::compact_results($results);
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
    private static function generate_warning(
        array $results,
        string $type,
        string $name,
        string $message
    ): array {

        $results[] = [
            'type' => $type,
            'name' => $name,
            'message' => $message,
        ];
        return $results;
    }

    /**
     * Compacts the results into a single array
     * @param array $results
     * @return array
     */
    private static function compact_results(array $results): array {
        $return = [
            'type' => 'info',
            'name' => '',
            'message' => '',
        ];
        foreach ($results as $result) {
            if ($result['type'] === 'error') {
                $return['type'] = 'error';
            }
            if ($result['type'] === 'warning' && $result['type'] !== 'error') {
                $return['type'] = 'warning';
            }

            $return['name'] .= $result['name'] . '\n';
            $return['message'] .= $result['message'] . '\n';
        }
        return $return;
    }
    /**
     * updates the grade and the feedback for the external assignment
     *
     * @param assign $assignment  the assignment the grades belong to
     * @param int $userid the id of the user
     * @param array $params the parameters from the POST request
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
            $grade->set_externalassignment($assignment->get_id());
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

        $cm = get_coursemodule_from_instance('externalassignment', $assignment->get_id(), 0, false, MUST_EXIST);
        list ($course, $coursemodule) = get_course_and_cm_from_cmid($cm->id, 'externalassignment');
        $completion = new \completion_info($course);
        if ($completion->is_enabled($coursemodule)) {
            $completion->update_state($coursemodule, COMPLETION_COMPLETE, $userid);
        }

    }

    /**
     * reads the grade using the assignment-name and userid
     *
     * @param int $coursemoduleid the id of the coursemodule for this external assignment
     * @param int $userid  the userid of the student
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
