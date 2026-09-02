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

namespace mod_externalassignment\local;

use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for class assign
 * @package mod_externalassignment
 * @category test
 * @copyright 2024 Marcel Suter <marcel@ghwalin.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[Group('mod_externalassignment')]
#[CoversMethod(assign::class, '__construct')]
#[CoversMethod(assign::class, 'load_data')]
#[CoversMethod(assign::class, 'load_db')]
#[CoversMethod(assign::class, 'load_db_external')]
#[CoversMethod(assign::class, 'sort_students')]
#[CoversMethod(assign::class, 'count_students')]
#[CoversMethod(assign::class, 'take_student')]
#[CoversMethod(assign::class, 'load_overrides')]
#[CoversMethod(assign::class, 'set_id')]
#[CoversMethod(assign::class, 'get_id')]
#[CoversMethod(assign::class, 'set_course')]
#[CoversMethod(assign::class, 'get_course')]
#[CoversMethod(assign::class, 'set_name')]
#[CoversMethod(assign::class, 'get_name')]
#[CoversMethod(assign::class, 'set_intro')]
#[CoversMethod(assign::class, 'get_intro')]
#[CoversMethod(assign::class, 'set_introformat')]
#[CoversMethod(assign::class, 'get_introformat')]
#[CoversMethod(assign::class, 'set_alwaysshowdescription')]
#[CoversMethod(assign::class, 'is_alwaysshowdescription')]
#[CoversMethod(assign::class, 'set_externalname')]
#[CoversMethod(assign::class, 'get_externalname')]
#[CoversMethod(assign::class, 'set_externallink')]
#[CoversMethod(assign::class, 'get_externallink')]
#[CoversMethod(assign::class, 'set_alwaysshowlink')]
#[CoversMethod(assign::class, 'is_alwaysshowlink')]
#[CoversMethod(assign::class, 'set_allowsubmissionsfromdate')]
#[CoversMethod(assign::class, 'get_allowsubmissionsfromdate')]
#[CoversMethod(assign::class, 'set_duedate')]
#[CoversMethod(assign::class, 'get_duedate')]
#[CoversMethod(assign::class, 'set_cutoffdate')]
#[CoversMethod(assign::class, 'get_cutoffdate')]
#[CoversMethod(assign::class, 'set_externalgrademax')]
#[CoversMethod(assign::class, 'get_externalgrademax')]
#[CoversMethod(assign::class, 'set_manualgrademax')]
#[CoversMethod(assign::class, 'get_manualgrademax')]
#[CoversMethod(assign::class, 'set_passingpercentage')]
#[CoversMethod(assign::class, 'get_passingpercentage')]
#[CoversMethod(assign::class, 'set_needspassinggrade')]
#[CoversMethod(assign::class, 'get_needspassinggrade')]
#[CoversMethod(assign::class, 'is_needspassinggrade')]
#[CoversMethod(assign::class, 'to_stdclass')]
final class assign_test extends \advanced_testcase {
    /**
     * Test constructor with formdata simulation the add/edit form
     */
    public function test_constructor_with_formdata(): void {
        $formdata = new \stdClass();
        $formdata->instance = 5;
        $formdata->course = 4;
        $formdata->coursemodule = 3;
        $formdata->name = 'Test Assignment';
        $formdata->intro = 'Test Assignment Description';
        $formdata->introformat = 1;
        $formdata->alwaysshowdescription = true;
        $formdata->externalname = 'Test Assignment';
        $formdata->externallink = 'http://example.com';
        $formdata->alwaysshowlink = true;
        $formdata->allowsubmissionsfromdate = 0;
        $formdata->duedate = 0;
        $formdata->cutoffdate = 0;
        $formdata->externalgrademax = 100;
        $formdata->manualgrademax = 10;
        $formdata->passingpercentage = 60;
        $formdata->needspassinggrade = 1;

        $assign = new assign($formdata);

        $this->assertEquals(5, $assign->get_id());
        $this->assertEquals(4, $assign->get_course());
        $this->assertEquals('Test Assignment', $assign->get_name());
        $this->assertEquals('Test Assignment Description', $assign->get_intro());
        $this->assertEquals(1, $assign->get_introformat());
        $this->assertTrue($assign->is_alwaysshowdescription());
        $this->assertEquals('Test Assignment', $assign->get_externalname());
        $this->assertEquals('http://example.com', $assign->get_externallink());
        $this->assertTrue($assign->is_alwaysshowlink());
        $this->assertEquals(0, $assign->get_allowsubmissionsfromdate());
        $this->assertEquals(0, $assign->get_duedate());
        $this->assertEquals(0, $assign->get_cutoffdate());
        $this->assertEquals(100, $assign->get_externalgrademax());
        $this->assertEquals(10, $assign->get_manualgrademax());
        $this->assertEquals(60, $assign->get_passingpercentage());
        $this->assertEquals(1, $assign->get_needspassinggrade());
    }

