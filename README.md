# Tulip API client

[![Latest version on Packagist][ico-version]][link-version]
[![Software License][ico-license]][link-license]
[![Build Status][ico-build]][link-build]
[![Coverage Status][ico-coverage]][link-coverage]

PHP client library for communicating with the Tulip API.

## Installation using Composer

Run the following command to add the package to the composer.json of your project:

``` bash
$ composer require connectholland/tulip-api-client
```

#### Versioning
This library uses [Semantic Versioning 2](http://semver.org/) for new versions.

## Usage

Below are some common usage examples for the API client. For more information, please see the Tulip API documentation that is supplied when you want to integrate with the Tulip CRM software.

```php
<?php

$client = new ConnectHolland\TulipAPI\Client('https://api.example.com', '1.1');

// Calls https://api.example.com/api/1.1/contact/detail with id=1
$response = $client->callService('contact', 'detail', array('id' => 1));

```

#### Inserting an object

```php
<?php

$client = new ConnectHolland\TulipAPI\Client('https://api.example.com', '1.1');

// Calls https://api.example.com/api/1.1/contact/save
$response = $client->callService('contact', 'save', array(
    'firstname' => 'John',
    'lastname' => 'Doe',
    'email' => 'johndoe@gmail.com',
));

```

#### Updating an object

```php
<?php

$client = new ConnectHolland\TulipAPI\Client('https://api.example.com', '1.1');

// Calls https://api.example.com/api/1.1/contact/save
$response = $client->callService('contact', 'save', array(
    'id' => 1,
    'firstname' => 'Jane',
    'lastname' => 'Doe',
    'email' => 'janedoe@gmail.com',
));

```

#### Uploading a file when inserting / updating an object

```php
<?php

$client = new ConnectHolland\TulipAPI\Client('https://api.example.com', '1.1');

// Calls https://api.example.com/api/1.1/contact/save
$response = $client->callService('contact', 'save',
    array(
        'id' => 1,
        'firstname' => 'Jane',
        'lastname' => 'Doe',
        'email' => 'janedoe@gmail.com',
    ),
    array(
        'photo' => fopen('/path/to/files/photo.jpg', 'r'),
    )
);

```

## Credits

- [Niels Nijens][link-author]

Also see the list of [contributors][link-contributors] who participated in this project.

## License

This library is licensed under the MIT License. Please see the [LICENSE file](LICENSE.md) for details.

[ico-version]: https://img.shields.io/packagist/v/connectholland/tulip-api-client.svg
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg
[ico-build]: https://travis-ci.org/ConnectHolland/tulip-api-client.svg?branch=master
[ico-coverage]: https://coveralls.io/repos/ConnectHolland/tulip-api-client/badge.svg?branch=master

[link-version]: https://packagist.org/packages/connectholland/tulip-api-client
[link-license]: LICENSE.md
[link-build]: https://travis-ci.org/ConnectHolland/tulip-api-client
[link-coverage]: https://coveralls.io/r/ConnectHolland/tulip-api-client?branch=master
[link-author]: https://github.com/niels-nijens
[link-contributors]: https://github.com/ConnectHolland/tulip-api-client/contributors
