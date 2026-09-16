@mod @mod_externalassignment @javascript
Feature: Grader form "Save and show next" button
  In order to grade a whole class quickly
  As a teacher
  I need a button on the grader form that saves the current student's feedback and takes me
  straight to the next student in the list

  This is the Behat-level regression test for GitHub issue #6 ("Grader form: Submit & Next":
  "Add a button 'Submit & Next' to the grader form. This will save the current feedback and
  navigate to the next student in the list.").

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And the following "users" exist:
      | username | firstname | lastname | email                 |
      | teacher1 | Teacher   | 1        | teacher1@example.com  |
      | student1 | Student   | 1        | student1@example.com  |
      | student2 | Student   | 2        | student2@example.com  |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "activities" exist:
      | activity           | course | name                     | externalname       | externallink                        | manualgrademax |
      | externalassignment | C1     | Grader navigation test   | m999-gradernext     | https://www.example.com/assignment | 20             |

  Scenario: Saving and showing next takes the teacher straight to the next student
    Given I am logged in as "teacher1"
    And I am on the "Grader navigation test" "externalassignment activity" page
    And I click on "Show all" "link"
    And I click on "Grade" "link" in the "Student 1" "table_row"
    And I set the field "Grading (max. 20)" to "12"
    When I press "Save and show next"
    Then I should see "Student 2"
    And the field "Grading (max. 20)" matches value ""
    And I click on "Show all" "link"
    And I should see "12.00" in the "Student 1" "table_row"

  Scenario: The button is not shown for the last student in the list
    Given I am logged in as "teacher1"
    And I am on the "Grader navigation test" "externalassignment activity" page
    And I click on "Show all" "link"
    And I click on "Grade" "link" in the "Student 2" "table_row"
    Then I should not see "Save and show next"