    /**
     * Test constructor without formdata
     */
    public function test_constructor_without_formdata(): void {
        $assign = new assign(null);
        $this->assertNull($assign->get_id());
    }

    /**
     * Test loaddata
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_loaddata(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id]);

        $assign = new assign(null);
        $assign->load_db($instance->cmid);
        $this->assertEquals($instance->id, $assign->get_id());
        $this->assertEquals($instance->course, $assign->get_course());
        $this->assertEquals($instance->name, $assign->get_name());
        $this->assertEquals($instance->intro, $assign->get_intro());
        $this->assertEquals($instance->introformat, $assign->get_introformat());
        $this->assertEquals($instance->alwaysshowdescription, $assign->is_alwaysshowdescription());
        $this->assertEquals($instance->externalname, $assign->get_externalname());
        $this->assertEquals($instance->externallink, $assign->get_externallink());
        $this->assertEquals($instance->alwaysshowlink, $assign->is_alwaysshowlink());
        $this->assertEquals($instance->allowsubmissionsfromdate, $assign->get_allowsubmissionsfromdate());
        $this->assertEquals($instance->duedate, $assign->get_duedate());
        $this->assertEquals($instance->cutoffdate, $assign->get_cutoffdate());
        $this->assertEquals($instance->externalgrademax, $assign->get_externalgrademax());
        $this->assertEquals($instance->manualgrademax, $assign->get_manualgrademax());
        $this->assertEquals($instance->passingpercentage, $assign->get_passingpercentage());
        $this->assertEquals($instance->needspassinggrade, $assign->get_needspassinggrade());
    }

    /**
     * Test load_db_external
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_load_db_external(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id, 'externalname' => 'externalname']);
        $cm = get_coursemodule_from_instance('externalassignment', $instance->id);
        $context = \context_module::instance($cm->id);

        $user = $this->getDataGenerator()->create_user(['firstname' => 'John', 'lastname' => 'Doe']);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $assign = new assign(null, $context);
        $assign->load_db_external('externalname', $user->id);
        $this->assertEquals($instance->id, $assign->get_id());
    }
    /**
     * Test sort_students
     */
    public function test_sort_students(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id]);
        $cm = get_coursemodule_from_instance('externalassignment', $instance->id);
        $context = \context_module::instance($cm->id);

        $user1 = $this->getDataGenerator()->create_user(['firstname' => 'John', 'lastname' => 'Doe']);
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $user2 = $this->getDataGenerator()->create_user(['firstname' => 'Jane', 'lastname' => 'Smith']);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $user3 = $this->getDataGenerator()->create_user(['firstname' => 'Alice', 'lastname' => 'Johnson']);
        $this->getDataGenerator()->enrol_user($user3->id, $course->id);
        $user4 = $this->getDataGenerator()->create_user(['firstname' => 'Bob', 'lastname' => 'Brown']);
        $this->getDataGenerator()->enrol_user($user4->id, $course->id);

        $assign = new assign(null, $context);

        // Sort by lastname ascending.
        $assign->load_db($instance->cmid, 'lastname', 'asc');
        $users = $assign->get_students();
        assert(is_array($users));
        $this->assertCount(4, $users);
        $this->assertEquals('Brown', reset($users)->get_lastname());

        // Sort by lastname descending.
        $assign->load_db($instance->cmid, 'lastname', 'desc');
        $users = $assign->get_students();
        assert(is_array($users));
        $this->assertCount(4, $users);
        $this->assertEquals('Smith', reset($users)->get_lastname());

        // Sort by firstname ascending.
        $assign->load_db($instance->cmid, 'firstname', 'asc');
        $users = $assign->get_students();
        assert(is_array($users));
        $this->assertCount(4, $users);
        $this->assertEquals('Alice', reset($users)->get_firstname());

        // Sort by firstname descending.
        $assign->load_db($instance->cmid, 'firstname', 'desc');
        $users = $assign->get_students();
        assert(is_array($users));
        $this->assertCount(4, $users);
        $this->assertEquals('John', reset($users)->get_firstname());

        /* Sort by grade ascending
        $assign->load_db($instance->cmid, 'grade', 'asc');
        $users = $assign->get_students();
        assert (is_array($users));
        $this->assertCount(4, $users);
        $this->assertEquals('Doe', $users[0]->lastname);
        // Sort by grade descending
        $assign->load_db($instance->cmid, 'grade', 'desc');
        $users = $assign->get_students();
        assert (is_array($users));
        $this->assertCount(4, $users);
        $this->assertEquals('Smith', $users[0]->lastname); */
    }

    /**
     * Test count_students
     */
    public function test_count_students(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id]);
        $cm = get_coursemodule_from_instance('externalassignment', $instance->id);
        $context = \context_module::instance($cm->id);

        $user1 = $this->getDataGenerator()->create_user(['firstname' => 'John', 'lastname' => 'Doe']);
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $user2 = $this->getDataGenerator()->create_user(['firstname' => 'Jane', 'lastname' => 'Smith']);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $user3 = $this->getDataGenerator()->create_user(['firstname' => 'Alice', 'lastname' => 'Johnson']);
        $this->getDataGenerator()->enrol_user($user3->id, $course->id);
        $user4 = $this->getDataGenerator()->create_user(['firstname' => 'Bob', 'lastname' => 'Brown']);
        $this->getDataGenerator()->enrol_user($user4->id, $course->id);

        $assign = new assign(null, $context);

        // Sort by lastname ascending.
        $assign->load_db($instance->cmid, 'lastname', 'asc');
        $this->assertEquals(4, $assign->count_students());
    }

    /**
     * Test take_students
     */
    public function test_take_student(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id]);
        $cm = get_coursemodule_from_instance('externalassignment', $instance->id);
        $context = \context_module::instance($cm->id);

        $user1 = $this->getDataGenerator()->create_user(['firstname' => 'John', 'lastname' => 'Doe']);
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $user2 = $this->getDataGenerator()->create_user(['firstname' => 'Jane', 'lastname' => 'Smith']);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $user3 = $this->getDataGenerator()->create_user(['firstname' => 'Alice', 'lastname' => 'Johnson']);
        $this->getDataGenerator()->enrol_user($user3->id, $course->id);
        $user4 = $this->getDataGenerator()->create_user(['firstname' => 'Bob', 'lastname' => 'Brown']);
        $this->getDataGenerator()->enrol_user($user4->id, $course->id);

        $assign = new assign(null, $context);

        // Sort by lastname ascending.
        $assign->load_db($instance->cmid, 'lastname', 'asc');
        $users = $assign->get_students();

        $user = reset($users);
        $this->assertEquals($user->get_lastname(), $assign->take_student($user->get_userid())->get_lastname());

        $user = next($users);
        $this->assertEquals($user->get_lastname(), $assign->take_student($user->get_userid())->get_lastname());
    }

    /**
     * Test load_overrides
     */
    public function test_load_overrides(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment');
        $instance = $generator->create_instance(['course' => $course->id]);
        $cm = get_coursemodule_from_instance('externalassignment', $instance->id);
        $context = \context_module::instance($cm->id);
        $assign = new assign(null, $context);
        $user1 = $this->getDataGenerator()->create_user(['firstname' => 'John', 'lastname' => 'Doe']);
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $user2 = $this->getDataGenerator()->create_user(['firstname' => 'Jane', 'lastname' => 'Smith']);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

        $students = [
            $user1->id => new student($assign, $user1),
            $user2->id => new student($assign, $user2),
        ];
        $assign->set_students($students);

        $override = $generator->create_override_entry(
            [
                'externalassignment' => $instance->cmid,
                'userid' => $user1->id,
                'allowsubmissionsfromdate' => time() - 3600,
                'duedate' => time() + 3600,
                'cutoffdate' => time() + 7200,
            ]
        );
        $reflection = new \ReflectionClass($assign);
        $method = $reflection->getMethod('load_overrides');
        $method->setAccessible(true);
        $method->invoke($assign, $instance->cmid, $user1->id);
        $result = $assign->take_student($user1->id)->get_override();
        $this->assertNotNull($result);
        $this->assertEquals($instance->cmid, $result->get_externalassignment());
        $this->assertEquals($user1->id, $result->get_userid());

        $this->assertNull($assign->take_student($user2->id)->get_override());
    }

    /**
     * Test the setters and getters
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_setters_getters(): void {
        $assign = new assign(null);
        $assign->set_id(5);
        $assign->set_course(4);
        $assign->set_name('Test Assignment');
        $assign->set_intro('Test Assignment Description');
        $assign->set_introformat(1);
        $assign->set_alwaysshowdescription(true);
        $assign->set_externalname('Test Assignment');
        $assign->set_externallink('http://example.com');
        $assign->set_alwaysshowlink(true);
        $assign->set_allowsubmissionsfromdate(0);
        $assign->set_duedate(0);
        $assign->set_cutoffdate(0);
        $assign->set_externalgrademax(100);
        $assign->set_manualgrademax(10);
        $assign->set_passingpercentage(60);
        $assign->set_needspassinggrade(1);

        $this->assertEquals(5, $assign->get_id());
        $this->assertEquals(4, $assign->get_course());
        $this->assertEquals('Test Assignment', $assign->get_name());
        $this->assertEquals('Test Assignment Description', $assign->get_intro());
        $this->assertEquals(1, $assign->get_introformat());
        $this->assertTrue($assign->is_alwaysshowdescription());
        $this->assertEquals('Test Assignment', $assign->get_externalname());
        $this->assertEquals('http://example.com', $assign->get_externallink());
        $this->assertTrue($assign->is_alwaysshowlink());
        $this->assertEquals(0, $assign->get_allowsubmissionsfromdate());
        $this->assertEquals(0, $assign->get_duedate());
        $this->assertEquals(0, $assign->get_cutoffdate());
        $this->assertEquals(100, $assign->get_externalgrademax());
        $this->assertEquals(10, $assign->get_manualgrademax());
        $this->assertEquals(60, $assign->get_passingpercentage());
        $this->assertEquals(1, $assign->get_needspassinggrade());
    }


    /**
     * Test the casting to a stdclass
     */
    public function test_to_stdclass(): void {
        $assign = new assign(null);
        $assign->set_id(1);
        $assign->set_course(1);
        $assign->set_name('Test Assignment');
        $assign->set_intro('Test Assignment Description');
        $assign->set_introformat(1);
        $assign->set_alwaysshowdescription(true);
        $assign->set_externalname('Test Assignment');
        $assign->set_externallink('http://example.com');
        $assign->set_alwaysshowlink(true);
        $assign->set_allowsubmissionsfromdate(150000);
        $assign->set_duedate(151000);
        $assign->set_cutoffdate(152000);
        $assign->set_externalgrademax(100);
        $assign->set_manualgrademax(10);
        $assign->set_passingpercentage(60);
        $assign->set_needspassinggrade(1);

        $stdclass = $assign->to_stdclass();

        $this->assertEquals(1, $stdclass->id);
        $this->assertEquals(1, $stdclass->course);
        $this->assertEquals('Test Assignment', $stdclass->name);
        $this->assertEquals('Test Assignment Description', $stdclass->intro);
        $this->assertEquals(1, $stdclass->introformat);
        $this->assertTrue($stdclass->alwaysshowdescription);
        $this->assertEquals('Test Assignment', $stdclass->externalname);
        $this->assertEquals('http://example.com', $stdclass->externallink);
        $this->assertTrue($stdclass->alwaysshowlink);
        $this->assertEquals(150000, $stdclass->allowsubmissionsfromdate);
        $this->assertEquals(151000, $stdclass->duedate);
        $this->assertEquals(152000, $stdclass->cutoffdate);
        $this->assertEquals(100, $stdclass->externalgrademax);
        $this->assertEquals(10, $stdclass->manualgrademax);
        $this->assertEquals(60, $stdclass->passingpercentage);
        $this->assertEquals(1, $stdclass->needspassinggrade);
    }
}
