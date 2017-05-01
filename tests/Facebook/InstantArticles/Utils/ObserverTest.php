<?php
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */
namespace Facebook\InstantArticles\Utils;

use Facebook\InstantArticles\Validators\Type;

class ObserverTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        \Logger::configure(
            [
                'rootLogger' => [
                    'appenders' => ['facebook-instantarticles-traverser']
                ],
                'appenders' => [
                    'facebook-instantarticles-traverser' => [
                        'class' => 'LoggerAppenderConsole',
                        'threshold' => 'INFO',
                        'layout' => [
                            'class' => 'LoggerLayoutSimple'
                        ]
                    ]
                ]
            ]
        );
    }

    public function method()
    {
        return 'result';
    }

    public function methodFiltering($value)
    {
        return $value.'-filtered';
    }

    public function testFilterDefault()
    {
        $obs = Observer::create();
        $result = $obs->applyFilters('filterName', $this->method());
        $this->assertEquals('result', $result);
    }

    public function testFilterDefaultWithFilter()
    {
        $obs = Observer::create();
        $obs->addFilter('filterName', array($this, 'methodFiltering'));
        $result = $obs->applyFilters('filterName', $this->method());
        $this->assertEquals('result-filtered', $result);
    }

    public function testFilterWithReferences()
    {
        $foo0 = new Foobar("foo");
        $bar1 = new Foobar("bar1");
        $bar2 = new Foobar("bar2");

        $obs = Observer::create();
        $obs->addFilter('filterName', array(&$bar1, "hook"));
        $obs->addFilter('filterName', array(&$bar2, "hook"));

        $result = $obs->applyFilters('filterName', $foo0->hook('param'));
        $this->assertEquals('bar2 bar1 foo param', $result);
    }

    public function testFilterWithParameters()
    {
        $foo0 = new Foobar("foo");
        $bar1 = new Foobar("bar1");

        $obs = Observer::create();
        $obs->addFilter('filterName', array(&$bar1, "hook"), 10, 3);

        $result = $obs->applyFilters('filterName', $foo0->hook('param'), ' param2', ' param3');
        $this->assertEquals('bar1 foo param param2 param3', $result);
    }

    public function testFilterWithReferencesAndParameters()
    {
        $foo0 = new Foobar("foo");
        $bar1 = new Foobar("bar1");
        $bar2 = new Foobar("bar2");

        $obs = Observer::create();
        $obs->addFilter('filterName', array(&$bar1, "hook"), 10, 3);
        $obs->addFilter('filterName', array(&$bar2, "hook"), 10, 3);

        $result = $obs->applyFilters('filterName', $foo0->hook('param'), ' param2', ' param3');
        $this->assertEquals('bar2 bar1 foo param param2 param3 param2 param3', $result);
    }

    public function testRemoveFilterWithReferencesAndParameters()
    {
        $foo0 = new Foobar("foo");
        $bar1 = new Foobar("bar1");
        $bar2 = new Foobar("bar2");

        $obs = Observer::create();
        $obs->addFilter('filterName', array(&$bar1, "hook"), 10, 3);
        $obs->addFilter('filterName', array(&$bar2, "hook"), 10, 3);

        $result = $obs->applyFilters('filterName', $foo0->hook('param'), ' param2', ' param3');
        $this->assertEquals('bar2 bar1 foo param param2 param3 param2 param3', $result);

        $obs->removeFilter('filterName', array(&$bar1, "hook"), 10, 3);
        $result = $obs->applyFilters('filterName', $foo0->hook('param'), ' param2', ' param3');
        $this->assertEquals('bar2 foo param param2 param3', $result);
    }

    public function testRemoveAllFiltersWithReferencesAndParameters()
    {
        $foo0 = new Foobar("foo");
        $bar1 = new Foobar("bar1");
        $bar2 = new Foobar("bar2");

        $obs = Observer::create();
        $obs->addFilter('filterName', array(&$bar1, "hook"), 10, 3);
        $obs->addFilter('filterName', array(&$bar2, "hook"), 10, 3);

        $result = $obs->applyFilters('filterName', $foo0->hook('param'), ' param2', ' param3');
        $this->assertEquals('bar2 bar1 foo param param2 param3 param2 param3', $result);

        $obs->removeAllFilters('filterName', 10);
        $result = $obs->applyFilters('filterName', $foo0->hook('param'), ' param2', ' param3');
        $this->assertEquals('foo param', $result);
    }

    public function testHasFilter()
    {
        $foo0 = new Foobar("foo");
        $bar1 = new Foobar("bar1");
        $bar2 = new Foobar("bar2");

        $obs = Observer::create();
        $obs->addFilter('filterName', array(&$bar1, "hook"), 10, 3);

        $result = $obs->hasFilter('filterName', array(&$bar1, "hook"));
        $this->assertEquals(10, $result);

        $result = $obs->hasFilter('filterName');
        $this->assertTrue($result);
    }
}

class Foobar {
    private $name;

    function __construct($name){
        $this->name = $name;
    }

    function hook($v, $param1=null, $param2=null) {
        return $this->name . " $v" . (!Type::isTextEmpty($param1) ? $param1 : '').(!Type::isTextEmpty($param2) ? $param2 : '');
    }
}
