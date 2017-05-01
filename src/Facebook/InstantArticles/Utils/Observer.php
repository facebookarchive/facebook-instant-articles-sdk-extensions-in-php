<?php
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */
namespace Facebook\InstantArticles\Utils;

/**
 * Class Observer for managing filtering into the key and extensible points into
 * your project architecture. This works exactly as WordPress hooking system of
 * add_filter and apply_filters.
 * This class was implemented based on WordPress [plugin.php](https://raw.github.com/WordPress/WordPress/master/wp-includes/plugin.php).
 * Usage example:
 * <code>
 * // Obtain the observer instance or create one
 * $obs = Observer::create();
 * // Name your hook with the string you want and set the default function to be called.
 * $result = $obs->applyFilters('filter name', SomeClass::statciMethodBeingCalled('param1', 'param2'));
 * // Anyone can override your callable by using this line:
 * $obs->addFilter('filter name', array($this, 'methodHookReplacing'));
 * </code>
 */
class Observer
{
    private static $filter_id_count = 0;

    /* Filters configured. Mapped array: 'filter name' => CallbackHolder  */
    private $callbacks = array();

    /**
     * Private constructor to force factory method: Hook::create();
     */
    private function __construct()
    {}

    /**
     * @return Hook new instance of the Hook class.
     */
    public static function create()
    {
        return new self();
    }

    /**
     * Hook a function or method to a specific filter action.
     *
     * Anyone can modify data by binding a callback to a filter hook. When the filter
     * is later applied, each bound callback is run in order of priority, and given
     * the opportunity to modify a value by returning a new value or modifying the
     * value orinally returned.
     *
     * The following example shows how a callback function is bound to a filter hook.
     *
     * Note that `$example` is passed to the callback, (maybe) modified, then returned:
     *
     *     function example_callback( $example ) {
     *         // Maybe modify $example in some way.
     *         return $example;
     *     }
     *     Observer::addFilter('example_filter', 'example_callback');
     *
     * Bound callbacks can accept from none to the total number of arguments passed as parameters
     * in the corresponding Observer::applyFilters() call.
     *
     * In other words, if an applyFilters() call passes four total arguments, callbacks bound to
     * it can accept none (the same as 1) of the arguments or up to four. The important part is that
     * the `$acceptedArgs` value must reflect the number of arguments the bound callback *actually*
     * opted to accept. If no arguments were accepted by the callback that is considered to be the
     * same as accepting 1 argument. For example:
     *
     *     // Filter call.
     *     $value = Observer::applyFilters( 'hook', $value, $arg2, $arg3 );
     *
     *     // Accepting zero/one arguments.
     *     function example_callback() {
     *         ...
     *         return 'some value';
     *     }
     *     Observer::addFilter( 'hook', 'example_callback' ); // Where $priority is default 10, $acceptedArgs is default 1.
     *
     *     // Accepting two arguments (three possible).
     *     function example_callback( $value, $arg2 ) {
     *         ...
     *         return $maybe_modified_value;
     *     }
     *     Observer::addFilter( 'hook', 'example_callback', 10, 2 ); // Where $priority is 10, $acceptedArgs is 2.
     *
     * *Note:* The function will return true whether or not the callback is valid.
     * It is up to you to take care. This is done for optimization purposes, so
     * everything is as quick as possible.
     *
     * @param string   $tag             The name of the filter to hook the $functionToAdd callback to.
     * @param callable $functionToAdd   The callback to be run when the filter is applied.
     * @param int      $priority        Optional. Used to specify the order in which the functions
     *                                  associated with a particular action are executed. Default 10.
     *                                  Lower numbers correspond with earlier execution,
     *                                  and functions with the same priority are executed
     *                                  in the order in which they were added to the action.
     * @param int      $acceptedArgs   Optional. The number of arguments the function accepts. Default 1.
     * @return true
     */
    public function addFilter( $tag, $functionToAdd, $priority = 10, $acceptedArgs = 1 ) {
        if (!isset($this->callbacks[$tag])) {
            $this->callbacks[$tag] = new CallbackHook();
        }

        $idx = $this->filterBuildUniqueId($tag, $functionToAdd, $priority);
        $this->callbacks[$tag]->addFilter($idx, $tag, $functionToAdd, $priority, $acceptedArgs);

        return true;
    }

