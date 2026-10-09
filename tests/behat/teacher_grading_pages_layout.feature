@mod @mod_externalassignment @javascript
Feature: The grading pages use the course layout that carries the previous/next activity links
  In order to move on to the neighbouring activities without going back to the course page
  As a teacher
  I need the submissions overview and the grader form to use the "incourse" page layout, which is
  the layout core needs to show the links to the previous and next activity

  This is the Behat-level regression test for GitHub issue #41 ("Missing 'next' and 'previous'
  activity"). Core hides the links when the theme shows the course index, so the test checks the
  page layout rather than the links themselves.

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
    And the following "activities" exist:
      | activity           | course | name           | externalname | externallink                       | manualgrademax |
      | externalassignment | C1     | Layout test    | m999-layout  | https://www.example.com/assignment | 20             |

  Scenario: The submissions overview uses the incourse layout
    Given I am logged in as "teacher1"
    And I am on the "Layout test" "externalassignment activity" page
    When I click on "Submissions" "link" in the ".secondary-navigation" "css_element"
    Then "body.pagelayout-incourse" "css_element" should exist

  Scenario: The grader form uses the incourse layout
    Given I am logged in as "teacher1"
    And I am on the "Layout test" "externalassignment activity" page
    And I click on "Submissions" "link" in the ".secondary-navigation" "css_element"
    When I click on "Grade" "link" in the "Student 1" "table_row"
    Then "body.pagelayout-incourse" "css_element" should exist
