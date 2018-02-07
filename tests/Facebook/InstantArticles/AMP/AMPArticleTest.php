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
use Facebook\InstantArticles\Parser\Parser;
use Facebook\InstantArticles\Utils\FileUtilsPHPUnitTestCase;

class AMPArticleTest extends FileUtilsPHPUnitTestCase
{
    public function testParseIA()
    {
        $html_file = $this->loadHTMLFile(__DIR__ . '/articles/test1-instant-article.html');
        $document = $this->loadDOMDocument(__DIR__ . '/articles/test1-instant-article.html');

        $parser = new Parser();
        $instant_article = $parser->parse($document);
        $instant_article->addMetaProperty('op:generator:version', '1.0.0');
        $instant_article->addMetaProperty('op:generator:transformer:version', '1.0.0');
        $result = $instant_article->render('', true)."\n";

        $this->assertEqualsHtml($html_file, $result);
    }

    public function testTransformIAtoAMPTest1()
    {
        $customProperties = array_merge(
            $this->getWodExpertCustomProperties(),
            array(
                'media-sizes' => array(
                    'http://blog.wod.expert/wp-content/uploads/2017/03/fail1.jpg' => array(
                        800,
                        454,
                    ),
                ),
            )
        );
        $this->runIAtoAMPTest('test1', $customProperties);
    }

    public function testTransformIAtoAMPTest2()
    {
        $customProperties = $this->getWodExpertCustomProperties();
        $this->runIAtoAMPTest('test2', $customProperties);
    }

    public function testTransformIAtoAMPTest3()
    {
        $customProperties = $this->getWodExpertCustomProperties();
        $this->runIAtoAMPTest('test3', $customProperties);
    }

    public function testTransformIAtoAMPTest4()
    {
        $customProperties = $this->getWodExpertCustomProperties();
        $this->runIAtoAMPTest('test4', $customProperties);
    }

    public function testTransformIAtoAMPTest5()
    {
        $customProperties = $this->getWodExpertCustomProperties();
        $this->runIAtoAMPTest('test5', $customProperties);
    }

