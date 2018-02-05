<?php
/**
 * Copyright (c) 2017-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */
namespace Facebook\InstantArticles\Utils;

use Facebook\InstantArticles\Validators\Type;
use PHPUnit\Framework;

class HookTest extends Framework\TestCase
{
    public function methodHook()
    {
        return 'result';
    }

    public function methodHookReplacing($param1 = null, $param2 = null)
    {
        return 'REPLACED' . (isset($param1) && !Type::isTextEmpty($param1) ? '-'.$param1.'-'.$param2 : '');
    }

    public static function staticMethodHook()
    {
        return 'STATIC';
    }

    public function methodHookBefore1($objToChange)
    {
        $objToChange->first = '1st';
    }

    public function methodHookTestingBefore($objToChange)
    {
        return 'MAIN-WITH-BEFORE-'.$objToChange->first.'-'.$objToChange->second;
    }

    public function methodHookAfter2($objToChange)
    {
        $objToChange->second = '2nd';
    }

    public function methodHookTestingAfter($objToChange)
    {
        return 'MAIN-WITH-AFTER-'.$objToChange->first.'-'.$objToChange->second;
    }

    public static function staticMethodHookParams($arg1, $arg2)
    {
        return 'STATIC-with-params-'.$arg1.'-'.$arg2;
    }

    public function testHookDefault()
    {
        $hook = Hook::create();
        $result = $hook->call('hook_name', array($this, 'methodHook'));
        $this->assertEquals('result', $result);
    }

    public function testHookReplacingDefault()
    {
        $hook = Hook::create();
        $hook->setHook('hook_name', array($this, 'methodHookReplacing'));
        $result = $hook->call('hook_name', array($this, 'methodHook'));
        $this->assertEquals('REPLACED', $result);
    }

    public function testHookReplacingDefaultRemoved()
    {
        $hook = Hook::create();
        $hook->setHook('hook_name', array($this, 'methodHookReplacing'));
        $result = $hook->call('hook_name', array($this, 'methodHook'));
        $this->assertEquals('REPLACED', $result);

        $hook->removeHook('hook_name');
        $result = $hook->call('hook_name', array($this, 'methodHook'));
        $this->assertEquals('result', $result);
    }

    public function testHookRemovingSomethingNeverAdded()
    {
        $hook = Hook::create();
        $result = $hook->call('hook_name', array($this, 'methodHook'));
        $this->assertEquals('result', $result);

        $hook->removeHook('hook_name');
        $result = $hook->call('hook_name', array($this, 'methodHook'));
        $this->assertEquals('result', $result);
    }

    public function testHookDefaultBefore()
    {
        $hook = Hook::create();
        $objToChange = new \StdClass;
        $objToChange->first = '1';
        $objToChange->second = '2';

        // Calls method without overriding the value, so the return should be 1
        $objToChange->first = '1';
        $objToChange->second = '2';
        $result = $hook->call('hook_name', array($this, 'methodHookTestingBefore'), array($objToChange));
        $this->assertEquals('MAIN-WITH-BEFORE-1-2', $result);

        // Calls method overriding the value, so the return should be 1st
        $objToChange->first = '1';
        $objToChange->second = '2';
        $hook->setBeforeHook('hook_name', array($this, 'methodHookBefore1'), array($objToChange));
        $result = $hook->call('hook_name', array($this, 'methodHookTestingBefore'), array($objToChange));
        $this->assertEquals('MAIN-WITH-BEFORE-1st-2', $result);
    }

    public function testHookDefaultAfter()
    {
        $hook = Hook::create();
        $objToChange = new \StdClass;
        $objToChange->first = '1';
        $objToChange->second = '2';

        // Calls method without overriding the value, so the return should be 1
        $objToChange->first = '1';
        $objToChange->second = '2';
        $result = $hook->call('hook_name', array($this, 'methodHookTestingAfter'), array($objToChange));
        $this->assertEquals('MAIN-WITH-AFTER-1-2', $result);

        // Calls method overriding the value, so the return should be 2nd
        $objToChange->first = '1';
        $objToChange->second = '2';
        $hook->setAfterHook('hook_name', array($this, 'methodHookAfter2'), array($objToChange));
        $result = $hook->call('hook_name', array($this, 'methodHookTestingAfter'), array($objToChange));
        $this->assertEquals('MAIN-WITH-AFTER-1-2', $result);
        $this->assertEquals('2nd', $objToChange->second);
    }

