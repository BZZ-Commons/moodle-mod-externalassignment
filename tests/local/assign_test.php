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

/**
 * Unit tests for class assign
 * @group mod_externalassignment
 * @package mod_externalassignment
 * @category test
 * @copyright 2024 Marcel Suter <marcel@ghwalin.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * /
 */
final class assign_test extends \advanced_testcase {
    /**
     * Test constructor with formdata simulation the add/edit form
     * @covers \assign::__construct
     * @covers \assign::load_data
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
     * @covers \assign::__construct
     */
    public function test_constructor_without_formdata(): void {
        $assign = new assign(null);
        $this->assertNull($assign->get_id());
    }

    /**
     * Test loaddata
     * @covers \assign::load_db
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
     * @covers ::load_db_external
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_load_db_external() {
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
    * @covers \assign::sort_students
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

        // Sort by lastname ascending
        $assign->load_db($instance->cmid, 'lastname', 'asc');
        $users = $assign->get_students();
        assert (is_array($users));
        $this->assertCount(4, $users);
        $this->assertEquals('Brown', reset($users)->get_lastname());

        // Sort by lastname descending
        $assign->load_db($instance->cmid, 'lastname', 'desc');
        $users = $assign->get_students();
        assert (is_array($users));
        $this->assertCount(4, $users);
        $this->assertEquals('Smith', reset($users)->get_lastname());

        // Sort by firstname ascending
        $assign->load_db($instance->cmid, 'firstname', 'asc');
        $users = $assign->get_students();
        assert (is_array($users));
        $this->assertCount(4, $users);
        $this->assertEquals('Alice', reset($users)->get_firstname());

        // Sort by firstname descending
        $assign->load_db($instance->cmid, 'firstname', 'desc');
        $users = $assign->get_students();
        assert (is_array($users));
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
     * @covers \assign::count_students
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

        // Sort by lastname ascending
        $assign->load_db($instance->cmid, 'lastname', 'asc');
        $this->assertEquals(4, $assign->count_students());
    }

    /**
     * Test take_students
     * @covers \assign::take_students
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

        // Sort by lastname ascending
        $assign->load_db($instance->cmid, 'lastname', 'asc');
        $users = $assign->get_students();

        $user = reset($users);
        $this->assertEquals($user->get_lastname(), $assign->take_student($user->get_userid())->get_lastname());

        $user = next($users);
        $this->assertEquals($user->get_lastname(), $assign->take_student($user->get_userid())->get_lastname());

    }

    /**
     * Test the setters and getters
     * @covers \assign::set_id
     * @covers \assign::get_id
     * @covers \assign::set_course
     * @covers \assign::get_course
     * @covers \assign::set_name
     * @covers \assign::get_name
     * @covers \assign::set_intro
     * @covers \assign::get_intro
     * @covers \assign::set_introformat
     * @covers \assign::get_introformat
     * @covers \assign::set_alwaysshowdescription
     * @covers \assign::is_alwaysshowdescription
     * @covers \assign::set_externalname
     * @covers \assign::get_externalname
     * @covers \assign::set_externallink
     * @covers \assign::get_externallink
     * @covers \assign::set_alwaysshowlink
     * @covers \assign::is_alwaysshowlink
     * @covers \assign::set_allowsubmissionsfromdate
     * @covers \assign::get_allowsubmissionsfromdate
     * @covers \assign::set_duedate
     * @covers \assign::get_duedate
     * @covers \assign::set_cutoffdate
     * @covers \assign::get_cutoffdate
     * @covers \assign::set_externalgrademax
     * @covers \assign::get_externalgrademax
     * @covers \assign::set_manualgrademax
     * @covers \assign::get_manualgrademax
     * @covers \assign::set_passingpercentage
     * @covers \assign::get_passingpercentage
     * @covers \assign::set_needspassinggrade
     * @covers \assign::get_needspassinggrade
     * @covers \assign::is_needspassinggrade
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
     * @covers \assign::to_stdclass
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
