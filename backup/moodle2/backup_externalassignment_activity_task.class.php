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
 * externalassignment backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 *
 * @package   mod_externalassignment
 * @copyright 2024 Marcel Suter <marcel.suter@bzz.ch>
 * @copyright 2024 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/externalassignment/backup/moodle2/backup_externalassignment_stepslib.php');
class backup_externalassignment_activity_task extends backup_activity_task {
    protected function define_my_settings() {
        // No settings
    }

    protected function define_my_steps() {
        // External assignment only has one structure step
        $this->add_step(new backup_externalassignment_activity_structure_step('externalassignment_structure', 'externalassignment.xml'));
    }

    static public function encode_content_links($content) {
        global $CFG;
        $base = preg_quote($CFG->wwwroot,"/");

        // Link to the list of choices
        $search="/(".$base."\/mod\/externalassignment\/index.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@CHOICEINDEX*$2@$', $content);

        // Link to choice view by moduleid
        $search="/(".$base."\/mod\/externalassignment\/view.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@CHOICEVIEWBYID*$2@$', $content);
        return $content;
    }
}