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

class CSSBuilderTest extends Framework\TestCase
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

    public function genSimpleCSSFormatted($prefix = '')
    {
        return
            ".{$prefix}someClass {\n".
            "    width: 300px;\n".
            "    height: 400px;\n".
            "}";
    }

    public function genSpacingCSSFormatted($prefix = '')
    {
        return
            ".{$prefix}someClass {\n".
            "    width: 300px;\n".
            "    height: 400px;\n".
            "}\n".
            "\n".
            ".{$prefix}someClass + .{$prefix}spacing {\n".
            "    height: 18px;\n".
            "}";
    }

    public function genMarginCSSFormatted($prefix = '')
    {
        return
            ".{$prefix}someClass {\n".
            "    margin: 0 10px 5px 0;\n".
            "    height: 400px;\n".
            "}";
    }

    public function genArrayCSSFormatted($prefix = '')
    {
        return
            ".{$prefix}someClass1, .{$prefix}someClass2 {\n".
            "    width: 300px;\n".
            "    height: 400px;\n".
            "}\n".
            "\n".
            ".{$prefix}someClass1 {\n".
            "    color: #fff;\n".
            "}";
    }

    public function genOtherCSSFormatted($prefix = '')
    {
        return
            ".{$prefix}otherClass {\n".
            "    background-color: #aabbcc;\n".
            "    border-width: 2px;\n".
            "}";
    }

    public function genSimpleCSS($prefix = '')
    {
        return
            ".{$prefix}someClass {".
            "width: 300px;".
            "height: 400px;".
            "}";
    }



    public function testSimpleCSS()
    {
        $expected = $this->genSimpleCSS();

        $cssBuilder = new CSSBuilder();
        $cssBuilder->addProperty('.someClass', 'width', '300px')
                   ->addProperty('.someClass', 'height', '400px');
        $result = $cssBuilder->build(false);
        $this->assertEquals($expected, $result);
    }

    public function testSimpleCSSFormatted()
    {
        $expected = $this->genSimpleCSSFormatted();

        $cssBuilder = new CSSBuilder('');
        $cssBuilder->addProperty('.someClass', 'width', '300px')
                   ->addProperty('.someClass', 'height', '400px');
        $result = $cssBuilder->build(true);
        $this->assertEquals($expected, $result);
    }

    public function testMultipleCSSFormatted()
    {
        $expected = $this->genSimpleCSSFormatted()."\n\n".$this->genOtherCSSFormatted();

        $cssBuilder = new CSSBuilder('');
        $cssBuilder->addProperty('.someClass', 'width', '300px')
                   ->addProperty('.someClass', 'height', '400px')
                   ->addProperty('.otherClass', 'background-color', '#aabbcc')
                   ->addProperty('.otherClass', 'border-width', '2px');
        $result = $cssBuilder->build(true);
        $this->assertEquals($expected, $result);
    }

    public function testMultipleCSSFormattedWithPrefix()
    {
        $expected = $this->genSimpleCSSFormatted('myprefix-');

        $cssBuilder = new CSSBuilder('myprefix-');
        $cssBuilder->addToSelector('someClass', 'width', '300px')
                   ->addToSelector('someClass', 'height', '400px');
        $result = $cssBuilder->build(true);
        $this->assertEquals($expected, $result);
    }

    public function testMultipleDimmensionCSSFormattedWithPrefix()
    {
        $expected = $this->genSimpleCSSFormatted('myprefix-');

        $cssBuilder = new CSSBuilder('myprefix-');
        $cssBuilder->addDimensionToSelector('someClass', 'width', '300', 'px')
                   ->addDimensionToSelector('someClass', 'height', '400', 'px');
        $result = $cssBuilder->build(true);
        $this->assertEquals($expected, $result);
    }

    public function testSpacingCSSFormattedWithPrefix()
    {
        $expected = $this->genSpacingCSSFormatted('myprefix-');

        $cssBuilder = new CSSBuilder('myprefix-');
        $cssBuilder->addDimensionToSelector('someClass', 'width', '300', 'px')
                   ->addDimensionToSelector('someClass', 'height', '400', 'px')
                   ->addHeightSpacingToSelector('someClass', '18');
        $result = $cssBuilder->build(true);
        $this->assertEquals($expected, $result);
    }

    public function testTopRightBottomLeftCSSFormattedWithPrefix()
    {
        $expected = $this->genMarginCSSFormatted('myprefix-');

        $cssBuilder = new CSSBuilder('myprefix-');
        $cssBuilder->addTopRightBottomLeftToSelector('someClass', 'margin', null, '10', 5, 0, 'px')
                   ->addDimensionToSelector('someClass', 'height', '400', 'px');
        $result = $cssBuilder->build(true);
        $this->assertEquals($expected, $result);
    }

    public function testArrayCSSFormattedWithPrefix()
    {
        $expected = $this->genArrayCSSFormatted('myprefix-');

        $cssBuilder = new CSSBuilder('myprefix-');
        $cssBuilder->addToSelector(array('someClass1', 'someClass2'), 'width', '300px')
                   ->addToSelector(array('someClass1', 'someClass2'), 'height', '400px')
                   ->addToSelector('someClass1', 'color', '#fff');
        $result = $cssBuilder->build(true);
        $this->assertEquals($expected, $result);
    }
}
