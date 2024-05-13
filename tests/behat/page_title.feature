@mod @mod_externalassignment
Feature: In an assignment, page titles are informative
  In order to know I am viewing the correct page
  The page titles need to reflect the current assignment and action

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And the following "activities" exist:
      | activity       | course | name                 | intro                       |
      | externalassignment | C1     | LU08.A11-Test        | Do something |

  Scenario: I view an assignment as a student and take an action
    When I am on the "LU08.A11-Test" Activity page logged in as student1
    Then the page title should contain "C1: LU08.A11-Test"