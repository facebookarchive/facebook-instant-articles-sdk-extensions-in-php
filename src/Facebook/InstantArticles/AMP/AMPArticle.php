<?php
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */
namespace Facebook\InstantArticles\AMP;

use Facebook\InstantArticles\Elements\Element;
use Facebook\InstantArticles\Elements\Paragraph;
use Facebook\InstantArticles\Elements\Blockquote;
use Facebook\InstantArticles\Elements\H1;
use Facebook\InstantArticles\Elements\H2;
use Facebook\InstantArticles\Elements\ListElement;
use Facebook\InstantArticles\Elements\Pullquote;
use Facebook\InstantArticles\Elements\Image;
use Facebook\InstantArticles\Elements\AnimatedGIF;
use Facebook\InstantArticles\Elements\Video;
use Facebook\InstantArticles\Elements\Audio;
use Facebook\InstantArticles\Elements\Slideshow;
use Facebook\InstantArticles\Elements\Interactive;
use Facebook\InstantArticles\Elements\SocialEmbed;
use Facebook\InstantArticles\Elements\Map;
use Facebook\InstantArticles\Elements\RelatedArticles;
use Facebook\InstantArticles\Elements\Container;
use Facebook\InstantArticles\Elements\TextContainer;
use Facebook\InstantArticles\Elements\InstantArticleInterface;

use Facebook\InstantArticles\Parser\Parser;
use Facebook\InstantArticles\Validators\Type;

class AMPArticle extends Element implements InstantArticleInterface
{
    const DEFAULT_MARGIN = 16.4;
    const DEFAULT_WIDTH = 380;
    const DEFAULT_HEIGHT = 240;
    const DEFAULT_DATE_FORMAT = 'F d, Y';

    public const STYLES_FOLDER_KEY = 'styles-folder';
    public const OVERRIDE_STYLES_KEY = 'override-styles';

    private $instantArticle;
    /*
       'lang' => 'en-US',
       'css-selector-prefix' => 'ia2amp-',
       'header-logo-image-url' => 'http://domain.com/someimage.png',
     */
    private $properties = array();

    private $dateFormat = AMPArticle::DEFAULT_DATE_FORMAT;

    public function __construct($instantArticle, $properties = array())
    {
        $this->instantArticle = $instantArticle;
        $this->properties = $properties;
    }

    public static function create($instantArticleString, $properties = array())
    {
        libxml_use_internal_errors(true);
        $document = new \DOMDocument();
        $document->loadHTML($instantArticleString);
        libxml_use_internal_errors(false);

        $parser = new Parser();
        $instant_article = $parser->parse($document);
        return new self($instant_article, $properties);
    }

    public function render($doctype = '<!doctype html>', $format = false)
    {
        $doctype = is_null($doctype) ? '<!doctype html>' : $doctype;
        $rendered = parent::render($doctype, $format);

        // TODO fix the workaround just like on Element.php class
        $rendered = str_replace('amp=""', 'amp', $rendered);
        $rendered = str_replace('amp-custom=""', 'amp-custom', $rendered);
        $rendered = str_replace('amp-boilerplate=""', 'amp-boilerplate', $rendered);
        $rendered = str_replace('async=""', 'async', $rendered);

        return $rendered;
    }

    public function toDOMElement($document = null)
    {
        if (!$document) {
            $document = new \DOMDocument();
        }
        $ampDocument = $this->transformInstantArticle($document, $this->instantArticle);
        return $ampDocument;
    }

    public function transformInstantArticle($document, $instantArticle) {
        // Builds and appends head to the HTML document
        $html = $document->createElement('html');
        $html->setAttribute('amp','');
        if ($instantArticle->isRTLEnabled()) {
            $html->setAttribute('dir', 'rtl');
        }
        if (isset($this->properties['lang'])) {
            $html->setAttribute('lang', $this->properties['lang']);
        }

        $head = $this->transformMetaInfoHead($document, $html, $instantArticle);


        // Build and append body and article tags to the HTML document
        $body = $document->createElement('body');
        $html->appendChild($body);
        $body->setAttribute('class', $this->buildClassName('body'));


        $header = $this->transformArticleHeader($document, $body, $instantArticle);

        $article = $this->transformArticleContent($document, $body, $head, $instantArticle);

        $footer = $this->transformArticleFooter($document, $body, $head, $instantArticle);

        return $html;
    }

