hellonico/wp-cli-fixtures
=========================

Inspired by [Faker](https://github.com/trendwerk/faker), this package provides an easy way to create massive and custom fake data for your WordPress installation.
This package is based on [nelmio/alice](https://github.com/nelmio/alice) and [fzaninotto/Faker](https://github.com/fzaninotto/Faker). Please refer to these package docs for advanced usage.

Quick links: [Install](#install) | [Usage](#usage) | [Contributing](#contributing)

## Install

```
wp package install hellonico/wp-cli-fixtures
```
Requires [wp-cli](https://github.com/wp-cli/wp-cli) >= 0.23.

## Usage

### Create fixtures

At the root of your project, create a `fixtures.yml` file:

```yaml
Hellonico\Fixtures\Entity\User:
  user{1..10}:
    user_login (unique): <username()>
    user_pass: '123456'
    user_email: '<safeEmail()>'
    first_name: '<firstName()>'
    last_name: '<lastName()>'
    description: '<sentence()>'
    meta:
        phone_number: '<phoneNumber()>'
        address: '<streetAddress()>'
        zip: '<postcode()>'
        city: '<city()>'

Hellonico\Fixtures\Entity\Attachment:
  attachment{1..15}:
    post_title: '<sentence()>'
    file (unique): <image(<uploadDir()>, 1200, 1200, 'cats')>

Hellonico\Fixtures\Entity\Term:
  category{1..10}:
    name (unique): '<words(2, true)>'
    description: '<sentence()>'

Hellonico\Fixtures\Entity\Post:
  post{1..30}:
    post_title: '<sentence()>'
    post_content: '<paragraphs(5, true)>'
    post_excerpt: '<paragraphs(1, true)>'
    meta:
        _thumbnail_id: '@attachment*->ID'
    meta_input:
        extra_field: '<paragraphs(1, true)>'
    post_category: ['@category*->term_id']

Hellonico\Fixtures\Entity\Comment:
  comment{1..50}:
    comment_post_ID: '@post*->ID'
    user_id: '@user*->ID'
    comment_author: '<username()>'
    comment_author_email: '<safeEmail()>'
    comment_author_url: '<url()>'
    comment_content: '<paragraphs(2, true)>'
    comment_agent: '<userAgent()>'

```

**IMPORTANT** Make sure referenced IDs are placed **BEFORE** they are used.

Example: `Term` or `Attachment` objects must be placed before `Post` if they are referenced in your posts fixtures.

### Load fixtures

```
wp fixtures load
```

You can also specify a custom file by using the `--file` argument:

```
wp fixtures load --file=data.yml
```

### Delete fixtures

```
wp fixtures delete
```

You also can delete a specific fixture type:

```
wp fixtures delete post
```

Valid types are `post`, `attachment`, `comment`, `term`, `user`.


## Contributing

This package follows PSR2 coding standards. Please ensure your PR sticks to these standards. 
