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

namespace mod_externalassignment;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;

global $CFG;
require_once($CFG->dirroot . '/mod/externalassignment/db/upgradelib.php');

/**
 * Unit tests for db/upgradelib.php.
 *
 * Regression tests for GitHub issue #9 ("User profile: username for external system": the admin
 * had to manually create the "github_user" user profile field before the plugin could match
 * external submissions to a Moodle account). mod_externalassignment_create_github_username_profile_field()
 * is called from both db/install.php (new installs) and xmldb_externalassignment_upgrade()
 * (existing sites), so it must create the field once and be safe to call repeatedly.
 *
 * @package mod_externalassignment
 * @category test
 * @copyright 2026 Marcel Suter <marcel@ghwalin.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[Group('mod_externalassignment')]
#[CoversFunction('mod_externalassignment_create_github_username_profile_field')]
final class upgradelib_test extends \advanced_testcase {
    /**
     * Removes any "github_user" field / "external_usernames" category already present.
     *
     * This plugin's own db/install.php now creates them as part of a normal installation (that is
     * the whole point of GitHub issue #9), so a freshly reset PHPUnit site already has both by the
     * time these tests run. Most of the scenarios below are about a *different* starting state
     * (nothing yet, an admin-made field elsewhere, a category with no field in it yet), so they
     * clear the real ones out first to get a deterministic precondition.
     *
     * @return void
     */
    private function remove_existing_field_and_category(): void {
        global $DB;
        $DB->delete_records('user_info_field', ['shortname' => 'github_user']);
        $DB->delete_records('user_info_category', ['name' => 'external_usernames']);
    }

    /**
     * On a site with no such field yet, the helper must create both the "external_usernames"
     * category and the "github_user" field inside it, matching what the "external_username"
     * admin setting expects by default (see settings.php).
     */
    public function test_creates_category_and_field(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->remove_existing_field_and_category();

        $this->assertFalse($DB->record_exists('user_info_field', ['shortname' => 'github_user']));

        mod_externalassignment_create_github_username_profile_field();

        $category = $DB->get_record('user_info_category', ['name' => 'external_usernames'], '*', MUST_EXIST);
        $field = $DB->get_record('user_info_field', ['shortname' => 'github_user'], '*', MUST_EXIST);

        $this->assertEquals($category->id, $field->categoryid);
        $this->assertEquals('GitHub username', $field->name);
        $this->assertEquals('text', $field->datatype);
        // Two students sharing a GitHub username would make update_grade's lookup ambiguous.
        $this->assertEquals(1, $field->forceunique);
    }

    /**
     * Calling the helper more than once (e.g. because both an old site's upgrade step and a
     * later, unrelated code path invoke it) must not create a duplicate field or category.
     */
    public function test_is_idempotent(): void {
        global $DB;

        $this->resetAfterTest(true);

        mod_externalassignment_create_github_username_profile_field();
        mod_externalassignment_create_github_username_profile_field();

        $this->assertEquals(1, $DB->count_records('user_info_field', ['shortname' => 'github_user']));
        $this->assertEquals(1, $DB->count_records('user_info_category', ['name' => 'external_usernames']));
    }

    /**
     * If an admin already created a "github_user" field by hand before upgrading to a plugin
     * version that includes this helper, their existing field (and whichever category they put
     * it in) must be left completely untouched.
     */
    public function test_leaves_an_existing_field_untouched(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->remove_existing_field_and_category();

        $existing = $this->getDataGenerator()->create_custom_profile_field([
            'shortname' => 'github_user',
            'name' => 'My own GitHub field',
            'datatype' => 'text',
            // No 'category' given: the generator puts it in its own "Testing" category, distinct
            // from the "external_usernames" one the helper would use.
        ]);

        mod_externalassignment_create_github_username_profile_field();

        $this->assertEquals(1, $DB->count_records('user_info_field', ['shortname' => 'github_user']));
        $field = $DB->get_record('user_info_field', ['shortname' => 'github_user'], '*', MUST_EXIST);
        $this->assertEquals($existing->id, $field->id);
        $this->assertEquals('My own GitHub field', $field->name);
        $this->assertFalse($DB->record_exists('user_info_category', ['name' => 'external_usernames']));
    }

    /**
     * If an "external_usernames" category already exists but has no "github_user" field in it yet
     * (e.g. an admin created the category by hand, or a previous run was interrupted between the
     * two inserts), the helper must reuse that category rather than creating a second one with
     * the same name.
     */
    public function test_reuses_an_existing_category(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->remove_existing_field_and_category();

        $category = $this->getDataGenerator()->create_custom_profile_field_category(['name' => 'external_usernames']);

        mod_externalassignment_create_github_username_profile_field();

        $this->assertEquals(1, $DB->count_records('user_info_category', ['name' => 'external_usernames']));
        $field = $DB->get_record('user_info_field', ['shortname' => 'github_user'], '*', MUST_EXIST);
        $this->assertEquals($category->id, $field->categoryid);
    }

    /**
     * End-to-end regression check: a freshly created Moodle user immediately has somewhere to
     * store their GitHub username, and update_grade's own lookup mechanism (matching against
     * user_info_data for the configured "external_username" shortname) finds it - exactly the
     * workflow issue #9 was about the admin having to wire up by hand.
     */
    public function test_created_field_is_usable_for_the_configured_external_username_lookup(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        mod_externalassignment_create_github_username_profile_field();

        $shortname = get_config('mod_externalassignment', 'external_username');
        $this->assertEquals('github_user', $shortname, 'The default setting must match the field this helper creates.');

        $fieldid = $DB->get_field('user_info_field', 'id', ['shortname' => $shortname], MUST_EXIST);

        $student = $this->getDataGenerator()->create_user();
        $DB->insert_record('user_info_data', [
            'userid' => $student->id,
            'fieldid' => $fieldid,
            'data' => 'octocat',
            'dataformat' => FORMAT_MOODLE,
        ]);

        $foundid = $DB->get_field_sql(
            'SELECT userid FROM {user_info_data} WHERE fieldid = :fieldid AND data = :data',
            ['fieldid' => $fieldid, 'data' => 'octocat']
        );
        $this->assertEquals($student->id, $foundid);
    }
}
