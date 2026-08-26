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
require_once("$CFG->dirroot/lib/externallib.php");

use context_module;
use core_external\restricted_context_exception;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use invalid_parameter_exception;
use mod_externalassignment\local\assign;
use required_capability_exception;

/**
 * webservice to update the externalgrade and externalfeedback
 *
 * @package   mod_externalassignment
 * @copyright 2023 Marcel Suter <marcel@ghwalin.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class read_students extends external_api {
    /**
     * creates the return structure
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure|external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'userid' => new external_value(PARAM_INT, 'user-id'),
                'firstname' => new external_value(PARAM_TEXT, 'firstname'),
                'lastname' => new external_value(PARAM_TEXT, 'lastname'),
                'email' => new external_value(PARAM_TEXT, 'email address'),
                'done' => new external_value(PARAM_BOOL, 'whether the student has already been graded'),
            ])
        );
    }

    /**
     * execute the service
     * @param int $coursemoduleid the id of the externalassignment coursemodule
     * @param string $sort the field to sort the students by (firstname, lastname, status, grade)
     * @param string $tdir the direction to sort the students by (asc, desc)
     * @param string $status filter the students by status: 'open', 'done' or '' for no filter
     * @throws restricted_context_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     */
    public static function execute(
        int $coursemoduleid,
        string $sort = 'lastname',
        string $tdir = 'asc',
        string $status = ''
    ): array {
        $params = self::validate_parameters(
            self::execute_parameters(),
            [
                'coursemoduleid' => $coursemoduleid,
                'sort' => $sort,
                'tdir' => $tdir,
                'status' => $status,
            ]
        );
        $context = context_module::instance($params['coursemoduleid']);
        self::validate_context($context);
        require_capability('mod/externalassignment:reviewgrades', $context);

        $assign = new assign(null, $context);
        $assign->load_db($params['coursemoduleid'], $params['sort'], $params['tdir']);

        $students = [];
        foreach ($assign->get_students() as $student) {
            $done = $student->get_grade() !== null;
            if ($params['status'] === 'open' && $done) {
                continue;
            }
            if ($params['status'] === 'done' && !$done) {
                continue;
            }
            $students[] = [
                'userid' => $student->get_userid(),
                'firstname' => $student->get_firstname(),
                'lastname' => $student->get_lastname(),
                'email' => $student->get_email(),
                'done' => $done,
            ];
        }

        return $students;
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'coursemoduleid' => new external_value(PARAM_INT, 'id of the externalassignment coursemodule'),
            'sort' => new external_value(PARAM_ALPHA, 'the field to sort the students by', VALUE_DEFAULT, 'lastname'),
            'tdir' => new external_value(PARAM_ALPHA, 'the direction to sort the students by', VALUE_DEFAULT, 'asc'),
            'status' => new external_value(
                PARAM_ALPHA,
                'filter the students by status: open, done or empty for no filter',
                VALUE_DEFAULT,
                ''
            ),
        ]);
    }

}