    public function transformArticleHeader($document, $body, $instantArticle)
    {
        // Builds the content Header, with proper colors and image, adding to body
        $header = $document->createElement('header');
        $body->appendChild($header);

        $header->setAttribute('class', $this->buildClassName('header'));
        if (isset($this->properties['header-logo-image-url'])) {
            $imageURL = $this->properties['header-logo-image-url'];
            if (isset($this->properties['header-logo-image-width']) && isset($this->properties['header-logo-image-height'])) {
                $imageWidth = $this->properties['header-logo-image-width'];
                $imageHeight = $this->properties['header-logo-image-height'];
            }
            else {
                $imageDimmensions = getimagesize($imageURL);
                $imageWidth = $imageDimmensions[0];
                $imageHeight = $imageDimmensions[1];
            }

            // Creates the cover content for the header and appends to the header
            if ($instantArticle->getHeader()->getCover()) {
                $headerCover = $this->buildCover($instantArticle->getHeader()->getCover(), $document);
                $header->appendChild($headerCover);
            }

            // Creates the header bar with image (maybe fb like?) and appends to header
            $headerBar = $document->createElement('div');
            $header->appendChild($headerBar);
            $headerBar->setAttribute('class', $this->buildClassName('header-bar'));
            $ampImageContainer = $document->createElement('div');
            $headerBar->appendChild($ampImageContainer);
            $ampImageContainer->setAttribute('class', $this->buildClassName('header-bar-img-container'));
            $ampImage = $document->createElement('amp-img');
            $ampImageContainer->appendChild($ampImage);
            $ampImage->setAttribute('src', $imageURL);
            // TODO work on this image logo size
            $ampImage->setAttribute('width', '200');
            $ampImage->setAttribute('height', '40');

        }
        // The kicker for article
        if ($instantArticle->getHeader()->getKicker()) {
            $kicker = $document->createElement('h2');
            $header->appendChild($kicker);
            $kicker->setAttribute('class', 'ia2amp-header-category');
            $kicker->appendChild($instantArticle->getHeader()->getKicker()->textToDOMDocumentFragment($document));
        }

        // The Title for article
        $h1 = $document->createElement('h1');
        $header->appendChild($h1);
        $h1->setAttribute('class', 'ia2amp-header-h1');
        $h1->appendChild($instantArticle->getHeader()->getTitle()->textToDOMDocumentFragment($document));

        // The article authors
        $authors = $document->createElement('h3');
        $header->appendChild($authors);
        $authors->setAttribute('class', 'ia2amp-header-author');
        $authorsElement = $instantArticle->getHeader()->getAuthors();
        $authorsString = [];
        foreach($authorsElement as $author) {
            $authorsString[] = $author->getName();
        }
        $authors->appendChild($document->createTextNode('BY '.implode($authorsString, ', ')));

        // Aritcle publish date
        $publishDate = $document->createElement('h3');
        $header->appendChild($publishDate);
        $publishDate->setAttribute('class', 'ia2amp-header-date');
        $datetime = $instantArticle->getHeader()->getPublished()->getDatetime();
        $publishDate->appendChild($document->createTextNode(date_format($datetime, $this->dateFormat)));

        return $header;
    }

