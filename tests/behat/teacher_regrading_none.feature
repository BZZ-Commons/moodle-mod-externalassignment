@mod @mod_externalassignment @javascript
Feature: Re-grading an external assignment must not error when there is no completion tracking
  In order to correct a student's grade regardless of whether completion is tracked
  As a teacher
  I need changing an already-existing grade to work when the assignment has no completion
  tracking at all

  This covers acceptance_tests.md section 5 ("Grading -> completion status"), row e.

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
      | activity           | course | name                        | externalname      | externallink                        | manualgrademax | completion |
      | externalassignment | C1     | No completion regrading test | m999-regradingnone | https://www.example.com/assignment | 20             | 0          |

  Scenario: Changing an existing grade succeeds when there is no completion tracking
    Given I am logged in as "teacher1"
    And I am on the "No completion regrading test" "externalassignment activity" page
    And I click on "Show all" "link"
    And I click on "Grade" "link" in the "Student 1" "table_row"
    And I set the field "Grading (max. 20)" to "10"
    And I press "Save changes"
    When I set the field "Grading (max. 20)" to "18"
    And I press "Save changes"
    Then I should not see "An internal error occurred in the completion system"
    And I click on "Show all" "link"
    And I should see "18.00" in the "Student 1" "table_row"
