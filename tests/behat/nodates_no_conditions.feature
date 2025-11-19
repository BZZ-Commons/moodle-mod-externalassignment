@mod @mod_externalassignment
Feature: Create external assignment without dates and completion conditions
  In order to manage assignments effectively
  As a teacher
  I need to be able to create external assignments without dates and completion conditions

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
  Scenario: Create an external assignment without due date and no completion conditions
    Given I am logged in as "teacher1"
    And I turn editing mode on
    And I add an externalassignment activity to course "Course 1" section "1" and I fill the form with:
      | Assignment name              | Assignment without due Date and completion conditions |
      | External assignment          | m999-extassignmentsample                              |
      | Assignment link              | https://www.example.com/assignment                    |
      | Description                  | This is a test assignment.                            |
      | Due date                     | ##last day of this month noon##                       |
      | Cut-off date                 | ##last day of this month 11pm##                       |
      | Completion conditions > None | 1                                                     |
    Then I should see "Assignment without due Date and completion conditions"
    And I should see "Due:"
    And I should see "##last day of this month noon##%A, %d %B %Y, %I:%M##"
    And I should not see "Mark as done"
    And I should not see "Completion"