    private function getWodExpertCustomProperties()
    {
        return array(
            AMPArticle::PUBLISHER_KEY => array(
                '@type' => 'Organization',
                'name' => 'WOD Expert',
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => 'http://blog.wod.expert/wp-content/uploads/2017/04/wod-expert-amp-org-logo.png',
                    'width' => 600,
                    'height' => 60,
                ),
            )
        );
    }

    public function testTransformIAtoAMPTestTutorial()
    {
        $this->runIAtoAMPTest('tutorial', array('google_maps_key'=>'123'));
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

        $this->assertEqualsHtml($ampExpectedNoStyles, $ampRenderedNoStyles);
    }

    public function runIAtoAMPTest($test, $customProperties = null)
    {
        $ampRendered = $this->getRenderedAMP($test, $customProperties);

        $ampExpected = $this->loadHTMLFile(__DIR__.'/articles/'.$test.'-amp-converted.html');
        $this->compareIgnoringStyles($ampExpected, $ampRendered);

        // Sets content into the file for double checking testing
        // file_put_contents(__DIR__.'/articles/'.$test.'-amp-converted.html', $ampRendered);
    }

    private function getRenderedMarkupXPathQuery($test, $xPathExpression, $customProperties = null)
    {
        $amp_rendered = $this->getRenderedAMP($test, $customProperties);

        $renderedDocument = $this->loadDOMDocumentFromString($amp_rendered);
        $xPath = new \DOMXPath($renderedDocument);

        return $xPath->query($xPathExpression);
    }

    public function testArticleHasSingleLdJsonScript()
    {
        $xPathQuery = $this->getRenderedMarkupXPathQuery('test1', '//script[@type="application/ld+json"]');
        $this->assertEquals(1, $xPathQuery->length);
    }

    private function getDiscoveryMetadata($test, $customProperties = null)
    {
        $renderer = $this->getRenderer($test, $customProperties);
        $context = AMPContext::create(new \DOMDocument(), $renderer->getInstantArticle(), 'ia2amp-');

        $discoveryMetadataContent = $renderer->buildSchemaOrgMetadata($context);
        return json_decode($discoveryMetadataContent, true);
    }

    private function verifySchemaOrgHasExpectedValue($key, $expectedValue, $test = 'test1', $customProperties = null)
    {
        $discoveryMetadata = $this->getDiscoveryMetadata($test, $customProperties);

        $this->assertArrayHasKey($key, $discoveryMetadata, "Could not find $key key in Schema.org metadata");
        $this->assertEquals($expectedValue, $discoveryMetadata[$key], "Unexpected value found for $key");
    }

    private function verifySchemaOrgDoesNotHaveKey($key, $test = 'test1')
    {
        $discoveryMetadata = $this->getDiscoveryMetadata($test);

        $this->assertArrayNotHasKey($key, $discoveryMetadata, "Found unexpected '$key' key in Schema.org metadata");
    }

    public function testSchemaOrgContext()
    {
        $this->verifySchemaOrgHasExpectedValue('@context', 'http://schema.org');
    }

    public function testSchemaOrgType()
    {
        $this->verifySchemaOrgHasExpectedValue('@type', 'NewsArticle');
    }

    public function testSchemaOrgMainEntityOfPage()
    {
        $this->verifySchemaOrgHasExpectedValue('mainEntityOfPage', 'http://blog.wod.expert/very-first-wod/');
    }

    public function testSchemaOrgHeadline()
    {
        $this->verifySchemaOrgHasExpectedValue('headline', 'Very First WOD!');
    }

    public function testSchemaOrgDatePublished()
    {
        $this->verifySchemaOrgHasExpectedValue('datePublished', '2016-05-10T18:05:36+00:00');
    }

    public function testSchemaOrgDateModified()
    {
        $this->verifySchemaOrgHasExpectedValue('dateModified', '2017-03-17T16:46:07+00:00');
    }

    public function testSchemaOrgHasDateModified()
    {
        $key = 'dateModified';
        $discoveryMetadata = $this->getDiscoveryMetadata('tutorial');
        $this->assertArrayHasKey($key, $discoveryMetadata, "Did not found expected '$key' key in Schema.org metadata");
    }

    public function testSchemaOrgAuthor()
    {
        $expectedAuthor = array(
            '@type' => 'Person',
            'name' => 'Éverton Rosário',
        );

        $this->verifySchemaOrgHasExpectedValue('author', $expectedAuthor);
    }

    public function testSchemaOrgImageNoCache()
    {
        $expectedImage = array(
            '@type' => 'ImageObject',
            'url' => 'http://blog.wod.expert/wp-content/uploads/2017/03/fail1.jpg',
            'width' => 380,
            'height' => 240,
        );

        $this->verifySchemaOrgHasExpectedValue('image', $expectedImage);
    }

    public function testSchemaOrgImageWithCache()
    {
        $expectedImage = array(
            '@type' => 'ImageObject',
            'url' => 'http://blog.wod.expert/wp-content/uploads/2017/03/fail1.jpg',
            'width' => 400,
            'height' => 227,
        );
        $customProperties = array(
            AMPArticle::MEDIA_CACHE_FOLDER_KEY => __DIR__ . '/articles/media-cache',
        );

        $this->verifySchemaOrgHasExpectedValue('image', $expectedImage, 'test1', $customProperties);
    }

    public function testSchemaOrgDescription()
    {
        $this->verifySchemaOrgHasExpectedValue('description', 'The first WOD we never forget! Just to be sure we are talking about same thing, WOD stands for “Workout of the Day”.  You feel you’re already gone on the warm up session.');
    }

    public function testSchemaOrgPublisherName()
    {
        $publisherName = 'The Publisher';
        $customProperties = array(AMPArticle::PUBLISHER_KEY => $publisherName);

        $expectedPublisher = array(
            '@type' => 'Organization',
            'name' => $publisherName,
        );

        $this->verifySchemaOrgHasExpectedValue('publisher', $expectedPublisher, 'test1', $customProperties);
    }

    public function testSchemaOrgPublisherArray()
    {
        $publisher = array(
            '@type' => 'Robot',
            'name' => 'The Robot',
        );
        $customProperties = array(AMPArticle::PUBLISHER_KEY => $publisher);

        $this->verifySchemaOrgHasExpectedValue('publisher', $publisher, 'test1', $customProperties);
    }

    public function testSchemaOrgNoPublisher()
    {
        $this->verifySchemaOrgDoesNotHaveKey('publisher');
    }

    private function getRenderedLogoElement($test = 'test1')
    {
        $xPathQuery = $this->getRenderedMarkupXPathQuery(
            $test,
            '//div[@class=\'ia2amp-header-bar-img-container\']/amp-img'
        );

        return $xPathQuery->item(0);
    }

    public function testLogoURL()
    {
        $logoElement = $this->getRenderedLogoElement();
        $this->assertNotNull($logoElement);
        $src = $logoElement->getAttribute('src');

        $this->assertEquals(
            'https://fb-s-c-a.akamaihd.net/h-ak-xpa1/v/t39.5687-6/17351511_1229084560538118_5982709905105092608_n.png?_nc_log=1&oh=c8337650a88e7fdb6d31088a15a7d9d8&oe=599B24B5&__gda__=1502041799_7139cf314c7cdaa52fa44ba26fd253f8',
            $src
        );
    }

    public function testLogoWidth()
    {
        $logoElement = $this->getRenderedLogoElement();
        $this->assertNotNull($logoElement);
        $width = $logoElement->getAttribute('width');

        $this->assertEquals(223, $width);
    }

    public function testLogoHeight()
    {
        $logoElement = $this->getRenderedLogoElement();
        $this->assertNotNull($logoElement);
        $height = $logoElement->getAttribute('height');

        $this->assertEquals(44, $height);
    }

    public function testCachedImageHeight()
    {
        $expectedHeight = 181;
        $customProperties = array(
            AMPArticle::MEDIA_CACHE_FOLDER_KEY => __DIR__ . '/articles/media-cache',
        );

        $coverImageXPathQuery = $this->getRenderedMarkupXPathQuery(
            'test2',
            '//article//amp-img',
            $customProperties
        );
        $coverImageElement = $coverImageXPathQuery->item(0);

        $this->assertEquals($expectedHeight, $coverImageElement->getAttribute('height'));
    }

    public function testImageHeightDownloadDisabled()
    {
        $expectedHeight = 240;
        $customProperties = array(
            AMPArticle::MEDIA_CACHE_FOLDER_KEY => __DIR__ . '/articles/media-cache',
            AMPArticle::ENABLE_DOWNLOAD_FOR_MEDIA_SIZING_KEY => false,
        );

        $imageXPathQuery = $this->getRenderedMarkupXPathQuery(
            'test1',
            '//div[@class=\'ia2amp-slideshow-image\']//amp-img',
            $customProperties
        );
        $firstArticleImageElement = $imageXPathQuery->item(0);

        $this->assertEquals($expectedHeight, $firstArticleImageElement->getAttribute('height'));
    }

    public function testImageHeightDefaultProperty()
    {
        $expectedHeight = 120;
        $customProperties = array(
            AMPArticle::ENABLE_DOWNLOAD_FOR_MEDIA_SIZING_KEY => false,
            AMPArticle::DEFAULT_MEDIA_HEIGHT_KEY => $expectedHeight,
        );

        $imageXPathQuery = $this->getRenderedMarkupXPathQuery(
            'test1',
            '//div[@class=\'ia2amp-slideshow-image\']//amp-img',
            $customProperties
        );
        $firstArticleImageElement = $imageXPathQuery->item(0);

        $this->assertEquals($expectedHeight, $firstArticleImageElement->getAttribute('height'));
    }

    public function testImageHeightFromMediaSizes()
    {
        $expectedHeight = 253;
        $customProperties = array(
            AMPArticle::ENABLE_DOWNLOAD_FOR_MEDIA_SIZING_KEY => false,
            AMPArticle::MEDIA_SIZES_KEY => array(
                "http://blog.wod.expert/wp-content/uploads/2017/03/fail2.jpg" => array(90, 60),
            ),
        );

        $imageXPathQuery = $this->getRenderedMarkupXPathQuery(
            'test1',
            '//div[@class=\'ia2amp-slideshow-image\']//amp-img',
            $customProperties
        );
        $firstArticleImageElement = $imageXPathQuery->item(0);

        $this->assertEquals($expectedHeight, $firstArticleImageElement->getAttribute('height'));
    }

    public function testVideoHeightDefaultProperty()
    {
        $expectedHeight = 120;
        $customProperties = array(
            AMPArticle::ENABLE_DOWNLOAD_FOR_MEDIA_SIZING_KEY => false,
            AMPArticle::DEFAULT_MEDIA_HEIGHT_KEY => $expectedHeight,
        );

        $videoXPathQuery = $this->getRenderedMarkupXPathQuery(
            'tutorial',
            '//amp-video',
            $customProperties
        );
        $firstArticleVideoElement = $videoXPathQuery->item(0);

        $this->assertEquals($expectedHeight, $firstArticleVideoElement->getAttribute('height'));
    }

    public function testVideoWidthFromMediaSizes()
    {
        $expectedWidth = 380;
        $videoXPathQuery = $this->getRenderedMarkupXPathQuery(
            'tutorial',
            '//amp-video',
            null
        );
        $firstArticleVideoElement = $videoXPathQuery->item(0);

        $this->assertEquals($expectedWidth, $firstArticleVideoElement->getAttribute('width'));
    }

    public function testVideoHeightFromMediaSizes()
    {
        $expectedHeight = 240;
        $videoXPathQuery = $this->getRenderedMarkupXPathQuery(
            'tutorial',
            '//amp-video',
            null
        );
        $firstArticleVideoElement = $videoXPathQuery->item(0);

        $this->assertEquals($expectedHeight, $firstArticleVideoElement->getAttribute('height'));
    }

    public function testFooterCredits()
    {
        $creditsXPathQuery = $this->getRenderedMarkupXPathQuery(
            'test1',
            '//article/footer/aside'
        );

        $creditsElement = $creditsXPathQuery->item(0);

        $this->assertEquals('WOD Expert', trim($creditsElement->textContent));
    }

    public function testFooterCopyright()
    {
        $copyrightXPathQuery = $this->getRenderedMarkupXPathQuery(
            'test1',
            '//article/footer/small'
        );

        $copyrightElement = $copyrightXPathQuery->item(0);

        $this->assertEquals('© 2017 WOD Expert', trim($copyrightElement->textContent));
    }

    public function testCoverImageWidth()
    {
        $expectedWidth = 422;
        $coverImageXPathQuery = $this->getRenderedMarkupXPathQuery(
            'test1',
            '//amp-img[@class=\'ia2amp-header-cover-img\']',
            array(
                'media-sizes' => array(
                    'http://blog.wod.expert/wp-content/uploads/2017/03/fail1.jpg' => array(
                        800,
                        454,
                    ),
                ),
            )
        );

        $coverImageElement = $coverImageXPathQuery->item(0);

        $this->assertEquals($expectedWidth, $coverImageElement->getAttribute('width'));
    }

    public function testCoverImageHeight()
    {
        $expectedHeight = 240;
        $coverImageXPathQuery = $this->getRenderedMarkupXPathQuery(
            'test1',
            '//amp-img[@class=\'ia2amp-header-cover-img\']',
            array(
                'media-sizes' => array(
                    'http://blog.wod.expert/wp-content/uploads/2017/03/fail1.jpg' => array(
                        800,
                        454,
                    ),
                ),
            )
        );

        $coverImageElement = $coverImageXPathQuery->item(0);

        $this->assertEquals($expectedHeight, $coverImageElement->getAttribute('height'));
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
            array('#00FFFFFF', 'rgba(255,255,255,0)'),
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
        $context = AMPContext::create(new \DOMDocument(), $renderer->getInstantArticle(), 'ia2amp-');
        $css = $renderer->getCustomCSS($context);

        // Escape parenthesis before using regex
        $expectedValue = str_replace(')', '\)', str_replace('(', '\(', AMPArticle::toRGB($hexColor)));
        $this->validateCSSRule($css, 'html', 'background-color', $expectedValue);
    }

    public function textStylesDataProvider()
    {
        return array_merge(
            // Head
            $this->getTextStylesTestData('kicker', '.ia2amp-header-category'),
            $this->getTextStylesTestData('title', '.ia2amp-header-h1'),
            $this->getTextStylesTestData('subtitle', '.ia2amp-header-h2'),
            $this->getTextStylesTestData('byline', '.ia2amp-header h3'),
            // Body
            $this->getTextStylesTestData('primary_heading', '.ia2amp-h1'),
            $this->getTextStylesTestData('secondary_heading', '.ia2amp-h2'),
            $this->getTextStylesTestData('body_text', '.ia2amp-p'),
            $this->getTextStylesTestData('inline_link', '.ia2amp-article a'),
            // Quotes
            $this->getTextStylesTestData('block_quote', '.ia2amp-blockquote'),
            $this->getTextStylesTestData('pull_quote', '.ia2amp-pullquote'),
            $this->getTextStylesTestData('pull_quote_attribution', '.ia2amp-pullquote cite'),
            // Captions
            $this->getTextStylesTestData('caption_title_small', '.ia2amp-op-small h1'),
            $this->getTextStylesTestData('caption_description_small', '.ia2amp-op-small h2'),
            $this->getTextStylesTestData('caption_title', '.ia2amp-op-medium h1'),
            $this->getTextStylesTestData('caption_description', '.ia2amp-op-medium h2'),
            $this->getTextStylesTestData('caption_title_large', '.ia2amp-op-large h1'),
            $this->getTextStylesTestData('caption_description_large', '.ia2amp-op-large h2'),
            $this->getTextStylesTestData('caption_title_extra_large', '.ia2amp-op-extra-large h1'),
            $this->getTextStylesTestData('caption_description_extra_large', '.ia2amp-op-extra-large h2'),
            $this->getTextStylesTestData('caption_credit', '.ia2amp-figcaption cite'),
            // Footer
            $this->getTextStylesTestData('footer', '.ia2amp-footer')
        );
    }

    /**
    * @dataProvider textStylesDataProvider
    */
    public function testTextStyles($styleName, $secondLevelProperty, $cssSelector, $cssProperty, $styleValue, $expectedCSSValue)
    {
        $this->validateSecondLevelProperty($styleName, $secondLevelProperty, $cssSelector, $cssProperty, $styleValue, $expectedCSSValue);
    }

    /**
     * @dataProvider testTextStylesDataProvider
     */
    private function getTextStylesTestData($styleName, $cssSelector)
    {
        $testData = array();

        // Font Family
        $testData = array_merge(
            $testData,
            $this->getSecondLevelPropertyTestData(
                'randomDataProvider',
                'passThroughValuesProvider',
                $styleName,
                'font',
                $cssSelector,
                'font-family'
            )
        );

        // Color
        $testData = array_merge(
            $testData,
            $this->getSecondLevelPropertyTestData(
                'colorDataProvider',
                'passThroughValuesProvider',
                $styleName,
                'color',
                $cssSelector,
                'color'
            )
        );

        // Background Color
        $testData = array_merge(
            $testData,
            $this->getSecondLevelPropertyTestData(
                'colorDataProvider',
                'passThroughValuesProvider',
                $styleName,
                'background_color',
                $cssSelector,
                'background-color'
            )
        );

        // Text Transform
        $testData = array_merge(
            $testData,
            $this->getSecondLevelPropertyTestData(
                'textTransformDataProvider',
                'passThroughValuesProvider',
                $styleName,
                'capitalization',
                $cssSelector,
                'text-transform'
            )
        );

        // Text Alignment
        $testData = array_merge(
            $testData,
            $this->getSecondLevelPropertyTestData(
                'textAlignmentDataProvider',
                'passThroughValuesProvider',
                $styleName,
                'text_alignment',
                $cssSelector,
                'text-align'
            )
        );

        // Display
        $testData = array_merge(
            $testData,
            $this->getSecondLevelPropertyTestData(
                'displayDataProvider',
                'passThroughValuesProvider',
                $styleName,
                'display',
                $cssSelector,
                'display'
            )
        );

        // Margin
        $testData = array_merge(
            $testData,
            $this->getSecondLevelPropertyTestData(
                'marginDataProvider',
                'spacingValuesProvider',
                $styleName,
                'margin',
                $cssSelector,
                'margin'
            )
        );

        // Padding
        $testData = array_merge(
            $testData,
            $this->getSecondLevelPropertyTestData(
                'paddingDataProvider',
                'spacingValuesProvider',
                $styleName,
                'padding',
                $cssSelector,
                'padding'
            )
        );

        // Border Width
        $testData = array_merge(
            $testData,
            $this->getSecondLevelPropertyTestData(
                'borderWidthDataProvider',
                'borderWidthValuesProvider',
                $styleName,
                'border',
                $cssSelector,
                'border-width'
            )
        );

        return $testData;
    }

    /**
     * Builds the arguments that will be pased to function validateSecondLevelProperty
     *
     * @param string $dataProviderFunctionName The name of the parameterless function that serves as Data Provider
     * @param string $validationArgumentsProviderFunctionName The name of the function that receives the Data Provider rows and converts them to pairs of style
     * @param string $styleName The style name that will be used for validateSecondLevelProperty
     * @param object $secondLevelProperty The name of the property that will be transformed in the styles for the unit test
     * @param string $cssSelector The CSS selector that will be used to verify the unit test result
     * @param string $cssProperty The CSS property that will be used to verify the unit test result
     * @return array An array with the style value that will be set, and the expected CSS value that will be generated
     */
    private function getSecondLevelPropertyTestData(
        $dataProviderFunctionName,
        $validationArgumentsProviderFunctionName,
        $styleName,
        $secondLevelProperty,
        $cssSelector,
        $cssProperty
    ) {

        $testData = array();

        // Get all test cases for the given Data Provider function
        $dataProviderTestData = call_user_func_array(array($this, $dataProviderFunctionName), array());

        foreach ($dataProviderTestData as $dataProviderTestItem) {
            // Call the Validation Arguments Provider function using the values from the Data Provider
            $styleAndCssValues = call_user_func_array(array($this, $validationArgumentsProviderFunctionName), $dataProviderTestItem);
            // Merge the known values with the mappings of style value to expected CSS value
            $testDataItem = array_merge(
                array(
                    $styleName,
                    $secondLevelProperty,
                    $cssSelector,
                    $cssProperty,
                ),
                $styleAndCssValues
            );
            // Add the merged values to the list of test items
            $testData[] = $testDataItem;
        }

        return $testData;
    }

    /**
     * Takes an Instant Article style property name and an expected value and returns both in an array
     *
     * @param string $stylePropertyName
     * @param string $expectedCSSValue
     * @return array
     */
    private function passThroughValuesProvider($stylePropertyName, $expectedCSSValue)
    {
        return array($stylePropertyName, $expectedCSSValue);
    }

    /**
     * Generates random values for function passThroughValuesProvider
     *
     * @return array
     */
    private function randomDataProvider()
    {
        $randomValue = rand();
        return array(
            array($randomValue, $randomValue),
        );
    }

    /**
     * Generates color values for function passThroughValuesProvider
     *
     * @return array
     */
    private function colorDataProvider()
    {
        $hexColor = AMPArticleTest::getRandomHexColor();
        // Escape parenthesis before using regex
        $expectedValue = str_replace(')', '\)', str_replace('(', '\(', AMPArticle::toRGB($hexColor)));
        return array(
            array($hexColor, $expectedValue),
        );
    }

    /**
     * Generates text transform values for function passThroughValuesProvider
     *
     * @return array
     */
    private function textTransformDataProvider()
    {
        return array(
            array('ALL_CAPS', 'uppercase'),
            array('NONE', 'none'),
            array('ALL_LOWER_CASE', 'lowercase'),
        );
    }

    /**
     * Generates text alignment values for function passThroughValuesProvider
     *
     * @return array
     */
    public function textAlignmentDataProvider()
    {
        return array(
            array('LEFT', 'LEFT'),
            array('CENTER', 'CENTER'),
            array('RIGHT', 'RIGHT'),
        );
    }

    /**
     * Generates display values for function passThroughValuesProvider
     *
     * @return array
     */
    public function displayDataProvider()
    {
        return array(
            array('INLINE', 'INLINE'),
            array('BLOCK', 'BLOCK'),
        );
    }

    /**
     * Generates spacing (margin or padding) Instant Article style values and expected CSS values
     *
     * @param int $size The Instant Article style size value
     * @param int $baseSpacingValue The value that will be multiplied by the scaling factor
     * @param string $direction A valid direction for a border style e.g. 'top'
     * @param string $spacingFormat The expected spacing format e.g. '0 0 0 %spx'
     * @return array
     */
    public function spacingValuesProvider($size, $baseSpacingValue, $direction, $spacingFormat)
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

        return array($spacingStyle, $expectedValue);
    }

    /**
     * Generates margin values for function spacingValuesProvider
     *
     * @return array
     */
    private function marginDataProvider()
    {
        return array(
            // size, baseSpacingValue, direction, spacingFormat
            array('DOCUMENT_MARGIN', AMPArticle::DEFAULT_MARGIN, 'right', '0 %spx 0 0'),
            array('DOCUMENT_MARGIN', AMPArticle::DEFAULT_MARGIN, 'left', '0 0 0 %spx'),

            array('NONE', 0, 'right', '0 0 0 0'),
            array('NONE', 0, 'left', '0 0 0 0'),
        );
    }

    /**
     * Generates padding values for function spacingValuesProvider
     *
     * @return array
     */
    private function paddingDataProvider()
    {
        return array(
            // size, baseSpacingValue, direction, spacingFormat
            array('NONE', 0, 'top', '0 0 0 0'),
            array('NONE', 0, 'right', '0 0 0 0'),
            array('NONE', 0, 'bottom', '0 0 0 0'),
            array('NONE', 0, 'left', '0 0 0 0'),

            array('MEDIUM', 46, 'top', '%spx 0 0 0'),
            array('MEDIUM', 46, 'right', '0 %spx 0 0'),
            array('MEDIUM', 46, 'bottom', '0 0 %spx 0'),
            array('MEDIUM', 46, 'left', '0 0 0 %spx'),
        );
    }

    /**
     * Generates Border Color Instant Article style values and expected CSS values
     *
     * @param string $direction A valid direction for a border style e.g. 'top'
     * @return array
     */
    public function borderColorValuesProvider($direction)
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

        return array($borderStyle, $expectedValue);
    }

    /**
     * Generates values for function borderColorValuesProvider
     *
     * @return array
     */
    private function directionsDataProvider()
    {
        return array(
            array('top'),
            array('right'),
            array('bottom'),
            array('left'),
        );
    }

    /**
     * Generates Border Width Instant Article style values and expected CSS values
     *
     * @param string $direction A valid direction for a border style e.g. 'top'
     * @param string $borderFormat The expected format of the border width for the given direction e.g. '%s 0 0 0'
     * @return array
     */
    public function borderWidthValuesProvider($direction, $borderFormat)
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
        return array($borderStyle, $expectedValue);
    }

    /**
     * Generates arguments for function borderWidthValuesProvider
     *
     * @return array
     */
    private function borderWidthDataProvider()
    {
        return array(
            array('top', '%s 0 0 0'),
            array('right', '0 %s 0 0'),
            array('bottom', '0 0 %s 0'),
            array('left', '0 0 0 %s'),
        );
    }

    /**
     * Validates the transformation of Instant Article Styles to CSS by updating a second level JSON property and looking for an expected CSS value
     *
     * @param string $firstLevelKey The name of the Instant Article styles object that is the parent of the property that will be tested
     * @param string $secondLevelKey The name of the Instant Article styles object that will be tested
     * @param string $cssSelector The CSS selector that will be used to verify the genarted CSS
     * @param string $cssProperty The name of CSS property that will be used to test the transformation
     * @param string $styleValue The style value that will be assigned to test the generation of an expected CSS value
     * @param object $expectedCSSValue The value of CSS property that will be used to test the transformation
     * @return void
     */
    private function validateSecondLevelProperty($firstLevelKey, $secondLevelKey, $cssSelector, $cssProperty, $styleValue, $expectedCSSValue)
    {
        // Load the default values
        $testStyles = $this->getDefaultStyles();
        // Set the style property value that will be used to generate the expected CSS
        $testStyles[$firstLevelKey][$secondLevelKey] = $styleValue;

        $customProperties = array(
            // Use the updated styles instead of the ones defined in the Instant Article document
            AMPArticle::OVERRIDE_STYLES_KEY => $testStyles,
        );

        $renderer = $this->getRenderer('test1', $customProperties);
        $context = AMPContext::create(new \DOMDocument(), $renderer->getInstantArticle(), 'ia2amp-');
        $css = $renderer->getCustomCSS($context);

        // Look for the expected CSS
        $this->validateCSSRule($css, $cssSelector, $cssProperty, $expectedCSSValue);
    }

    /**
     * Inspects CSS rules looking for expected values using regular expressions
     *
     * @param string $css The CSS rules that will be inspected
     * @param string $selector The CSS selector whose rules will be inspected
     * @param string $property The CSS property that will be verified
     * @param string $value The CSS property value that will be verified
     * @return void
     */
    private function validateCSSRule($css, $selector, $property, $value)
    {
        // Escape the dot (used on class name selectors) so it is not interpreted as any character
        $selector = str_replace('.', '\.', $selector);

        $cssRulePattern = '/' . $selector. '\s*{[^}]*'. $property . ':\s*' . $value . ';/';
        $this->assertEquals(1, preg_match($cssRulePattern, $css), "Could not find CSS rule '$property' for selector '$selector'");
    }

    /**
     * Generates a random color expressed as three hexadecimal values
     *
     * @return string E.g. '#237A90'
     */
    private static function getRandomHexColor()
    {
        $red = rand(0, 255);
        $green = rand(0, 255);
        $blue = rand(0, 255);

        return '#' . str_pad(dechex($red), 2, '0', STR_PAD_LEFT) .
                    str_pad(dechex($green), 2, '0', STR_PAD_LEFT) .
                    str_pad(dechex($blue), 2, '0', STR_PAD_LEFT);
    }

    public function testBuildAnalytics()
    {
        $properties = array();
        $properties['analytics'] = [
            '<amp-pixel src="https://foo.com/pixel?RANDOM"></amp-pixel>',
            '<amp-analytics><script type="application/json">{}</script></amp-analytics>'
        ];

        $context = AMPContext::create(new \DOMDocument(), InstantArticle::create());

        $article = AMPArticle::create('<html></html>', $properties);
        $fragment = $article->buildAnalytics($context);

        $this->assertEquals(2, $fragment->childNodes->length);

        $pixel = $fragment->firstChild;
        $this->assertEquals('amp-pixel', $pixel->tagName);
        $this->assertEquals('https://foo.com/pixel?RANDOM', $pixel->getAttribute('src'));

        $analytics = $fragment->childNodes->item(1);
        $this->assertEquals('amp-analytics', $analytics->tagName);
        $this->assertEquals(1, $analytics->childNodes->length);

        $analyticsScript = $analytics->firstChild;
        $this->assertEquals('script', $analyticsScript->tagName);
        $this->assertEquals('application/json', $analyticsScript->getAttribute('type'));
        $this->assertEquals('{}', $analyticsScript->textContent);
    }
}
