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
use Facebook\InstantArticles\Hook\Hook;

class AMPArticle extends Element implements InstantArticleInterface
{
    const DEFAULT_MARGIN = 16.4;
    const DEFAULT_WIDTH = 380;
    const DEFAULT_HEIGHT = 240;
    const DEFAULT_DATE_FORMAT = 'F d, Y';
    const DEFAULT_CSS_PREFIX = 'ia2amp-';

    const STYLES_FOLDER_KEY = 'styles-folder';
    const OVERRIDE_STYLES_KEY = 'override-styles';

    private $instantArticle;
    /*
       'lang' => 'en-US',
       'css-selector-prefix' => 'ia2amp-',
     */
    private $properties = array();
    private $hook;

    private $dateFormat = AMPArticle::DEFAULT_DATE_FORMAT;
    private $logoURL;
    private $logoWidth;
    private $logoHeight;

    private function __construct($instantArticle, $properties, $hook)
    {
        $this->instantArticle = $instantArticle;
        $this->properties = $properties;
        $this->hook = $hook;
    }

    public static function create($instantArticleString, $properties = array(), $hook = null)
    {
        libxml_use_internal_errors(true);
        $document = new \DOMDocument();
        $document->loadHTML($instantArticleString);
        libxml_use_internal_errors(false);

        $parser = new Parser();
        $instant_article = $parser->parse($document);

        if ($properties === null) {
            $properties = array();
        }

        if ($hook == null) {
            $hook = Hook::create();
        }

        return new self($instant_article, $properties, $hook);
    }

    public function getHook()
    {
        return $this->hook;
    }

    public function getInstantArticle()
    {
        return $this->instantArticle;
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
        if (isset($this->properties['css-selector-prefix'])) {
            $prefix = $this->properties['css-selector-prefix'];
        }
        else {
            $prefix = self::DEFAULT_CSS_PREFIX;
        }
        $context = AMPContext::create($document, $this->instantArticle, $prefix);

        $ampDocument = $this->hook->call('HOOK_AMP_DOCUMENT', array($this, 'transformInstantArticle'), array($context));
        return $ampDocument;
    }

    public function transformInstantArticle($context) {
        // Builds and appends head to the HTML document
        $html = $context->createElement('html', null, null, array("amp" => ""));
        if ($context->getInstantArticle()->isRTLEnabled()) {
            $html->setAttribute('dir', 'rtl');
        }
        if (isset($this->properties['lang'])) {
            $html->setAttribute('lang', $this->properties['lang']);
        }
        $context->withHtml($html);

        $head = $this->hook->call('HOOK_AMP_HEAD', array($this, 'transformMetaInfoHead'), array($context));
        $context->withHead($head);

        // Build and append body and article tags to the HTML document
        $body = $this->hook->call('HOOK_AMP_BODY', array($this, 'buildBody'), array($context));
        $context->withBody($body);

        $header = $this->hook->call('HOOK_AMP_HEADER', array($this, 'transformArticleHeader'), array($context));
        $context->withHeader($header);

        $article = $this->hook->call('HOOK_AMP_ARTICLE', array($this, 'transformArticleContent'), array($context));
        $context->withArticle($article);

        $footer = $this->hook->call('HOOK_AMP_FOOTER', array($this, 'transformArticleFooter'), array($context));
        //$context->withFooter($footer);

        return $html;
    }

    public function buildBody($context)
    {
        return $context->createElement('body', $context->getHtml(), 'body');
    }