    public function transformMetaInfoHead($document, $html, $instantArticle)
    {
        // Builds the Head
        $head = $document->createElement('head');
        $html->appendChild($head);

        // Builds meta charset and append to head
        if ($instantArticle->getCharset()) {
            $charset = $document->createElement('meta');
            $charset->setAttribute('charset', $instantArticle->getCharset());
            $head->appendChild($charset);
        }

        // Builds meta viewport and append to head
        $viewport = $document->createElement('meta');
        $head->appendChild($viewport);
        $viewport->setAttribute('name', 'viewport');
        $viewport->setAttribute('content', 'width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no');

        // Builds ampjs script and append to head
        $ampjs = $document->createElement('script');
        $head->appendChild($ampjs);
        $ampjs->setAttribute('src', 'https://cdn.ampproject.org/v0.js');
        $ampjs->setAttribute('async','');

        // Builds boilerplate css style and append to head
        $boilerplate = $document->createElement('style');
        $head->appendChild($boilerplate);
        $boilerplate->setAttribute('amp-boilerplate','');
        $boilerplateContent = $document->createTextNode('body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}');
        $boilerplate->appendChild($boilerplateContent);

        // Builds noscript css style and append to head
        $noscript = $document->createElement('noscript');
        $head->appendChild($noscript);
        $noscriptBoilerplate = $document->createElement('style');
        $noscript->appendChild($noscriptBoilerplate);
        $noscriptBoilerplate->setAttribute('amp-boilerplate','');
        $noscriptBoilerplateContent = $document->createTextNode('body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}');
        $noscriptBoilerplate->appendChild($noscriptBoilerplateContent);

        // Builds canonical link and append to head
        $link = $document->createElement('link');
        $link->setAttribute('rel', 'canonical');
        $link->setAttribute('href', $instantArticle->getCanonicalURL());

        // Builds custom css style and append to head
        $ampCustomCSS = $this->buildCustomCSS($document);
        $head->appendChild($ampCustomCSS);
        $head->appendChild($link);

        // Builds Schema.org metadata and appends to head
        $discoveryScript = $document->createElement('script');
        $discoveryScript->setAttribute('type', 'application/ld+json');
        $discoveryScriptContent = $this->buildSchemaOrgMetadata();
        $discoveryScript->appendChild($document->createTextNode($discoveryScriptContent));
        $head->appendChild($discoveryScript);

        // Builds title and append to head
        $title = $document->createElement('title');
        $head->appendChild($title);
        $titleText = $instantArticle->getHeader()->getTitle()->textToDOMDocumentFragment($document);
        $title->appendChild($titleText);

        return $head;
    }

    public function transformArticleContent($document, $body, $head, $instantArticle)
    {
        $article = $document->createElement('article');
        $body->appendChild($article);
        $article->setAttribute('class', $this->buildClassName('article'));

        $containsIframe = false;
        $containsSlideshow = false;
        $containsAudio = false;

        if ($instantArticle->getChildren()) {
            foreach ($instantArticle->getChildren() as $child) {
                if (Type::is($child, TextContainer::getClassName())) {
                    if (count($child->getTextChildren()) === 0) {
                        continue;
                    } elseif (count($child->getTextChildren()) === 1) {
                        if (Type::is($child->getTextChildren()[0], Type::STRING) &&
                            trim($child->getTextChildren()[0]) === '') {
                            continue;
                        }
                    }
                }
                $childElement = $child->toDOMElement($document);
                if (Type::is($child, Paragraph::getClassName())) {
                    $childElement->setAttribute('class', $this->buildClassName('p'));
                }
                else if (Type::is($child, Blockquote::getClassName())) {
                    $childElement->setAttribute('class', $this->buildClassName('blockquote'));
                }
                else if (Type::is($child, H1::getClassName())) {
                    $childElement->setAttribute('class', $this->buildClassName('h1'));
                }
                else if (Type::is($child, H2::getClassName())) {
                    $childElement->setAttribute('class', $this->buildClassName('h2'));
                }
                else if (Type::is($child, ListElement::getClassName())) {
                    $childElement->setAttribute('class', $this->buildClassName('list'));
                }
                else if (Type::is($child, Pullquote::getClassName())) {
                    $childElement->setAttribute('class', $this->buildClassName('pullquote'));
                }
                else if (Type::is($child, Image::getClassName())) {
                    $childElement = $this->buildImage($child, $document, 'image');
                }
                else if (Type::is($child, AnimatedGIF::getClassName())) {
                    $childElement = $this->buildGIF($child, $document, 'gif');
                }
                else if (Type::is($child, Video::getClassName())) {
                    $childElement = $this->buildVideo($child, $document, 'video');
                }
                else if (Type::is($child, Audio::getClassName())) {
                    if (!$containsAudio) {
                        $containsAudio = true;
                        $head->appendChild($this->buildCustomElementScriptEntry('amp-audio', 'https://cdn.ampproject.org/v0/amp-audio-0.1.js', $document));
                    }
                    $childElement = $this->buildAudio($child, $document, 'audio');
                }
                else if (Type::is($child, Slideshow::getClassName())) {
                    if (!$containsSlideshow) {
                        $containsSlideshow = true;
                        $head->appendChild($this->buildCustomElementScriptEntry('amp-carousel', 'https://cdn.ampproject.org/v0/amp-carousel-0.1.js', $document));
                    }
                    $childElement = $this->buildSlideshow($child, $document, 'slideshow');
                }
                else if (Type::is($child, Interactive::getClassName()) || Type::is($child, SocialEmbed::getClassName())) {
                    if (!$containsIframe) {
                        $containsIframe = true;
                        $head->appendChild($this->buildCustomElementScriptEntry('amp-iframe', 'https://cdn.ampproject.org/v0/amp-iframe-0.1.js', $document));
                    }
                    $childElement = $this->buildIframe($child, $document, 'interactive');
                }
                else if (Type::is($child, Map::getClassName())) {
                    if (!$containsIframe) {
                        $containsIframe = true;
                        $head->appendChild($this->buildCustomElementScriptEntry('amp-iframe', 'https://cdn.ampproject.org/v0/amp-iframe-0.1.js', $document));
                    }
                    $childElement = $this->buildMaps($child, $document, 'map');
                }
                else if (Type::is($child, RelatedArticles::getClassName())) {
                    $childElement->setAttribute('class', $this->buildClassName('related-articles'));
                    // TODO RelatedArticles
                }

                $article->appendChild($childElement);
            }
        }

        return $article;
    }

