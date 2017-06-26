<?php
/**
 * Copyright (c) 2017-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */
namespace Facebook\InstantArticles\AMP;


use Facebook\InstantArticles\Elements\InstantArticle;
use Facebook\InstantArticles\Elements\Header;
use Facebook\InstantArticles\Elements\Image;
use Facebook\InstantArticles\Elements\Time;
use Facebook\InstantArticles\Elements\Author;
use Facebook\InstantArticles\Elements\Caption;
use PHPUnit\Framework;

class AMPCoverImageTest extends Framework\TestCase
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

    private function genInstantArticle()
    {
        return InstantArticle::create()
            ->withHeader(
                Header::create()
                    ->withTitle('Big Top Title')
                    ->withSubTitle('Smaller SubTitle')
                    ->withPublishTime(
                        Time::create(Time::PUBLISHED)
                            ->withDatetime(
                                \DateTime::createFromFormat(
                                    'j-M-Y G:i:s',
                                    '14-Aug-1984 19:30:00'
                                )
                            )
                    )
                    ->addAuthor(
                        Author::create()
                            ->withName('Author One')
                            ->withDescription('Passionate coder and mountain biker')
                    )
                    ->addAuthor(
                        Author::create()
                            ->withName('Author Two')
                            ->withDescription('Weend surfer with heavy weight coding skils')
                            ->withURL('http://facebook.com/author')
                    )
                    ->withKicker('Some kicker of this article')
                    ->withCover(
                        Image::create()
                            ->withURL('http://blog.wod.expert/wp-content/uploads/2017/03/fail1.jpg')
                    )
            );
    }

    private function genContext($document, $instantArticle)
    {
        $context = AMPContext::create($document, $instantArticle);

        $mediaSizes = array();
        $mediaCacheFolder = __DIR__ . '/articles/media-cache';
        $enableDownloadForMediaSizing = false;
        $defaultWidth = 1000;
        $defaultHeight = 900;

        $context->withMediaSizingSetup($mediaSizes, $mediaCacheFolder, $enableDownloadForMediaSizing, $defaultWidth, $defaultHeight);

        return $context;
    }

    public function testBuildCoverWithCaption()
    {
        $expected =
            '<div class="ia2amp-cover-image">'.
                '<figure class="ia2amp-figure">'.
                    '<amp-img class="ia2amp-header-cover-img" src="http://blog.wod.expert/wp-content/uploads/2017/03/fail1.jpg" width="422" height="240"/>'.
                    '<figcaption class="ia2amp-figcaption ia2amp-op-small">Some caption to the image</figcaption>'.
                '</figure>'.
            '</div>';
        $instantArticle = $this->genInstantArticle();
        $instantArticle->getHeader()->getCover()->withCaption(
            Caption::create()
                ->appendText('Some caption to the image')
        );

        $document = new \DOMDocument();
        $context = $this->genContext($document, $instantArticle);

        $result = AMPCoverImage::create($instantArticle->getHeader()->getCover(), $context, 'cover-image')->build();
        $this->assertEquals($expected, $result->ownerDocument->saveXML($result));
    }

    public function testBuildCoverWithoutCaption()
    {
        $expected =
            '<div class="ia2amp-cover-image">'.
                '<amp-img class="ia2amp-header-cover-img" src="http://blog.wod.expert/wp-content/uploads/2017/03/fail1.jpg" width="422" height="240"/>'.
            '</div>';
        $instantArticle = $this->genInstantArticle();

        $document = new \DOMDocument();
        $context = $this->genContext($document, $instantArticle);

        $result = AMPCoverImage::create($instantArticle->getHeader()->getCover(), $context, 'cover-image')->build();
        $this->assertEquals($expected, $result->ownerDocument->saveXML($result));
    }
}