    public function transformArticleHeader($context)
    {
        // Builds the content Header, with proper colors and image, adding to body
        $header = $context->createElement('header', $context->getBody(), 'header');


        if (isset($this->logoURL)) {
            $logoURL = $this->logoURL;
            $logoWidth = $this->logoWidth;
            $logoHeight = $this->logoHeight;
        }

        // Creates the cover content for the header and appends to the header
        if ($context->getInstantArticle()->getHeader()->getCover()) {
            $headerCover = $this->buildCover($context->getInstantArticle()->getHeader()->getCover(), $context);
            $header->appendChild($headerCover);
        }

        // Creates the header bar with image (maybe fb like?) and appends to header
        $headerBar = $context->createElement('div', $header, 'header-bar');
        $context->buildSpacingDiv($header);
        if (isset($this->logoURL)) {
            $ampImageContainer = $context->createElement('div', $headerBar, 'header-bar-img-container');
            $ampImage = $context->createElement(
                'amp-img',
                $ampImageContainer,
                null,
                array(
                    'src' => $logoURL,
                    'width' => $logoWidth,
                    'height' => $logoHeight
                ));
        }

        // The kicker for article
        if ($context->getInstantArticle()->getHeader()->getKicker()) {
            $kicker = $context->createElement('h2', $header, 'header-category');
            $kicker->appendChild($context->getInstantArticle()->getHeader()->getKicker()->textToDOMDocumentFragment($context->getDocument()));
            $context->buildSpacingDiv($header);
        }

        // The Title for article
        $h1 = $context->createElement('h1', $header, 'header-h1');
        $h1->appendChild($context->getInstantArticle()->getHeader()->getTitle()->textToDOMDocumentFragment($context->getDocument()));
        $context->buildSpacingDiv($header);

        // The subtitle
        if ($context->getInstantArticle()->getHeader()->getSubtitle()) {
            $subtitle = $context->createElement('h2', $header, 'header-subtitle');
            $subtitle->appendChild($context->getInstantArticle()->getHeader()->getSubtitle()->textToDOMDocumentFragment($context->getDocument()));
            $context->buildSpacingDiv($header);
        }

        // The article authors
        $authors = $context->createElement('h3', $header, 'header-author');
        $authorsElement = $context->getInstantArticle()->getHeader()->getAuthors();
        $authorsString = [];
        foreach($authorsElement as $author) {
            $authorsString[] = $author->getName();
        }
        $authors->appendChild($context->getDocument()->createTextNode('BY '.implode($authorsString, ', ')));
        $context->buildSpacingDiv($header);

        // Aritcle publish date
        $publishDate = $context->createElement('h3', $header, 'header-date');
        $datetime = $context->getInstantArticle()->getHeader()->getPublished()->getDatetime();
        $publishDate->appendChild($context->getDocument()->createTextNode(date_format($datetime, $this->dateFormat)));
        $context->buildSpacingDiv($header);

        return $header;
    }

    public function transformMetaInfoHead($context)
    {
        // Builds the Head
        $head = $context->createElement('head');
        $context->getHtml()->appendChild($head);

        // Builds meta charset and append to head
        if ($context->getInstantArticle()->getCharset()) {
            $context->createElement('meta', $head, null, array('charset' => $context->getInstantArticle()->getCharset()));
        }

        // Builds meta viewport and append to head
        $context->createElement(
            'meta',
            $head,
            null,
            array(
                'name' => 'viewport',
                'content' => 'width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no'
            ));

        // Builds ampjs script and append to head
        $context->createElement('script', $head, null, array('src' => 'https://cdn.ampproject.org/v0.js', 'async' => ''));

        // Builds boilerplate css style and append to head
        $boilerplate = $context->createElement('style', $head, null, array('amp-boilerplate' => ''));
        $boilerplateContent = $context->getDocument()->createTextNode('body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}');
        $boilerplate->appendChild($boilerplateContent);

        // Builds noscript css style and append to head
        $noscript = $context->createElement('noscript', $head);
        $noscriptBoilerplate = $context->createElement('style', $noscript, null, array('amp-boilerplate' => ''));
        $noscriptBoilerplateContent = $context->getDocument()->createTextNode('body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}');
        $noscriptBoilerplate->appendChild($noscriptBoilerplateContent);

        // Builds canonical link and append to head
        $link = $context->createElement('link', $head, null, array('rel' => 'canonical', 'href' => $context->getInstantArticle()->getCanonicalURL()));

        // Builds custom css style and append to head
        $ampCustomCSS = $this->buildCustomCSS($context);
        $head->appendChild($ampCustomCSS);

        // Builds Schema.org metadata and appends to head
        $discoveryScript = $context->createElement('script', $head, null, array('type' => 'application/ld+json'));
        $discoveryScriptContent = $this->buildSchemaOrgMetadata($context);
        $discoveryScript->appendChild($context->getDocument()->createTextNode($discoveryScriptContent));

        // Builds title and append to head
        $title = $context->createElement('title', $head);
        $titleText = $context->getInstantArticle()->getHeader()->getTitle()->textToDOMDocumentFragment($context->getDocument());
        $title->appendChild($titleText);

        return $head;
    }

