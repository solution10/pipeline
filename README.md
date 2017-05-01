# Solution10\Pipeline

A simple pipeline library that allows you to string together chains of tasks to perform in a given order.

This library, unlike others, allows you to name and insert steps in different orders to when they're defined.

[![Build Status](https://travis-ci.org/Solution10/pipeline.svg?branch=master)](https://travis-ci.org/Solution10/pipeline)
[![Latest Stable Version](https://poser.pugx.org/Solution10/pipeline/v/stable.svg)](https://packagist.org/packages/Solution10/pipeline)
[![Total Downloads](https://poser.pugx.org/Solution10/pipeline/downloads.svg)](https://packagist.org/packages/Solution10/pipeline)
[![License](https://poser.pugx.org/Solution10/pipeline/license.svg)](https://packagist.org/packages/Solution10/pipeline)

- [Usage](#usage)
- [PHP Requirements](#php-requirements)
- [Author](#author)
- [License](#license)

## Usage

The most simple pipeline is just a sequence of steps where the output is handed to the next step and eventually
returned out of the bottom:

```php
<?php

use Solution10\Pipeline\Pipeline;

$w = (new Pipeline())
    ->step('double', function ($input) {
        return $input * 2;
    })
    ->step('add-one', function ($input) {
        return $input + 1;
    })
    ->step('stringify', function ($input) {
        return 'Result: '.$input;
    })
;

$result = $w->run(2);
// $result is "Result: 5"
```

Each step is given a name as the first parameter and a `callable` as it's second.

`Pipeline::run()` is then called with the input to generate the output.

There are various types of `run()` you can do, as well as variety of ways of defining steps, see the
[Userguide](./docs/usage.md) for more details.

## PHP Requirements

- PHP >= 5.6 || HHVM >= 3.3

## Author

Alex Gisby: [GitHub](http://github.com/alexgisby), [Twitter](http://twitter.com/alexgisby)

## License

[MIT](http://github.com/Solution10/pipeline/tree/master/LICENSE.md)
