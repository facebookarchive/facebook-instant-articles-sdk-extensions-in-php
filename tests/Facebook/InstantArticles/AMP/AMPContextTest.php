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
use Facebook\InstantArticles\Elements\Paragraph;
use PHPUnit\Framework;

class AMPContextTest extends Framework\TestCase
{
    public function testContextCreation()
    {
        $context = AMPContext::create(new \DOMDocument(), InstantArticle::create());
        $this->assertNotNull($context);
    }

    public function testContextCreationErrorDocument()
    {
        $this->setExpectedException('InvalidArgumentException');
        $context = AMPContext::create("new \DOMDocument()", InstantArticle::create());
    }

    public function testContextCreationErrorIA()
    {
        $this->setExpectedException('InvalidArgumentException');
        $context = AMPContext::create(new \DOMDocument(), Paragraph::create());
    }

    public function testCreatingHtml()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $context->withHtml($document->createElement('html'));
        $this->assertTrue($context->hasHtml());
    }

    public function testCreatingHtmlEmpty()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $this->assertFalse($context->hasHtml());
    }

    public function testCreatingHtmlInvalid()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $this->setExpectedException('InvalidArgumentException', 'Tag <html> expected, <script> informed.');
        $context->withHtml($document->createElement('script'));
    }

    public function testCreatingHead()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $context->withHead($document->createElement('head'));
        $this->assertTrue($context->hasHead());
    }

    public function testCreatingHeadEmpty()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $this->assertFalse($context->hasHead());
    }

    public function testCreatingHeadInvalid()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $this->setExpectedException('InvalidArgumentException', 'Tag <head> expected, <script> informed.');
        $context->withHead($document->createElement('script'));
    }

    public function testCreatingBody()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $context->withBody($document->createElement('body'));
        $this->assertTrue($context->hasBody());
    }

    public function testCreatingBodyEmpty()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $this->assertFalse($context->hasBody());
    }

    public function testCreatingBodyInvalid()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $this->setExpectedException('InvalidArgumentException', 'Tag <body> expected, <script> informed.');
        $context->withBody($document->createElement('script'));
    }

    public function testCreatingArticle()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $context->withArticle($document->createElement('article'));
        $this->assertTrue($context->hasArticle());
    }

    public function testCreatingArticleEmpty()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $this->assertFalse($context->hasArticle());
    }

    public function testCreatingArticleInvalid()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $this->setExpectedException('InvalidArgumentException', 'Tag <article> expected, <script> informed.');
        $context->withArticle($document->createElement('script'));
    }

    public function testCreatingHeader()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $context->withHeader($document->createElement('header'));
        $this->assertTrue($context->hasHeader());
    }

    public function testCreatingHeaderEmpty()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $this->assertFalse($context->hasHeader());
    }

    public function testCreatingHeaderInvalid()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $this->setExpectedException('InvalidArgumentException', 'Tag <header> expected, <script> informed.');
        $context->withHeader($document->createElement('script'));
    }

    public function testCreatingHeaderBar()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $context->withHeaderBar($document->createElement('div'));
        $this->assertTrue($context->hasHeaderBar());
    }

    public function testCreatingHeaderBarEmpty()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $this->assertFalse($context->hasHeaderBar());
    }

    public function testCreatingHeaderBarInvalid()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $this->setExpectedException('InvalidArgumentException', 'Tag <div> expected, <script> informed.');
        $context->withHeaderBar($document->createElement('script'));
    }

    public function testCreatingHeaderBarLogo()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $context->withHeaderBarLogo($document->createElement('div'));
        $this->assertTrue($context->hasHeaderBarLogo());
    }

    public function testCreatingHeaderBarLogoEmpty()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $this->assertFalse($context->hasHeaderBarLogo());
    }

    public function testCreatingHeaderBarLogoInvalid()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $this->setExpectedException('InvalidArgumentException', 'Tag <div> expected, <script> informed.');
        $context->withHeaderBarLogo($document->createElement('script'));
    }

    public function testCreatingHeaderTitle()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $context->withHeaderTitle($document->createElement('h1'));
        $this->assertTrue($context->hasHeaderTitle());
    }

    public function testCreatingHeaderTitleEmpty()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $this->assertFalse($context->hasHeaderTitle());
    }

    public function testCreatingHeaderTitleInvalid()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $this->setExpectedException('InvalidArgumentException', 'Tag <h1> expected, <script> informed.');
        $context->withHeaderTitle($document->createElement('script'));
    }

    public function testCreatingHeaderAuthor()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $context->withHeaderAuthor($document->createElement('h3'));
        $this->assertTrue($context->hasHeaderAuthor());
    }

    public function testCreatingHeaderAuthorEmpty()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $this->assertFalse($context->hasHeaderAuthor());
    }

    public function testCreatingHeaderAuthorInvalid()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $this->setExpectedException('InvalidArgumentException', 'Tag <h3> expected, <script> informed.');
        $context->withHeaderAuthor($document->createElement('script'));
    }

    public function testCreatingHeaderKicker()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $context->withHeaderKicker($document->createElement('h2'));
        $this->assertTrue($context->hasHeaderKicker());
    }

    public function testCreatingHeaderKickerEmpty()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $this->assertFalse($context->hasHeaderKicker());
    }

    public function testCreatingHeaderKickerInvalid()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $this->setExpectedException('InvalidArgumentException', 'Tag <h2> expected, <script> informed.');
        $context->withHeaderKicker($document->createElement('script'));
    }

    public function testCreatingHeaderDate()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $context->withHeaderDate($document->createElement('h3'));
        $this->assertTrue($context->hasHeaderDate());
    }

    public function testCreatingHeaderDateEmpty()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $this->assertFalse($context->hasHeaderDate());
    }

    public function testCreatingHeaderDateInvalid()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());
        $this->setExpectedException('InvalidArgumentException', 'Tag <h3> expected, <script> informed.');
        $context->withHeaderDate($document->createElement('script'));
    }

    public function testInformedImageDimensions()
    {
        $expectedWidth = 800;
        $expectedHeight = 500;

        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());

        $mediaSizes = array('https://www.facebook.com/images/fb_icon_325x325.png' => array($expectedWidth, $expectedHeight));
        $mediaCacheFolder = __DIR__ . '/articles/media-cache';
        $enableDownloadForMediaSizing = true;
        $defaultWidth = 1000;
        $defaultHeight = 900;
        $context->withMediaSizingSetup($mediaSizes, $mediaCacheFolder, $enableDownloadForMediaSizing, $defaultWidth, $defaultHeight);

        $dimensions = $context->getMediaDimensions('https://www.facebook.com/images/fb_icon_325x325.png');

        $this->assertEquals($expectedWidth, $dimensions[0]);
        $this->assertEquals($expectedHeight, $dimensions[1]);
    }

    public function testCachedImageDimensions()
    {
        $expectedWidth = 325;
        $expectedHeight = 325;

        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());

        $mediaSizes = array();
        $mediaCacheFolder = __DIR__ . '/articles/media-cache';
        $enableDownloadForMediaSizing = true;
        $defaultWidth = 1000;
        $defaultHeight = 900;
        $context->withMediaSizingSetup($mediaSizes, $mediaCacheFolder, $enableDownloadForMediaSizing, $defaultWidth, $defaultHeight);

        $dimensions = $context->getMediaDimensions('https://www.facebook.com/images/fb_icon_325x325.png');

        $this->assertEquals($expectedWidth, $dimensions[0]);
        $this->assertEquals($expectedHeight, $dimensions[1]);
    }

    public function testImageDimensionsDownloadDisabled()
    {
        $expectedWidth = 325;
        $expectedHeight = 325;

        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());

        $mediaSizes = array();
        $mediaCacheFolder = __DIR__ . '/articles/media-cache';
        $enableDownloadForMediaSizing = false;
        $defaultWidth = 1000;
        $defaultHeight = 900;
        $context->withMediaSizingSetup($mediaSizes, $mediaCacheFolder, $enableDownloadForMediaSizing, $defaultWidth, $defaultHeight);

        $dimensions = $context->getMediaDimensions('https://www.facebook.com/images/fb_icon_325x325.png');

        $this->assertEquals($expectedWidth, $dimensions[0]);
        $this->assertEquals($expectedHeight, $dimensions[1]);
    }

    public function testImageDimensionsDownloadEnabled()
    {
        $expectedWidth = 325;
        $expectedHeight = 325;

        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());

        $mediaSizes = array();
        $mediaCacheFolder = null;
        $enableDownloadForMediaSizing = true;
        $defaultWidth = 1000;
        $defaultHeight = 900;
        $context->withMediaSizingSetup($mediaSizes, $mediaCacheFolder, $enableDownloadForMediaSizing, $defaultWidth, $defaultHeight);

        $dimensions = $context->getMediaDimensions('https://www.facebook.com/images/fb_icon_325x325.png', AMPContext::MEDIA_TYPE_IMAGE);

        $this->assertEquals($expectedWidth, $dimensions[0]);
        $this->assertEquals($expectedHeight, $dimensions[1]);
    }

    public function testImageDimensionsDefault()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());

        $mediaSizes = array();
        $mediaCacheFolder = null;
        $enableDownloadForMediaSizing = false;
        $defaultWidth = 1000;
        $defaultHeight = 900;
        $context->withMediaSizingSetup($mediaSizes, $mediaCacheFolder, $enableDownloadForMediaSizing, $defaultWidth, $defaultHeight);

        $dimensions = $context->getMediaDimensions('https://www.facebook.com/images/fb_icon_325x325.png', AMPContext::MEDIA_TYPE_IMAGE);

        $this->assertEquals($defaultWidth, $dimensions[0]);
        $this->assertEquals($defaultHeight, $dimensions[1]);
    }

    public function testInformedVideoDimensions()
    {
        $expectedWidth = 800;
        $expectedHeight = 500;

        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());

        $mediaSizes = array('http://ngm.nationalgeographic.com/2015/05/building-bees/v/timelapse-final-4x3.mp4' => array($expectedWidth, $expectedHeight));
        $mediaCacheFolder = __DIR__ . '/articles/media-cache';
        $enableDownloadForMediaSizing = true;
        $defaultWidth = 1000;
        $defaultHeight = 900;
        $context->withMediaSizingSetup($mediaSizes, $mediaCacheFolder, $enableDownloadForMediaSizing, $defaultWidth, $defaultHeight);

        $dimensions = $context->getMediaDimensions('http://ngm.nationalgeographic.com/2015/05/building-bees/v/timelapse-final-4x3.mp4', AMPContext::MEDIA_TYPE_VIDEO);

        $this->assertEquals($expectedWidth, $dimensions[0]);
        $this->assertEquals($expectedHeight, $dimensions[1]);
    }

    public function testCachedVideoDimensions()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());

        $mediaSizes = array();
        $mediaCacheFolder = __DIR__ . '/articles/media-cache';
        $enableDownloadForMediaSizing = true;
        $defaultWidth = 1000;
        $defaultHeight = 900;
        $context->withMediaSizingSetup($mediaSizes, $mediaCacheFolder, $enableDownloadForMediaSizing, $defaultWidth, $defaultHeight);

        $dimensions = $context->getMediaDimensions('http://ngm.nationalgeographic.com/2015/05/building-bees/v/timelapse-final-4x3.mp4', AMPContext::MEDIA_TYPE_VIDEO);

        $this->assertEquals($defaultWidth, $dimensions[0]);
        $this->assertEquals($defaultHeight, $dimensions[1]);
    }

    public function testVideoDimensionsDownloadDisabled()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());

        $mediaSizes = array();
        $mediaCacheFolder = __DIR__ . '/articles/media-cache';
        $enableDownloadForMediaSizing = false;
        $defaultWidth = 1000;
        $defaultHeight = 900;
        $context->withMediaSizingSetup($mediaSizes, $mediaCacheFolder, $enableDownloadForMediaSizing, $defaultWidth, $defaultHeight);

        $dimensions = $context->getMediaDimensions('http://ngm.nationalgeographic.com/2015/05/building-bees/v/timelapse-final-4x3.mp4', AMPContext::MEDIA_TYPE_VIDEO);

        $this->assertEquals($defaultWidth, $dimensions[0]);
        $this->assertEquals($defaultHeight, $dimensions[1]);
    }

    public function testVideoDimensionsDownloadEnabled()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());

        $mediaSizes = array();
        $mediaCacheFolder = null;
        $enableDownloadForMediaSizing = true;
        $defaultWidth = 1000;
        $defaultHeight = 900;
        $context->withMediaSizingSetup($mediaSizes, $mediaCacheFolder, $enableDownloadForMediaSizing, $defaultWidth, $defaultHeight);

        $dimensions = $context->getMediaDimensions('http://ngm.nationalgeographic.com/2015/05/building-bees/v/timelapse-final-4x3.mp4', AMPContext::MEDIA_TYPE_VIDEO);

        $this->assertEquals($defaultWidth, $dimensions[0]);
        $this->assertEquals($defaultHeight, $dimensions[1]);
    }

    public function testVideoDimensionsDefault()
    {
        $document = new \DOMDocument();
        $context = AMPContext::create($document, InstantArticle::create());

        $mediaSizes = array();
        $mediaCacheFolder = null;
        $enableDownloadForMediaSizing = false;
        $defaultWidth = 1000;
        $defaultHeight = 900;
        $context->withMediaSizingSetup($mediaSizes, $mediaCacheFolder, $enableDownloadForMediaSizing, $defaultWidth, $defaultHeight);

        $dimensions = $context->getMediaDimensions('http://ngm.nationalgeographic.com/2015/05/building-bees/v/timelapse-final-4x3.mp4', AMPContext::MEDIA_TYPE_VIDEO);

        $this->assertEquals($defaultWidth, $dimensions[0]);
        $this->assertEquals($defaultHeight, $dimensions[1]);
    }

    //
    // public function testVideoHeightDefaultProperty()
    // {
    //     $expectedHeight = 120;
    //     $customProperties = array(
    //         AMPArticle::ENABLE_DOWNLOAD_FOR_MEDIA_SIZING_KEY => false,
    //         AMPArticle::DEFAULT_MEDIA_HEIGHT_KEY => $expectedHeight,
    //     );
    //
    //     $videoXPathQuery = $this->getRenderedMarkupXPathQuery(
    //         'natgeo',
    //         '//amp-video',
    //         $customProperties
    //     );
    //     $firstArticleVideoElement = $videoXPathQuery->item(0);
    //
    //     $this->assertEquals($expectedHeight, $firstArticleVideoElement->getAttribute('height'));
    // }
    //
    // public function testVideoWidthFromMediaSizes()
    // {
    //     $expectedWidth = 90;
    //     $customProperties = array(
    //         AMPArticle::ENABLE_DOWNLOAD_FOR_MEDIA_SIZING_KEY => false,
    //         AMPArticle::MEDIA_SIZES_KEY => array(
    //             "http://ngm.nationalgeographic.com/2015/05/building-bees/v/timelapse-final-4x3.mp4" => array($expectedWidth, 60),
    //         ),
    //     );
    //
    //     $videoXPathQuery = $this->getRenderedMarkupXPathQuery(
    //         'natgeo',
    //         '//amp-video',
    //         $customProperties
    //     );
    //     $firstArticleVideoElement = $videoXPathQuery->item(0);
    //
    //     $this->assertEquals($expectedWidth, $firstArticleVideoElement->getAttribute('width'));
    // }
    //
    // public function testVideoHeightFromMediaSizes()
    // {
    //     $expectedHeight = 60;
    //     $customProperties = array(
    //         AMPArticle::ENABLE_DOWNLOAD_FOR_MEDIA_SIZING_KEY => false,
    //         AMPArticle::MEDIA_SIZES_KEY => array(
    //             "http://ngm.nationalgeographic.com/2015/05/building-bees/v/timelapse-final-4x3.mp4" => array(90, $expectedHeight),
    //         ),
    //     );
    //
    //     $videoXPathQuery = $this->getRenderedMarkupXPathQuery(
    //         'natgeo',
    //         '//amp-video',
    //         $customProperties
    //     );
    //     $firstArticleVideoElement = $videoXPathQuery->item(0);
    //
    //     $this->assertEquals($expectedHeight, $firstArticleVideoElement->getAttribute('height'));
    // }
}
