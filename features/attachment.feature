Feature: Attachment fixtures

  Scenario: Generate attachments
    Given a WP install
    And a fixtures.yml file:
    """
    Hellonico\Fixtures\Entity\Attachment:
      attachment{1..5}:
        post_title: <sentence()>
        post_date: <dateTimeThisDecade()>
        file: <picsum(<uploadDir()>, 200, 200)>
    """

    When I run `wp fixtures load`
    Then STDOUT should contain:
      """
      5 attachments have been successfully created
      """

    When I run `wp post list --post_type=attachment --meta_key=_fake --format=count`
    Then STDOUT should be:
      """
      5
      """

  Scenario: Delete attachments
    Given a WP install
    And a fixtures.yml file:
    """
    Hellonico\Fixtures\Entity\Attachment:
      attachment{1..5}:
        post_title: <sentence()>
        post_date: <dateTimeThisDecade()>
        file: <picsum(<uploadDir()>, 200, 200)>
    """

    When I run `wp fixtures load`
    When I run `wp fixtures delete attachment --yes`
    Then STDOUT should contain:
      """
      5 attachments have been successfully deleted
      """
