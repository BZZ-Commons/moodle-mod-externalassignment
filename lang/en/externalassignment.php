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
 * Plugin strings are defined here.
 *
 * @package     mod_externalassignment
 * @category    string
 * @copyright   2024 Marcel Suter <marcel.suter@bzz.ch>
 * @copyright   2024 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addinstance'] = 'Add instance';
$string['allowsubmissionsfromdate'] = 'Allow submissions from';
$string['allowsubmissionsfromdate_help'] = 'If enabled, students will not be able to submit before this date. If disabled, students will be able to start submitting right away.';
$string['alwaysshowdescription'] = 'Always show description';
$string['alwaysshowdescription_help'] = 'If disabled, the assignment description above will only become visible to students on the "Allow submissions from" date.';
$string['alwaysshowlink'] = 'Always show link';
$string['alwaysshowlink_help'] = 'If disabled, the assignment link above will only become visible to students on the "Allow submissions from" date.';
$string['assignmentisdue'] = 'Assignment is due';
$string['assignmentname'] = 'Assignment name';
$string['availability'] = 'Availability';

$string['changeuser'] = 'Change user';
$string['completiongradesgroup'] = 'Completion grades group';
$string['completiongradesgroup_help'] = 'TODO Completion grades group';
$string['configintro'] = 'The values set here are used by the "External assignment" plugin';
$string['cutoffdate'] = 'Cut-off date';
$string['cutoffdate_help'] = 'If set, submissions will not be accepted after this date without an extension. If not set, submissions will always be accepted.';
$string['cutoffdatefromdatevalidation'] = 'Cut-off date cannot be earlier than the allow submissions from date.';
$string['cutoffdatevalidation'] = 'Cut-off date cannot be earlier than the due date.';

$string['description'] = 'Description';
$string['done'] = 'done';
$string['duedate'] = 'Due date';
$string['duedate_help'] = 'TODO duedate_help';
$string['duedateaftersubmissionvalidation'] = 'Due date must be after the allow submissions from date.';
$string['duedatevalidation'] = 'Due date cannot be earlier than the allow submissions from date.';

$string['external'] = 'External';
$string['externalfeedback'] = 'Feedback from external system';
$string['externalgrade'] = 'External grade';
$string['externalgrademax'] = 'External grade max.';
$string['externalgrademax_help'] = 'Maximum grade from external assignment';
$string['externalgrading'] = 'Grading from external system';
$string['externallink'] = 'Assignment link';
$string['externallink_help'] = 'The link to the assignment in the external system';
$string['externalname'] = 'External assignment';
$string['externalname_help'] = 'The name of the assignment in the external system';
$string['externalusername'] = 'External username';
$string['externalusername_desc'] = 'The user profile field containing the external username';

$string['feedback'] = 'Feedback';
$string['finalgrade'] = 'Final grade';

$string['grade'] = 'Grade';
$string['gradecomponent'] = 'Grading component';
$string['graded'] = 'Graded';
$string['grading'] = 'Grading';
$string['gradingoverview'] = 'Grading overview';
$string['gradingstatus'] = 'Grading status';
$string['grantextension'] = 'Grant extension';

$string['isdue'] = 'is due';

$string['mandatory'] = 'Mandatory';
$string['manual'] = 'Manual';
$string['manualfeedback'] = 'Manual feedback';
$string['manualgrademax'] = 'Manual grade max.';
$string['manualgrademax_help'] = 'Maximum grade from manual grading';
$string['manualgrading'] = 'Manual grading';
$string['modulename'] = 'External assignment';
$string['modulename_help'] = 'The external assignment activity module lets you give your students an assignment in an external system (e.g. GitHub Classroom).\nIt includes a webservice to update the student\'s grading from the external assessment';
$string['modulenameplural'] = 'External assignments';

$string['needspassinggrade'] = 'Receive a passing grade';
$string['needspassinggradedesc'] = 'Student needs a passing grade to complete the assignment';
$string['nextuser'] = 'Next user';

$string['override'] = 'Override';

$string['passinggrade'] = 'Points needed to pass';
$string['passingpercentage'] = 'Percentage to pass';
$string['passingpercentage_help'] = 'What percentage of the maximum grade (external + manual) must be achieved to pass';
$string['pending'] = 'pending';
$string['percentage'] = 'Percentage';
$string['pluginadministration'] = 'External Assignment';
$string['pluginname'] = 'External Assignment';
$string['previoususer'] = 'Previous user';

$string['scoremaximum'] = 'Maximum points';
$string['scorereached'] = 'Points earned';
$string['seefeedback'] = 'See feedback';
$string['selectedusers'] = 'Selected users';
$string['studentlink'] = 'Link to your assignment';
$string['submissionsdue'] = 'Due:';
$string['submissionsopen'] = 'Opens:';
$string['submissionsopened'] = 'Opened:';
$string['submissionstatus'] = 'Submission status';

$string['timeremaining'] = 'Time left';
$string['timeremainingcolon'] = 'Time remaining: {$a}';
$string['totalgrade'] = 'Total points';
$string['view'] = 'View';
