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

/**
 * Checks for duplicate external assignment names that might cause problems
 *
 * @package   mod_externalassignment
 * @copyright 2026 Marcel Suter and Kevin Maurizi
 * @author    2026 Marcel Suter <marcel.suter+ea@bzz.ch>
 * @author    2026 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Find duplicate external assignment names
 *
 * @package   mod_externalassignment
 * @copyright 2026 Marcel Suter and Kevin Maurizi
 * @author    2026 Marcel Suter <marcel.suter+ea@bzz.ch>
 * @author    2026 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class duplicate_names_task extends \core\task\scheduled_task
{
    /**
     * Gets the name of the task
     */
    public function get_name(): string {
        return get_string('taskduplicatenames', 'mod_externalassignment');
    }

    /**
     * Execute the task: logs every external name that is used by more than one assignment a
     * student is enrolled in, since update_grade can then only update one of them.
     */
    public function execute(): void {
        mtrace('  Looking for duplicate external names...');
        foreach ($this->find_duplicates() as $externalname => $courses) {
            mtrace('  External assignment name "' . $externalname . '" is duplicated in courses: ' . implode(', ', $courses));
        }
        mtrace('  ... done');
    }

    /**
     * Finds the external names used by more than one assignment that the same student is enrolled in
     * @return array externalname => sorted list of the names of the courses using it
     * @throws \dml_exception
     */
    public function find_duplicates(): array {
        global $DB;
        // Only names that occur more than once at all can be duplicates for a student.
        $query =
            'SELECT ae.id AS assignmentid, ae.externalname, ae.course, co.fullname AS coursename, ue.userid' .
            '  FROM {externalassignment} ae' .
            '  JOIN {course_modules} cm ON (cm.instance = ae.id AND cm.deletioninprogress = 0)' .
            '  JOIN {modules} mo ON (mo.id = cm.module AND mo.name = :modname)' .
            '  JOIN {course} co ON (co.id = ae.course)' .
            '  JOIN {enrol} en ON (en.courseid = ae.course)' .
            '  JOIN {user_enrolments} ue ON (ue.enrolid = en.id)' .
            ' WHERE ae.externalname IN (' .
            '       SELECT externalname FROM {externalassignment} GROUP BY externalname HAVING COUNT(1) > 1' .
            '       )';
        $rows = $DB->get_recordset_sql($query, ['modname' => 'externalassignment']);

        // Collect the assignments per external name and student, ignoring teachers. A student
        // with several enrolments in one course appears several times for the same assignment.
        $assignments = [];
        $teachers = [];
        foreach ($rows as $row) {
            $key = $row->userid . '_' . $row->course;
            if (!array_key_exists($key, $teachers)) {
                $teachers[$key] = $this->is_teacher($row->userid, $row->course);
            }
            if (!$teachers[$key]) {
                $assignments[$row->externalname][$row->userid][$row->assignmentid] = $row->coursename;
            }
        }
        $rows->close();

        $duplicates = [];
        foreach ($assignments as $externalname => $students) {
            foreach ($students as $courses) {
                if (count($courses) > 1) {
                    foreach ($courses as $coursename) {
                        $duplicates[$externalname][$coursename] = $coursename;
                    }
                }
            }
        }
        ksort($duplicates);
        foreach ($duplicates as $externalname => $courses) {
            sort($courses);
            $duplicates[$externalname] = $courses;
        }
        return $duplicates;
    }

    /**
     * checks if the user is a teacher in the course
     * @param int $userid
     * @param int $courseid
     * @return bool
     * @throws \coding_exception
     */
    private function is_teacher($userid, $courseid): bool {
        $context = \context_course::instance($courseid);
        return has_capability('moodle/course:viewhiddenactivities', $context, $userid);
    }
}
