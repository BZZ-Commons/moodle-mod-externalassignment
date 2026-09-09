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
 * Plugin upgrade helper functions are defined here.
 *
 * @package     mod_externalassignment
 * @category    upgrade
 * @copyright   2024 Marcel Suter <marcel.suter@bzz.ch>
 * @copyright   2024 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Helper function used by the upgrade.php file.
 */
function mod_externalassignment_helper_function() {
    global $DB;

    // Please note: you can only use raw low level database access here.
    // Avoid Moodle API calls in upgrade steps.
    //
    // For more information please read {@link https://docs.moodle.org/dev/Upgrade_API}.
}

/**
 * Creates the "github_user" custom user profile field (in an "external_usernames" category) that
 * this plugin uses to match a Moodle account to an external system's username, so the admin does
 * not have to create it by hand (GitHub issue #9). Does nothing if a field with that shortname
 * already exists - including one an admin already created manually before this existed.
 *
 * Called from both db/install.php (new installs) and xmldb_externalassignment_upgrade()
 * (existing sites upgrading to the version that introduced this), so it must remain safe to run
 * more than once and, per the Upgrade API guidance above, sticks to raw low-level database access
 * only - no Moodle API calls (e.g. get_string(), profile_save_field()) that could behave
 * differently depending on which version of core this ends up running against.
 *
 * @return void
 */
function mod_externalassignment_create_github_username_profile_field(): void {
    global $DB;

    if ($DB->record_exists('user_info_field', ['shortname' => 'github_user'])) {
        return;
    }

    $categoryid = $DB->get_field('user_info_category', 'id', ['name' => 'external_usernames']);
    if (!$categoryid) {
        $category = new stdClass();
        $category->name = 'external_usernames';
        $category->sortorder = $DB->count_records('user_info_category') + 1;
        $categoryid = $DB->insert_record('user_info_category', $category);
    }

    $field = new stdClass();
    $field->shortname = 'github_user';
    $field->name = 'GitHub username';
    $field->datatype = 'text';
    $field->description = '';
    $field->descriptionformat = FORMAT_HTML;
    $field->categoryid = $categoryid;
    $field->sortorder = $DB->count_records('user_info_field', ['categoryid' => $categoryid]) + 1;
    $field->required = 0;
    $field->locked = 0;
    $field->visible = 1; // PROFILE_VISIBLE_PRIVATE: visible to the user themselves and staff, not on the public profile page.
    $field->forceunique = 1; // Two students sharing a GitHub username would make update_grade's lookup ambiguous.
    $field->signup = 0;
    $field->defaultdata = '';
    $field->defaultdataformat = FORMAT_HTML;
    $field->param1 = 30; // Field size, matching the text profile field type's own default.
    $field->param2 = 2048; // Max length, matching the text profile field type's own default.
    $field->param3 = 0; // Not a password field.
    $DB->insert_record('user_info_field', $field);
}
