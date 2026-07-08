@mod @mod_externalassignment
Feature: As I student view external assignment with due/cut off dates and manual completion
  In order to see assignment details

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
      | activity           | course | name                       | intro        | completion | duedate                        | cutoffdate                    |
      | externalassignment | C1     | Cutoff & Manual completion | Do something | 1          | ##last day of this month noon## | ##last day of this month 11pm## |

  @javascript
  Scenario: Student views external assignment with due/cut off dates and manual completion
    Given I am logged in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "Cutoff & Manual completion"
    And I should see "Due:"
    And I should see "##last day of this month noon##%A, %d %B %Y, %I:%M##"
    And I should see "Mark as done"

