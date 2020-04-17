@ou @ou_vle @tool @tool_profileroles
Feature: Auto-assigning roles to user on login
  In order to be given access automatically based on user profile fields
  As anybody
  I can have my roles adjusted when I log in

  Background:
    Given the following "users" exist:
      | username | department |
      | student1 | IT         |
      | student2 | Maths      |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following config values are set as admin:
      | roleconfig | manager: department=IT | tool_profileroles |
      | enabled    | 1                      | tool_profileroles |

  Scenario: Log in with user who automatically gets manager role
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    # I should get to the course page with manager features.
    Then I should see "Topic 1"
    And I should see "Site administration"

  Scenario: Log in with user who does not automatically get manager role
    Given I log in as "student2"
    And I am on "Course 1" course homepage
    Then I should see "You can not enrol yourself in this course"
