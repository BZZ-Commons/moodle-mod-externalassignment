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
 * Define all the backup steps that will be used by the backup_externalassignment_activity_task
 *
 * @package   mod_externalassignment
 * @copyright 2024 Marcel Suter <marcel.suter@bzz.ch>
 * @copyright 2024 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_externalassignment_activity_structure_step extends backup_activity_structure_step {

    /**
     * Define the structure of the externalassignment element
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo'); // To know if we are including userinfo.

        // Define each element separated.
        $assignment = new backup_nested_element(
            'externalassignment',
            ['id'],
            [
                'name', 'intro', 'introformat', 'alwaysshowdescription', 'externalname', 'externallink',
                'alwaysshowlink', 'allowsubmissionsfromdate', 'duedate', 'cutoffdate', 'externalgrademax',
                'manualgrademax', 'passingpercentage', 'needspassinggrade',
            ]
        );

        $grades = new backup_nested_element('grades');
        $grade = new backup_nested_element(
            'grade',
            ['id'],
            [
                'userid', 'grader', 'externallink', 'externalgrade', 'externalfeedback', 'manualgrade', 'manualfeedback',
            ]
        );

        $overrides = new backup_nested_element('overrides');
        $override = new backup_nested_element(
            'override',
            ['id'],
            [
                'userid', 'allowsubmissionsfromdate', 'duedate', 'cutoffdate',
            ]
        );

        // Build the tree.
        $assignment->add_child($grades);
        $grades->add_child($grade);
        $assignment->add_child($overrides);
        $overrides->add_child($override);

        // Define sources.
        $assignment->set_source_table('externalassignment', ['id' => backup::VAR_ACTIVITYID]);
        if ($userinfo) {
            $grade->set_source_sql(
                'SELECT * ' .
                    '  FROM {externalassignment_grades} ' .
                    ' WHERE externalassignment = ?',
                [backup::VAR_PARENTID],
            );

            $override->set_source_sql(
                'SELECT * '.
                '  FROM {externalassignment_overrides} '.
                ' WHERE externalassignment = ?',
                [backup::VAR_PARENTID],
            );
        }
        // Define id annotations.
        $grade->annotate_ids('user', 'userid');
        $override->annotate_ids('user', 'userid');

        // Define file annotations
        $assignment->annotate_files('mod_externalassignment', 'intro', null);

        // Return the root element, wrapped into standard activity structure.
        return $this->prepare_activity_structure($assignment);
    }
}
