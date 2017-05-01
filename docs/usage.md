# Solution10\Pipeline Documentation

- [Basics](#basics)
- [first() and last()](#first-and-last)
- [before() and after()](#before-and-after)
- [Multiple Parameters](#multiple-parameters)
- [Pipelines of Pipelines](#pipelines-of-pipelines)
- [Run Types](#run-types)
    - [run()](#run)
    - [runOnly()](#runonly)
    - [runWithout()](#runwithout)
    - [runFrom()](#runfrom)
    - [runUntil()](#rununtil)
- [Error Handling](#error-handling)

## Basics

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

## first() and last()

You can push steps to either the front or back of the queue using `first()` and `last()` (`last()` is just an alias
of `step()` since they both append to the end of the step list).

> **Note**: these functions DO NOT guarantee that the step will run either first or last! They merely push the step
> into that position in the queue at the time you do it. Subsequent `first()`, `step()` and `last()` calls can
> mess with the order. It's generally better to use `before()` and `after()` to ensure position.

```php
<?php

use Solution10\Pipeline\Pipeline;

$w = (new Pipeline())
    ->step('add-one', function ($input) {
        return $input + 1;
    })
    ->step('stringify', function ($input) {
        return 'Result: '.$input;
    })
    ->first('double', function ($input) {
        return $input * 2;
    })
;

$result = $w->run(2);
// $result is "Result: 5", 'double' was applied first.
```

## before() and after()

You can insert steps before and after elements already in the pipeline using `before()` and `after()` respectively.

```php
<?php

use Solution10\Pipeline\Pipeline;

// Imagine a pre-determined Pipeline:
$w = (new Pipeline())
    ->step('double', function ($input) {
        return $input * 2;
    })
    ->step('stringify', function ($input) {
        return 'Result: '.$input;
    })
;

// We want to insert an add-one step before stringify:
$w->before('stringify', 'add-one', function ($input) {
    return $input + 1;
});

$result = $w->run(2);
// $result is "Result: 5"
```

The above could also be implemented via an `after()`:

```php
<?php

use Solution10\Pipeline\Pipeline;

// Imagine a pre-determined Pipeline:
$w = (new Pipeline())
    ->step('double', function ($input) {
        return $input * 2;
    })
    ->step('stringify', function ($input) {
        return 'Result: '.$input;
    })
;

// We want to insert an add-one step after double:
$w->after('double', 'add-one', function ($input) {
    return $input + 1;
});

$result = $w->run(2);
// $result is "Result: 5"
```

> **Note**: `before()` and `after()` DO NOT guarantee that that step will be run *immediately* before or after the
> target step; subsequent calls to `before()` and `after()` can change the order of steps, they simply guarantee the
> relative positions are correct.

## Multiple Parameters

It's possible to run a Pipeline with multiple parameters. Only the first will be modified as the chain executes,
the others will be passed down as-is:

```php
<?php

use Solution10\Pipeline\Pipeline;

$w = (new Pipeline())
    ->step('multiply', function ($input, $factor) {
        return $input * $factor;
    })
    ->step('add', function ($input, $factor) {
        return $input + $factor;
    })
;

$result = $w->run(10, 2);
// $result is 22 ('multiply' = 10 * 2, then 'add' 20 + 2)
```

In the above example, `$factor` is passed in allowing each step to use it, however the `$input` is the running output
value of the pipeline.

## Pipelines of Pipelines

You can add other Pipeline objects as steps of a Pipeline.

```php
<?php

use Solution10\Pipeline\Pipeline;

$subPipe = (new Pipeline())
    ->step('double', function ($input) {
        return $input * 2;
    });

$mainPipe = (new Pipeline())
    ->step('sub', $subPipe)
    ->step('stringify', function ($input) {
        return 'Result: '.$input;
    });

$result = $mainPipe->run(2);
// $result is "Result: 4"
```

## Run Types

There are several ways of executing a Pipeline; you can run the whole thing, exclude certain steps, or start from a
point within the pipeline.

### `run()`

Performs all steps within the pipeline, in order.

### `runOnly()`

Performs only the given steps, in the correct order as defined by the Pipeline, skipping everything else:

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

$result = $w->runOnly(['stringify', 'double'], 2);
// $result is "Result: 4", the 'add-one' step was ignored and the steps run
// in the correct order.
```

### `runWithout()`

The inverse of `runOnly()`, this will run the entire chain, excluding the steps you give it:

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

$result = $w->runWithout(['stringify', 'double'], 2);
// $result is 3, only the 'add-one' step executed.
```

### `runFrom()`

Allows you to pick a point within the Pipeline to start execution from:

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

$result = $w->runFrom('add-one', 2);
// $result is "Result: 3", the 'double' step was not run, but everything else was.
```

### `runUntil()`

The inverse of `runFrom()`, allows you to run a Pipeline to a given point and stop. This will **include** the step
you provide in the parameters.

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

$result = $w->runUntil('add-one', 2);
// $result is 5, the 'double' and 'add-one' steps ran, but not stringify.
```

## Error Handling

None! Any exceptions that are thrown during the Pipeline will bubble up into your code, so handle as appropriate.