    public function testHookDefaultBeforeAndAfter()
    {
        $hook = Hook::create();
        $objToChange = new \StdClass;
        $objToChange->first = '1';
        $objToChange->second = '2';

        // Calls method without overriding the value, so the return should be 1
        $objToChange->first = '1';
        $objToChange->second = '2';
        $result = $hook->call('hook_name', array($this, 'methodHookTestingBefore'), array($objToChange));
        $this->assertEquals('MAIN-WITH-BEFORE-1-2', $result);

        // Calls method overriding the value, so the return should be 1st
        $objToChange->first = '1';
        $objToChange->second = '2';
        $hook->setBeforeHook('hook_name', array($this, 'methodHookBefore1'), array($objToChange));
        $result = $hook->call('hook_name', array($this, 'methodHookTestingBefore'), array($objToChange));
        $this->assertEquals('MAIN-WITH-BEFORE-1st-2', $result);

        // Calls method overriding the value, so the return should be 2nd
        $objToChange->first = '1';
        $objToChange->second = '2';
        $hook->setAfterHook('hook_name', array($this, 'methodHookAfter2'), array($objToChange));
        $result = $hook->call('hook_name', array($this, 'methodHookTestingAfter'), array($objToChange));
        $this->assertEquals('MAIN-WITH-AFTER-1st-2', $result);
        $this->assertEquals('2nd', $objToChange->second);
    }

    public function testHookDefaultBeforeAndAfterRemoved()
    {
        $hook = Hook::create();
        $objToChange = new \StdClass;

        // Calls method without overriding the value, so the return should be 1
        $objToChange->first = '1';
        $objToChange->second = '2';
        $hook->setBeforeHook('hook_name', array($this, 'methodHookBefore1'), array($objToChange));
        $hook->setAfterHook('hook_name', array($this, 'methodHookAfter2'), array($objToChange));
        $result = $hook->call('hook_name', array($this, 'methodHookTestingAfter'), array($objToChange));
        $this->assertEquals('MAIN-WITH-AFTER-1st-2', $result);
        $this->assertEquals('2nd', $objToChange->second);

        $objToChange->first = '1';
        $objToChange->second = '2';
        $hook->clearHooks('hook_name');
        $result = $hook->call('hook_name', array($this, 'methodHookTestingBefore'), array($objToChange));
        $this->assertEquals('MAIN-WITH-BEFORE-1-2', $result);
    }

    public function testHookReplacingDefaultParams()
    {
        $hook = Hook::create();
        $hook->setHook('hook_name', array($this, 'methodHookReplacing'), array('param1', 'param2'));
        $result = $hook->call('hook_name', array($this, 'methodHook'));
        $this->assertEquals('REPLACED-param1-param2', $result);
    }

    public function testHookStaticMethod()
    {
        $hook = Hook::create();
        $result = $hook->call('hook_name', array('Facebook\InstantArticles\Utils\HookTest', 'staticMethodHook'));
        $this->assertEquals('STATIC', $result);
    }

    public function testHookStaticMethodParams()
    {
        $hook = Hook::create();
        $result = $hook->call('hook_name', array('Facebook\InstantArticles\Utils\HookTest', 'staticMethodHookParams'), array('param1', 'param2'));
        $this->assertEquals('STATIC-with-params-param1-param2', $result);
    }

    public function testHookFunctionNoClass()
    {
        $hook = Hook::create();
        $result = $hook->call('hook_name', 'Facebook\InstantArticles\Utils\functionOutsideClass');
        $this->assertEquals('OUTSIDER', $result);
    }

    public function testHookFunctionNoClassWithParam()
    {
        $hook = Hook::create();
        $result = $hook->call('hook_name', 'Facebook\InstantArticles\Utils\functionOutsideClassWithParams', array('param1'));
        $this->assertEquals('OUTSIDER-with-param-param1', $result);
    }
}

function functionOutsideClass()
{
    return 'OUTSIDER';
}

function functionOutsideClassWithParams($arg1)
{
    return 'OUTSIDER-with-param-'.$arg1;
}
