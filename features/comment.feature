Feature: Comment fixtures

  Scenario: Generate comments
    Given a WP install
    And a fixtures.yml file:
    """
    Hellonico\Fixtures\Entity\Comment:
      comment{1..5}:
        comment_post_ID: '<postId()>'
        user_id: '<userId()>'
        comment_date: '<dateTimeThisDecade()>'
        comment_author: '<username()>'
        comment_author_email: '<safeEmail()>'
        comment_author_url: '<url()>'
        comment_content: '<paragraphs(2, true)>'
        comment_agent: '<userAgent()>'
        comment_author_IP: '<ipv4()>'
        comment_approved: 1
        comment_karma: '<numberBetween(1, 100)>'
        comment_meta:
            foo_key: '<sentence()>'
        meta:
            bar_key: '<sentence()>'
    """

    When I run `wp fixtures load`
    Then STDOUT should contain:
      """
      5 comments have been successfully created
      """

    When I run `wp comment list --meta_key=_fake --format=count`
    Then STDOUT should be:
      """
      5
      """

    When I run `wp comment list --meta_key=foo_key --format=count`
    Then STDOUT should be:
      """
      5
      """

    When I run `wp comment list --meta_key=bar_key --format=count`
    Then STDOUT should be:
      """
      5
      """

  Scenario: Delete comments
    Given a WP install
    And a fixtures.yml file:
    """
    Hellonico\Fixtures\Entity\Comment:
      comment{1..5}:
        comment_post_ID: '<postId()>'
        user_id: '<userId()>'
        comment_author: '<username()>'
        comment_author_email: '<safeEmail()>'
        comment_content: '<paragraphs(2, true)>'
        comment_agent: '<userAgent()>'
        comment_author_IP: '<ipv4()>'
    """

    When I run `wp fixtures load`
    When I run `wp fixtures delete comment --yes`
    Then STDOUT should contain:
      """
      5 comments have been successfully deleted
      """
