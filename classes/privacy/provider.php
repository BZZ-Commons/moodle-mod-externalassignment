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
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use mod_externalassignment\local\assign;

/**
 * Privacy class for requesting user data.
 *
 * @package   mod_externalassignment
 * @copyright 2024 Marcel Suter <marcel.suter@bzz.ch>
 * @copyright 2024 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {
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
            'privacy:metadata:externalassignment:grades'
        );
        $collection->add_database_table(
            'externalassignment_overrides',
            $overrides,
            'privacy:metadata:externalassignment:overrides'
        );

        return $collection;
    }

    /**
     * Returns all the contexts that has information relating to the userid.
     *
     * @param int $userid The user ID.
     * @return contextlist an object with the contexts related to a userid.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $query = 'SELECT coursemodule.instance ' .
            'FROM {course_modules} coursemodule ' .
            'JOIN {externalassignment} extassign ON (coursemodule.instance = extassign.id) ' .
            'JOIN {externalassignment_grades} grades ON (extassign.id = grades.externalassignment) ' .
            'WHERE grades.userid = :userid';
        $params = [
            'modname' => 'externalassignment',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ];
        $contextlist->add_from_sql($query, $params);

        $query = 'SELECT coursemodule.instance ' .
            'FROM {course_modules} coursemodule ' .
            'JOIN {externalassignment} extassign ON (coursemodule.instance = extassign.id) ' .
            'JOIN {externalassignment_overrides} overrides ON (extassign.id = overrides.externalassignment) ' .
            'WHERE overrides.userid = :userid';
        $params = [
            'modname' => 'externalassignment',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ];
        $contextlist->add_from_sql($query, $params);
        return $contextlist;
    }

    /**
     * Returns all the users that have data in the given context.
     *
     * @param userlist $userlist An object with the users related to a context.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        $params = [
            'instanceid' => $context->instanceid,
            'modulename' => 'externalassignment',
        ];
        $query = 'SELECT DISTINCT user.id ' .
            'FROM {user} user ' .
            'JOIN {externalassignment_grades} grades ON (user.id = grades.userid) ' .
            'JOIN {externalassignment} extassign ON (grades.externalassignment = extassign.id) ' .
            'JOIN {course_modules} cm ON (extassign.id = cm.instance) ' .
            'WHERE cm.id = :instanceid ';
        $userlist->add_from_sql('userid', $query, $params);

        $query = 'SELECT DISTINCT user.id ' .
            'FROM {user} user ' .
            'JOIN {externalassignment_overrides} overrides ON (user.id = overrides.userid) ' .
            'JOIN {externalassignment} extassign ON (overrides.externalassignment = extassign.id) ' .
            'JOIN {course_modules} cm ON (extassign.id = cm.instance) ' .
            'WHERE cm.id = :instanceid';
        $userlist->add_from_sql('userid', $query, $params);
    }

    /**
     * Export all user data for the given contextlist.
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $user = $contextlist->get_user();
        $userid = $user->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_MODULE) {
                $assign = new assign(null, $context);
                $assign->load_db($context->instanceid, $userid);
                $data = new \stdClass();
                $data->userid = $userid;
                $student = $assign->take_student($userid);
                $data->grader = $student->get_grade()->get_grader();
                $data->externallink = $student->get_grade()->get_externallink();
                $data->externalgrade = $student->get_grade()->get_externalgrade();
                $data->externalfeedback = $student->get_grade()->get_externalfeedback();
                $data->manualgrade = $student->get_grade()->get_manualgrade();
                $data->manualfeedback = $student->get_grade()->get_manualfeedback();

                writer::with_context($context)
                    ->export_data([], $data)
                    ->export_metadata(
                        [],
                        'externalassignment:grades',
                        (object)['userid' => $userid],
                        new \lang_string('privacy:export:externalassignment:grades')
                    );
            }
        }
    }

    /**
     * Delete all user data for the given contextlist.
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        // Check that this is a context_module.
        if (!$context instanceof \context_module) {
            return;
        }

        // Get the course module and exit if not 'externalassignment'.
        if (!$cm = get_coursemodule_from_id('externalassignment', $context->instanceid)) {
            return;
        }

        $assignid = $cm->instance;
        $DB->delete_records('externalassignment_grades', ['externalassignment' => $assignid]);
        $DB->delete_records('externalassignment_overrides', ['externalassignment' => $assignid]);
    }

    /**
     * Delete all user data for the given contextlist.
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $user = $contextlist->get_user();
        $userid = $user->id;
        foreach ($contextlist as $context) {
            // Get the course module.
            $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);
            $assignment = $DB->get_record('externalassignment', ['id' => $cm->instance]);
            $DB->delete_records(
                'externalassignment_grades',
                ['externalassignment' => $assignment->id,
                    'userid' => $userid]
            );
            $DB->delete_records(
                'externalassignment_overrides',
                ['externalassignment' => $assignment->id,
                    'userid' => $userid]
            );
        }
    }

    /**
     * Delete all user data for the given userlist.
     * @param approved_userlist $userlist
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);
        $assignid = $DB->get_record('externalassignment', ['id' => $cm->instance]);
        $userids = $userlist->get_userids();
        $params = [
            'externalassignment' => $assignid->id,
            'userids' => $userids,
        ];
        $in = $DB->get_in_or_equal($userids);
        $DB->delete_records_select(
            'externalassignment_grades',
            'externalassignment = :externalassignment AND userid $in', $params
        );
        $DB->delete_records_select(
            'externalassignment_overrides',
            'externalassignment = :externalassignment AND userid $in', $params
        );
    }

    /**
     * Get the course module ids from the contextlist.
     *
     * @param approved_contextlist $contextlist
     * @return array
     */
    private static function get_course_module_ids(approved_contextlist $contextlist) {
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_MODULE) {
                $coursemoduleids[] = $context->instanceid;
            }
        }
        return $coursemoduleids;
    }

    /**
     * Get the ids of the external assignments from the array of cmids.
     *
     * @param array $cmids
     * @return array
     */
    private static function get_assign_ids(array $cmids): array {
        global $DB;
        $params = [
            'cmids' => $cmids,
        ];
        $in = $DB->get_in_or_equal($cmids);
        $sql = 'SELECT instance FROM {course_module} WHERE id $in';
        return $DB->get_fieldset_sql($sql, $params);
    }
}