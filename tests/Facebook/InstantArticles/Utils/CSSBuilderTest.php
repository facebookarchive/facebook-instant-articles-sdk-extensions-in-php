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

class CSSBuilderTest extends \PHPUnit_Framework_TestCase
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

    public function genSimpleCSSFormatted()
    {
        return
            ".someClass {\n".
            "    width: 300px;\n".
            "    height: 400px;\n".
            "}";
    }

    public function genSimpleCSS()
    {
        return
            ".someClass {".
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

        $cssBuilder = new CSSBuilder();
        $cssBuilder->addProperty('.someClass', 'width', '300px')
                   ->addProperty('.someClass', 'height', '400px');
        $result = $cssBuilder->build(true);
        $this->assertEquals($expected, $result);
    }
}
