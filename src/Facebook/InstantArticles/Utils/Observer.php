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
 * Class Observer for managing filtering into the key and extensible points into
 * your project architecture.
 *
 * Usage example:
 * <code>
 * // Obtain the observer instance or create one
 * $observer = Observer::create();
 * // Name your hook with the string you want and set the default function to be called.
 * $result = $obs->applyFilters('filter name', SomeClass::statciMethodBeingCalled('param1', 'param2'));
 * // Anyone can override your callable by using this line:
 * $obs->addFilter('filter name', array($this, 'methodHookReplacing'));
 * </code>
 */
class Observer
{
    private static $filterCount = 0;

    /* Filters configured. Mapped array: 'filter name' => CallbackHolder  */
    private $callbacks = array();

    /**
     * Private constructor to force factory method: Hook::create();
     */
    private function __construct()
    {
    }

    /**
     * @return Hook new instance of the Hook class.
     */
    public static function create()
    {
        return new self();
    }

    /**
     * Hook functions/methods to change, replace or add info to the data you are filtering.
     *
     * Anyone can modify data by binding a callback to a filter hook. When the filter
     * is later applied, each bound callback is run in order of priority, and given
     * the opportunity to modify a value by returning a new value or modifying the
     * value orinally returned.
     *
     * Check the following example on how we can bind callback to a filter:
     *
     *     function myCallback( $value ) {
     *         // Maybe modify $value in some way.
     *         return $value;
     *     }
     *     $observer = Observer::create();
     *     $observer->addFilter('myFilterName', 'myCallback');
     *
     * Bound callbacks can receive zero to the total defined in addFilter method call.
     *
     * Here are few more examples about how to call the apply filters and how to set it up
     * on the addFilter method. For example:
     *
     *     // Call to be filtered
     *     $value = $observer->applyFilters('filterName', $value, $arg2, $arg3);
     *
     *     // Accepting zero/one arguments.
     *     function callbackFunction() {
     *         ...
     *         return 'value overriden';
     *     }
     *     $observer = Observer::create();
     *     $observer->addFilter('filterName', 'callbackFunction'); // Where $priority is default 10, $acceptedArgs is default 1.
     *
     *     // Accepting two arguments (three possible).
     *     function callbackFunction( $value, $arg2 ) {
     *         ...
     *         return $modifiedValue;
     *     }
     *     $observer = Observer::create();
     *     $observer->addFilter( 'filterName', 'callbackFunction', 5, 2 ); // Where $priority is 5, $acceptedArgs is 2.
     *
     * @param string $tag The name of the filter hook.
     * @param callable $functionToAdd The callback to be called when the filter is applied.
     * @param int $priority Optional. Priority order to run the multiple hooks appended to same $tag.
     * As lower the number is, more priority it will have. Default 10.
     * @param int $acceptedArgs Optional. The number of arguments the function accepts. Default 1.
     */
    public function addFilter($tag, $functionToAdd, $priority = 10, $acceptedArgs = 1)
    {
        // This is a crude filter, needs to create the CallbackHook manager for that
        if (!isset($this->callbacks[$tag])) {
            $this->callbacks[$tag] = new CallbackHook();
        }
        // Builds unique identifier for this callback call. This is important due to
        // same method calls from different instances.
        $idx = $this->getUniqueIndexID($tag, $functionToAdd);
        $this->callbacks[$tag]->addFilter($idx, $tag, $functionToAdd, $priority, $acceptedArgs);
    }

    /**
     * Call all the functions hooked to that filter name. If none are hooked the value will be returned back.
     *
     * A good strategy to use this function is to create hooking points into your code, by easily calling the
     * applyFilters() method.
     *
     * Check this example:
     * $observer = Observer::create();
     * $url = $observer->applyFilters('GET_THE_URL', $a_url);
     *
     * You might not have initially a filter designed to this URL getter, but in
     * the future you might have a URL redirect or even https enforcement, or anything
     *
     * When callback functions get attached to this 'GET_THE_URL' hook name, it will
     * filter and can modify the final result.
     *
     * Full example:
     *
     *     // The filter callback function
     *     function myCallback($value, $param1, $param2) {
     *         // do something with the value
     *         return $value;
     *     }
     *     $observer = Observer::create();
     *     $observer->addFilter('filter name', 'myCallback', 10, 3);
     *
     *     /*
     *      * Apply the filters calling the 'myCallback' function we
     *      * "hooked" to $tag using the addFilter() method.
     *      * 'filter name' is the filter hook $tag
     *      * 'value to filter' is the value being filtered
     *      * $param1 and $param2 are the additional arguments passed to the callback.
     *     $value = $observer->applyFilters('filter name', 'value to filter', $param1, $param2);
     *
     * @param string $tag The name of the filter hook.
     * @param mixed $value The value on which the filters hooked to `$tag` are applied on.
     * @param mixed $var,... Additional variables passed to the functions hooked to `$tag`.
     * @return mixed The filtered value after all hooked functions are applied to it.
     */
    public function applyFilters($tag, $value/*, $var...*/)
    {
        $args = array();

        // In case no filters configured for this hook, simply return informed value.
        if (!isset($this->callbacks[$tag])) {
            return $value;
        }

        // Uses this to be compatible with PHP < 5.6
        $args = func_get_args();

        // Removes the $tag, since this is not an expected parameter to callbacks.
        array_shift($args);

        $filtered = $this->callbacks[$tag]->applyFilters($value, $args);

        return $filtered;
    }

