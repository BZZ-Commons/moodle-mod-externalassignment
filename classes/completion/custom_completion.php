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

namespace mod_externalassignment\completion;

use core_completion\activity_custom_completion;
use mod_externalassignment\local\assign;
use mod_externalassignment\local\grade;

/**
 * Activity custom completion subclass for the external assignment activity.
 *
 * Class for defining mod_externalassignment's custom completion rules and fetching the completion statuses
 * of the custom completion rules for a given assign instance and a user.
 *
 * @package mod_externalassignment
 * @copyright 2024 Marcel Suter <marcel.suter@bzz.ch>
 * @copyright 2024 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {

    /**
     * Fetches the completion state for a given completion rule.
     *
     * @param string $rule The completion rule.
     * @return int The completion state.
     * @throws \dml_exception
     */
    public function get_state(string $rule): int {
        $this->validate_rule($rule);
        $completed = false;
        $coursemoduleid = $this->cm->id;
        $assign = new assign(null);
        $assign->load_db($coursemoduleid);
        if ($assign->get_needspassinggrade()) {
            $grade = new grade(null);
            $grade->load_db($assign->get_id(), $this->userid);
            $maxgrade = $assign->get_externalgrademax() + $assign->get_manualgrademax();
            $passinggrade = $maxgrade * $assign->get_passingpercentage() / 100;
            $totalgrade = $grade->get_externalgrade() + $grade->get_manualgrade();
            $completed = $totalgrade >= $passinggrade;
        }
        return $completed ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Fetch the list of custom completion rules that this module defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return ['needspassinggrade'];
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        return [
            'needspassinggrade' => get_string('needspassinggrade', 'externalassignment'),
        ];
    }

    /**
     * Returns an array of all completion rules, in the order they should be displayed to users.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'needspassinggrade',
        ];
    }
}
