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
 * Activity index for the mod_externalassignment plugin.
 *
 * @package   mod_externalassignment
 * @copyright 2024 Marcel Suter <marcel.suter@bzz.ch>
 * @copyright 2024 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
global $DB, $OUTPUT, $PAGE;

// The `id` parameter is the course id.
$id = required_param('id', PARAM_INT);

// Fetch the requested course.
$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

// Require that the user is logged into the course.
require_course_login($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/mod/externalassignment/index.php', ['id' => $course->id]);
$PAGE->set_title($course->shortname.': '. get_string('modulenameplural', 'externalassignment'));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string('modulenameplural', 'externalassignment'));
echo $OUTPUT->header();

$table = new html_table();
$table->head = [get_string('modulenameplural', 'externalassignment')];
$modinfo = get_fast_modinfo($course);

foreach ($modinfo->get_instances_of('externalassignment') as $instanceid => $cm) {
    $link = '<a href="view.php?id=' . $cm->id .'">'.format_string($cm->name, true).'</a>';
    $table->data[] = [$link];
}
echo html_writer::table($table);
echo $OUTPUT->footer();