    /**
     * Call the functions added to a filter hook.
     *
     * The callback functions attached to filter hook $tag are invoked by calling
     * this function. This function can be used to create a new filter hook by
     * simply calling this function with the name of the new hook specified using
     * the $tag parameter.
     *
     * The function allows for additional arguments to be added and passed to hooks.
     *
     *     // Our filter callback function
     *     function example_callback( $string, $arg1, $arg2 ) {
     *         // (maybe) modify $string
     *         return $string;
     *     }
     *     Observer::addFilter( 'example_filter', 'example_callback', 10, 3 );
     *
     *     /*
     *      * Apply the filters by calling the 'example_callback' function we
     *      * "hooked" to 'example_filter' using the add_filter() function above.
     *      * - 'example_filter' is the filter hook $tag
     *      * - 'filter me' is the value being filtered
     *      * - $arg1 and $arg2 are the additional arguments passed to the callback.
     *     $value = Observer::applyFilters('example_filter', 'filter me', $arg1, $arg2);
     *
     * @param string $tag     The name of the filter hook.
     * @param mixed  $value   The value on which the filters hooked to `$tag` are applied on.
     * @param mixed  $var,... Additional variables passed to the functions hooked to `$tag`.
     * @return mixed The filtered value after all hooked functions are applied to it.
     */
    public function applyFilters($tag, $value)
    {
        $args = array();

        if ( !isset($this->callbacks[$tag]) ) {
            return $value;
        }

        $args = func_get_args();

        // don't pass the tag name to WP_Hook
        array_shift($args);

        $filtered = $this->callbacks[$tag]->applyFilters($value, $args);

        return $filtered;
    }

    /**
     * Removes a function from a specified filter hook.
     *
     * This function removes a function attached to a specified filter hook. This
     * method can be used to remove default functions attached to a specific filter
     * hook and possibly replace them with a substitute.
     *
     * To remove a hook, the $functionToRemove and $priority arguments must match
     * when the hook was added. This goes for both filters and actions. No warning
     * will be given on removal failure.
     *
     * @param string   $tag                The filter hook to which the function to be removed is hooked.
     * @param callable $functionToRemove   The name of the function which should be removed.
     * @param int      $priority           Optional. The priority of the function. Default 10.
     * @return bool    Whether the function existed before it was removed.
     */
    function removeFilter($tag, $functionToRemove, $priority = 10)
    {
        $return = false;
        if (isset($this->callbacks[$tag])) {
            $idx = $this->filterBuildUniqueId($tag, $functionToRemove, $priority);
            $return = $this->callbacks[$tag]->removeFilter($idx, $tag, $priority);
            if (! $this->callbacks[$tag]->callbacks) {
                unset($this->callbacks[$tag]);
            }
        }
        return $return;
    }

    /**
     * Remove all of the hooks from a filter.
     *
     * @param string   $tag      The filter to remove hooks from.
     * @param int|bool $priority Optional. The priority number to remove. Default false.
     * @return true True when finished.
     */
    function removeAllFilters($tag, $priority = false)
    {
        if (isset($this->callbacks[$tag])) {
            $this->callbacks[$tag]->removeAllFilters($priority);
            if (!$this->callbacks[$tag]->hasFilters()) {
                unset($this->callbacks[$tag]);
            }
        }

        return true;
    }

    /**
     * Build Unique ID for storage and retrieval.
     *
     * Functions and static method callbacks are just returned as strings and
     * shouldn't have any speed penalty.
     *
     * @param string   $tag      Used in counting how many hooks were applied
     * @param callable $function Used for creating unique id
     * @param int|bool $priority Used in counting how many hooks were applied. If === false
     *                           and $function is an object reference, we return the unique
     *                           id only if it already has one, false otherwise.
     * @return string|false Unique ID for usage as array key or false if $priority === false
     *                      and $function is an object reference, and it does not already have
     *                      a unique id.
     */
    private function filterBuildUniqueId($tag, $function, $priority)
    {
        if ( is_string($function)) {
            return $function;
        }

        if ( is_object($function) ) {
            // Closures are currently implemented as objects
            $function = array( $function, '' );
        } else {
            $function = (array) $function;
        }

        if (is_object($function[0]) ) {
            // Object Class Calling
            if ( function_exists('spl_object_hash') ) {
                return spl_object_hash($function[0]) . $function[1];
            } else {
                $obj_idx = get_class($function[0]).$function[1];
                if ( !isset($function[0]->wp_filter_id) ) {
                    if ( false === $priority )
                        return false;
                    $obj_idx .= isset($this->callbacks[$tag][$priority]) ? count((array)$this->callbacks[$tag][$priority]) : self::$filter_id_count;
                    $function[0]->wp_filter_id = self::$filter_id_count;
                    ++self::$filter_id_count;
                } else {
                    $obj_idx .= $function[0]->wp_filter_id;
                }

                return $obj_idx;
            }
        } elseif ( is_string( $function[0] ) ) {
            // Static Calling
            return $function[0] . '::' . $function[1];
        }
    }

    /**
     * Check if any filter has been registered for a hook.
     *
     * @param string        $tag               The name of the filter hook.
     * @param callable|bool $functionToCheck   Optional. The callback to check for. Default false.
     * @return false|int If $functionToCheck is omitted, returns boolean for whether the hook has
     *                   anything registered. When checking a specific function, the priority of that
     *                   hook is returned, or false if the function is not attached. When using the
     *                   $functionToCheck argument, this function may return a non-boolean value
     *                   that evaluates to false (e.g.) 0, so use the === operator for testing the
     *                   return value.
     */
    function hasFilter($tag, $functionToCheck = false) {
        if (!isset($this->callbacks[$tag])) {
            return false;
        }
        $idx = $this->filterBuildUniqueId( $tag, $functionToCheck, false );
        return $this->callbacks[$tag]->hasFilter($idx, $tag, $functionToCheck);
    }
}
