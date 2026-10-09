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

namespace mod_externalassignment\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy class for requesting user data.
 *
 * @package   mod_externalassignment
 * @copyright 2026 Marcel Suter and Kevin Maurizi
 * @author    2026 Marcel Suter <marcel.suter+ea@bzz.ch>
 * @author    2026 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Provides metadata that is stored about a user with mod_externalassignment.
     *
     * @param collection $collection A collection of metadata items to be added to.
     * @return  collection Returns the collection of metadata.
     */
    public static function get_metadata(collection $collection): collection {
        $grades = [
            'userid' => 'privacy:metadata:userid',
            'grader' => 'privacy:metadata:grader',
            'externallink' => 'privacy:metadata:externallink',
            'externalgrade' => 'privacy:metadata:externalgrade',
            'externalfeedback' => 'privacy:metadata:externalfeedback',
            'manualgrade' => 'privacy:metadata:manualgrade',
            'manualfeedback' => 'privacy:metadata:manualfeedback',
        ];

        $overrides = [
            'userid' => 'privacy:metadata:userid',
            'allowsubmissionsfromdate' => 'privacy:metadata:allowsubmissionsfromdate',
            'duedate' => 'privacy:metadata:duedate',
            'cutoffdate' => 'privacy:metadata:cutoffdate',
        ];
        $collection->add_database_table(
            'externalassignment_grades',
            $grades,
            'privacy:metadata:externalassignment_grades'
        );
        $collection->add_database_table(
            'externalassignment_overrides',
            $overrides,
            'privacy:metadata:externalassignment_overrides'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $params = [
            'modname' => 'externalassignment',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ];
        // Instance ids are only unique per module type, so course_modules must be restricted to
        // this module - otherwise the contexts of unrelated activities are returned as well.
        foreach (['externalassignment_grades', 'externalassignment_overrides'] as $table) {
            $query = 'SELECT ctx.id ' .
                '  FROM {context} ctx ' .
                '  JOIN {course_modules} cm ON (cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel) ' .
                '  JOIN {modules} m ON (m.id = cm.module AND m.name = :modname) ' .
                '  JOIN {' . $table . '} data ON (data.externalassignment = cm.instance) ' .
                ' WHERE data.userid = :userid';
            $contextlist->add_from_sql($query, $params);
        }
        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $assignmentid = self::get_assignment_id($userlist->get_context());
        if ($assignmentid === null) {
            return;
        }

        $params = ['assignmentid' => $assignmentid];
        foreach (['externalassignment_grades', 'externalassignment_overrides'] as $table) {
            $userlist->add_from_sql(
                'userid',
                'SELECT userid FROM {' . $table . '} WHERE externalassignment = :assignmentid',
                $params
            );
        }
    }

    /**
     * Export all user data (grades and extensions) for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $assignmentid = self::get_assignment_id($context);
            if ($assignmentid === null) {
                continue;
            }
            $conditions = ['externalassignment' => $assignmentid, 'userid' => $userid];

            if ($grade = $DB->get_record('externalassignment_grades', $conditions)) {
                $data = (object)[
                    'userid' => $userid,
                    'grader' => $grade->grader,
                    'externallink' => $grade->externallink,
                    'externalgrade' => $grade->externalgrade,
                    'externalfeedback' => $grade->externalfeedback,
                    'manualgrade' => $grade->manualgrade,
                    'manualfeedback' => $grade->manualfeedback,
                ];
                writer::with_context($context)->export_data(
                    [get_string('privacy:export:externalassignment:grades', 'externalassignment')],
                    $data
                );
            }

            if ($override = $DB->get_record('externalassignment_overrides', $conditions)) {
                $data = (object)['userid' => $userid];
                foreach (['allowsubmissionsfromdate', 'duedate', 'cutoffdate'] as $field) {
                    $data->$field = empty($override->$field) ? null : transform::datetime($override->$field);
                }
                writer::with_context($context)->export_data(
                    [get_string('privacy:export:externalassignment:overrides', 'externalassignment')],
                    $data
                );
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        $assignmentid = self::get_assignment_id($context);
        if ($assignmentid === null) {
            return;
        }
        $DB->delete_records('externalassignment_grades', ['externalassignment' => $assignmentid]);
        $DB->delete_records('externalassignment_overrides', ['externalassignment' => $assignmentid]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $assignmentid = self::get_assignment_id($context);
            if ($assignmentid === null) {
                continue;
            }
            $conditions = ['externalassignment' => $assignmentid, 'userid' => $userid];
            $DB->delete_records('externalassignment_grades', $conditions);
            $DB->delete_records('externalassignment_overrides', $conditions);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $assignmentid = self::get_assignment_id($userlist->get_context());
        $userids = $userlist->get_userids();
        if ($assignmentid === null || empty($userids)) {
            return;
        }
        [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params = array_merge(['assignmentid' => $assignmentid], $inparams);
        $DB->delete_records_select('externalassignment_grades', "externalassignment = :assignmentid AND userid $insql", $params);
        $DB->delete_records_select('externalassignment_overrides', "externalassignment = :assignmentid AND userid $insql", $params);
    }

    /**
     * Returns the id of the external assignment a context belongs to
     *
     * @param \context $context
     * @return int|null null if the context is not the context of an external assignment
     */
    private static function get_assignment_id(\context $context): ?int {
        if (!$context instanceof \context_module) {
            return null;
        }
        $cm = get_coursemodule_from_id('externalassignment', $context->instanceid);
        return $cm ? (int)$cm->instance : null;
    }
}
