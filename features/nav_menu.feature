Feature: Nav menus fixtures

  Scenario: Generate nav menus
    Given a WP install
    And a fixtures.yml file:
    """
    Hellonico\Fixtures\Entity\NavMenu:
      header:
        name: header
        locations:
          - header
          - footer
    """

    When I run `wp fixtures load`
    Then STDOUT should contain:
      """
      1 navmenu have been successfully created
      """

    When I run `wp menu list --format=count`
    Then STDOUT should be:
      """
      1
      """

  Scenario: Delete terms
    Given a WP install
    And a fixtures.yml file:
    """
    Hellonico\Fixtures\Entity\NavMenu:
      header:
        name: header
        locations:
          - header
          - footer
    """

    When I run `wp fixtures load`
    When I run `wp fixtures delete term --yes`
    Then STDOUT should contain:
      """
      1 term have been successfully deleted
      """
