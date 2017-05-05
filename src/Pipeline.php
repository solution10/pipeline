<?php

namespace Solution10\Pipeline;

/**
 * Class Pipeline
 *
 * Implements a pipeline.
 *
 * @package     Solution10\Pipeline
 * @author      Alex Gisby<alex@solution10.com>
 * @license     MIT
 */
class Pipeline
{
    /**
     * @var     callable[]
     */
    protected $callbacks = [];

    /**
     * @var     string[]
     */
    protected $steps = [];

    /**
     * Adds a step into the pipeline.
     *
     * @param   string      $name
     * @param   callable    $step
     * @return  $this
     */
    public function step($name, callable $step)
    {
        if (!array_key_exists($name, $this->callbacks)) {
            $this->steps[] = $name;
        }
        $this->callbacks[$name] = $step;
        return $this;
    }

    /**
     * Adds a step into the pipeline before the given step.
     *
     * @param   string      $before     Step to insert before
     * @param   string      $name
     * @param   callable    $step
     * @return  $this
     * @throws  \InvalidArgumentException
     */
    public function before($before, $name, callable $step)
    {
        if (count($this->steps) != 0 && !in_array($before, $this->steps)) {
            throw new \InvalidArgumentException(
                'Cannot place "'.$name.'" before "'.$before.'" since "'.$before.'" is not yet defined.'
            );
        }

        $pivot = array_search($before, $this->steps);
        array_splice($this->steps, $pivot, 0, $name);
        $this->callbacks[$name] = $step;
        return $this;
    }

    /**
     * Adds a step into the pipeline after the given step.
     *
     * @param   string      $after      Step to insert after
     * @param   string      $name
     * @param   callable    $step
     * @return  $this
     * @throws  \InvalidArgumentException
     */
    public function after($after, $name, callable $step)
    {
        if (count($this->steps) != 0 && !in_array($after, $this->steps)) {
            throw new \InvalidArgumentException(
                'Cannot place "'.$name.'" after "'.$after.'" since "'.$after.'" is not yet defined.'
            );
        }

        $pivot = array_search($after, $this->steps);
        array_splice($this->steps, $pivot + 1, 0, $name);
        $this->callbacks[$name] = $step;
        return $this;
    }

    /**
     * Adds a step into the pipeline at the beginning of the chain.
     *
     * @param   string      $name
     * @param   callable    $step
     * @return  $this
     */
    public function first($name, callable $step)
    {
        if (count($this->steps) == 0 || $this->steps[0] !== $name) {
            array_unshift($this->steps, $name);
        }
        $this->callbacks[$name] = $step;
        return $this;
    }

    /**
     * Adds a step into the pipeline at the end of the chain.
     * Alias for step()
     *
     * @param   string      $name
     * @param   callable    $step
     * @return  $this
     * @see     Pipeline::step()
     */
    public function last($name, callable $step)
    {
        return $this->step($name, $step);
    }

    /**
     * Removes a step from a pipeline entirely. Will fail silently if the
     * step is not present in the pipeline.
     *
     * @param   string  $name
     * @return  $this
     */
    public function drop($name)
    {
        $pivot = array_search($name, $this->steps);
        if ($pivot !== false) {
            array_splice($this->steps, $pivot, 1);
        }
        return $this;
    }

    /**
     * Allows the Pipeline to be passed as a callable.
     *
     * @param   mixed   $input
     * @param   array   ...$args
     * @return  mixed
     */
    public function __invoke($input, ...$args)
    {
        return $this->run($input, ...$args);
    }

    /**
     * Runs through all steps of the Pipeline, returning the result at the end.
     *
     * @param   mixed   $input
     * @param   array   ...$args
     * @return  mixed
     */
    public function run($input, ...$args)
    {
        $output = $input;
        foreach ($this->steps as $stepName) {
            $output = call_user_func_array(
                $this->callbacks[$stepName],
                array_merge([$output], $args)
            );
        }
        return $output;
    }

    /**
     * Runs only the given steps of the Pipeline, returning the result at the end.
     *
     * @param   array   $steps
     * @param   mixed   $input
     * @param   array   ...$args
     * @return  mixed
     */
    public function runOnly(array $steps, $input, ...$args)
    {
        $output = $input;
        foreach ($this->steps as $stepName) {
            if (in_array($stepName, $steps)) {
                $output = call_user_func_array(
                    $this->callbacks[$stepName],
                    array_merge([$output], $args)
                );
            }
        }
        return $output;
    }

    /**
     * Runs through all steps of the Pipeline, excluding the steps given, returning the result at the end.
     *
     * @param   array   $stepsIgnored
     * @param   mixed   $input
     * @param   array   ...$args
     * @return  mixed
     */
    public function runWithout(array $stepsIgnored, $input, ...$args)
    {
        $output = $input;
        foreach ($this->steps as $stepName) {
            if (!in_array($stepName, $stepsIgnored)) {
                $output = call_user_func_array(
                    $this->callbacks[$stepName],
                    array_merge([$output], $args)
                );
            }
        }
        return $output;
    }

    /**
     * Runs through all steps of the Pipeline including and after the given step, returning the result at the end.
     *
     * @param   string  $step
     * @param   mixed   $input
     * @param   array   ...$args
     * @return  mixed
     */
    public function runFrom($step, $input, ...$args)
    {
        if (!in_array($step, $this->steps)) {
            throw new \InvalidArgumentException(
                'Cannot run from "'.$step.'" as step is undefined.'
            );
        }
        $output = $input;
        $pivot = array_search($step, $this->steps);
        for ($i = $pivot; $i < count($this->steps); $i ++) {
            $output = call_user_func_array(
                $this->callbacks[$this->steps[$i]],
                array_merge([$output], $args)
            );
        }
        return $output;
    }

    /**
     * Runs through all steps of the Pipeline up to and including the given step, returning the result at the end.
     *
     * @param   string  $step
     * @param   mixed   $input
     * @param   array   ...$args
     * @return  mixed
     */
    public function runUntil($step, $input, ...$args)
    {
        if (!in_array($step, $this->steps)) {
            throw new \InvalidArgumentException(
                'Cannot run until "'.$step.'" as step is undefined.'
            );
        }

        $output = $input;
        foreach ($this->steps as $stepName) {
            $output = call_user_func_array(
                $this->callbacks[$stepName],
                array_merge([$output], $args)
            );
            if ($stepName === $step) {
                break;
            }
        }

        return $output;
    }
}
