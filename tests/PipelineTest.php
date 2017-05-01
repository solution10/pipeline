<?php

namespace Solution10\Pipeline\Tests;

use PHPUnit\Framework\TestCase;
use Solution10\Pipeline\Pipeline;

class PipelineTest extends TestCase
{
    public function testSingleStep()
    {
        $w = new Pipeline();
        $this->assertSame($w, $w->step('two', function ($number) {
            return $number * 2;
        }));

        $result = $w->run(10);
        $this->assertEquals(20, $result);
    }

    public function testMultiStep()
    {
        $w = new Pipeline();
        $w
            ->step('first', function ($string) {
                return $string.'First Step';
            })
            ->step('second', function ($string) {
                return $string.' Second Step';
            })
        ;

        $result = $w->run('');
        $this->assertEquals('First Step Second Step', $result);
    }

    public function testInitialStep()
    {
        $w = new Pipeline();
        $this->assertSame($w, $w->first('initial', function ($string) {
            return $string.'Initial Step';
        }));

        $w->step('first', function ($string) {
            return $string.' First Step';
        });

        $result = $w->run('');
        $this->assertEquals('Initial Step First Step', $result);
    }

    public function testFinalStep()
    {
        $w = new Pipeline();
        $w->step('first', function ($string) {
            return $string.'First Step';
        });

        $this->assertSame($w, $w->last('last', function ($string) {
            return $string.' Final Step';
        }));

        $result = $w->run('');
        $this->assertEquals('First Step Final Step', $result);
    }

    public function testFullChain()
    {
        $w = new Pipeline();
        $w
            ->first('initial', function ($string) {
                return $string.' Initial';
            })
            ->step('step', function ($string) {
                return $string.' Step';
            })
            ->last('final', function ($string) {
                return $string.' Final';
            });

        $result = $w->run('Result:');
        $this->assertEquals('Result: Initial Step Final', $result);
    }

    public function testFullChainWrongOrder()
    {
        $w = new Pipeline();
        $w
            ->step('step', function ($string) {
                return $string.' Step';
            })
            ->last('final', function ($string) {
                return $string.' Final';
            })
            ->first('initial', function ($string) {
                return $string.' Initial';
            })
        ;

        $result = $w->run('Result:');
        $this->assertEquals('Result: Initial Step Final', $result);
    }

    public function testMultiArgument()
    {
        $w = (new Pipeline())
            ->first('first', function ($string, $colour) {
                return $string.' Initial ('.$colour.')';
            })
            ->step('second', function ($string, $colour) {
                return $string.' Step ('.$colour.')';
            })
            ->last('final', function ($string, $colour) {
                return $string.' Final ('.$colour.')';
            })
        ;
        $result = $w->run('Result:', 'green');
        $this->assertEquals('Result: Initial (green) Step (green) Final (green)', $result);
    }

    /* ------------------ before and after tests -------------------- */

    public function testBeforeSimple()
    {
        $w = (new Pipeline())
            ->step('double', function ($input) {
                return $input * 2;
            });

        $this->assertSame($w, $w->before('double', 'add-one', function ($input) {
            return $input + 1;
        }));

        $this->assertEquals(6, $w->run(2));
    }

    public function testBeforeEmptySteps()
    {
        $w = (new Pipeline())
            ->before('double', 'add-one', function ($input) {
                return $input + 1;
            });

        $this->assertEquals(3, $w->run(2));
    }

    /**
     * @expectedException           \InvalidArgumentException
     * @expectedExceptionMessage    Cannot place "add-one" before "unknown" since "unknown" is not yet defined.
     */
    public function testBeforeInvalid()
    {
        (new Pipeline())
            ->step('double', function ($input) {
                return $input * 2;
            })
            ->before('unknown', 'add-one', function ($input) {
                return $input + 1;
            });
    }

    public function testAfterSimple()
    {
        $w = (new Pipeline())
            ->step('double', function ($input) {
                return $input * 2;
            })
            ->step('stringify', function ($input) {
                return 'Result: '.$input;
            });

        $this->assertSame($w, $w->after('double', 'add-one', function ($input) {
            return $input + 1;
        }));

        $this->assertEquals('Result: 5', $w->run(2));
    }

    public function testAfterEnd()
    {
        $w = (new Pipeline())
            ->step('double', function ($input) {
                return $input * 2;
            })
            ->after('double', 'add-one', function ($input) {
                return $input + 1;
            });

        $this->assertEquals(5, $w->run(2));
    }

    public function testAfterEmptySteps()
    {
        $w = (new Pipeline())
            ->after('unknown', 'add-one', function ($input) {
                return $input + 1;
            });

        $this->assertEquals(3, $w->run(2));
    }

    /**
     * @expectedException           \InvalidArgumentException
     * @expectedExceptionMessage    Cannot place "add-one" after "unknown" since "unknown" is not yet defined.
     */
    public function testAfterInvalid()
    {
        (new Pipeline())
            ->step('double', function ($input) {
                return $input * 2;
            })
            ->after('unknown', 'add-one', function ($input) {
                return $input + 1;
            });
    }

    /* -------------- Pipeline of Pipelines ------------------ */

    public function testPipelineOfPipeline()
    {
        $subPipe = (new Pipeline())
            ->step('double', function ($input) {
                return $input * 2;
            });

        $mainPipe = (new Pipeline())
            ->step('sub', $subPipe);

        $this->assertEquals(4, $mainPipe->run(2));
    }

    public function testPipelineMixed()
    {
        $subPipe = (new Pipeline())
            ->step('double', function ($input) {
                return $input * 2;
            });

        $mainPipe = (new Pipeline())
            ->step('sub', $subPipe)
            ->step('add-one', function ($input) {
                return $input + 1;
            })
        ;

        $this->assertEquals(5, $mainPipe->run(2));
    }

    public function testPipelineMixedRepeats()
    {
        $subPipe = (new Pipeline())
            ->step('double', function ($input) {
                return $input * 2;
            });

        $mainPipe = (new Pipeline())
            ->step('sub', $subPipe)
            ->step('add-one', function ($input) {
                return $input + 1;
            })
            ->step('sub-again', $subPipe)
        ;

        $this->assertEquals(10, $mainPipe->run(2));
    }

    /* -------------- Partial pipeline runs ------------------ */

    public function testRunOnly()
    {
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

        $this->assertEquals(4, $w->runOnly(['double'], 2));
    }

    public function testRunOnlyRespectsOrder()
    {
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

        $this->assertEquals('Result: 4', $w->runOnly(['stringify', 'double'], 2));
    }

    public function testRunWithout()
    {
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
        $this->assertEquals('Result: 4', $w->runWithout(['add-one'], 2));
    }

    public function testRunFrom()
    {
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
        $this->assertEquals('Result: 3', $w->runFrom('add-one', 2));
    }

    /**
     * @expectedException           \InvalidArgumentException
     * @expectedExceptionMessage    Cannot run from "unknown" as step is undefined.
     */
    public function testRunFromInvalidStep()
    {
        (new Pipeline())
            ->runFrom('unknown', 2);
    }

    public function testRunUntil()
    {
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
        $this->assertEquals(5, $w->runUntil('add-one', 2));
    }

    /**
     * @expectedException           \InvalidArgumentException
     * @expectedExceptionMessage    Cannot run until "unknown" as step is undefined.
     */
    public function testRunUntilInvalidStep()
    {
        (new Pipeline())
            ->runUntil('unknown', 2);
    }
}
