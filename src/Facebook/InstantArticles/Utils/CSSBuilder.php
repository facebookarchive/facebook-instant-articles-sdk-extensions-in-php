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

    /**
     * @var string spacing For height on the separator divs
     */
    private $spacing;

    public function __construct($prefix = 'ia2amp-', $spacing = 'spacing')
    {
        $this->prefix = $prefix;
        $this->spacing = $spacing;
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
     * @param string|array<string> $class The class to be applied to the selector.
     * @param string $property The property name on CSS, free format accepted. Be cautious.
     * @param string $value The property value on CSS, free format accepted. Be cautious.
     * @return CSSBuilder $this instance.
     */
    public function addToSelector($class, $property, $value)
    {
        return $this->addProperty($this->buildCssSelector($class), $property, $value);
    }

    /**
     * Adds dimension sized property value to the $class. It creates the selector, apply prefix and builds up
     * css selector class grouping the properties. If value not informed, zero will be placed.
     * @param string|array<string> $class The class to be applied to the selector.
     * @param string $property The property name on CSS, free format accepted. Be cautious.
     * @param string|float|int $dimension The property value on CSS.
     * @param string $unit The unit to be applied. Default value: 'px'.
     * @return CSSBuilder $this instance.
     */
    public function addDimensionToSelector($class, $property, $dimension, $unit = 'px')
    {
        return $this->addProperty($this->buildCssSelector($class), $property, $dimension ? $dimension.$unit : '0');
    }

    /**
     * Adds condensed dimensions to property value selected by $class. If value not informed, zero will be placed.
     * @param string|array<string> $class The class to be applied to the selector.
     * @param string $property The property name on CSS, free format accepted. Be cautious.
     * @param string|float|int $top The property value for top position on CSS.
     * @param string|float|int $right The property value for right position on CSS.
     * @param string|float|int $bottom The property value for bottom position on CSS.
     * @param string|float|int $left The property value for left position on CSS.
     * * @param string $unit The unit to be applied. Default value: 'px'.
     * @return CSSBuilder $this instance.
     */
    public function addTopRightBottomLeftToSelector($class, $property, $top, $right, $bottom, $left, $unit = 'px')
    {
        $dimension =
            ($top ? $top.$unit : '0').' '.
            ($right ? $right.$unit : '0').' '.
            ($bottom ? $bottom.$unit : '0').' '.
            ($left ? $left.$unit : '0');
        return $this->addProperty($this->buildCssSelector($class), $property, $dimension);
    }

    /**
     * Adds spacing height value to the proper selected class.
     * @param string|array<string> $class The class to be applied to the selector.
     * @param string|float|int $height The spacing value for spacing.
     * @param string $unit The unity to be applied. Default value: 'px'.
     * @return CSSBuilder $this instance.
     */
    public function addHeightSpacingToSelector($class, $height, $unit = 'px')
    {
        return $this->addProperty(
            $this->buildCssSelector($class).' + '.$this->buildCssSelector($this->spacing),
            'height',
            $height ? $height.$unit : '0'
        );
    }


    private function buildCssClass($cssClassName)
    {
        return $this->prefix.$cssClassName;
    }

    private function buildCssSelector($class)
    {
        if (is_array($class)) {
            $selectors = array();
            foreach ($class as $singleClass) {
                $selectors[] = $this->buildCssSelector($singleClass);
            }
            return implode(', ', $selectors);
        } else {
            return '.'.$this->buildCssClass($class);
        }
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

    /**
     * Auxiliary method to extract the full qualified class name.
     *
     * @return string The full qualified name of class.
     */
    public static function getClassName()
    {
        return get_called_class();
    }
}
