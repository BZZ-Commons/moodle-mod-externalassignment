@mod @mod_externalassignment
Feature: The "Submissions" tab is highlighted on the submissions overview
  In order to know where I am in the activity navigation
  As a teacher
  I need the "Submissions" tab, not the "External assignment" tab, to be highlighted while I view
  the submissions overview or the grader form

  This is the Behat-level regression test for GitHub issue #40 ("Wrong link highlighted for
  submissions overview": "When clicking on the 'Submission' tab, the overview of all submissions
  displays. However, the 'External assignment' link is highlighted instead of 'Submissions.'").

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
      | activity           | course | name                 | externalname | externallink                       | manualgrademax |
      | externalassignment | C1     | Highlight test       | m999-tabs    | https://www.example.com/assignment | 20             |

  Scenario: The Submissions tab is the active tab on the submissions overview
    Given I am logged in as "teacher1"
    And I am on the "Highlight test" "externalassignment activity" page
    When I click on "Submissions" "link" in the ".secondary-navigation" "css_element"
    Then I should see "Submissions" in the ".secondary-navigation .nav-link.active" "css_element"
    And I should not see "Highlight test" in the ".secondary-navigation .nav-link.active" "css_element"

  @javascript
  Scenario: The Submissions tab stays active on the grader form
    Given I am logged in as "teacher1"
    And I am on the "Highlight test" "externalassignment activity" page
    And I click on "Submissions" "link" in the ".secondary-navigation" "css_element"
    When I click on "Grade" "link" in the "Student 1" "table_row"
    Then I should see "Submissions" in the ".secondary-navigation .nav-link.active" "css_element"
