Feature: User fixtures

  Scenario: Generate users
    Given a WP install
    And a fixtures.yml file:
    """
    Hellonico\Fixtures\Entity\User:
      user{1..5}:
        user_login (unique): <username()>
        user_pass: 123456
        user_email: <safeEmail()>
        user_url: <url()>
        user_registered: <dateTimeThisDecade()>
        first_name: <firstName()>
        last_name: <lastName()>
        description: <sentence()>
        role: <randomElement(['subscriber', 'editor'])>
        meta:
          phone_number: <phoneNumber()>
          address: <streetAddress()>
          zip: <postcode()>
          city: <city()>
    """

    When I run `wp fixtures load`
    Then STDOUT should contain:
      """
      5 users have been successfully created
      """

    When I run `wp user list --meta_key=_fake --format=count`
    Then STDOUT should be:
      """
      5
      """

    When I run `wp user list --meta_key=phone_number --format=count`
    Then STDOUT should be:
      """
      5
      """

  Scenario: Delete users
    Given a WP install
    And a fixtures.yml file:
    """
    Hellonico\Fixtures\Entity\User:
      user{1..5}:
        user_login (unique): <username()>
        user_pass: 123456
        user_email: <safeEmail()>
    """

    When I run `wp fixtures load`
    When I run `wp fixtures delete user --yes`
    Then STDOUT should contain:
      """
      5 users have been successfully deleted
      """