    public function transformArticleContent($context)
    {
        $article = $context->createElement('article', $context->getBody(), 'article');

        $containsIframe = false;
        $containsSlideshow = false;
        $containsAudio = false;

        if ($context->getInstantArticle()->getChildren()) {
            foreach ($context->getInstantArticle()->getChildren() as $child) {
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
                if (Type::is($child, Paragraph::getClassName())) {
                    $childElement = $this->buildRegularDomElement($context, $child, 'p');
                }
                else if (Type::is($child, Blockquote::getClassName())) {
                    $childElement = $this->buildRegularDomElement($context, $child, 'blockquote');
                }
                else if (Type::is($child, H1::getClassName())) {
                    $childElement = $this->buildRegularDomElement($context, $child, 'h1');
                }
                else if (Type::is($child, H2::getClassName())) {
                    $childElement = $this->buildRegularDomElement($context, $child, 'h2');
                }
                else if (Type::is($child, ListElement::getClassName())) {
                    $childElement = $this->buildRegularDomElement($context, $child, 'list');
                }
                else if (Type::is($child, Pullquote::getClassName())) {
                    $childElement = $this->buildRegularDomElement($context, $child, 'pullquote');
                }
                else if (Type::is($child, Image::getClassName())) {
                    $childElement = $this->buildImage($child, $context, 'image');
                }
                else if (Type::is($child, AnimatedGIF::getClassName())) {
                    $childElement = $this->buildGIF($child, $context, 'gif');
                }
                else if (Type::is($child, Video::getClassName())) {
                    $childElement = $this->buildVideo($child, $context, 'video');
                }
                else if (Type::is($child, Audio::getClassName())) {
                    if (!$containsAudio) {
                        $containsAudio = true;
                        $context->getHead()->appendChild($this->buildCustomElementScriptEntry('amp-audio', 'https://cdn.ampproject.org/v0/amp-audio-0.1.js', $context));
                    }
                    $childElement = $this->buildAudio($child, $context, 'audio');
                }
                else if (Type::is($child, Slideshow::getClassName())) {
                    if (!$containsSlideshow) {
                        $containsSlideshow = true;
                        $context->getHead()->appendChild($this->buildCustomElementScriptEntry('amp-carousel', 'https://cdn.ampproject.org/v0/amp-carousel-0.1.js', $context));
                    }
                    $childElement = $this->buildSlideshow($child, $context, 'slideshow');
                }
                else if (Type::is($child, Interactive::getClassName()) || Type::is($child, SocialEmbed::getClassName())) {
                    if (!$containsIframe) {
                        $containsIframe = true;
                        $context->getHead()->appendChild($this->buildCustomElementScriptEntry('amp-iframe', 'https://cdn.ampproject.org/v0/amp-iframe-0.1.js', $context));
                    }
                    $childElement = $this->buildIframe($child, $context, 'interactive');
                }
                else if (Type::is($child, Map::getClassName())) {
                    if (!$containsIframe) {
                        $containsIframe = true;
                        $context->getHead()->appendChild($this->buildCustomElementScriptEntry('amp-iframe', 'https://cdn.ampproject.org/v0/amp-iframe-0.1.js', $context));
                    }
                    $childElement = $this->buildMaps($child, $context, 'map');
                }
                else if (Type::is($child, RelatedArticles::getClassName())) {
                    $childElement->setAttribute('class', $context->buildCssClass('related-articles'));
                    // TODO RelatedArticles
                }
                else {
                    // Not a know element, bypasses it
                    continue;
                }

                $context->addItem($childElement);
                $article->appendChild($childElement);
                $context->buildSpacingDiv($article);
            }
        }

        return $article;
    }