    public function transformArticleFooter()
    {
        // if ($instantArticle->getFooter() && $this->instantArticle->getFooter()->isValid()) {
        //     $article->appendChild($this->instantArticle->getFooter()->toDOMElement($document));
        // }
        return null;
    }

    private function buildCover($media, $document)
    {
        if (Type::is($media, Image::getClassName())) {
            return $this->buildImage($media, $document, 'cover-image', false);
        }
        else if (Type::is($media, Slideshow::getClassName())) {
            return $this->buildSlideshow($media, $document, 'cover-slideshow');
        }
        else if (Type::is($media, Video::getClassName())) {
            return $this->buildVideo($media, $document, 'cover-video');
        }
    }

    public function buildCustomElementScriptEntry($customElementName, $src, $document)
    {
        $script = $document->createElement('script');
        $script->setAttribute('async', '');
        $script->setAttribute('custom-element', $customElementName);
        $script->setAttribute('src', $src);
        return $script;
    }

    private function buildImage($image, $document, $cssClass, $withContainer = true)
    {
        if ($withContainer) {
            $ampImgContainer = $document->createElement('div');
            $ampImgContainer->setAttribute('class', $this->buildClassName($cssClass));
        }

        $ampImg = $document->createElement('amp-img');
        if ($withContainer) {
            $ampImgContainer->appendChild($ampImg);
        }
        $imageURL = $image->getUrl();

        // $imageDimmensions = getimagesize($imageURL);
        $imageDimmensions = array(380, 240);
        $imageWidth = $imageDimmensions[0];
        $imageHeight = $imageDimmensions[1];

        // Somehow the full width on mobile is 380, so I resize image height on same ratio
        $resizedWidthFactor = (double) (380 / (int) $imageWidth);
        $newHeight = (int) ($imageHeight * $resizedWidthFactor);

        $ampImg->setAttribute('src', $imageURL);
        $ampImg->setAttribute('width', '380');
        $ampImg->setAttribute('height', (string) $newHeight);

        return ($withContainer) ? $ampImgContainer : $ampImg;
    }

    private function buildGIF($image, $document, $cssClass, $withContainer = true)
    {
        if ($withContainer) {
            $ampImgContainer = $document->createElement('div');
            $ampImgContainer->setAttribute('class', $this->buildClassName($cssClass));
        }

        $ampImg = $document->createElement('amp-anim');
        if ($withContainer) {
            $ampImgContainer->appendChild($ampImg);
        }
        $imageURL = $image->getUrl();

        // $imageDimmensions = getimagesize($imageURL);
        // $imageWidth = $imageDimmensions[0];
        // $imageHeight = $imageDimmensions[1];

        $ampImg->setAttribute('src', $imageURL);
        $ampImg->setAttribute('width', self::DEFAULT_WIDTH);
        $ampImg->setAttribute('height', self::DEFAULT_HEIGHT);

        return ($withContainer) ? $ampImgContainer : $ampImg;
    }

