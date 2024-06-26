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
 * shows reports for external assignment
 *
 * @package mod_externalassignment
 * @copyright 2024 Marcel Suter <marcel.suter@bzz.ch>
 * @copyright 2024 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
global $DB, $PAGE;

$courseid = required_param('courseid', PARAM_INT);
$assignmentid  = required_param('assignmentid', PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$coursemodule = get_coursemodule_from_instance('externalassignment', $assignmentid, $courseid, false, MUST_EXIST);
$assignment = $DB->get_record('externalassignment', ['id' => $coursemodule->instance], '*', MUST_EXIST);

$PAGE->set_url(
    '/mod/externalassignment/reports.php',
    ['courseid' => $courseid, 'assignmentid' => $assignmentid],
);

require_login($course, true, $coursemodule);
$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($coursemodule->id);

require_capability('mod/externalassignment:view', $modulecontext);
