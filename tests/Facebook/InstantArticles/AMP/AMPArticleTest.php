<?php
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */
namespace Facebook\InstantArticles\AMP;

use Facebook\InstantArticles\Elements\InstantArticle;
use Facebook\InstantArticles\AMP\AMPArticle;
use Facebook\InstantArticles\Parser\Parser;

class AMPArticleTest extends \PHPUnit_Framework_TestCase
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

    public function testParseIA()
    {
        $html_file = file_get_contents(__DIR__ . '/instant-article-example.html');

        libxml_use_internal_errors(true);
        $document = new \DOMDocument();
        $document->loadHTML($html_file);
        libxml_use_internal_errors(false);

        $parser = new Parser();
        $instant_article = $parser->parse($document);
        $instant_article->addMetaProperty('op:generator:version', '1.0.0');
        $instant_article->addMetaProperty('op:generator:transformer:version', '1.0.0');
        $result = $instant_article->render('', true)."\n";

        $this->assertEquals($html_file, $result);
    }

    public function testTransformIAtoAMP()
    {
        $html_file = file_get_contents(__DIR__ . '/instant-article-example.html');

        $renderer = AMPArticle::create(
            $html_file,
            array(
                'lang' => 'en-US',
                'header-logo-image-url' => 'https://scontent.xx.fbcdn.net/v/t39.5687-6/16685642_1354057641326734_3025298852186947584_n.png?_nc_log=1&oh=d1182aec9d7615eb9bf49b4d9833d52c&oe=59689087',
                'header-logo-image-width' => '132',
                'header-logo-image-height' => '26'
            ));
        $amp_rendered = $renderer->render(null, true)."\n";

        $amp_expected = file_get_contents(__DIR__ . '/amp-converted.html');
        libxml_use_internal_errors(true);
        $document = new \DOMDocument();
        $document->loadHTML($amp_expected);
        libxml_use_internal_errors(false);

        $this->assertEquals($amp_expected, $amp_rendered);
        // var_dump($amp_rendered);
    }

    /**
    * @dataProvider testToRGBDataProvider
    */
    public function testToRG($hexColor, $expected)
    {
        $rgb = AMPArticle::toRGB($hexColor);
        $this->assertEquals($expected, $rgb);
    }

    public function testToRGBDataProvider() {
        return array(
            array('FFAABB', 'rgb(255,170,187)'),
            array('#FFAABB', 'rgb(255,170,187)'),
            array('#FFFFAABB', 'rgb(255,170,187)'),
            array('EEFFAABB', 'rgba(255,170,187,0.93)'),
            array('#EEFFAABB', 'rgba(255,170,187,0.93)'),
        );
    }
}