    private function buildVideo($video, $document, $cssClass)
    {
        $ampVideoContainer = $document->createElement('div');
        $ampVideoContainer->setAttribute('class', $this->buildClassName($cssClass));

        $ampVideo = $document->createElement('amp-video');
        $ampVideoContainer->appendChild($ampVideo);
        $videoUrl = $video->getUrl();

        // $videoDimensions = getimagesize($videoUrl);
        // $videoWidth = $videoDimensions[0];
        // $videoHeight = $videoDimensions[1];

        $ampVideo->setAttribute('src', $videoUrl);
        $ampVideo->setAttribute('width', self::DEFAULT_WIDTH);
        $ampVideo->setAttribute('height', self::DEFAULT_HEIGHT);

        return $ampVideoContainer;
    }

    private function buildAudio($video, $document, $cssClass)
    {
        $ampAudio = $document->createElement('div');
        $ampAudio->setAttribute('class', $this->buildClassName($cssClass));

        // TODO

        return $ampAudio;
    }

    private function buildSlideshow($slideshow, $document, $cssClass)
    {
      $ampCarouselContainer = $document->createElement('div');
      $ampCarouselContainer->setAttribute('class', $this->buildClassName($cssClass));

      $ampCarousel = $document->createElement('amp-carousel');
      $ampCarouselContainer->appendChild($ampCarousel);

      foreach ($slideshow->getArticleImages() as $image) {
          $ampImage = $this->buildImage($image, $document, 'slideshow-image', true);
          $ampCarousel->appendChild($ampImage);

          // if (!isset($imageWidth) && !isset($imageHeight)) {
          //     $imageUrl = $image->getUrl();
          //     $imageDimensions = getimagesize($imageUrl);
          //     $imageWidth = $imageDimensions[0];
          //     $imageHeight = $imageDimensions[1];
          // }
      }
      if (isset($imageWidth) && isset($imageHeight)) {
          $ampCarousel->setAttribute('width', self::DEFAULT_WIDTH);
          $ampCarousel->setAttribute('height', self::DEFAULT_HEIGHT);
      }

      return $ampCarouselContainer;
    }

    private function buildIframe($interactive, $document, $cssClass)
    {
        $srcUrl = $interactive->getSource();

        // Based on $srcUrl build:
        // TODO check URLs for youtube
        // TODO check URLs for Facebook
        // TODO check URLs for Twitter
        // TODO check URLs for Instagram
        // TODO check URLs for Vimeo
        // TODO check URLs for Vine
        // TODO check URLs for playbuzz
        // TODO check URLs for soundcloud

        $iframeContainer = $document->createElement('div');
        $iframeContainer->setAttribute('class', $this->buildClassName($cssClass));

        $ampIframe = $document->createElement('amp-iframe');
        $iframeContainer->appendChild($ampIframe);
        $ampIframe->setAttribute('src', $srcUrl);
        $ampIframe->setAttribute('width', self::DEFAULT_WIDTH);
        $ampIframe->setAttribute('height', self::DEFAULT_HEIGHT);
        $ampIframe->setAttribute('sandbox', 'allow-scripts allow-same-origin');
        $ampIframe->setAttribute('layout', 'responsive');
        $ampIframe->setAttribute('frameborder', '0');

        return $iframeContainer;
    }

    private function buildMaps($map, $document, $cssClass)
    {
        // TODO google maps requires a key to embed
        // The URL should be: https://www.google.com/maps/embed/v1/place?key=<API_GOOGLE_KEY>q=%2244.0,122.0%22
        return $document->createElement('div');
    }

    private function buildClassName($selectorName, $prefix = null) {
        if (isset($prefix) || !$prefix) {
            if (isset($this->properties['css-selector-prefix'])) {
                $prefix = $this->properties['css-selector-prefix'];
            }
            else {
                $prefix = 'ia2amp-';
            }
        }
        return $prefix.$selectorName;
    }

    private function buildCustomCSS($document) {
      $ampCustomCSS = $document->createElement('style');
      $ampCustomCSS->setAttribute('amp-custom','');
      $cssDeclarationss = $this->getCustomCSS();
      $cssTextContent = $document->createTextNode($cssDeclarationss);
      $ampCustomCSS->appendChild($cssTextContent);
      return $ampCustomCSS;
    }

