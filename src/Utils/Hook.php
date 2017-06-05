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
 * Class to facilitate hooking callable statements.
 * Usage example:
 * <code>
 * // Obtain the hook instance or create one
 * Hook::create();
 * // Name your hook with the string you want and set the default function to be called.
 * $result = $hook->call('hook_name', array('Facebook\InstantArticles\hook\HookTest', 'staticMethodHookParams'), array('param1', 'param2'));
 * // Anyone can override your callable by using this line:
 * $hook->setHook('hook_name', array($this, 'methodHookReplacing'));
 * </code>
 */
class Hook
{
    /* Hooks to have overriden callable */
    private $hooks = array();
    private $params = array();

    /* Hooks to have before callable */
    private $beforeHooks = array();
    private $beforeParams = array();

    /* Hooks to have after callable */
    private $afterHooks = array();
    private $afterParams = array();

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

    public function clearHooks($hookName)
    {
        $this->removeHook($hookName);
        $this->removeAfterHook($hookName);
        $this->removeBeforeHook($hookName);
    }

    /**
     * Overrides a hook by name, by setting a new callable, with params.
     * @param string $hookName The name for callable instruction to be intercepted/hooked. Use different names if you need different hooks.
     * @param string/array $callable The string method to be called or array with array('Namespace.ClassName', 'methodName')
     * @param array(mixed) The params to be used into your methodName from your callable.
     */
    public function setHook($hookName, $callable, $params = null)
    {
        $this->hooks[$hookName] = $callable;
        if (isset($params) && $params) {
            $this->params[$hookName] = $params;
        }
    }

    public function removeHook($hookName)
    {
        unset($this->hooks[$hookName]);
    }

    /**
     * Overrides a before event hook by name, by setting a new callable, with params. The returned value from the beforeHook will be ignored. There is no blocking system.
     * @param string $hookName The name for callable instruction to be intercepted/hooked. Use different names if you need different hooks.
     * @param string/array $callable The string method to be called or array with array('Namespace.ClassName', 'methodName')
     * @param array(mixed) The params to be used into your methodName from your callable.
     */
    public function setBeforeHook($hookName, $callable, $params = null)
    {
        $this->beforeHooks[$hookName] = $callable;
        if (isset($params) && $params) {
            $this->beforeParams[$hookName] = $params;
        }
    }

    public function removeBeforeHook($hookName)
    {
        unset($this->beforeHooks[$hookName]);
    }

    /**
     * Overrides a after event hook by name, by setting a new callable, with params. The returned value from the after will be ignored.
     * @param string $hookName The name for callable instruction to be intercepted/hooked. Use different names if you need different hooks.
     * @param string/array $callable The string method to be called or array with array('Namespace.ClassName', 'methodName')
     * @param array(mixed) The params to be used into your methodName from your callable.
     */
    public function setAfterHook($hookName, $callable, $params = null)
    {
        $this->afterHooks[$hookName] = $callable;
        if (isset($params) && $params) {
            $this->afterParams[$hookName] = $params;
        }
    }

    public function removeAfterHook($hookName)
    {
        unset($this->afterHooks[$hookName]);
    }

    /**
     * Any callable method/call that you want to make it possible to have it
     * possible to be overriden or a before/after hook, just call it by name
     * using your hook instance. This make the callable to be intercepted/overriden.
     * @param string $hookName The name for callable instruction to be intercepted/hooked. Use different names if you need different hooks.
     * @param string/array $callable The string method to be called or array with array('Namespace.ClassName', 'methodName')
     * @param array(mixed) The params to be used into your methodName from your callable.
     */
    public function call($hookName, $callable, $params = array())
    {
        // Treats before hook is called. To set something to happen before any hook, just use setBeforeHook('hook_name', ...)
        if (array_key_exists($hookName, $this->beforeHooks) && isset($this->beforeHooks[$hookName])) {
            $beforeCallable = $this->beforeHooks[$hookName];
            $beforeParams = array();
            if (array_key_exists($hookName, $this->beforeParams) && isset($this->beforeParams[$hookName])) {
                $beforeParams = $this->beforeParams[$hookName];
            }
            // Calls the "before" event $hookName, ignoring the return
            call_user_func_array($beforeCallable, $beforeParams);
        }

        // Treats the Hook itself
        if (array_key_exists($hookName, $this->hooks) && isset($this->hooks[$hookName])) {
            $callable = $this->hooks[$hookName];
            if (array_key_exists($hookName, $this->params) && isset($this->params[$hookName])) {
                $params = $this->params[$hookName];
            }
        }
        $return = call_user_func_array($callable, $params);


        // Treats after hook is called. To set something to happen after any hook, just use setAfterHook('hook_name', ...)
        if (array_key_exists($hookName, $this->afterHooks) && isset($this->afterHooks[$hookName])) {
            $afterCallable = $this->afterHooks[$hookName];
            $afterParams = array();
            if (array_key_exists($hookName, $this->afterParams) && isset($this->afterParams[$hookName])) {
                $afterParams = $this->afterParams[$hookName];
            }

            // Calls the "after" event $hookName ignoring the return
            call_user_func_array($afterCallable, $afterParams);
        }

        return $return;
    }
}
