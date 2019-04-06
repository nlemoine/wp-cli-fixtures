Feature: Post fixtures

  Scenario: Generate posts
    Given a WP install
    And a fixtures.yml file:
    """
    Hellonico\Fixtures\Entity\Post:
      hello_fixtures:
        post_title: 'Hello Fixtures !'
        post_content: <paragraphs(5, true)>
        post_excerpt: <paragraphs(1, true)>
        post_date: <dateTimeThisDecade()>
        meta_input:
          _foo_custom_field: <paragraphs(1, true)>
        meta:
          _bar_custom_field: <sentence()>
      post{1..4}:
        post_title: <sentence()>
        post_content: <paragraphs(5, true)>
        post_excerpt: <paragraphs(1, true)>
        post_date: <dateTimeThisDecade()>
        meta_input:
          _foo_custom_field: <paragraphs(1, true)>
        meta:
          _bar_custom_field: <sentence()>
    """

    When I run `wp fixtures load`
    Then STDOUT should contain:
      """
      5 posts have been successfully created
      """

    When I run `wp post list --meta_key=_fake --format=count`
    Then STDOUT should be:
      """
      5
      """

    When I run `wp post list --meta_key=_foo_custom_field --format=count`
    Then STDOUT should be:
      """
      5
      """

    When I run `wp post list --meta_key=_bar_custom_field --format=count`
    Then STDOUT should be:
      """
      5
      """

    When I run `wp post list --name=hello-fixtures --format=count`
    Then STDOUT should be:
      """
      1
      """

  # Scenario: Generate fixtures to existing posts
  #   Given a WP install
  #   And a fixtures.yml file:
  #   """
  #   Hellonico\Fixtures\Entity\Post:
  #     hello_fixtures:
  #       __construct: [1]
  #       post_title: <sentence()>
  #       post_content: <paragraphs(5, true)>
  #       post_excerpt: <paragraphs(1, true)>
  #   """

  Scenario: Generate custom post types
    Given a WP install
    And a fixtures.yml file:
    """
    Hellonico\Fixtures\Entity\Post:
      product{1..5}:
        post_title: <sentence()>
        post_content: <paragraphs(5, true)>
        post_type: 'product'
    """

    When I run `wp fixtures load`
    Then STDOUT should contain:
      """
      5 products have been successfully created
      """

    When I run `wp post list --post_type=product --format=count`
    Then STDOUT should be:
      """
      5
      """

  Scenario: Delete posts
    Given a WP install
    And a fixtures.yml file:
    """
    Hellonico\Fixtures\Entity\Post:
      post{1..5}:
        post_title: <sentence()>
        post_content: <paragraphs(5, true)>
    """

    When I run `wp fixtures load`
    When I run `wp fixtures delete post --yes`
    Then STDOUT should contain:
      """
      5 posts have been successfully deleted
      """
