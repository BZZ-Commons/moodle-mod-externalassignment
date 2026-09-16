@mod @mod_externalassignment @javascript
Feature: Edit an external assignment to remove its dates and completion conditions
  In order to manage assignments effectively
  As a teacher
  I need to be able to edit an existing external assignment to remove its due/cut off
  dates and completion conditions

  This covers acceptance_tests.md section 3 ("Edit existing external assignment"), row i. Unlike
  the other rows, which edit a bare assignment to add dates/conditions, this one starts from a
  fully-configured assignment and edits it back down to nothing, to prove that clearing settings
  through the edit form works as well as adding them.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode | enablecompletion |
      | Course 1 | C1        | 0        | 1         | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email                 |
      | teacher1 | Teacher   | 1        | teacher1@example.com  |
      | student1 | Student   | 1        | student10@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity           | course | name                                   | externalname       | externallink                        | duedate                          | cutoffdate                       | completion | needspassinggrade |
      | externalassignment | C1     | Assignment with dates and passing grade | m999-editremoveall  | https://www.example.com/assignment | ##last day of this month noon## | ##last day of this month 11pm## | 2          | 1                  |

  Scenario: Editing an external assignment to remove its dates and completion conditions
    Given I am logged in as "teacher1"
    And I turn editing mode on
    And I am on the "Assignment with dates and passing grade" "externalassignment activity editing" page
    And I set the following fields to these values:
      | Due date                     | disabled |
      | Cut-off date                 | disabled |
      | Completion conditions > None | 1        |
    And I press "Save and return to course"
    Then I should see "Assignment with dates and passing grade"
    And I should not see "Due:"
    And I should not see "Mark as done"
    And I should not see "Completion"
