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

defined('MOODLE_INTERNAL') || die();
// Because it must exist.
require_once($CFG->dirroot . '/mod/externalassignment/backup/moodle2/restore_externalassignment_stepslib.php');

/**
 * Define all the restore steps that will be used by the restore_externalassignment_activity_task
 * @package   mod_externalassignment
 * @copyright 2024 Marcel Suter <marcel.suter@bzz.ch>
 * @copyright 2024 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_externalassignment_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have, none at this time
     */
    protected function define_my_settings() {
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Activity externalassignment only has one structure step.
        $this->add_step(
            new restore_externalassignment_activity_structure_step(
                'externalassignment_structure', 'externalassignment.xml'
            )
        );
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    public static function define_decode_rules() {
        $rules = [];

        $rules[] = new restore_decode_rule('EXTERNALASSIGNMENTVIEWBYID', '/mod/externalassignment/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('EXTERNALASSIGNMENTINDEX', '/mod/externalassignment/index.php?id=$1', 'course');

        return $rules;

    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder.
     *
     * @return array
     */
    public static function define_decode_contents() {
        $contents = [];

        $contents[] = new restore_decode_content('externalassignment', ['intro'], 'externalassignment');

        return $contents;
    }

    /**
     * Define structure of the restored externalassignment
     */
    protected function define_course_plugin_structure() {
        $paths = [];
        $this->step->log('Yay, restore!', backup::LOG_DEBUG);
        return $paths;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * externalassignment logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    public static function define_restore_log_rules() {
        $rules = [];

        $rules[] = new restore_log_rule(
            'externalassignment', 'add', 'view.php?id={course_module}', '{externalassignment}'
        );
        $rules[] = new restore_log_rule(
            'externalassignment', 'update', 'view.php?id={course_module}', '{externalassignment}'
        );
        $rules[] = new restore_log_rule(
            'externalassignment', 'view', 'view.php?id={course_module}', '{externalassignment}'
        );
        $rules[] = new restore_log_rule(
            'externalassignment', 'choose', 'view.php?id={course_module}', '{externalassignment}'
        );
        $rules[] = new restore_log_rule(
            'externalassignment', 'choose again', 'view.php?id={course_module}', '{externalassignment}'
        );
        $rules[] = new restore_log_rule(
            'externalassignment', 'report', 'report.php?id={course_module}', '{externalassignment}'
        );

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    public static function define_restore_log_rules_for_course() {
        $rules = [];

        // Fix old wrong uses (missing extension).
        $rules[] = new restore_log_rule(
            'externalassignment',
            'view all',
            'index?id={course}',
            null,
            null,
            null,
            'index.php?id={course}'
        );
        $rules[] = new restore_log_rule(
            'externalassignment',
            'view all',
            'index.php?id={course}',
            null
        );

        return $rules;
    }
}
