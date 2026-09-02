@mod @mod_externalassignment
Feature: The external assignment link must be shown to students according to its visibility rules
  In order to reach the external system where the actual work happens
  As a student
  I need to see the link to the external assignment whenever "Always show link" is enabled, or
  once submissions have opened

  This is the Behat-level regression test for GitHub issue #27 ("Assignment link": "On the
  students view of the assignment, the link to the external assignment isn't shown").

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                 |
      | teacher1 | Teacher   | 1        | teacher1@example.com  |
      | student1 | Student   | 1        | student1@example.com  |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  @javascript
  Scenario: The link is always shown when "Always show link" is enabled
    Given I am logged in as "teacher1"
    And I turn editing mode on
    And I add an externalassignment activity to course "Course 1" section "1" and I fill the form with:
      | Assignment name        | Always visible link assignment              |
      | External assignment    | m999-alwaysvisiblelink                       |
      | Assignment link        | https://www.example.com/always-visible-link  |
      | Always show link       | 1                                             |
      | Allow submissions from | ##tomorrow noon##                            |
    When I log out
    And I log in as "student1"
    And I am on the "Always visible link assignment" "externalassignment activity" page
    Then I should see "https://www.example.com/always-visible-link"

  @javascript
  Scenario: The link is hidden before submissions open when "Always show link" is off
    Given I am logged in as "teacher1"
    And I turn editing mode on
    And I add an externalassignment activity to course "Course 1" section "1" and I fill the form with:
      | Assignment name        | Not yet open link assignment       |
      | External assignment    | m999-notyetopenlink                 |
      | Assignment link        | https://www.example.com/not-yet-open |
      | Always show link       | 0                                    |
      | Allow submissions from | ##tomorrow noon##                   |
    When I log out
    And I log in as "student1"
    And I am on the "Not yet open link assignment" "externalassignment activity" page
    Then I should not see "https://www.example.com/not-yet-open"
