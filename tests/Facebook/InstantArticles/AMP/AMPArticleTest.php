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

use Aws\S3\S3Client;
use Aws\Common\Aws;

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
        $html_file = file_get_contents(__DIR__ . '/test1-instant-article.html');

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

    public function testTransformIAtoAMPTest1()
    {
        $this->runIAtoAMPTest('test1');
    }

    public function testTransformIAtoAMPTest2()
    {
        $this->runIAtoAMPTest('test2');
    }

    public function testTransformIAtoAMPTest3()
    {
        $this->runIAtoAMPTest('test3');
    }

    public function testTransformIAtoAMPTest4()
    {
        $this->runIAtoAMPTest('test4');
    }

    public function testTransformIAtoAMPTest5()
    {
        $this->runIAtoAMPTest('test5');
    }

    public function testTransformIAtoAMPTestNatGeo()
    {
        $this->runIAtoAMPTest('natgeo');
    }

    private function getRenderer($test, $customProperties = null) {
        $html_file = file_get_contents(__DIR__ . '/'.$test.'-instant-article.html');

        $propeties = array(
            'lang' => 'en-US',
            'header-logo-image-url' => 'http://blog.wod.expert/wp-content/uploads/2017/03/wod-expert-horizontal@033x.png',
            'header-logo-image-width' => '132',
            'header-logo-image-height' => '26',
            AMPArticle::STYLES_FOLDER_KEY => __DIR__,
        );
        if (!is_null($customProperties)) {
            $propeties = array_merge($propeties, $customProperties);
        }

        return AMPArticle::create($html_file, $propeties);
    }

    private function getRenderedAMP($test) {
        $renderer = $this->getRenderer($test);
        
        return $renderer->render(null, true)."\n";
    }

    public function runIAtoAMPTest($test)
    {
        $amp_rendered = $this->getRenderedAMP($test);

        $amp_expected = file_get_contents(__DIR__ . '/'.$test.'-amp-converted.html');
        $this->assertEquals($amp_expected, $amp_rendered);

        // Sets content into the file for fast testing
        // file_put_contents(__DIR__ . '/'.$test.'-amp-converted.html', $amp_rendered);

        // URL of file: https://s3.amazonaws.com/wodexpert/test1-amp-converted.html
        // AMP url for testing: https://search.google.com/search-console/amp
        $this->uploadToS3(__DIR__ . '/'.$test.'-amp-converted.html', ''.$test.'-amp-converted.html');
    }

    public function testArticleHasSingleLdJsonScript() {
        $amp_rendered = $this->getRenderedAMP('test1');
        
        libxml_use_internal_errors(true);
        $renderedDocument = new \DOMDocument();
        $renderedDocument->loadHTML($amp_rendered);
        libxml_use_internal_errors(false);
        $xPath = new \DOMXPath($renderedDocument);

        $this->assertEquals(1, $xPath->query('//script[@type="application/ld+json"]')->length);
    }

    private function getDiscoveryMetadata($test) {
        $renderer = $this->getRenderer($test);

        $discoveryMetadataContent = $renderer->buildSchemaOrgMetadata();
        return json_decode($discoveryMetadataContent, true);
    }

    private function verifySchemaOrgHasExpectedValue($key, $expectedValue, $test = 'test1') {
        $discoveryMetadata = $this->getDiscoveryMetadata($test);

        $this->assertArrayHasKey($key, $discoveryMetadata, "Could not find $key key in Schema.org metadata");
        $this->assertEquals($expectedValue, $discoveryMetadata[$key], "Unexpected value found for $key");
    }

    private function verifySchemaOrgDoesNotHaveKey($key, $test = 'test1') {
        $discoveryMetadata = $this->getDiscoveryMetadata($test);

        $this->assertFalse(array_key_exists($key, $discoveryMetadata), "Found unexpected $key key in Schema.org metadata");
    }

    public function testSchemaOrgContext() {
        $this->verifySchemaOrgHasExpectedValue('@content', 'http://schema.org');
    }

    public function testSchemaOrgType() {
        $this->verifySchemaOrgHasExpectedValue('@type', 'NewsArticle');
    }

    public function testSchemaOrgMainEntityOfPage() {
        $this->verifySchemaOrgHasExpectedValue('mainEntityOfPage', 'http://blog.wod.expert/very-first-wod/');
    }

    public function testSchemaOrgHeadline() {
        $this->verifySchemaOrgHasExpectedValue('headline', 'Very First WOD!');
    }

    public function testSchemaOrgDatePublished() {
        $this->verifySchemaOrgHasExpectedValue('datePublished', '2016-05-10T18:05:36+00:00');
    }

    public function testSchemaOrgDateModified() {
        $this->verifySchemaOrgHasExpectedValue('dateModified', '2017-03-17T16:46:07+00:00');
    }

    public function testSchemaOrgNoDateModified() {
        $this->verifySchemaOrgDoesNotHaveKey('dateModified', 'natgeo');
    }

    public function testSchemaOrgAuthor() {
        $expectedAuthor = array(
            '@type' => 'Person',
            'name' => 'Éverton Rosário',
        );

        $this->verifySchemaOrgHasExpectedValue('author', $expectedAuthor);
    }

    public function testSchemaOrgImage() {
        $expectedImage = array(
            '@type' => 'ImageObject',
            'url' => 'http://blog.wod.expert/wp-content/uploads/2017/03/fail1.jpg',
            'width' => 380,
            'height' => 240,
        );

        $this->verifySchemaOrgHasExpectedValue('image', $expectedImage);
    }

    /**
    * @dataProvider testToRGBDataProvider
    */
    public function testToRG($hexColor, $expected)
    {
        $rgb = AMPArticle::toRGB($hexColor);
        $this->assertEquals($expected, $rgb);
    }

    public function testToRGBDataProvider()
    {
        return array(
            array('FFAABB', 'rgb(255,170,187)'),
            array('#FFAABB', 'rgb(255,170,187)'),
            array('#FFFFAABB', 'rgb(255,170,187)'),
            array('EEFFAABB', 'rgba(255,170,187,0.93)'),
            array('#EEFFAABB', 'rgba(255,170,187,0.93)'),
        );
    }

    public function uploadToS3($fileToUpload, $fileNameToStoreAtS3)
    {
        $awsClient = S3Client::factory(array(
            'credentials' => array(
                'key'    => 'AKIAIA5UXSRCJTQL66QA',
                'secret' => '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',
            ),
            'region'     => 'us-east-1',
            'version'    => '2006-03-01',
        ));

        $awsClient->putObject(array(
            'Bucket'     => 'wodexpert',
            'Key'        => $fileNameToStoreAtS3,
            'SourceFile' => $fileToUpload,
            'ACL'        => 'public-read'
        ));
    }
}
