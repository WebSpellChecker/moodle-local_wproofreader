@local @local_wproofreader
Feature: The availability matrix on the settings page
  In order to control proofreading without editing config by hand
  As an administrator
  I need the matrix to offer a cell per area and role, and to take effect once saved

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam       | Student  | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And I log in as "admin"
    And I navigate to "Plugins > Local plugins > WProofreader" in site administration

  Scenario: The matrix offers a cell for every area and role that can act on it
    Then "//input[@aria-label='Courses and activities: Student']" "xpath_element" should exist
    And "//input[@aria-label='Quiz attempts: Student']" "xpath_element" should exist
    And "//input[@aria-label='System pages: Student']" "xpath_element" should exist
    And "//input[@aria-label='Site administration: Manager']" "xpath_element" should exist

  Scenario: Roles that cannot reach an area get no checkbox there
    Then "//input[@aria-label='Site administration: Student']" "xpath_element" should not exist
    And "//input[@aria-label='Site administration: Guest']" "xpath_element" should not exist
    And "//input[@aria-label='User pages: Authenticated user on site home']" "xpath_element" should not exist
    And "//td[contains(@class, 'local-wproofreader-cell')]//span[contains(@class, 'local-wproofreader-na')]" "xpath_element" should exist

  Scenario: Unticking a cell on the settings page withholds proofreading from that role
    Given the WProofreader availability matrix is cleared
    And I am on the "Course 1" "course" page logged in as "student1"
    And WProofreader should be active
    And I log out
    And I log in as "admin"
    And I navigate to "Plugins > Local plugins > WProofreader" in site administration
    When I set the field with xpath "//input[@aria-label='Courses and activities: Student']" to ""
    And I press "Save changes"
    And I log out
    And I am on the "Course 1" "course" page logged in as "student1"
    Then WProofreader should not be active
