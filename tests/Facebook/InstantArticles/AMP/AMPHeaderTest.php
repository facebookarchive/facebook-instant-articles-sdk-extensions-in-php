<?php
/**
 * Copyright (c) 2017-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */
namespace Facebook\InstantArticles\AMP;

use Facebook\InstantArticles\Utils\FileUtilsPHPUnitTestCase;

class AMPHeaderTest extends FileUtilsPHPUnitTestCase
{
    private $logo;
    private $testHeader;

    protected function setUp()
    {
        $url = 'http://blog.wod.expert/wp-content/uploads/2017/04/wod-expert-amp-org-logo.png';
        $width = 600;
        $height = 60;
        $this->logo = new AMPImage($url, $width, $height);
    }

    private function getRenderer($test, $customProperties = null)
    {
        $instantArticle = $this->loadInstantArticle(__DIR__ . '/articles/'.$test.'-instant-article.html');
        $properties = array(
            'lang' => 'en-US',
            AMPArticle::STYLES_FOLDER_KEY => __DIR__,
            AMPArticle::ENABLE_DOWNLOAD_FOR_MEDIA_SIZING_KEY => false,
        );
        if (!is_null($customProperties)) {
            $properties = array_merge($properties, $customProperties);
        }

        return AMPArticle::create($instantArticle, $properties);
    }

    private function genContext()
    {
        $renderer = $this->getRenderer('test1', null);
        $context = AMPContext::create(new \DOMDocument('1.0'), $renderer->getInstantArticle());

        $mediaSizes = array();
        $mediaCacheFolder = __DIR__ . '/articles/media-cache';
        $enableDownloadForMediaSizing = false;
        $defaultWidth = 1000;
        $defaultHeight = 900;

        $context->withMediaSizingSetup($mediaSizes, $mediaCacheFolder, $enableDownloadForMediaSizing, $defaultWidth, $defaultHeight);

        return $context;
    }

    private function genTestingHeader()
    {
        $context = $this->genContext();

        $this->testHeader = new AMPHeader($context);
        $target = $this->testHeader->build();
        $this->testHeader->genHeaderLogo($this->logo);

        $document = new \DOMDocument;
        $document->appendChild($document->importNode($target, true));

        return new \DOMXPath($document);
    }

    public function testBuild()
    {
        $testingHeader = $this->genTestingHeader();

        $publicationDateFetch = $testingHeader->query('//h3[@class="ia2amp-header-date"][1]')->item(0);
        $titleFetch = $testingHeader->query('//h1[@class="ia2amp-header-h1"][1]')->item(0);
        $byLineFetch = $testingHeader->query('//h3[@class="ia2amp-header-author"][1]')->item(0);
        $kickerFetch = $testingHeader->query('//h2[@class="ia2amp-header-category"][1]')->item(0);
        $logo = $testingHeader->query('//div[@class="ia2amp-header-bar-img-container"]/amp-img[1]')->item(0);

        $publicationDate = $publicationDateFetch->textContent;
        $title = $titleFetch->textContent;
        $byLine = $byLineFetch->textContent;
        $kicker = $kickerFetch->textContent;

        $this->assertEquals("http://blog.wod.expert/wp-content/uploads/2017/04/wod-expert-amp-org-logo.png", $logo->getAttribute("src"));
        $this->assertEquals(600, $logo->getAttribute("width"));
        $this->assertEquals(60, $logo->getAttribute("height"));
        $this->assertEquals("motivational", $kicker);
        $this->assertEquals("Very First WOD!", $title);
        //$this->assertEquals("May 10, 2016", $publicationDate);
        $this->assertEquals("By Éverton Rosário", $byLine);

        $spaceNodes = array(
        '//div[@class="ia2amp-header-bar"][1]',
        '//h2[@class="ia2amp-spacing after-header-date"][1]',
        '//h2[@class="ia2amp-spacing after-header-author before-header-date"][1]',
        '//div[@class="ia2amp-spacing after-header-category before-header-h1"][1]',
        '//div[@class="ia2amp-spacing after-header-h1 before-header-author"]',
        '//div[@class="ia2amp-spacing after-header-bar before-header-category"]',
        );

        foreach ($spaceNodes as $currentNode) {
              $this->assertNotEmpty($testingHeader->query($currentNode));
        }
    }
}
