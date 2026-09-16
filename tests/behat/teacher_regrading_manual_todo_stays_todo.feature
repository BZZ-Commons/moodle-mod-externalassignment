@mod @mod_externalassignment @javascript
Feature: Re-grading an external assignment must not mark an untouched manual completion as done
  In order to trust manual completion tracking
  As a teacher
  I need changing an already-existing grade to leave a student's not-yet-completed manual
  completion toggle untouched, since manual completion is driven only by the student, never
  by grades

  This covers acceptance_tests.md section 5 ("Grading -> completion status"), row g.

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
      | activity           | course | name                        | externalname          | externallink                        | manualgrademax | completion |
      | externalassignment | C1     | Manual completion todo test | m999-regradingmanualtodo | https://www.example.com/assignment | 20             | 1          |

  Scenario: Re-grading an assignment the student hasn't marked done leaves it not done
    Given I am logged in as "teacher1"
    And I am on the "Manual completion todo test" "externalassignment activity" page
    And I click on "Show all" "link"
    And I click on "Grade" "link" in the "Student 1" "table_row"
    And I set the field "Grading (max. 20)" to "10"
    And I press "Save changes"
    When I set the field "Grading (max. 20)" to "18"
    And I press "Save changes"
    Then I should not see "Unexpected manual completion state"
    And I should not see "An internal error occurred in the completion system"
    And I am on the "Manual completion todo test" "externalassignment activity" page logged in as student1
    Then the manual completion button of "Manual completion todo test" is displayed as "Mark as done"
