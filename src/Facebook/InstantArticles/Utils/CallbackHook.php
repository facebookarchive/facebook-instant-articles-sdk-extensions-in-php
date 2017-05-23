<?php
/**
 * Copyright (c) 2017-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */
namespace Facebook\InstantArticles\Utils;

/**
 * CallbackHook class. This is the helper class to get the hooks prioritized, called, removed and mantained.
 */
class CallbackHook implements \Iterator, \ArrayAccess
{

    /**
     * @var array Callback functions/instance->methods
     */
    public $callbacks = array();

    /**
     * @var array The priority keys of actively running iterations of a hook.
     */
    private $iterations = array();

    /**
     * @var array The current priority of actively running iterations of a hook.
     */
    private $currentPriority = array();

    /**
     * @var int Number of levels this hook can be recursively called.
     */
    private $nestingLevel = 0;

    /**
     * Hooks a function or method to a specific filter action.
     *
     * @param string   $tag             The name of the filter to hook the $functionToAdd callback to.
     * @param callable $functionToAdd   The callback to be run when the filter is applied.
     * @param int      $priority        The order in which the functions associated with a
     *                                  particular action are executed. Lower numbers correspond with
     *                                  earlier execution, and functions with the same priority are executed
     *                                  in the order in which they were added to the action.
     * @param int      $acceptedArgs    The number of arguments the function accepts.
     */
    public function addFilter($idx, $tag, $functionToAdd, $priority, $acceptedArgs)
    {
        $priorityExisted = isset($this->callbacks[$priority]);

        $this->callbacks[$priority][$idx] = array(
            'function' => $functionToAdd,
            'accepted_args' => $acceptedArgs
        );

        // if we're adding a new priority to the list, put them back in sorted order
        if (!$priorityExisted && count($this->callbacks) > 1) {
            ksort($this->callbacks, SORT_NUMERIC);
        }

        if ($this->nestingLevel > 0) {
            $this->resortPerPriority($priority, $priorityExisted);
        }
    }

    /**
     * Handles reseting callback priority keys mid-iteration.
     *
     * @param bool|int $newPriority      Optional. The priority of the new filter being added. Default false,
     *                                   for no priority being added.
     * @param bool     $priorityExisted  Optional. Flag for whether the priority already existed before the new
     *                                   filter was added. Default false.
     */
    private function resortPerPriority($newPriority = false, $priorityExisted = false)
    {
        $newPriorities = array_keys($this->callbacks);

        // If there are no remaining hooks, clear out all running iterations.
        if (! $newPriorities) {
            foreach ($this->iterations as $index => $iteration) {
                $this->iterations[$index] = $newPriorities;
            }
            return;
        }

        $min = min($newPriorities);
        foreach ($this->iterations as $index => &$iteration) {
            $current = current($iteration);
            // If we're already at the end of this iteration, just leave the array pointer where it is.
            if (false === $current) {
                continue;
            }

            $iteration = $newPriorities;

            if ($current < $min) {
                array_unshift($iteration, $current);
                continue;
            }

            while (current($iteration) < $current) {
                if (false === next($iteration)) {
                    break;
                }
            }

            // If we have a new priority that didn't exist, but ::applyFilters() thinks it's the current priority...
            if ($newPriority === $this->currentPriority[$index] && ! $priorityExisted) {
                /*
                 * ... and the new priority is the same as what $this->iterations thinks is the previous
                 * priority, we need to move back to it.
                 */

                if (false === current($iteration)) {
                    // If we've already moved off the end of the array, go back to the last element.
                    $prev = end($iteration);
                } else {
                    // Otherwise, just go back to the previous element.
                    $prev = prev($iteration);
                }
                if (false === $prev) {
                    // Start of the array. Reset, and go about our day.
                    reset($iteration);
                } elseif ($newPriority !== $prev) {
                    // Previous wasn't the same. Move forward again.
                    next($iteration);
                }
            }
        }
        unset($iteration);
    }

    /**
     * Unhooks a function or method from a specific filter action.
     *
     * @param string   $tag                The filter hook to which the function to be removed is hooked. Used
     *                                     for building the callback ID when SPL is not available.
     * @param int      $priority           The exact priority used when adding the original filter callback.
     * @return bool Whether the callback existed before it was removed.
     */
    public function removeFilter($idx, $tag, $priority)
    {
        $exists = isset($this->callbacks[$priority][$idx]);
        if ($exists) {
            unset($this->callbacks[$priority][$idx]);
            if (! $this->callbacks[$priority]) {
                unset($this->callbacks[$priority]);
                if ($this->nestingLevel > 0) {
                    $this->resortPerPriority();
                }
            }
        }
        return $exists;
    }

