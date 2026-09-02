@mod @mod_externalassignment
Feature: An external assignment name must be unique within a course
  In order to avoid mixing up two assignments' submissions from the same student
  As a teacher
  I need to be stopped from creating a second external assignment that reuses an external
  name already used by another assignment in the same course

  This is the Behat-level regression test for GitHub issue #35 ("Duplicate external name":
  "When a student has two assignments with the same external name, the plugin picks one (at
  random) to update. Before inserting/updating the assignment, check for duplicates in the same
  course.").

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity           | course | name             | externalname       | externallink                   |
      | externalassignment | C1     | First assignment | m999-duplicatename  | https://www.example.com/first  |

  @javascript
  Scenario: Creating a second assignment with an already-used external name shows a validation error
    Given I am logged in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add an "External assignment" to section "1" using the activity chooser
    And I set the following fields to these values:
      | Assignment name     | Second assignment              |
      | External assignment | m999-duplicatename             |
      | Assignment link     | https://www.example.com/second |
      | External grade max. | 100                             |
      | Percentage to pass  | 60                              |
    And I press "Save and display"
    Then I should see "An assignment with this external name already exists in this course. Please choose another name."
