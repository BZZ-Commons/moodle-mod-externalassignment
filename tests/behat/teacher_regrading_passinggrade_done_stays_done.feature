@mod @mod_externalassignment @javascript
Feature: Re-grading a passing external assignment with another passing grade keeps it complete
  In order to trust the completion tracking of an external assignment
  As a teacher
  I need changing an already-passing grade to another passing grade to leave the "needs
  passing grade" completion condition marked done

  This covers acceptance_tests.md section 5 ("Grading -> completion status"), row h.

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
      | activity           | course | name                    | externalname            | externallink                        | externalgrademax | manualgrademax | passingpercentage | needspassinggrade | completion |
      | externalassignment | C1     | Regrading done to done  | m999-regradingdonedone  | https://www.example.com/assignment  | 0                 | 20              | 60                 | 1                  | 2          |

  Scenario: Re-grading a passing assignment with another passing grade stays done
    Given I am logged in as "teacher1"
    And I am on the "Regrading done to done" "externalassignment activity" page
    And I click on "Show all" "link"
    And I click on "Grade" "link" in the "Student 1" "table_row"
    And I set the field "Grading (max. 20)" to "15"
    And I press "Save changes"
    When I set the field "Grading (max. 20)" to "16"
    And I press "Save changes"
    And I log out
    And I am logged in as "student1"
    And I am on "Course 1" course homepage
    Then the "Receive a passing grade" completion condition of "Regrading done to done" is displayed as "done"
