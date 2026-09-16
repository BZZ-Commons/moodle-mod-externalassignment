@mod @mod_externalassignment @javascript
Feature: Grading an external assignment for the first time must not error when there is no completion tracking
  In order to grade students regardless of whether completion is tracked
  As a teacher
  I need saving a first grade to work when the assignment has no completion tracking at all

  This covers acceptance_tests.md section 5 ("Grading -> completion status"), row a.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode | enablecompletion |
      | Course 1 | C1        | 0        | 1         | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity           | course | name                     | externalname       | externallink                        | manualgrademax | completion |
      | externalassignment | C1     | No completion tracking test | m999-gradingnonefirst | https://www.example.com/assignment | 20             | 0          |

  Scenario: Grading an assignment with no completion tracking succeeds
    Given I am logged in as "teacher1"
    And I am on the "No completion tracking test" "externalassignment activity" page
    And I click on "Show all" "link"
    And I click on "Grade" "link" in the "Student 1" "table_row"
    When I set the field "Grading (max. 20)" to "15"
    And I press "Save changes"
    Then I should not see "An internal error occurred in the completion system"
    And I click on "Show all" "link"
    And I should see "15.00" in the "Student 1" "table_row"
