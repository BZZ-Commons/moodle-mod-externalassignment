<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Privacy Subsystem implementation for mod_externalassignment.
 *
 * @package     mod_externalassignment
 * @copyright   2024 Marcel Suter <marcel.suter@bzz.ch>
 * @copyright   2024 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_externalassignment\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\helper;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\transform;

/**
 * Privacy Subsystem implementation for mod_externalassignment.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Return the fields which contain personal data.
     *
     * @param collection $items a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $items): collection {
        $items->add_database_table(
            'externalassignment_grades',
            [
                'userid' => 'privacy:metadata:userid',
                'grader' => 'privacy:metadata:grader',
                'externallink' => 'privacy:metadata:externallink',
                'externalgrade' => 'privacy:metadata:externalgrade',
                'externalfeedback' => 'privacy:metadata:externalfeedback',
                'manualgrade' => 'privacy:metadata:manualgrade',
                'manualfeedback' => 'privacy:metadata:manualfeedback',
            ],
            'privacy:metadata:externalassignment_grades'
        );

        $items->add_database_table(
            'externalassignment_overrides',
            [
                'userid' => 'privacy:metadata:userid',
                'allowsubmissionsfromdate' => 'privacy:metadata:allowsubmissionsfromdate',
                'duedate' => 'privacy:metadata:duedate',
                'cutoffdate' => 'privacy:metadata:cutoffdate',
            ],
            'privacy:metadata:externalassignment_overrides'
        );

        return $items;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Contexts where the user has grades.
        $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {externalassignment} ea ON ea.id = cm.instance
            INNER JOIN {externalassignment_grades} eag ON eag.externalassignment = ea.id
                 WHERE eag.userid = :userid";

        $params = [
            'modname' => 'externalassignment',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        // Contexts where the user has overrides.
        $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {externalassignment} ea ON ea.id = cm.instance
            INNER JOIN {externalassignment_overrides} eao ON eao.externalassignment = ea.id
                 WHERE eao.userid = :userid";

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist the userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        // Fetch all users who have grades.
        $sql = "SELECT eag.userid
                  FROM {course_modules} cm
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {externalassignment} ea ON ea.id = cm.instance
            INNER JOIN {externalassignment_grades} eag ON eag.externalassignment = ea.id
                 WHERE cm.id = :cmid";

        $params = [
            'cmid' => $context->instanceid,
            'modname' => 'externalassignment',
        ];

        $userlist->add_from_sql('userid', $sql, $params);

        // Fetch all users who have overrides.
        $sql = "SELECT eao.userid
                  FROM {course_modules} cm
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {externalassignment} ea ON ea.id = cm.instance
            INNER JOIN {externalassignment_overrides} eao ON eao.externalassignment = ea.id
                 WHERE cm.id = :cmid";

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT cm.id AS cmid,
                       ea.name,
                       ea.intro,
                       eag.externallink,
                       eag.externalgrade,
                       eag.externalfeedback,
                       eag.manualgrade,
                       eag.manualfeedback,
                       eao.allowsubmissionsfromdate,
                       eao.duedate,
                       eao.cutoffdate
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {externalassignment} ea ON ea.id = cm.instance
             LEFT JOIN {externalassignment_grades} eag ON eag.externalassignment = ea.id AND eag.userid = :userid1
             LEFT JOIN {externalassignment_overrides} eao ON eao.externalassignment = ea.id AND eao.userid = :userid2
                 WHERE c.id {$contextsql}";

        $params = [
                'modname' => 'externalassignment',
                'userid1' => $user->id,
                'userid2' => $user->id,
            ] + $contextparams;

        $records = $DB->get_records_sql($sql, $params);

        foreach ($records as $record) {
            $context = \context_module::instance($record->cmid);
            $data = [];

            if (!empty($record->externallink) || !empty($record->externalgrade) ||
                !empty($record->externalfeedback) || !empty($record->manualgrade) ||
                !empty($record->manualfeedback)) {
                $data['grades'] = [
                    'externallink' => $record->externallink,
                    'externalgrade' => $record->externalgrade,
                    'externalfeedback' => $record->externalfeedback,
                    'manualgrade' => $record->manualgrade,
                    'manualfeedback' => $record->manualfeedback,
                ];
            }

            if (!empty($record->allowsubmissionsfromdate) || !empty($record->duedate) ||
                !empty($record->cutoffdate)) {
                $data['overrides'] = [
                    'allowsubmissionsfromdate' => $record->allowsubmissionsfromdate ?
                        transform::datetime($record->allowsubmissionsfromdate) : null,
                    'duedate' => $record->duedate ?
                        transform::datetime($record->duedate) : null,
                    'cutoffdate' => $record->cutoffdate ?
                        transform::datetime($record->cutoffdate) : null,
                ];
            }

            if (!empty($data)) {
                writer::with_context($context)->export_data([], (object)$data);
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('externalassignment', $context->instanceid);
        if (!$cm) {
            return;
        }

        $DB->delete_records('externalassignment_grades', ['externalassignment' => $cm->instance]);
        $DB->delete_records('externalassignment_overrides', ['externalassignment' => $cm->instance]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }

            $cm = get_coursemodule_from_id('externalassignment', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $DB->delete_records('externalassignment_grades', [
                'externalassignment' => $cm->instance,
                'userid' => $userid,
            ]);

            $DB->delete_records('externalassignment_overrides', [
                'externalassignment' => $cm->instance,
                'userid' => $userid,
            ]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('externalassignment', $context->instanceid);
        if (!$cm) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $DB->delete_records_select('externalassignment_grades',
            "externalassignment = :externalassignment AND userid {$usersql}",
            ['externalassignment' => $cm->instance] + $userparams);

        $DB->delete_records_select('externalassignment_overrides',
            "externalassignment = :externalassignment AND userid {$usersql}",
            ['externalassignment' => $cm->instance] + $userparams);
    }
}