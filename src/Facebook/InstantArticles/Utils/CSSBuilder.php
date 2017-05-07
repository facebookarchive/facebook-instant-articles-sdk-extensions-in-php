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
 *   $cssBuilder = new CSSBuilder();
 *   $cssBuilder->addProperty('.someClass', 'width', '300px')
 *              ->addProperty('.someClass', 'height', '400px')
 *              ->addProperty('.otherClass', 'background-color', '#aabbcc')
 *              ->addProperty('.otherClass', 'border-width', '2px');
 *   $result = $cssBuilder->build(true);
 * </code>
 */
class CSSBuilder
{
    /**
     * @var array('string'=>array('string' => properties))
     */
    private $selectors = array();

    /**
     * @var string prefix for css selector classes
     */
    private $prefix;

    public function __construct($prefix = 'ia2amp-')
    {
        $this->prefix = $prefix;
    }

    /**
     * Simple key => value method setting for CSS properties. This method does not apply any validation
     * be cautious about using it.
     * @param string $selector The selector for the CSS, free format accepted. Be cautious.
     * @param string $property The property name on CSS, free format accepted. Be cautious.
     * @param string $value The property value on CSS, free format accepted. Be cautious.
     * @return CSSBuilder $this instance.
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

    /**
     * Adds property and value to the $class. It creates the selector, apply prefix and builds up
     * css selector class grouping the properties.
     * @param string $class The class to be applied to the selector.
     * @param string $property The property name on CSS, free format accepted. Be cautious.
     * @param string $value The property value on CSS, free format accepted. Be cautious.
     * @return CSSBuilder $this instance.
     */
    public function addToSelector($class, $property, $value)
    {
        return $this->addProperty($this->buildCssSelector($class), $property, $value);
    }

    private function buildCssClass($cssClassName)
    {
        return $this->prefix.$cssClassName;
    }

    private function buildCssSelector($cssClassName)
    {
        return '.'.$this->buildCssClass($cssClassName);
    }


    /**
     * Builds the css output representing the current status of the CSSBuilder structure.
     * @param $formatOutput boolean Indicates if output will be formated.
     * @return string The CSS generated based on current status.
     */
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