    public function getCustomCSS()
    {
        $stylesFolder = (array_key_exists(AMPArticle::STYLES_FOLDER_KEY, $this->properties)
            ? $this->properties[AMPArticle::STYLES_FOLDER_KEY]
            : __DIR__) . '/';

        // TODO: Make sure you don't have double slashes in the path above

        $styleName = $this->instantArticle->getStyle();
        if ($styleName == NULL) {
            $styleName = 'default';
        }
        // Try to get the IA styles from properties
        if (array_key_exists(AMPArticle::OVERRIDE_STYLES_KEY, $this->properties)) {
            $styles = $this->properties[AMPArticle::OVERRIDE_STYLES_KEY];
        }
        else {
            
            $stylesFile = file_get_contents($stylesFolder . $styleName . '.style.json');
            $styles = json_decode($stylesFile, true);
        }

        // TODO: Refactor this logic for custom CSS (global and style specific)
        $globalCSSFile = file_get_contents($stylesFolder . 'global.amp-custom.css');
        $globalCSSFile = str_replace(array("\r", "\n"), ' ', $globalCSSFile);

        $customCSSFile = file_get_contents($stylesFolder . $styleName . '.amp-custom.css');
        $customCSSFile = str_replace(array("\r", "\n"), ' ', $customCSSFile);

        return $this->articleColorsStyles($styles) .
            $this->articleHeadStyles($styles) .
            $this->articleBodyStyles($styles) .
            $this->articleQuoteStyles($styles) .
            $this->articleCaptionStyles($styles) .
            $this->articleAdditionalCaptionStyles($styles) .
            $this->articleFooterStyles($styles) .
            $globalCSSFile .
            $customCSSFile;
    }

    private function articleColorsStyles($styles)
    {
      $backgroundColor = AMPArticle::toRGB($styles['background_color']);
      return "html {background-color: $backgroundColor;}";
    }

    private function articleHeadStyles($styles)
    {
        $mappings = array(
            // TODO: Logo
            // TODO: Shoud this class be 'ia2amp-header-kicker'
            '.ia2amp-header-category' => 'kicker',
            '.ia2amp-header-h1' => 'title',
            '.ia2amp-header-h2' => 'subtitle',
            '.ia2amp-header h3' => 'byline'
        );

        // Move to constant/static
        $dateFormatMappings = array(
            'MONTH_AND_DAY' => 'F d',
            'MONTH_AND_YEAR' => 'F Y',
            'MONTH_DAY_YEAR' => 'F d, Y',
            'YEAR' => 'Y',
            'MONTH_DAY_YEAR_TIME' => 'F d, Y H:i A',
        );
        if (array_key_exists('date_style', $styles)) {
            $dateFormat = $styles['date_style'];
            if (array_key_exists($dateFormat, $dateFormatMappings)) {
                $this->dateFormat = $dateFormatMappings[$dateFormat];
            }
        }

        return $this->buildCSSRulesFromMappings($mappings, $styles) .
            $this->articleLogo($styles);
    }

    private function articleLogo($styles) {
        $logoStyles = $styles['header'];
        // TODO: Add style for Like button
        return AMPArticle::buildCSSRule('.ia2amp-header-bar',
            AMPArticle::buildCSSDeclarationBlock(
                array(
                    AMPArticle::buildCSSDeclaration('background-color', $logoStyles['background_color'])
                )
            )
        );
    }

    private function articleBodyStyles($styles)
    {
        $mappings = array(
            '.ia2amp-h1' => 'primary_heading',
            '.ia2amp-h2' => 'secondary_heading',
            '.ia2amp-p' => 'body_text',
            '.ia2amp-article a' => 'inline_link',
        );
        return $this->buildCSSRulesFromMappings($mappings, $styles);
    }

    private function articleQuoteStyles($styles)
    {
        $mappings = array(
            '.ia2amp-blockquote' => 'block_quote',
            '.ia2amp-pullquote' => 'pull_quote',
            '.ia2amp-pullquote cite' => 'pull_quote_attribution',
        );
        return $this->buildCSSRulesFromMappings($mappings, $styles);
    }

    private function articleCaptionStyles($styles)
    {
        $mappings = array(
            // TODO: Validate selectors
            'figcaption h1' => 'caption_title_small',
            'figcaption h2' => 'caption_description_small',
            'figcaption cite' => 'caption_credit',
        );
        return $this->buildCSSRulesFromMappings($mappings, $styles);
    }

    private function articleAdditionalCaptionStyles($styles)
    {
        $mappings = array(
            // TODO: Define mappings for additional styles
        );
        return $this->buildCSSRulesFromMappings($mappings, $styles);
    }

