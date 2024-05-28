@mod @mod_assign
Feature: Check that the starting and due dates are display correctly and will work as intended

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And the following "users" exist:
      | username | firstname | lastname | email                 |
      | teacher1 | Teacher   | 1        | teacher1@example.com  |
      | student1 | Student   | 1        | student10@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity           | course | name                                | intro        | allowsubmissionsfromdate | duedate             | alwaysshowdescription |
      | externalassignment | C1     | Future start and always display on  | Do something | 2040-01-01 00:00:00      | 2040-01-02 00:00:00 | 1                     |
      | externalassignment | C1     | Future start and always display off | Do something | 2040-01-01 00:00:00      | 2040-01-02 00:00:00 | 0                     |
      | externalassignment | C1     | Future due date                     | Do something | 2020-01-01 00:00:00      | 2040-01-02 00:00:00 | 1                     |
      | externalassignment | C1     | Due date past                       | Do something | 2020-01-01 00:00:00      | 2020-01-02 00:00:00 | 1                     |

  @javascript
  Scenario: See the description of the activity as a student
    When I am on the "Future start and always display on" Activity page logged in as student1
    Then I should see "Do something"

  @javascript
  Scenario: Do not see the description of the activity as a student
    When I am on the "Future start and always display on" Activity page logged in as student1
    Then I should not see "Do something"

  @javascript
  Scenario: See the remaining time of the activity as a student
    When I am on the "Future due date" Activity page logged in as student1
    Then I should see "Time remaining"

    @javascript
    Scenario: Do not see the remaining time of the activity as a student
    When I am on the "Due date past" Activity page logged in as student1
    Then I should see "Assignment is due"