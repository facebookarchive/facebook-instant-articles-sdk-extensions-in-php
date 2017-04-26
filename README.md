# Facebook Instant Articles PHP SDK Extensions #

[![Build Status](https://travis-ci.org/facebook/facebook-instant-articles-sdk-extensions-in-php.svg?branch=master)](https://travis-ci.org/facebook/facebook-instant-articles-sdk-extensions-in-php)
[![Latest Stable Version](https://poser.pugx.org/facebook/facebook-instant-articles-sdk-extensions-in-php/v/stable)](https://packagist.org/packages/facebook/facebook-instant-articles-sdk-extensions-in-php)

The Facebook Instant Articles SDK Extensions in PHP provides a native PHP interface for converting valid Instant Articles into AMP and Apple News(Coming soon). This gives developers the ability to have AMP and Apple News right after getting his own Instant Article markup format.

The Extension package consists of:
- **Dependencies**: It relies solely on the [Instant Articles SDK](https://github.com/Facebook/facebook-instant-articles-sdk-php) and its dependencies to get the Instant Article markup format available into the Elements object tree structure.
- **AMP**: The AMP transformation was based on the current implementation and definition from [AMP project](https://www.ampproject.org/).
- **Apple News**: The Apple News transformation was based on the current implementation and definition from [Apple News Project](https://developer.apple.com/news-publisher/).

## Quick Start
You can find examples on how to use the different components of this SDK to integrate with your CMS in the [Getting Started section](https://developers.facebook.com/docs/instant-articles/other-formats/#getting-started) of the documentation.

## Installation

The Facebook Instant Articles PHP SDK can be installed with [Composer](https://getcomposer.org/). Run this command:

```sh
composer require facebook/facebook-instant-articles-sdk-extensions-php
```

## Testing and Developing ##

[Composer](https://getcomposer.org/) is a prerequisite for testing and developing. [Install composer globally](https://getcomposer.org/doc/00-intro.md#globally), then install project dependencies by running this command in the project root directory:
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

## Troubleshooting

If you are encountering problems, the following tips may help in troubleshooting issues:

- Set the `threshold` in the [configuration of the Logger](https://logging.apache.org/log4php/docs/configuration.html#PHP) to `DEBUG` to expose more details about the items processed by the Transformer.

## Contributing

For us to accept contributions you will have to first have signed the [Contributor License Agreement](https://code.facebook.com/cla). Please see [CONTRIBUTING](https://github.com/facebook/facebook-instant-articles-sdk-extensions-php/blob/master/CONTRIBUTING.md) for details.

## License

Please see the [license file](https://github.com/facebook/facebook-instant-articles-sdk-extensions-php/blob/master/LICENSE) for more information.
