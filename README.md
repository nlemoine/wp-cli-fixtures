wp-cli-fixtures
=========================

[![Packagist](https://img.shields.io/packagist/v/hellonico/wp-cli-fixtures.svg)](https://packagist.org/packages/hellonico/wp-cli-fixtures)
[![Build Status](https://travis-ci.org/nlemoine/wp-cli-fixtures.svg?branch=master)](https://travis-ci.org/nlemoine/wp-cli-fixtures)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/hellonico/wp-cli-fixtures.svg)](https://packagist.org/packages/hellonico/wp-cli-fixtures)

Inspired by [Faker](https://github.com/trendwerk/faker), this package provides an easy way to create massive and custom fake data for your WordPress installation.
This package is based on [nelmio/alice](https://github.com/nelmio/alice) and [fzaninotto/Faker](https://github.com/fzaninotto/Faker). Please refer to these packages docs for advanced usage.

![wp-cli-fixtures demo](http://hellonico.s3-website-eu-west-1.amazonaws.com/dev/wp-cli-fixtures.gif)

**WARNING:** This package is mostly intented to be used for development purposes. Use it at your own risk, don't run it on a production database or make sure to back it up first.

Quick links: [Install](#install) | [Usage](#usage) | [Contribute](#contribute)

## Install

```bash
wp package install git@github.com:nlemoine/wp-cli-fixtures.git
```

Requires PHP `^7.1`.

## Usage

### Create fixtures

At the root of your project, create a `fixtures.yml` file (you can download it [here](https://raw.githubusercontent.com/nlemoine/wp-cli-fixtures/master/examples/fixtures.yml)):

```yaml
#
# USERS
#
Hellonico\Fixtures\Entity\User:
  user{1..10}:
    user_login (unique): <username()> # '(unique)' is required
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
    acf:
      facebook_url: <url()>
      twitter_url: <url()>

#
# ATTACHMENTS
#
Hellonico\Fixtures\Entity\Attachment:
  default (template): # templates can be extended to keep things DRY
    post_title: <words(2, true)>
    post_date: <dateTimeThisDecade()>
    post_content: <paragraphs(5, true)>
  images{1..15} (extends default):
    file: <image(<uploadDir()>, 1200, 1200, 'cats')> # <uploadDir()> is required
  documents{1..2} (extends default):
    file: <fileIn('relative/path/to/pdfs')>
  custom_images{1..10} (extends default):
    file: <fileIn('relative/path/to/images')>

#
# TERMS
#
Hellonico\Fixtures\Entity\Term:
  category{1..10}:
    name (unique): <words(2, true)> # '(unique)' is required
    description: <sentence()>
    parent: '50%? <termId(childless=1)>' # 50% of created categories will have a top level parent category
    taxonomy: 'category' # could be skipped, default to 'category'
  tag{1..40}:
    name (unique): <words(2, true)> # '(unique)' is required
    description: <sentence()>
    taxonomy: post_tag
  places{1..4}: # custom taxonomy
    name (unique): <words(2, true)> # '(unique)' is required
    description: <sentences(3, true)>
    taxonomy: place
    acf:
      address: <streetAddress>
      zip: <postcode()>
      city: <city()>
      image: '@custom_images*->ID'

#
# POSTS
#
Hellonico\Fixtures\Entity\Post:

  # TEMPLATE
  default (template):
    post_title: <words(2, true)>
    post_date: <dateTimeThisDecade()>
    post_content: <paragraphs(5, true)>
    post_excerpt: <paragraphs(1, true)>
    meta:
      _thumbnail_id: '@attachment*->ID'

  # POSTS
  post{1..30} (extends default):
    # 'meta' and 'meta_input' are basically the same, you can use one or both,
    # they will be merged, just don't provide the same keys in each definition
    meta:
      _thumbnail_id: '@attachment*->ID'
    meta_input:
      _extra_field: <paragraphs(1, true)>
    post_category: '3x @category*->term_id' # post_category only accepts IDs
    tax_input:
      post_tag: '5x @tag*->term_id'
      # post_tag: '5x <words(2, true)> # Or tags can be dynamically created

  # PAGES
  page{contact, privacy}:
    post_title: <current()>
    post_type: page

  # CUSTOM POST TYPE
  product{1..15}:
    post_type: product
    acf:
      # number field
      price: <numberBetween(10, 200)>
      # gallery field
      gallery: '3x @attachment*->ID'
      # oembed field
      video: https://www.youtube.com/watch?v=E90_aL870ao
      # link field
      link:
        url: https://www.youtube.com/watch?v=E90_aL870ao
        title: <words(2, true)>
        target: _blank
      # repeater field
      features:
        - label: <words(2, true)>
          value: <sentence()
        - label: <words(2, true)>
          value: <sentence()>
        - label: <words(2, true)>
          value: <sentence()>
      # layout field
      blocks:
        - acf_fc_layout: text_image
          title: <words(4, true)>
          content: <sentences(8, true)>
          image: '@attachment*->ID'
        - acf_fc_layout: image_image
          image_left: '@attachment*->ID'
          image_right: '@attachment*->ID'

#
# COMMENTS
#
Hellonico\Fixtures\Entity\Comment:
  comment{1..50}:
    comment_post_ID: '@post*->ID'
    user_id: '@user*->ID'
    comment_date: <dateTimeThisDecade()>
    comment_author: <username()>
    comment_author_email: <safeEmail()>
    comment_author_url: <url()>
    comment_content: <paragraphs(2, true)>
    comment_agent: <userAgent()>
    comment_author_IP: <ipv4()>
    comment_approved: 1
    comment_karma: <numberBetween(1, 100)>
    # 'meta' and 'comment_meta' are basically the same, you can use one or both,
    # they will be merged, just don't provide the same keys in each definition
    comment_meta:
      some_key: <sentence()>
    meta:
      another_key: <sentence()>

```

The example above will generate:

- 10 users
- 15 attachments
- 10 categories
- 40 tags
- 30 posts with a thumbnail, 3 categories and 5 tags
- 10 pages
- 15 custom post types named 'product'
- 50 comments associated with post and user

**IMPORTANT:** Make sure referenced IDs are placed **BEFORE** they are used.

Example: `Term` or `Attachment` objects **must** be placed before `Post` if you're referencing them in your fixtures.

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

You also can delete a single fixture type:

```
wp fixtures delete post
```

Valid types are `post`, `attachment`, `comment`, `term`, `user`.

### Add fake data to existing content

`wp-cli-fixtures` allows you to add/update content to existing entities by passing the ID as a constructor argument.

Add/update data to post ID 1:

```yaml
Hellonico\Fixtures\Entity\Post:
  my_post:
    __construct: [1] # Pass your post ID as the constructor argument
    post_title: '<sentence()>'
    post_content: '<paragraphs(5, true)>'
    post_excerpt: '<paragraphs(1, true)>'
```

Add/update data to 10 random existing posts:

```yaml
Hellonico\Fixtures\Entity\Post:
  post{1..10}:
    __construct: [<postId()>] # Use a custom formatters to return a random post ID as the constructor argument
    post_title: '<sentence()>'
    post_content: '<paragraphs(5, true)>'
    post_excerpt: '<paragraphs(1, true)>'
```

### Entities

#### Post

`Hellonico\Fixtures\Entity\Post` can take any parameters available in [`wp_insert_post`](https://developer.wordpress.org/reference/functions/wp_insert_post/#parameters) + `meta` and `acf` key.

*Note: `post_date_gmt` and `post_modified_gmt` have been disabled, there are set from `post_date` and `post_modified`.*

#### Attachment

`Hellonico\Fixtures\Entity\Attachment` can take any parameters available in [`wp_insert_attachment`](https://developer.wordpress.org/reference/functions/wp_insert_attachment/#parameters) + `meta`, `file` and `acf` custom keys.

*Note: `parent` must be passed with `post_parent` key.*

#### Term

`Hellonico\Fixtures\Entity\Term` can take any parameters available in [`wp_insert_term`](https://developer.wordpress.org/reference/functions/wp_insert_term/#parameters) + `meta` and `acf` custom keys.

*Note: `term` and `taxonomy` must be respectively passed with `name` and `taxonomy` key.*

#### User

`Hellonico\Fixtures\Entity\User` can take any parameters available in [`wp_insert_user`](https://developer.wordpress.org/reference/functions/wp_insert_user/#parameters) + `meta` and `acf` custom keys.

#### Comment

`Hellonico\Fixtures\Entity\Comment` can take any parameters available in [`wp_insert_comment`](https://developer.wordpress.org/reference/functions/wp_insert_comment/#parameters) + `meta` custom key.

`comment_date_gmt` has been disabled, it is set from `comment_date`.

### ACF Support

Each ACF supported entity (post, term, user) can have an `acf` key, which works just like `meta`.

```yaml
Hellonico\Fixtures\Entity\Post:
  post{1..30}:
    post_title: <words(3, true)>
    post_date: <dateTimeThisDecade()>
    acf:
      # number field
      number: <numberBetween(10, 200)>
      # repeater field
      features:
        - label: <words(2, true)>
          value: <sentence()
        - label: <words(2, true)>
          value: <sentence()>
        - label: <words(2, true)>
          value: <sentence()>
```

Be careful with duplicate field keys, if you have multiple field with the same key, prefer using ACF field key (`field_948d1qj5mn4d3`).

### Custom formatters

In addition to formatters provided by [fzaninotto/Faker](https://github.com/fzaninotto/Faker#formatters), you can use custom formatters below.

#### `postId($args)`

Returns a random existing post ID.
`$args` is optional and can take any arguments from [`get_posts`](https://developer.wordpress.org/reference/functions/get_posts/#parameters)

Example:

```
<postId(category=1,2,3)>
```

#### `attachmentId($args)`

Returns a random existing attachment ID.
`$args` is optional and can take any arguments from [`get_posts`](https://developer.wordpress.org/reference/functions/get_posts/#parameters)

Example:

```
<attachmentId(year=2016)>
```

#### `termId($args)`

Returns a random existing term ID.
`$args` is optional and can take any arguments from [`get_terms`](https://developer.wordpress.org/reference/functions/get_terms/#parameters)

Example:

```
<termId(taxonomy=post_tag)>
```

#### `userId($args)`

Returns a random existing user ID.
`$args` is optional and can take any arguments from [`get_users`](https://developer.wordpress.org/reference/functions/get_users/#parameters)

Example:

```
<userId(role=subscriber)>
```

#### `fileContent($file)`

Returns the content of a file.

Example:

```
<fileContent('path/to/file.html')>
```

#### `fileIn($src, $target, false)`

Wrapper around [file provider](https://github.com/fzaninotto/Faker#fakerproviderfile) because some Faker providers [conflicts with PHP native ](https://github.com/nelmio/alice/blob/master/doc/getting-started.md#symfony). Returns file path or file name in a directory (`$src` relative to `fixtures.yml`).

Default target is the WordPress `uploads`.

Example:

```
<fileIn('my/set/of/images')>
```

#### Tips

While playing with fixtures, the [database command](https://github.com/ernilambar/database-command) package can be useful to reset database faster than `wp fixtures delete` and start over.

## Contribute

This package follows PSR2 coding standards and is tested with Behat. Execute `composer run tests` to ensure your PR passes.