    private function articleFooterStyles($styles)
    {
      $mappings = array(
            '.ia2amp-footer' => 'footer',
        );
        return $this->buildCSSRulesFromMappings($mappings, $styles);
    }

    private function buildTextCSSDeclarationBlock($textStyles, $textType)
    {
        // TODO: Move to constant
        // TODO: Determine how we want to handle this. React components use screen size and resolution to generate styles dynamically
        
        // $textSizes = array(
        //     'title' => 60,
        //     'subtitle' => 38,
        //     'body_text' => 34,
        //     'block_quote' => 34,
        //     'pull_quote' => 46,
        //     // TODO: Add all
        // );

        // TODO: Move to constant
        $mappings = array(
            'font-family' => 'font',
            'text-align' => 'text_alignment',
            'display' => 'display',
            // TODO: Implement
        );
        $filteredMappings = AMPArticle::filterMappings($mappings, $textStyles);

        // $textSize = ((array_key_exists($textType, $textSizes) ? $textSizes[$textType] : 25) * $textStyles['text_size_scale']) . 'px';
        // $filteredMappings['font-size'] = $textSize;
        // $filteredMappings['line-height'] = ($textStyles['line_height_scale'] * 100) . '%';

        $textTransformMappings = array(
            'ALL_CAPS' => 'uppercase',
            'ALL_LOWER_CASE' => 'lowercase',
            'NONE' => 'none',
        );
        $filteredMappings['text-transform'] = $textTransformMappings[$textStyles['capitalization']];

        if (array_key_exists('underline', $textStyles) && $textStyles['underline'] != 'NONE') {
            $filteredMappings['text-decoration'] = 'underline';
        }

        if (array_key_exists('background_color', $textStyles)) {
            $filteredMappings['background-color'] = AMPArticle::toRGB($textStyles['background_color']);
        }

        if (array_key_exists('color', $textStyles)) {
            $filteredMappings['color'] = AMPArticle::toRGB($textStyles['color']);
        }

        $spacingMappings = array(
            'NONE' => 0,
            'DOCUMENT_MARGIN' => AMPArticle::DEFAULT_MARGIN,
            'EXTRA_SMALL' => 16,
            'SMALL' => 32,
            'MEDIUM' => 46,
            'LARGE' => 64,
            'EXTRA_LARGE' => 96,
        );
        $marginMappings = AMPArticle::getSpacingDeclarationBlocks($spacingMappings, 'margin', $textStyles);
        $filteredMappings = array_merge($filteredMappings, $marginMappings);
        $paddingMappings = AMPArticle::getSpacingDeclarationBlocks($spacingMappings, 'padding', $textStyles);
        $filteredMappings = array_merge($filteredMappings, $paddingMappings);

        $borderMappings = AMPArticle::getBorderDeclarationBlocks($textStyles);
        $filteredMappings = array_merge($filteredMappings, $borderMappings);

        $cssDeclarations = array();
        foreach ($filteredMappings as $filteredKey => $cssValue) {
            $cssDeclarations[] = AMPArticle::buildCSSDeclaration($filteredKey, $cssValue);
        }
        return AMPArticle::buildCSSDeclarationBlock($cssDeclarations);
    }

    private static function filterMappings($mappings, $styles)
    {
        $result = array();
        foreach ($mappings as $cssKey => $styleKey) {
            if (array_key_exists($styleKey, $styles)) {
                $result[$cssKey] = $styles[$styleKey];
            }
        }
        return $result;
    }

    private static function buildCSSDeclaration($cssKey, $cssValue)
    {
        return "$cssKey: $cssValue;";
    }

    private static function buildCSSDeclarationBlock($cssDeclarations)
    {
        return '{' . implode(' ', $cssDeclarations) . '}';
    }

    private static function buildCSSRule($cssSelector, $cssDeclarationBlock)
    {
        return "$cssSelector $cssDeclarationBlock";
    }

    private static function getSpacingDeclarationBlocks($spacingMappings, $spacingType, $textStyles)
    {
        $directions = array(
            'top',
            'right',
            'bottom',
            'left',
        );
        $spacingStyles = $textStyles[$spacingType];
        $spacings = array();
        foreach ($directions as $direction) {
            $spacing = AMPArticle::getDirectionSpacing($spacingMappings, $direction, $spacingStyles);
            $spacings[] = $spacing != 0 ? $spacing . 'px' : '0';
        }
        return array($spacingType => implode(' ', $spacings));
    }

