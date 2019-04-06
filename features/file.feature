Feature: Custom fixtures file

  Scenario: Load a custom file
    Given a WP install
    And a post.yml file:
    """
    Hellonico\Fixtures\Entity\Post:
      post{1..5}:
        post_title: <sentence()>
        post_content: <paragraphs(5, true)>
        post_excerpt: <paragraphs(1, true)>
        post_date: <dateTimeThisDecade()>
    """

    When I run `wp fixtures load --file=post.yml`
    Then STDOUT should contain:
      """
      5 posts have been successfully created
      """
