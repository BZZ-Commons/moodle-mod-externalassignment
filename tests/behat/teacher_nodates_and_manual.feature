@mod @mod_externalassignment
Feature: Create external assignment without due date and manual completion
  In order to manage assignments effectively
  As a teacher
  I need to be able to create external assignments with specific dates and manual completion

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
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |

  @javascript
  Scenario: Create an external assignment without dates and manual completion
    Given I am logged in as "teacher1"
    And I turn editing mode on
    And I add an externalassignment activity to course "Course 1" section "1" and I fill the form with:
      | Assignment name                                  | Assignment without due Date and manual completion |
      | External assignment                              | m999-extassignmentsample                          |
      | Assignment link                                  | https://www.example.com/assignment                |
      | Description                                      | This is a test assignment.                        |
      | Add requirements                                 | 1                                                 |
      | Students must manually mark the activity as done | 1                                                 |
    And I click on "Completion" "button"
    Then I should see "Assignment without due Date and manual completion"
    And I should not see "Due:"
    And I should see "Mark as done"


