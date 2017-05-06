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
 * Helper class to build up CSS structure file.
 * Usage example:
 * <code>
 * // TODO
 * </code>
 */
class CSSBuilder
{
    /**
     * @var array('string'=>array('string' => properties))
     */
    private $selectors = array();

    /**
     * Simple key => value method setting for CSS properties
     */
    public function addProperty($selector, $property, $value)
    {
        if (isset($this->selectors[$selector])) {
            $selectorProps = $this->selectors[$selector];
        } else {
            $selectorProps = array();
        }

        $selectorProps[$property] = $value;
        $this->selectors[$selector] = $selectorProps;

        return $this;
    }

    public function build($formatOutput = true)
    {
        $result = '';
        $formatIdent = $formatOutput ? '    ' : '';
        $formatNewLine = $formatOutput ? "\n" : '';
        foreach ($this->selectors as $selector => $properties) {
            if ($properties && !empty($properties)) {
                if ($result !== '') {
                    $result = $result.$formatNewLine.$formatNewLine;
                }

                $result = $result.$selector.' {'.$formatNewLine;
                foreach ($properties as $property => $value) {
                    $result = $result.$formatIdent.$property.': '.$value.';';
                    $result = $result.$formatNewLine;
                }
                $result = $result.'}';
            }
        }

        return $result;
    }
}
