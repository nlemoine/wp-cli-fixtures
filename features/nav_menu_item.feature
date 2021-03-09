Feature: Nav menu item fixtures

  Scenario: Generate nav menu items
    Given a WP install
    And a fixtures.yml file:
    """
    Hellonico\Fixtures\Entity\NavMenu:
      header:
        name: header
        locations:
          - header
    Hellonico\Fixtures\Entity\NavMenuItem:
      items{1..5}:
        menu_item_url: <url()>
        menu_item_title: <words(4, true)>
        menu_id: '@header->term_id'
    """

    When I run `wp fixtures load`
    Then STDOUT should contain:
      """
      5 navmenuitems have been successfully created
      """

    When I run `wp menu item list header --format=count`
    Then STDOUT should be:
      """
      5
      """