    private function buildCSSRulesFromMappings($mappings, $styles)
    {
        $rule = '';
        foreach ($mappings as $selector => $objectKey) {
            if (array_key_exists($objectKey, $styles)) {
                $declarationBlock = $this->buildTextCSSDeclarationBlock($styles[$objectKey], $objectKey);
                $rule = $rule . AMPArticle::buildCssRule($selector, $declarationBlock);
            }
        }
        return $rule;
    }

    private static function getDirectionSpacing($spacingMappings, $direction, $spacingStyles)
    {
        return array_key_exists($direction, $spacingStyles)
            ? AMPArticle::getSpacing($spacingMappings, $spacingStyles[$direction])
            : 0;
    }

    private static function getSpacing($spacingMappings, $spacingDirectionStyles) {
        $size = $spacingDirectionStyles['size'];
        $scalingFactor = $spacingDirectionStyles['scaling_factor'];
        return $spacingMappings[$size] * $scalingFactor;
    }

    private static function getBorderDeclarationBlocks($textStyles)
    {
        // TODO: Move to constant
        $directions = array(
            'top',
            'right',
            'bottom',
            'left',
        );
        $borderStyles = $textStyles['border'];
        $declarationBlocks = array();
        $borderWidths = array();
        foreach ($directions as $direction) {
            if (!array_key_exists($direction, $borderStyles)) {
                continue;
            }
            $borderDirectionStyles = $borderStyles[$direction];
            $borderWidth = $borderDirectionStyles['width'];
            $borderWidths[] = $borderWidth != 0 ? $borderWidth . 'px' : '0';
            if (array_key_exists('color', $borderDirectionStyles)) {
                $declarationBlocks["border-$direction-color"] = AMPArticle::toRGB($borderDirectionStyles['color']);
            }
        }
        $declarationBlocks['border-width'] = implode(' ', $borderWidths);
        $declarationBlocks['border-style'] = 'solid';
        return $declarationBlocks;
    }

    public static function toRGB($color)
    {
        if ($color[0] == '#')
            $color = substr($color, 1);

        $opacity = 1.0;
        if (strlen($color) == 8) {
            $opacity = round(hexdec(substr($color, 0, 2)) / 255, 2);
            $color = substr($color, 2);
        }

        $hex = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);
        $rgb = array_map('hexdec', $hex);

        return $opacity == 1.0
            ? 'rgb(' . implode(",", $rgb) . ')'
            :  'rgba(' . implode(",", $rgb) . ',' . $opacity . ')';
    }

    public function buildSchemaOrgMetadata() {
        $header = $this->instantArticle->getHeader();
        $published = $header->getPublished();
        $modified = $header->getModified();

        // TODO: Finish implementation
        $metadata = array(
            '@content' => 'http://schema.org',
            // TODO: Should we always use NewsArticle as Type? Some of the fields below depend on the type
            '@type' => 'NewsArticle',
            'mainEntityOfPage' => $this->instantArticle->getCanonicalURL(),
            // TODO: Is there a better way to get the text?
            'headline' => $this->instantArticle->getHeader()->getTitle()->getTextChildren()[0],
            'datePublished' => date_format($published->getDatetime(), 'c'),
            // TODO: How can we define a description?
            // 'description' => 'Lorem ipsum',  
        );

        if ($modified) {
            $metadata['dateModified'] = date_format($modified->getDatetime(), 'c');
        }

        $authors = $header->getAuthors();
        foreach($authors as $author) {
            $metadata['author'] = array(
                '@type' => 'Person',
                'name' => $author->getName(),
            );
            break; // TODO: How to define multiple authors?
        }

        $cover = $header->getCover();
        if ($cover) {
            if (Type::is($cover, Image::getClassName())) {
                $metadata['image'] = array(
                    '@type' => 'ImageObject',
                    'url' => $cover->getUrl(),
                    // TODO: Get image dimensions
                    'width' => 380,
                    'height' => 240,
                );
            }
            // TODO: Should we take the the first image from a slideshow?
        }

        // Prevent URL slashes to be escaped
        return json_encode($metadata, JSON_UNESCAPED_SLASHES);
    }
}
