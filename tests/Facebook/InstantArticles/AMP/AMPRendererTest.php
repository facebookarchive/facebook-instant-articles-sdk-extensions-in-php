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

        $renderer = AMPArticle::create($html_file, array('lang' => 'en-US'));
        $amp_rendered = $renderer->render(null, true);

        $amp_expected = file_get_contents(__DIR__ . '/amp-example.html');
        libxml_use_internal_errors(true);
        $document = new \DOMDocument();
        $document->loadHTML($amp_expected);
        libxml_use_internal_errors(false);

        //$this->assertEquals($amp_expected, $amp_rendered);
        //  var_dump($amp_rendered);
    }
}
