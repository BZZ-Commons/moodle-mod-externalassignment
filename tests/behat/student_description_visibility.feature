@mod @mod_externalassignment
Feature: The assignment description must respect "Always show description" before the opening date
  In order to avoid revealing draft assignment content early
  As a student
  I should only see the assignment's description before its submission opening date if the
  teacher has explicitly enabled "Always show description"

  This is the Behat-level regression test for GitHub issue #13 ("Always show description
  ignored": "If an 'opendate' is set and 'always show description' is not checked, the
  description is still shown").

  NOTE: view.php's show_details() only blanks a local copy of the description
  ($assignment->set_intro('')) - it is never echoed anywhere in view.php or its templates. The
  activity's description on this page is actually rendered by Moodle's standard activity header,
  which reads the description straight from the course module record and is never told about
  this flag. Based on that reading, the first scenario below is expected to fail until the
  description is either suppressed on the activity header itself or actually echoed conditionally.

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
  Scenario: The description is hidden before the opening date when "Always show description" is off
    Given I am logged in as "teacher1"
    And I turn editing mode on
    And I add an externalassignment activity to course "Course 1" section "1" and I fill the form with:
      | Assignment name        | Hidden description assignment      |
      | External assignment    | m999-hiddendescription              |
      | Assignment link        | https://www.example.com/assignment |
      | Description             | ThisDescriptionTextMustStayHidden  |
      | Allow submissions from | ##tomorrow noon##                  |
    When I log out
    And I log in as "student1"
    And I am on the "Hidden description assignment" "externalassignment activity" page
    Then I should not see "ThisDescriptionTextMustStayHidden"

  @javascript
  Scenario: The description is shown before the opening date when "Always show description" is on
    Given I am logged in as "teacher1"
    And I turn editing mode on
    And I add an externalassignment activity to course "Course 1" section "1" and I fill the form with:
      | Assignment name          | Shown description assignment       |
      | External assignment      | m999-showndescription               |
      | Assignment link          | https://www.example.com/assignment |
      | Description               | ThisDescriptionTextMustBeVisible   |
      | Always show description | 1                                   |
      | Allow submissions from   | ##tomorrow noon##                  |
    When I log out
    And I log in as "student1"
    And I am on the "Shown description assignment" "externalassignment activity" page
    Then I should see "ThisDescriptionTextMustBeVisible"
