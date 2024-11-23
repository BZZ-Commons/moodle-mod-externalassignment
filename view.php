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
 * Activity view page for the mod_externalassignment plugin.
 *
 * @package mod_externalassignment
 * @copyright 2024 Marcel Suter <marcel.suter@bzz.ch>
 * @copyright 2024 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_externalassignment\local\assign;
use mod_externalassignment\local\grade;
use mod_externalassignment\local\grade_control;
use mod_externalassignment\output\view_grader_navigation;
use mod_externalassignment\output\view_grading;
use mod_externalassignment\output\view_link;
use mod_externalassignment\output\view_student;
use mod_externalassignment\output\view_summary;

require_once('../../config.php');
global $PAGE;

$coursemoduleid = required_param('id', PARAM_INT);

list ($course, $coursemodule) = get_course_and_cm_from_cmid($coursemoduleid, 'externalassignment');
require_login($course, true, $coursemodule);
$context = context_module::instance($coursemodule->id);
require_capability('mod/externalassignment:view', $context);

$urlparams = [
    'id' => $coursemoduleid,
    'action' => optional_param('action', '', PARAM_ALPHA),
    'userid' => optional_param('userid', null, PARAM_INT),
    'userids' => optional_param_array('uid', [], PARAM_INT),
    'sort' => optional_param('sort', 'lastname', PARAM_ALPHA),
];

$url = new moodle_url(
    '/mod/externalassignment/view.php',
    [
        'id' => $urlparams['id'],
        'action' => $urlparams['action'],
    ]
);
if ($urlparams['userid'] != null && $urlparams['userid'] != 0) {
    $url->param('userid', $urlparams['userid']);
}

$PAGE->set_url($url);

if ($urlparams['action'] == '') {
    show_details($context, $coursemoduleid);
} else if ($urlparams['action'] == 'grading') {
    show_grading($context, $coursemoduleid, $urlparams['sort']);
} else if ($urlparams['action'] == 'grader') {
    show_grader($context, $coursemoduleid, $urlparams['userid']);
} else if ($urlparams['action'] == 'override') {
    show_override($context, $coursemoduleid, $urlparams['userids']);
}

/**
 * shows the details for the external assignment
 * @param $context
 * @param $coursemoduleid
 * @return void
 * @throws \coding_exception
 * @throws \dml_exception
 */
function show_details($context, $coursemoduleid): void {
    global $PAGE, $USER;

    $courseshortname = $context->get_course_context()->get_context_name(false, true);
    $assignmentname = $context->get_context_name(false, true);
    $assignment = new assign(null);
    $assignment->load_db($coursemoduleid);
    $title = $courseshortname . ': ' . $assignmentname;
    $PAGE->set_title($title);
    $PAGE->set_heading('External assignment details');
    $PAGE->set_pagelayout('standard');

    if (!$assignment->is_alwaysshowdescription() ||
        ($assignment->get_allowsubmissionsfromdate() > 0 && $assignment->get_allowsubmissionsfromdate() >= time())) {
        $assignment->set_intro('');
    }
    $output = $PAGE->get_renderer('mod_externalassignment');
    echo $output->header();

    if ($assignment->is_alwaysshowlink() ||
        ($assignment->get_allowsubmissionsfromdate() > 0 && $assignment->get_allowsubmissionsfromdate() <= time())) {
        $renderable = new view_link($coursemoduleid, $assignment);
        echo $output->render($renderable);
    }

    if (has_capability('mod/externalassignment:reviewgrades', $context)) {
        $renderable = new view_summary($coursemoduleid, $context);
        echo $output->render($renderable);
    } else {
        $grade = new grade(null);
        $grade->load_db($assignment->get_id(), $USER->id);

        $renderable = new view_student($coursemoduleid, $context, $assignment, $grade);
        echo $output->render($renderable);
    }
    echo $output->footer();
}

/**
 * shows the grading overview
 * @param $context context the context of the course module
 * @param $coursemoduleid int the id of the course module
 * @param $sort String the sort order for the students
 * @return void
 * @throws \coding_exception
 * @throws \required_capability_exception
 */
function show_grading(context $context, int $coursemoduleid, String $sort): void {
    global $PAGE;
    require_capability('mod/externalassignment:reviewgrades', $context);

    $courseshortname = $context->get_course_context()->get_context_name(false, true);
    $assignmentname = $context->get_context_name(false, true);
    $title = $courseshortname . ': ' . $assignmentname . ' - ' . get_string('grading', 'externalassignment');
    $PAGE->set_title($title);
    $PAGE->set_heading('Grading overview');
    $PAGE->set_pagelayout('base');
    $PAGE->add_body_class('externalassignment-grading');
    $output = $PAGE->get_renderer('mod_externalassignment');
    echo $output->header();
    $renderable = new view_grading($coursemoduleid, $context, $sort);
    echo $output->render($renderable);
    echo $output->footer();
}

/**
 * shows the grading form for the student
 * @param $context
 * @param $coursemoduleid
 * @param $userid
 * @return void
 * @throws \coding_exception
 * @throws \required_capability_exception
 * @throws \dml_exception
 * @throws \moodle_exception
 */
function show_grader($context, $coursemoduleid, $userid): void {
    global $PAGE;
    require_capability('mod/externalassignment:reviewgrades', $context);
    $assign = new \mod_externalassignment\local\assign(null, $context);

    if ($userid == null) {
        $userid = array_key_first($assign->get_students());
        $urlparams = [
            'id' => $coursemoduleid,
            'action' => 'grader',
            'userid' => $userid,
        ];

        $url = new moodle_url('/mod/externalassignment/view.php', $urlparams);
        redirect($url);
    }

    $courseshortname = $context->get_course_context()->get_context_name(false, true);
    $assignmentname = $context->get_context_name(false, true);
    $title = $courseshortname . ': ' . $assignmentname . ' - ' . get_string('grade', 'externalassignment');
    $PAGE->set_title($title);
    $PAGE->set_heading('Grader form');
    $PAGE->set_pagelayout('base');
    $PAGE->add_body_class('externalassignment-grading');
    $output = $PAGE->get_renderer('mod_externalassignment');
    echo $output->header();

    $renderable = new view_grader_navigation($coursemoduleid, $context, $userid);
    echo $output->render($renderable);
    $gradecontrol = new grade_control($coursemoduleid, $context, $userid);
    $gradecontrol->process_feedback();
    echo $output->footer();
}

/**
 * shows the overrides
 * @param $context
 * @param int $coursemoduleid
 * @param array $userids
 * @return void
 * @throws coding_exception
 * @throws required_capability_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function show_override($context, int $coursemoduleid, array $userids): void {
    global $CFG;
    global $PAGE;

    require_capability('mod/externalassignment:reviewgrades', $context);
    $courseshortname = $context->get_course_context()->get_context_name(false, true);
    $assignmentname = $context->get_context_name(false, true);
    $title = $courseshortname . ': ' . $assignmentname . ' - ' . get_string('override', 'externalassignment');
    $PAGE->set_title($title);
    $PAGE->set_heading('My modules page heading');
    $PAGE->set_pagelayout('base');
    $PAGE->add_body_class('externalassignment-grading');
    $output = $PAGE->get_renderer('mod_externalassignment');
    echo $output->header();

    $gradecontrol = new grade_control($coursemoduleid, $context);
    $gradecontrol->process_override($userids);

    echo $output->footer();
}