    /**
     * Checks if a specific action has been registered for this hook.
     *
     * @param callable|bool $functionToCheck   Optional. The callback to check for. Default false.
     * @param string        $tag               Optional. The name of the filter hook. Used for building
     *                                         the callback ID when SPL is not available. Default empty.
     * @return bool|int The priority of that hook is returned, or false if the function is not attached.
     */
    public function hasFilter($idx, $tag = '', $functionToCheck = false)
    {
        if (false === $functionToCheck) {
            return $this->hasFilters();
        }

        if (! $idx) {
            return false;
        }

        foreach ($this->callbacks as $priority => $callbacks) {
            if (isset($callbacks[$idx])) {
                return $priority;
            }
        }

        return false;
    }

    /**
     * Checks if any callbacks have been registered for this hook.
     *
     * @return bool True if callbacks have been registered for the current hook, otherwise false.
     */
    public function hasFilters()
    {
        foreach ($this->callbacks as $callbacks) {
            if ($callbacks) {
                return true;
            }
        }
        return false;
    }

    /**
     * Removes all callbacks from the current filter.
     *
     * @param int|bool $priority Optional. The priority number to remove. Default false.
     */
    public function removeAllFilters($priority = false)
    {
        if (! $this->callbacks) {
            return;
        }

        if (false === $priority) {
            $this->callbacks = array();
        } else if (isset($this->callbacks[$priority])) {
            unset($this->callbacks[$priority]);
        }

        if ($this->nestingLevel > 0) {
            $this->resortPerPriority();
        }
    }

    /**
     * Calls the callback functions added to a filter hook.
     *
     * @param mixed $value The value to filter.
     * @param array $args  Arguments to pass to callbacks.
     * @return mixed The filtered value after all hooked functions are applied to it.
     */
    public function applyFilters($value, $args)
    {
        if (!$this->callbacks) {
            return $value;
        }

        $nestingLevel = $this->nestingLevel++;

        $this->iterations[$nestingLevel] = array_keys($this->callbacks);
        $num_args = count($args);

        do {
            $this->currentPriority[$nestingLevel] = $priority = current($this->iterations[$nestingLevel]);

            foreach ($this->callbacks[$priority] as $callbackPriority) {
                $args[0] = $value;

                // Avoid the array_slice if possible.
                if ($callbackPriority['accepted_args'] == 0) {
                    $value = call_user_func_array($callbackPriority['function'], array());
                } elseif ($callbackPriority['accepted_args'] >= $num_args) {
                    $value = call_user_func_array($callbackPriority['function'], $args);
                } else {
                    $value = call_user_func_array($callbackPriority['function'], array_slice($args, 0, (int)$callbackPriority['accepted_args']));
                }
            }
        } while (false !== next($this->iterations[$nestingLevel]));

        unset($this->iterations[$nestingLevel]);
        unset($this->currentPriority[$nestingLevel]);

        $this->nestingLevel--;

        return $value;
    }

    /**
     * Return the current priority level of the currently running iteration of the hook.
     *
     * @return int|false If the hook is running, return the current priority level. If it isn't running, return false.
     */
    public function currentPriority()
    {
        if (false === current($this->iterations)) {
            return false;
        }

        return current(current($this->iterations));
    }

    /**
     * Determines whether an offset value exists.
     *
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset An offset to check for.
     * @return bool True if the offset exists, false otherwise.
     */
    public function offsetExists($offset)
    {
        return isset($this->callbacks[$offset]);
    }

    /**
     * Retrieves a value at a specified offset.
     *
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset The offset to retrieve.
     * @return mixed If set, the value at the specified offset, null otherwise.
     */
    public function offsetGet($offset)
    {
        return isset($this->callbacks[$offset]) ? $this->callbacks[$offset] : null;
    }

    /**
     * Sets a value at a specified offset.
     *
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value The value to set.
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->callbacks[] = $value;
        } else {
            $this->callbacks[$offset] = $value;
        }
    }

    /**
     * Unsets a specified offset.
     *
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset The offset to unset.
     */
    public function offsetUnset($offset)
    {
        unset($this->callbacks[$offset]);
    }

    /**
     * Returns the current element.
     *
     * @link http://php.net/manual/en/iterator.current.php
     *
     * @return array Of callbacks at current priority.
     */
    public function current()
    {
        return current($this->callbacks);
    }

    /**
     * Moves forward to the next element.
     *
     * @link http://php.net/manual/en/iterator.next.php
     *
     * @return array Of callbacks at next priority.
     */
    public function next()
    {
        return next($this->callbacks);
    }

    /**
     * Returns the key of the current element.
     *
     * @link http://php.net/manual/en/iterator.key.php
     *
     * @return mixed Returns current priority on success, or NULL on failure
     */
    public function key()
    {
        return key($this->callbacks);
    }

    /**
     * Checks if current position is valid.
     *
     * @link http://php.net/manual/en/iterator.valid.php
     *
     * @return boolean
     */
    public function valid()
    {
        return key($this->callbacks) !== null;
    }

    /**
     * Rewinds the Iterator to the first element.
     *
     * @link http://php.net/manual/en/iterator.rewind.php
     */
    public function rewind()
    {
        reset($this->callbacks);
    }
}
