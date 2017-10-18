<?php
/**
 * Copyright (c) 2017-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */
namespace Facebook\InstantArticles\Utils;

/*
 * Sample class used for testing the Observer.
 */
class Greeting
{
    private $greeting;

    public function __construct($greeting)
    {
        $this->greeting = $greeting;
    }

    /**
     * Says hello to someone
     */
    public static function hello($name)
    {
        return "Hello $name";
    }

    /**
     * Method that returns a string greeting someone
     */
    public function greet($name, $middleName = null, $lastName = null)
    {
        $name = ($this->greeting).' '.$name;
        if ($middleName) {
            $name = $name.' '.$middleName;
        }
        if ($lastName) {
            $name = $name.' '.$lastName;
        }
        return $name;
    }
}
