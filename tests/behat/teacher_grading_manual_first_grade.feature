@mod @mod_externalassignment @javascript
Feature: Grading an external assignment for the first time must not affect an untouched manual completion status
  In order to trust manual completion tracking
  As a teacher
  I need saving a first grade to leave a student's manual completion toggle exactly as it
  was, since manual completion is driven only by the student, never by grades

  This covers acceptance_tests.md section 5 ("Grading -> completion status"), row b.

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
      | activity           | course | name                       | externalname            | externallink                        | manualgrademax | completion |
      | externalassignment | C1     | Manual completion untouched | m999-gradingmanualfirst | https://www.example.com/assignment | 20             | 1          |

  Scenario: A first grade does not mark an untouched manual completion as done
    Given I am logged in as "teacher1"
    And I am on the "Manual completion untouched" "externalassignment activity" page
    And I click on "Show all" "link"
    And I click on "Grade" "link" in the "Student 1" "table_row"
    When I set the field "Grading (max. 20)" to "15"
    And I press "Save changes"
    Then I should not see "Unexpected manual completion state"
    And I should not see "An internal error occurred in the completion system"
    And I am on the "Manual completion untouched" "externalassignment activity" page logged in as student1
    Then the manual completion button of "Manual completion untouched" is displayed as "Mark as done"
