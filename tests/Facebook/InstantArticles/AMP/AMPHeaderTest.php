<?php
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */
namespace Facebook\InstantArticles\AMP;

use Facebook\InstantArticles\AMP\AMPHeader;
use Facebook\InstantArticles\AMP\AMPImage;
use Facebook\InstantArticles\AMP\AMPArticle;

class AMPHeaderTest extends \PHPUnit_Framework_TestCase
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
        $html_file = file_get_contents(__DIR__ . '/articles/'.$test.'-instant-article.html');
        $propeties = array(
          'lang' => 'en-US',
          AMPArticle::STYLES_FOLDER_KEY => __DIR__,
          AMPArticle::ENABLE_DOWNLOAD_FOR_MEDIA_SIZING_KEY => false,
        );
        if (!is_null($customProperties)) {
            $propeties = array_merge($propeties, $customProperties);
        }

        return AMPArticle::create($html_file, $propeties);
    }

    private function genContext()
    {
        $renderer = $this->getRenderer('test1', null);
        $context = AMPContext::create(
            new \DOMDocument(),
            $renderer->getInstantArticle(),
            'ia2amp-'
        );
        return $context;
    }

    private function genTestingHeader()
    {
        $context = $this->genContext();
        $header = $context->createElement('header', $context->getBody(), 'header');

        $this->testHeader = new AMPHeader($header, $this->logo, $context);
        $target = $this->testHeader->build();

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

        $this->assertEquals($logo->getAttribute("src"), "http://blog.wod.expert/wp-content/uploads/2017/04/wod-expert-amp-org-logo.png");
        $this->assertEquals($logo->getAttribute("width"), 600);
        $this->assertEquals($logo->getAttribute("height"), 60);
        $this->assertEquals($kicker, "motivational");
        $this->assertEquals($title, "Very First WOD!");
        $this->assertEquals($publicationDate, "May 10, 2016");
        $this->assertEquals($byLine, "BY Éverton Rosário");

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
