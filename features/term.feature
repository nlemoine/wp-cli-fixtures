Feature: Term fixtures

  Scenario: Generate terms
    Given a WP install
    And a fixtures.yml file:
    """
    Hellonico\Fixtures\Entity\Term:
      category{1..5}:
        name (unique): '<words(3, true)>'
        description: '<sentence()>'
        parent: '50%? <termId(childless=1)>'
      tag{1..5}:
        name (unique): '<words(3, true)>'
        description: '<sentence()>'
        taxonomy: 'post_tag'
    """

    When I run `wp fixtures load`
    Then STDOUT should contain:
      """
      10 terms have been successfully created
      """

    When I run `wp term list $(wp taxonomy list --field=name) --meta_key=_fake --format=count`
    Then STDOUT should be:
      """
      10
      """

    When I run `wp term list category --meta_key=_fake --format=count`
    Then STDOUT should be:
      """
      5
      """

    When I run `wp term list post_tag --meta_key=_fake --format=count`
    Then STDOUT should be:
      """
      5
      """

  Scenario: Delete terms
    Given a WP install
    And a fixtures.yml file:
    """
    Hellonico\Fixtures\Entity\Term:
      category{1..5}:
        name (unique): '<words(3, true)>'
        description: '<sentence()>'
        parent: '50%? <termId(childless=1)>'
        taxonomy: 'category'
      tag{1..5}:
        name (unique): '<words(3, true)>'
        description: '<sentence()>'
        taxonomy: 'post_tag'
    """

    When I run `wp fixtures load`
    When I run `wp fixtures delete term --yes`
    Then STDOUT should contain:
      """
      10 terms have been successfully deleted
      """
