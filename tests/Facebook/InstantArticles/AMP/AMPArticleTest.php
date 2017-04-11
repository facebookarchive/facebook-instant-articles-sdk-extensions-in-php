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

    private function getRenderer($test, $customProperties = null)
    {
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

    private function getRenderedAMP($test, $customProperties = null)
    {
        $renderer = $this->getRenderer($test, $customProperties);
        
        return $renderer->render(null, true)."\n";
    }

    private function getMarkupWithoutStyles($markup)
    {
        libxml_use_internal_errors(true);
        $markupDocument = new \DOMDocument();
        $markupDocument->loadHTML($markup);
        libxml_use_internal_errors(false);

        $xPath = new \DOMXPath($markupDocument);
        if ($customStyle = $xPath->query('//style[@amp-custom]')->item(0)) {
            $customStyle->parentNode->removeChild($customStyle);
        }
        return $markupDocument->saveHTML();
    }

    private function compareIgnoringStyles($ampExpected, $ampRendered)
    {
        $ampExpectedNoStyles = $this->getMarkupWithoutStyles($ampExpected);
        $ampRenderedNoStyles = $this->getMarkupWithoutStyles($ampRendered);

        $this->assertEquals($ampExpectedNoStyles, $ampRenderedNoStyles);
    }

    public function runIAtoAMPTest($test)
    {
        $ampRendered = $this->getRenderedAMP($test);

        $ampExpected = file_get_contents(__DIR__ . '/'.$test.'-amp-converted.html');
        $this->compareIgnoringStyles($ampExpected, $ampRendered);

        // Sets content into the file for fast testing
        // file_put_contents(__DIR__ . '/'.$test.'-amp-converted.html', $amp_rendered);

        // URL of file: https://s3.amazonaws.com/wodexpert/test1-amp-converted.html
        // AMP url for testing: https://search.google.com/search-console/amp
        // $this->uploadToS3(__DIR__ . '/'.$test.'-amp-converted.html', ''.$test.'-amp-converted.html');
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

    private function getDefaultStyles()
    {
        $jsonStyles = file_get_contents(__DIR__ . '/default.style.json');
        return json_decode($jsonStyles, true);
    }

    public function testBackgroundColorStyle()
    {
        $hexColor = AMPArticleTest::getRandomHexColor();
        
        $defaultStyles = $this->getDefaultStyles();
        $defaultStyles['background_color'] = $hexColor;

        $customProperties = array(
            AMPArticle::OVERRIDE_STYLES_KEY => $defaultStyles,
        );

        $renderer = $this->getRenderer('test1', $customProperties);
        $css = $renderer->getCustomCSS();

        // Escape parenthesis before using regex
        $expectedValue = str_replace(')', '\)', str_replace('(', '\(', AMPArticle::toRGB($hexColor)));
        $this->validateCSSRule($css, 'html', 'background-color', $expectedValue);
    }

    public function testKickerFontFamily()
    {
        $this->validateRandomSecondLevelProperty('kicker', 'font', '.ia2amp-header-category', 'font-family');
    }

    public function testKickerColor()
    {
        $hexColor = AMPArticleTest::getRandomHexColor();
        // Escape parenthesis before using regex
        $expectedValue = str_replace(')', '\)', str_replace('(', '\(', AMPArticle::toRGB($hexColor)));
        $this->validateSecondLevelProperty('kicker', 'color', '.ia2amp-header-category', 'color', $hexColor, $expectedValue);
    }

    public function testKickerBackgroundColor()
    {
        $hexColor = AMPArticleTest::getRandomHexColor();
        // Escape parenthesis before using regex
        $expectedValue = str_replace(')', '\)', str_replace('(', '\(', AMPArticle::toRGB($hexColor)));
        $this->validateSecondLevelProperty('kicker', 'background_color', '.ia2amp-header-category', 'background-color', $hexColor, $expectedValue);
    }

    /**
    * @dataProvider testKickerTextTransformDataProvider
    */
    public function testKickerTextTransform($styleValue, $expectedValue)
    {
        $this->validateSecondLevelProperty('kicker', 'capitalization', '.ia2amp-header-category', 'text-transform', $styleValue, $expectedValue);
    }

    public function testKickerTextTransformDataProvider()
    {
        return array(
            array('ALL_CAPS', 'uppercase'),
            array('NONE', 'none'),
            array('ALL_LOWER_CASE', 'lowercase'),
        );
    }

    /**
    * @dataProvider testKickerTextAlignmentDataProvider
    */
    public function testKickerTextAlignment($value)
    {
        $this->validateSecondLevelProperty('kicker', 'text_alignment', '.ia2amp-header-category', 'text-align', $value, $value);
    }

    public function testKickerTextAlignmentDataProvider()
    {
        return array(
            array('LEFT'),
            array('CENTER'),
            array('RIGHT'),
        );
    }

    /**
    * @dataProvider testKickerDisplayDataProvider
    */
    public function testKickerDisplay($value)
    {
        $this->validateSecondLevelProperty('kicker', 'display', '.ia2amp-header-category', 'display', $value, $value);
    }

    public function testKickerDisplayDataProvider()
    {
        return array(
            array('INLINE'),
            array('BLOCK'),
        );
    }

    /**
    * @dataProvider testKickerSpacingDataProvider
    */
    public function testKickerSpacing($spacing, $size, $baseSpacingValue, $direction, $spacingFormat)
    {
        $scalingFactor = rand(0, 1000) / 1000 + 0.5;
        $directionSpacing = array(
            'scaling_factor' => $scalingFactor,
            'size' => $size,
        );
        $spacingStyle = array(
            $direction => $directionSpacing,
        );

        $expectedSpacing = $baseSpacingValue * $scalingFactor;
        $expectedValue  = sprintf($spacingFormat, $expectedSpacing);

        $this->validateSecondLevelProperty('kicker', $spacing, '.ia2amp-header-category', $spacing, $spacingStyle, $expectedValue);
    }

    public function testKickerSpacingDataProvider()
    {
        // TODO: Create unit tests for other margin and padding values
        return array(
            array('margin', 'DOCUMENT_MARGIN', AMPArticle::DEFAULT_MARGIN, 'right', '0 %spx 0 0'),
            array('margin', 'DOCUMENT_MARGIN', AMPArticle::DEFAULT_MARGIN, 'left', '0 0 0 %spx'),

            array('margin', 'NONE', 0, 'right', '0 0 0 0'),
            array('margin', 'NONE', 0, 'left', '0 0 0 0'),

            array('padding', 'NONE', 0, 'top', '0 0 0 0'),
            array('padding', 'NONE', 0, 'right', '0 0 0 0'),
            array('padding', 'NONE', 0, 'bottom', '0 0 0 0'),
            array('padding', 'NONE', 0, 'left', '0 0 0 0'),

            array('padding', 'MEDIUM', 46, 'top', '%spx 0 0 0'),
            array('padding', 'MEDIUM', 46, 'right', '0 %spx 0 0'),
            array('padding', 'MEDIUM', 46, 'bottom', '0 0 %spx 0'),
            array('padding', 'MEDIUM', 46, 'left', '0 0 0 %spx'),
        );
    }

    /**
    * @dataProvider testKickerBorderColorDataProvider
    */
    public function testKickerBorderColor($direction)
    {
        $width = rand(0, 100);
        $hexColor = AMPArticleTest::getRandomHexColor();
        $directionBorder = array(
            'color' => $hexColor,
            'width' => $width,
        );
        $borderStyle = array(
            $direction => $directionBorder,
        );

        // Escape parenthesis before using regex
        $expectedValue = str_replace(')', '\)', str_replace('(', '\(', AMPArticle::toRGB($hexColor)));
        // TODO: Enable once implemented
        // $this->validateSecondLevelProperty('kicker', 'border', '.ia2amp-header-category', 'border-color', $borderStyle, $expectedValue);
    }

    public function testKickerBorderColorDataProvider()
    {
        return array(
            array('top'),
            array('right'),
            array('bottom'),
            array('left'),
        );
    }

    /**
    * @dataProvider testKickerBorderWidthDataProvider
    */
    public function testKickerBorderWidth($direction, $borderFormat)
    {
        $width = rand(0, 100);
        $directionBorder = array(
            'color' => '#000000',
            'width' => $width,
        );
        $borderStyle = array(
            $direction => $directionBorder,
        );

        $expectedValue  = sprintf($borderFormat, $width !== 0 ? $width . 'px' : 0);
        $this->validateSecondLevelProperty('kicker', 'border', '.ia2amp-header-category', 'border-width', $borderStyle, $expectedValue);
    }

    public function testKickerBorderWidthDataProvider()
    {
        return array(
            array('top', '%s 0 0 0'),
            array('right', '0 %s 0 0'),
            array('bottom', '0 0 %s 0'),
            array('left', '0 0 0 %s'),
        );
    }

    private function validateRandomSecondLevelProperty($firstLevelKey, $secondLevelKey, $cssSelector, $cssProperty)
    {
        $randomValue = rand();
        $this->validateSecondLevelProperty($firstLevelKey, $secondLevelKey, $cssSelector, $cssProperty, $randomValue, $randomValue);
    }

    private function validateSecondLevelProperty($firstLevelKey, $secondLevelKey, $cssSelector, $cssProperty, $styleValue, $expectedCSSValue)
    {
        $defaultStyles = $this->getDefaultStyles();
        $defaultStyles[$firstLevelKey][$secondLevelKey] = $styleValue;

        $customProperties = array(
            AMPArticle::OVERRIDE_STYLES_KEY => $defaultStyles,
        );

        $renderer = $this->getRenderer('test1', $customProperties);
        $css = $renderer->getCustomCSS();

        $this->validateCSSRule($css, $cssSelector, $cssProperty, $expectedCSSValue);
    }

    private function validateCSSRule($css, $selector, $property, $value)
    {
        // Escape the dot (used on class name selectors) so it is not interpreted as any character
        $selector = str_replace('.', '\.', $selector);

        $cssRulePattern = '/' . $selector. '\s*{[^}]*'. $property . ':\s*' . $value . ';/';
        $this->assertEquals(1, preg_match($cssRulePattern, $css), "Could not find CSS rule '$property' for selector '$selector'");
    }

    private static function getRandomHexColor()
    {
        $red = rand(0, 255);
        $green = rand(0, 255);
        $blue = rand(0, 255);
        
        return '#' . str_pad(dechex($red), 2, '0', STR_PAD_LEFT) .
                    str_pad(dechex($green), 2, '0', STR_PAD_LEFT) .
                    str_pad(dechex($blue), 2, '0', STR_PAD_LEFT);
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
