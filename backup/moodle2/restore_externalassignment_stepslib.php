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
 * Define all the restore steps that will be used by the restore_externalassignment_activity_task
 *
 * @package   mod_externalassignment
 * @copyright 2024 Marcel Suter <marcel.suter@bzz.ch>
 * @copyright 2024 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_externalassignment_activity_structure_step extends restore_activity_structure_step {
    /**
     * Define the structure of the externalassignment element
     */
    protected function define_structure() {

        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('externalassignment', '/activity/externalassignment');

        if ($userinfo) {
            $paths[] = new restore_path_element(
                'externalassignment_grades',
                '/activity/externalassignment/grades/grade'
            );
            $paths[] = new restore_path_element(
                'externalassignment_overrides',
                '/activity/externalassignment/overrides/override'
            );
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process externalassignment element
     *
     * @param array $data
     */
    protected function process_externalassignment($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->allowsubmissionsfromdate = $this->apply_date_offset($data->allowsubmissionsfromdate);
        $data->duedate = $this->apply_date_offset($data->duedate);
        $data->cutoffdate = $this->apply_date_offset($data->cutoffdate);

        $newitemid = $DB->insert_record('externalassignment', $data);

        // Immediately after inserting "activity" record, call this!
        $this->apply_activity_instance($newitemid);

        $this->calendar_event_add($data);
    }

    /**
     * Process externalassignment grades element
     *
     * @param array $data
     */
    protected function process_externalassignment_grades($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->externalassignment = $this->get_new_parentid('externalassignment');

        $newitemid = $DB->insert_record('externalassignment_grades', $data);
        $this->set_mapping('externalassignment_grades', $oldid, $newitemid);

    }

    /**
     * Process externalassignment overrides element
     *
     * @param array $data
     */
    protected function process_externalassignment_overrides($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->externalassignment = $this->get_new_parentid('externalassignment');

        $newitemid = $DB->insert_record('externalassignment_overrides', $data);
        $this->set_mapping('externalassignment_overrides', $oldid, $newitemid);
    }

    /**
     * After execute the step, add related files
     */
    protected function after_execute() {
        // Add related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_externalassignment', 'intro', null);
    }
}
