# Facebook Instant Articles SDK Extensions in PHP #

[![Build Status](https://travis-ci.org/facebook/facebook-instant-articles-sdk-extensions-in-php.svg?branch=master)](https://travis-ci.org/facebook/facebook-instant-articles-sdk-extensions-in-php)
[![Latest Stable Version](https://poser.pugx.org/facebook/facebook-instant-articles-sdk-extensions-in-php/v/stable)](https://packagist.org/packages/facebook/facebook-instant-articles-sdk-extensions-in-php)

The Facebook Instant Articles SDK Extensions in PHP provides a native PHP interface for converting valid Instant Articles into AMP. This gives developers the ability to have AMP content right after getting his own Instant Article markup format ready.

The Extension package consists of:
- **Environment**: PHP >= 5.4 or HHVM
- **Dependencies**: It relies solely on the [Instant Articles SDK](https://github.com/Facebook/facebook-instant-articles-sdk-php) and its dependencies to get the Instant Article markup format available into the Elements object tree structure. It also depends on [Composer](https://getcomposer.org/) dependency manager.
- **AMP**: The AMP transformation was based on the current implementation and definition from [AMP project](https://www.ampproject.org/).

## Quick Start

```sh
$ composer require facebook/facebook-instant-articles-sdk-extensions-in-php
```

After the installation, you can include the auto loader script in your source with:

```PHP
require_once('vendor/autoload.php');
```

Also be sure to check [the quick start example](https://github.com/facebook/facebook-instant-articles-sdk-extensions-in-php/blob/master/examples/example-quick-start.php).

## Official Documentation

You can find examples on how to use the different components of this SDK to integrate with your CMS in the [Quick Start Guide](https://developers.facebook.com/docs/instant-articles/other-formats/#quickstart) of the [documentation](https://developers.facebook.com/docs/instant-articles/other-formats/).

## Contributing

Clone the repository
```sh
$ git clone https://github.com/facebook/facebook-instant-articles-sdk-extensions-in-php.git
```

[Composer](https://getcomposer.org/) is a prerequisite for testing and developing. [Install composer globally](https://getcomposer.org/doc/00-intro.md#globally), then install project dependencies by running this command in the project's root directory:

```sh
$ composer install
```

To run the tests:

```sh
$ composer test
```

To fix and check for coding style issues:

```sh
$ composer cs
```

Extra lazy? Run

```sh
$ composer all
```

to fix and check for coding style issues, and run the tests.

If you change structure, paths, namespaces, etc., make sure you run the [autoload generator](https://getcomposer.org/doc/03-cli.md#dump-autoload):
```sh
$ composer dump-autoload
```

___
**For us to accept contributions you will have to first sign the [Contributor License Agreement](https://code.facebook.com/cla). Please see [CONTRIBUTING](https://github.com/facebook/facebook-instant-articles-sdk-extensions-in-php/blob/master/CONTRIBUTING.md) for details.**
___

## Troubleshooting

If you are encountering problems, the following tips may help in troubleshooting issues:

- Set the `threshold` in the [configuration of the Logger](https://logging.apache.org/log4php/docs/configuration.html#PHP) to `DEBUG` to expose more details about the items processed by the Transformer, conversely, to make it more quiet set it to `INFO`, check [this script](https://github.com/facebook/facebook-instant-articles-sdk-extensions-in-php/blob/master/examples/quiet_logger.php) for details.
- If your images are having dimension/aspect ratio problems, please check the [the quick start example](https://github.com/facebook/facebook-instant-articles-sdk-extensions-in-php/blob/master/examples/example-quick-start.php) for more information.
- At the moment, we have no way to determine a video's width and height, you need to explicitly pass that information via properties, see how to do it  [here](https://github.com/facebook/facebook-instant-articles-sdk-extensions-in-php/blob/master/examples/example-quick-start.php).

### Filing an issue

- Be sure you've looked for the similar issue into the [Issues list](https://github.com/facebook/facebook-instant-articles-sdk-extensions-in-php/issues)

- Inform the Canonical URL of your Instant Article being converted

- Inform the [exported JSON from the style editor](https://developers.facebook.com/docs/instant-articles/other-formats#style)

Issue template:
```
# Issue Data
Instant Article Canonical URL: `http://yourdomain.com/path/article.html`
Exported Style JSON: `{...}`
Page ID: `12345`

# Problem noticed
1. Image XYZ missing

# Expected result
1. The Image XYZ should be present.
```

## License

Please see the [license file](https://github.com/facebook/facebook-instant-articles-sdk-extensions-in-php/blob/master/LICENSE) for more information.