    public function buildRegularDomElement($context, $child, $cssClass)
    {
        $element = $child->toDOMElement($context->getDocument());
        $element->setAttribute('class', $context->buildCssClass($cssClass));
        $context->withPreviousElementIdentifier($cssClass);

        return $element;
    }

    public function transformArticleFooter()
    {
        // if ($instantArticle->getFooter() && $this->instantArticle->getFooter()->isValid()) {
        //     $article->appendChild($this->instantArticle->getFooter()->toDOMElement($context->getDocument()));
        // }
        return null;
    }

    private function buildCover($media, $context)
    {
        if (Type::is($media, Image::getClassName())) {
            return $this->buildImage($media, $context, 'cover-image', false);
        }
        else if (Type::is($media, Slideshow::getClassName())) {
            return $this->buildSlideshow($media, $context, 'cover-slideshow');
        }
        else if (Type::is($media, Video::getClassName())) {
            return $this->buildVideo($media, $context, 'cover-video');
        }
    }

    public function buildCustomElementScriptEntry($customElementName, $src, $context)
    {
        $script = $context->getDocument()->createElement('script');
        $script->setAttribute('async', '');
        $script->setAttribute('custom-element', $customElementName);
        $script->setAttribute('src', $src);
        return $script;
    }

    private function buildImage($image, $context, $cssClass, $withContainer = true)
    {
        if ($withContainer) {
            $ampImgContainer = $context->createElement('div', null, $cssClass);
        }

        $ampImg = $context->getDocument()->createElement('amp-img');
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

    private function buildGIF($image, $context, $cssClass, $withContainer = true)
    {
        if ($withContainer) {
            $ampImgContainer = $context->createElement('div', null, $cssClass);
        }

        $ampImg = $context->getDocument()->createElement('amp-anim');
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

    private function buildVideo($video, $context, $cssClass)
    {
        $ampVideoContainer = $context->createElement('div', null, $cssClass);

        $ampVideo = $context->getDocument()->createElement('amp-video');
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

    private function buildAudio($video, $context, $cssClass)
    {
        $ampAudio = $context->createElement('div', null, $cssClass);

        // TODO

        return $ampAudio;
    }

    private function buildSlideshow($slideshow, $context, $cssClass)
    {
      $ampCarouselContainer = $context->createElement('div', null, $cssClass);

      $ampCarousel = $context->getDocument()->createElement('amp-carousel');
      $ampCarouselContainer->appendChild($ampCarousel);

      foreach ($slideshow->getArticleImages() as $image) {
          $ampImage = $this->buildImage($image, $context, 'slideshow-image', true);
          $ampCarousel->appendChild($ampImage);

          // if (!isset($imageWidth) && !isset($imageHeight)) {
          //     $imageUrl = $image->getUrl();
          //     $imageDimensions = getimagesize($imageUrl);
          //     $imageWidth = $imageDimensions[0];
          //     $imageHeight = $imageDimensions[1];
          // }
      }
      $ampCarousel->setAttribute('width', (string) self::DEFAULT_WIDTH);
      $ampCarousel->setAttribute('height', (string) self::DEFAULT_HEIGHT);

      $context->withPreviousElementIdentifier($cssClass);

      return $ampCarouselContainer;
    }

    private function buildIframe($interactive, $context, $cssClass)
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

        $iframeContainer = $context->createElement('div', null, $cssClass);

        $ampIframe = $context->getDocument()->createElement('amp-iframe');
        $iframeContainer->appendChild($ampIframe);
        $ampIframe->setAttribute('src', $srcUrl);
        $ampIframe->setAttribute('width', self::DEFAULT_WIDTH);
        $ampIframe->setAttribute('height', self::DEFAULT_HEIGHT);
        $ampIframe->setAttribute('sandbox', 'allow-scripts allow-same-origin');
        $ampIframe->setAttribute('layout', 'responsive');
        $ampIframe->setAttribute('frameborder', '0');

        return $iframeContainer;
    }

    private function buildMaps($map, $context, $cssClass)
    {
        // TODO google maps requires a key to embed
        // The URL should be: https://www.google.com/maps/embed/v1/place?key=<API_GOOGLE_KEY>q=%2244.0,122.0%22
        return $context->createElement('div');
    }

    private function buildCustomCSS($context) {
      $ampCustomCSS = $context->getDocument()->createElement('style');
      $ampCustomCSS->setAttribute('amp-custom','');
      $cssDeclarations = $this->getCustomCSS($context);
      $cssTextContent = $context->getDocument()->createTextNode($cssDeclarations);
      $ampCustomCSS->appendChild($cssTextContent);
      return $ampCustomCSS;
    }

    public function getCustomCSS($context)
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

        return $this->articleColorsStyles($styles, $context) .
            $this->articleHeadStyles($styles, $context) .
            $this->articleBodyStyles($styles, $context) .
            $this->articleQuoteStyles($styles, $context) .
            $this->articleCaptionStyles($styles, $context) .
            $this->articleAdditionalCaptionStyles($styles, $context) .
            $this->articleFooterStyles($styles, $context) .
            $globalCSSFile .
            $customCSSFile;
    }

    private function articleColorsStyles($styles, $context)
    {
      $backgroundColor = AMPArticle::toRGB($styles['background_color']);
      return "html {background-color: $backgroundColor;}";
    }

    private function articleHeadStyles($styles, $context)
    {
        $mappings = array(
            // TODO: Logo
            // TODO: Shoud this class be 'ia2amp-header-kicker'
            $context->buildCssSelector('header-category') => 'kicker',
            $context->buildCssSelector('header-h1') => 'title',
            $context->buildCssSelector('header-h2') => 'subtitle',
            $context->buildCssSelector('header h3') => 'byline'
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

        return $this->buildCSSRulesFromMappings($mappings, $styles, $context) .
            $this->articleLogo($styles, $context);
    }

    private function articleLogo($styles, $context) {
        $headerStyles = $styles['header'];
        // TODO: Add style for Like button
        // TODO: Build class name
        $barStyles = AMPArticle::buildCSSRule('.ia2amp-header-bar',
            AMPArticle::buildCSSDeclarationBlock(
                array(
                    AMPArticle::buildCSSDeclaration('background-color', $headerStyles['background_color'])
                )
            )
        );

        // TODO: Should we move the code below to another place?
        // It is not really generating any CSS as the width and height are required fields of amp-image

        if (!array_key_exists('logo', $headerStyles)) {
            return '';
        }
        $logoStyles = $headerStyles['logo'];

        $dataURL = $logoStyles['dataURL'];
        $fullResURL = $logoStyles['full_resolution_url'];

        $defaultLogoHeight = 44; // TODO: Move to other place
        $defaultLogoWidth = 230; // TODO: Move to other place
        $logoWidth = $logoStyles['full_resolution_width'];
        $logoHeight = $logoStyles['full_resolution_height'];
        $resizeScale = $headerStyles['logo_scale'] || 1.0;
        $resizeScale *= min(
            $defaultLogoHeight / $logoHeight,
            $defaultLogoWidth / $logoWidth
        );

        $this->logoURL = $dataURL ? $dataURL : $fullResURL;
        $this->logoWidth = (int) ($logoWidth * $resizeScale);
        $this->logoHeight = (int) ($logoHeight * $resizeScale);

        return $barStyles;
    }

    private function articleBodyStyles($styles, $context)
    {
        $mappings = array(
            $context->buildCssSelector('h1') => 'primary_heading',
            $context->buildCssSelector('h2') => 'secondary_heading',
            $context->buildCssSelector('p') => 'body_text',
            $context->buildCssSelector('article a') => 'inline_link',
        );
        return $this->buildCSSRulesFromMappings($mappings, $styles, $context);
    }

    private function articleQuoteStyles($styles, $context)
    {
        $mappings = array(
            $context->buildCssSelector('blockquote') => 'block_quote',
            $context->buildCssSelector('pullquote') => 'pull_quote',
            $context->buildCssSelector('pullquote cite') => 'pull_quote_attribution',
        );
        return $this->buildCSSRulesFromMappings($mappings, $styles, $context);
    }

    private function articleCaptionStyles($styles, $context)
    {
        $mappings = array(
            // TODO: Validate selectors
            'figcaption h1' => 'caption_title_small',
            'figcaption h2' => 'caption_description_small',
            'figcaption cite' => 'caption_credit',
        );
        return $this->buildCSSRulesFromMappings($mappings, $styles, $context);
    }

    private function articleAdditionalCaptionStyles($styles, $context)
    {
        $mappings = array(
            // TODO: Define mappings for additional styles
        );
        return $this->buildCSSRulesFromMappings($mappings, $styles, $context);
    }

    private function articleFooterStyles($styles, $context)
    {
      $mappings = array(
            $context->buildCssSelector('footer') => 'footer',
        );
        return $this->buildCSSRulesFromMappings($mappings, $styles, $context);
    }

    private function buildTextCSSDeclarationBlock($textStyles, $textType, $context)
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
        if (array_key_exists('capitalization', $textStyles)) {
            $filteredMappings['text-transform'] = $textTransformMappings[$textStyles['capitalization']];
        }

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
        if (!array_key_exists($spacingType, $textStyles)) {
            return array();
        }
        $spacingStyles = $textStyles[$spacingType];
        $spacings = array();
        foreach ($directions as $direction) {
            $spacing = AMPArticle::getDirectionSpacing($spacingMappings, $direction, $spacingStyles);
            $spacings[] = $spacing != 0 ? $spacing . 'px' : '0';
        }
        return array($spacingType => implode(' ', $spacings));
    }

    private function buildCSSRulesFromMappings($mappings, $styles, $context)
    {
        $rule = '';
        foreach ($mappings as $selector => $objectKey) {
            if (array_key_exists($objectKey, $styles)) {
                $declarationBlock = $this->buildTextCSSDeclarationBlock($styles[$objectKey], $objectKey, $context);
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
        if (!array_key_exists('border', $textStyles)) {
            return array();
        }
        $borderStyles = $textStyles['border'];
        $declarationBlocks = array();
        $borderWidths = array();
        foreach ($directions as $direction) {
            if (array_key_exists($direction, $borderStyles)) {
                $borderDirectionStyles = $borderStyles[$direction];
                $borderWidth = $borderDirectionStyles['width'];

                if (array_key_exists('color', $borderDirectionStyles)) {
                    $declarationBlocks["border-$direction-color"] = AMPArticle::toRGB($borderDirectionStyles['color']);
                }
            }
            else {
                $borderWidth = 0;
            }
            $borderWidths[] = $borderWidth !== 0 ? $borderWidth . 'px' : '0';
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

    public function buildSchemaOrgMetadata($context) {
        $header = $this->instantArticle->getHeader();
        $published = $header->getPublished();
        $modified = $header->getModified();

        // TODO: Finish implementation
        $metadata = array(
            '@content' => 'http://schema.org',
            // TODO: Should we always use NewsArticle as Type? Some of the fields below depend on the type
            '@type' => 'NewsArticle',
            'mainEntityOfPage' => $this->instantArticle->getCanonicalURL(),
            'headline' => $this->instantArticle->getHeader()->getTitle()->getPlainText(),
            'datePublished' => date_format($published->getDatetime(), 'c'),
            'description' => $this->instantArticle->getFirstParagraph()->getPlainText(),
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

        $publisher = $this->hook->call('HOOK_AMP_GETPUBLISHER', array($this, 'getPublisher'), array($this->properties));
        if ($publisher) {
            if (Type::is($publisher, Type::STRING)) {
                // String values will be treated as organization names
                $publisher = array(
                    '@type' => 'Organization',
                    'name' => $publisher,
                );
            }
            $metadata['publisher'] = $publisher;
        }

        // Prevent URL slashes to be escaped
        return json_encode($metadata, JSON_UNESCAPED_SLASHES);
    }

    public function getPublisher($properties)
    {
        return array_key_exists('publisher', $properties) ? $properties['publisher'] : null;
    }
}
