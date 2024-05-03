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
 * This file contains the moodle hooks for the externalassignment module.
 *
 * It delegates most functions to the controller classes.
 *
 * @package     mod_externalassignment
 * @copyright   2024 Marcel Suter <marcel.suter@bzz.ch>
 * @copyright   2024 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_externalassignment\local\assign_control;

/**
 * Adds an assignment instance
 *
 * This is done by calling the add_instance() method of the assignment type class
 * @param stdClass $instancedata
 * @param mod_externalassignment_mod_form $mform
 * @return int The instance id of the new assignment
 */
function externalassignment_add_instance(\stdClass $instancedata, mod_externalassignment_mod_form $mform = null) {
    $instance = context_module::instance($instancedata->coursemodule);
    $assigncontrol = new assign_control($instance, null);
    return $assigncontrol->add_instance($instancedata, $instance->instanceid);
}

/**
 * Update an assignment instance
 *
 * This is done by calling the update_instance() method of the assignment type class
 * @param \stdClass $data
 * @param \stdClass $form - unused
 * @return bool
 */
function assignexternal_update_instance(\stdClass $data, $form) {
    global $CFG;
    $context = context_module::instance($data->coursemodule);
    $assignment = new assign_control($context, null, null);
    return $assignment->update_instance($data, $context->instanceid);
}

/**
 * delete an assignment instance
 * @param int $id
 * @return bool
 * @throws dml_exception
 */
function externalassignment_delete_instance($id): bool
{
    $cm = get_coursemodule_from_instance('externalassignment', $id, 0, false, MUST_EXIST);

    $context = context_module::instance($cm->id);
    $assignment = new assign_control($context, null, null);
    $assignment->delete_instance($id);

    return false;
}

/**
 * Update an assignment instance
 *
 * This is done by calling the update_instance() method of the assignment type class
 * @param stdClass $data
 * @param stdClass $form - unused
 * @return bool
 */
function externalassignment_update_instance(stdClass $data, $form) {
    return true;
}

/**
 * Returns additional information for showing the assignment in course listing
 *
 * @param stdClass $coursemodule
 * @return cached_cm_info
 * @throws dml_exception
 */
function externalassignment_get_coursemodule_info(stdClass $coursemodule) {
    $result = new cached_cm_info();

    return $result;
}

/**
 * customize module display for the current user on course listing
 *
 * @param cm_info $coursemodule
 * @return void
 * @throws dml_exception
 */
function externalassignment_cm_info_view(cm_info $coursemodule): void {

}

/**
 * customize module display
 * @param cm_info $coursemodule
 * @return void
 * @throws coding_exception
 */
function externalassignment_cm_info_dynamic(cm_info $coursemodule) {
    $context = context_module::instance($coursemodule->id);
}


/**
 * Return the features this module supports
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know or string for the module purpose.
 */
function externalassignment_supports($feature) {
    switch ($feature) {
        /*  TODO
         case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_ADVANCED_GRADING:
            return true;
        case FEATURE_PLAGIARISM:
            return true;
        case FEATURE_COMMENT:
            return true;
         */
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ASSESSMENT;

        default:
            return null;
    }
}

/**
 * Obtains the automatic completion state for this external assignment based on the conditions in settings.
 *
 * @param object $course Course
 * @param object $coursemodule Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 * @throws dml_exception
 */
function externalassignment_get_completion_state(
    $course,
    $coursemodule,
    $userid,
    $type
) {
    return $type;
}

/**
 * Callback which returns human-readable strings describing the active completion custom rules for the module instance.
 *
 * @param cm_info|stdClass $coursemodule object with fields ->completion and ->customdata['customcompletionrules']
 * @return array $descriptions the array of descriptions for the custom rules.
 */
function mod_externalassignment_get_completion_active_rule_descriptions($coursemodule) {
    if (empty($cm->customdata['customcompletionrules']) || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return ['foobar'];
    }

    $descriptions = [];
    foreach ($coursemodule->customdata['customcompletionrules'] as $key => $val) {
        if ($key == 'haspassinggrade') {
            $descriptions[] = get_string('haspassinggradedesc', 'externalassignment', $val);
        }
    }
    return $descriptions;
}