    /**
     * Removes a function from a specified filter hook.
     *
     * This function removes a specific function attached to a filter hook.
     * To remove a hook, the $functionToRemove and $priority arguments must match
     * when the hook was added by calling #addFilter() method.
     *
     * @param string $tag The filter hook name where function + priority will be removed from
     * @param callable $functionToRemove The function/instance->method will be removed.
     * @param int $priority Optional. The priority of the function to be removed. Default 10.
     * @return bool True if found function to remove from filter list, false otherwise.
     */
    public function removeFilter($tag, $functionToRemove, $priority = 10)
    {
        $return = false;
        if (isset($this->callbacks[$tag])) {
            $idx = $this->getUniqueIndexID($tag, $functionToRemove);
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
     * @param string $tag The filter to remove hooks from.
     * @param int|bool $priority Optional. The priority number to remove. Default false.
     */
    public function removeAllFilters($tag, $priority = false)
    {
        if (isset($this->callbacks[$tag])) {
            $this->callbacks[$tag]->removeAllFilters($priority);
            if (!$this->callbacks[$tag]->hasFilters()) {
                unset($this->callbacks[$tag]);
            }
        }
    }

    /**
     * Serialize and generates an index for storage and retrival of method/functions as callbacks.
     *
     * This index will be used mostly for instance method callback hooks. Since those cam imply into
     * having conflicting names.
     *
     * <code>
     * $bar1 = new Bar();
     * $bar2 = new Bar();
     *
     * // Check that the callbacks are registered under same method name, same class,
     * // same priority, but using different instances.
     * $observer->addFilter('FILTER_NAME', array($bar1, 'foo'), 10);
     * $observer->addFilter('FILTER_NAME', array($bar2, 'foo'), 10);
     * </code>
     *
     * @param string $tag Used in counting how many hooks were applied
     * @param callable $function Used for creating unique id
     * @param int|bool $priority Used in counting how many hooks were applied. If === false
     * and $function is an object reference, we return the unique id only if it already has one,
     * false otherwise.
     * @return string|false Unique ID for usage as array key or false if $priority === false
     * and $function is an object reference, and it does not already have a unique id.
     */
    private function getUniqueIndexID($tag, $function)
    {
        if (is_string($function)) {
            return $function;
        }
        if ($function instanceof \Closure) {
            // If a Closure is used, it cannot be retrieved or removed
            // individually later
            return 'Closure' . self::$filterCount++;
        }
        if (is_object($function)) {
            $function = array($function, '');
        } else {
            $function = (array) $function;
        }

        if (is_string($function[0])) {
            // Static call
            return $function[0] . '::' . $function[1];
        } elseif (is_object($function[0])) {
            // Instance call
            if (function_exists('spl_object_hash')) {
                return spl_object_hash($function[0]).'->' . $function[1];
            } else {
                $obj_idx = get_class($function[0]).'->'.$function[1];
                if (!isset($function[0]->filterIdx)) {
                    $obj_idx = $obj_idx . self::$filterCount;
                    $function[0]->filterIdx = self::$filterCount;
                    self::$filterCount++;
                } else {
                    $obj_idx = $obj_idx . $function[0]->filterIdx;
                }
                return $obj_idx;
            }
        }
    }

    /**
     * Check if any filter has been registered for a hook.
     *
     * @param string $tag The name of the filter.
     * @param callable|bool $functionToCheck Optional. Callable that will be checked.
     * @return false|int If $functionToCheck is omitted, returns boolean for whether the hook has
     * anything registered. With a specific function, the priority of that
     * hook is returned, false otherwise.
     */
    public function hasFilter($tag, $functionToCheck = false)
    {
        // If hook name has nothing, then nothing is hooked there.
        if (!isset($this->callbacks[$tag])) {
            return false;
        }
        $idx = $this->getUniqueIndexID($tag, $functionToCheck);
        return $this->callbacks[$tag]->hasFilter($idx, $tag, $functionToCheck);
    }
}
