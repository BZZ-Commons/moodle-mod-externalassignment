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

namespace mod_externalassignment\task;
defined('MOODLE_INTERNAL') || die();

/**
 * Represents the model of an external assignment
 *
 * @package   mod_externalassignment
 * @copyright 2026 Marcel Suter <marcel.suter@bzz.ch>
 * @copyright 2026 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Update Overdue Attempts Task
 *
 * @package    mod_quiz
 * @copyright  2017 Michael Hughes
 * @author Michael Hughes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class duplicate_names_task extends \core\task\scheduled_task
{
    /**
     * Gets the name of the task
     */
    public function get_name(): string
    {
        return get_string('taskduplicatenames', 'mod_externalassignment');
    }

    /**
     * Execute the task
     * @throws \dml_exception
     */
    public function execute(): void
    {
        mtrace('  Looking for duplicate external names...');
        // Find all courses and students with duplicate external assignment names.
        $rows = $this->read_duplicates();
        $duplicates = [];
        foreach ($rows as $row) {
            if (!$this->is_teacher($row->userid, $row->course)) {
                $duplicates[$row->externalname][$row->coursename] = true;
            }
        }

        foreach ($duplicates as $externalname => $courses) {
            $courselist = implode(', ', array_keys($courses));
            mtrace('  External assignment name "' . $externalname . '" is duplicated in courses: ' . $courselist);
        }
        mtrace('  ... done');
    }

    /**
     * Read all duplicate external assignment names for the same user across all courses.
     * @return array
     * @throws \dml_exception
     */
    private function read_duplicates()
    {
        global $DB;
        $query =
            'SELECT t.*' .
            ' FROM (' .
            '    SELECT UUID(), ae.id, ae.course, ae.externalname, ae.name,' .
            '           ue.userid, ue.id AS userenrolid, us.firstname, us.lastname, en.id AS enroleid,' .
            '           cm.id AS coursemoduleid, co.fullname AS coursename' .
            '   FROM mdl_externalassignment ae' .
            '   JOIN mdl_enrol en ON (ae.course = en.courseid)' .
            '   JOIN mdl_user_enrolments ue ON (ue.enrolid = en.id)' .
            '   JOIN mdl_user us ON (us.id = ue.userid)' .
            '   JOIN mdl_course_modules cm ON (cm.instance = ae.id)' .
            '   JOIN mdl_course co ON (ae.course = co.id)' .
            ' ) AS t' .
            ' JOIN (' .
            '    SELECT externalname, userid' .
            '    FROM (' .
            '        SELECT ae.externalname, ue.userid' .
            '        FROM mdl_externalassignment ae' .
            '        JOIN mdl_enrol en ON (ae.course = en.courseid)' .
            '        JOIN mdl_user_enrolments ue ON (ue.enrolid = en.id)' .
            '        JOIN mdl_user us ON (us.id = ue.userid)' .
            '        JOIN mdl_course_modules cm ON (cm.instance = ae.id)' .
            '        JOIN mdl_course co ON (ae.course = co.id)' .
            '    ) d' .
            '    GROUP BY externalname, userid' .
            '    HAVING COUNT(*) > 1' .
            ' ) AS dup' .
            ' ON t.externalname = dup.externalname' .
            ' AND t.userid = dup.userid;';
        return $DB->get_records_sql($query);
    }

    /**
     * Check if the user is a teacher in the given course.
     * @param int $userid
     * @param int $courseid
     * @return bool
     * @throws \dml_exception
     */
    private function is_teacher($userid, $courseid): bool
    {
        global $DB;
        $context = \context_course::instance($courseid);
        return has_capability('moodle/course:viewhiddenactivities', $context, $userid);
    }
}