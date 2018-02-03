<?php
/**
 * Copyright (c) 2017-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */
namespace Facebook\InstantArticles\Utils;

use PHPUnit\Framework;

class ObserverTest extends Framework\TestCase
{
    public function testNoFilter()
    {
        $observererver = Observer::create();
        $name = $observererver->applyFilters('name', "Bob");
        $this->assertEquals('Bob', $name);
    }

    public function testSingleFilter()
    {
        $observer = Observer::create();
        $observer->addFilter('name', function ($name) {
            return "$name-san";
        });
        $name = $observer->applyFilters('name', "Bob");
        $this->assertEquals('Bob-san', $name);
    }

    public function testStaticFilter()
    {
        $observer = Observer::create();
        $observer->addFilter('name', array("Facebook\InstantArticles\Utils\Greeting", "hello"));

        $name = $observer->applyFilters('name', "Bob");
        $this->assertEquals('Hello Bob', $name);
    }

    public function testFilterWithReferences()
    {
        $mr = new Greeting("Mr.");
        $hello = new Greeting("Hello");

        $observer = Observer::create();
        $observer->addFilter('name', array(&$mr, "greet"));
        $observer->addFilter('name', array(&$hello, "greet"));

        $name = $observer->applyFilters('name', "Bob");
        $this->assertEquals('Hello Mr. Bob', $name);
    }

    public function testFilterWithParameters()
    {
        $hello = new Greeting("Hello");

        $observer = Observer::create();
        $observer->addFilter('name', array(&$hello, "greet"), 10, 4);

        $name = $observer->applyFilters('name', "Spongebob", "Square", "Pants");
        $this->assertEquals('Hello Spongebob Square Pants', $name);
    }

    public function testFilterWithReferencesAndParameters()
    {
        $hello = new Greeting("Hello");
        $mr = new Greeting("Mr.");

        $observer = Observer::create();
        $observer->addFilter('name', array(&$hello, "greet"), 11, 2);
        $observer->addFilter('name', array(&$mr, "greet"), 10, 1);

        $name = $observer->applyFilters('name', "Bob", "Bobby");
        $this->assertEquals('Hello Mr. Bob Bobby', $name);
    }

    public function testRemoveFilterWithReferencesParameters()
    {
        $hello = new Greeting("Hello");
        $mr = new Greeting("Mr.");

        $observer = Observer::create();
        $observer->addFilter('name', array(&$hello, "greet"), 11, 2);
        $observer->addFilter('name', array(&$mr, "greet"), 10, 1);

        $name = $observer->applyFilters('name', "Bob", "Bobby");
        $this->assertEquals('Hello Mr. Bob Bobby', $name);

        $observer->removeFilter('name', array(&$mr, "greet"), 10);

        $name = $observer->applyFilters('name', "Bob", "Bobby");
        $this->assertEquals('Hello Bob Bobby', $name);
    }

    public function testRemoveAllFiltersWithReferencesAndParameters()
    {
        $hello = new Greeting("Hello");
        $mr = new Greeting("Mr.");

        $observer = Observer::create();
        $observer->addFilter('name', array(&$hello, "greet"), 11, 2);
        $observer->addFilter('name', array(&$mr, "greet"), 10, 1);

        $name = $observer->applyFilters('name', "Bob", "Bobby");
        $this->assertEquals('Hello Mr. Bob Bobby', $name);

        $observer->removeAllFilters('name', 11);

        $name = $observer->applyFilters('name', "Bob", "Bobby");
        $this->assertEquals('Mr. Bob', $name);

        $observer->removeAllFilters('name');

        $name = $observer->applyFilters('name', "Bob", "Bobby");
        $this->assertEquals('Bob', $name);
    }

    public function testHasFilter()
    {
        $hello = new Greeting("Hello");
        $mr = new Greeting("Mr.");
        $mrs = new Greeting("Mrs.");

        $observer = Observer::create();
        $observer->addFilter('name', array(&$hello, "greet"), 11, 2);
        $observer->addFilter('name', array(&$mr, "greet"), 10, 1);

        $this->assertEquals(11, $observer->hasFilter('name', array(&$hello, "greet")));
        $this->assertEquals(10, $observer->hasFilter('name', array(&$mr, "greet")));
        $this->assertFalse($observer->hasFilter('name', array(&$mrs, "greet")));
        $this->assertTrue($observer->hasFilter('name'));
    }
}
