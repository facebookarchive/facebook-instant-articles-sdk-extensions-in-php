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
use Facebook\InstantArticles\Elements\Ad;
use Facebook\InstantArticles\Elements\H1;
use Facebook\InstantArticles\Elements\H2;
use Facebook\InstantArticles\Elements\ListElement;
use Facebook\InstantArticles\Elements\Pullquote;
use Facebook\InstantArticles\Elements\Image;
use Facebook\InstantArticles\Elements\Caption;
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
use Facebook\InstantArticles\Utils\Observer;
use Facebook\InstantArticles\Utils\Warning;

class AMPArticle extends Element implements InstantArticleInterface
{
    const DEFAULT_MARGIN = 16.4;
    const DEFAULT_WIDTH = 380;
    const DEFAULT_HEIGHT = 240;
    const DEFAULT_LOGO_WIDTH = 230;
    const DEFAULT_LOGO_HEIGHT = 44;
    const DEFAULT_DATE_FORMAT = 'F d, Y';
    const DEFAULT_CSS_PREFIX = 'ia2amp-';

    const STYLES_FOLDER_KEY = 'styles-folder';
    const OVERRIDE_STYLES_KEY = 'override-styles';
    const MEDIA_CACHE_FOLDER_KEY = 'media-cache-folder';
    const ENABLE_DOWNLOAD_FOR_MEDIA_SIZING_KEY = 'enable-download-for-media-sizing';
    const DEFAULT_MEDIA_WIDTH_KEY = 'default-media-width';
    const DEFAULT_MEDIA_HEIGHT_KEY = 'default-media-height';
    const MEDIA_SIZES_KEY = 'media-sizes';
    const PUBLISHER_KEY = 'publisher';
    const GOOGLE_MAPS_KEY = 'google_maps_key';

    const MEDIA_TYPE_IMAGE = 'image';
    const MEDIA_TYPE_VIDEO = 'video';

    private $instantArticle;
    /*
       'lang' => 'en-US',
       'css-selector-prefix' => 'ia2amp-',
       'styles-folder' => '/articles/styles'
       // TODO: Is the value below the expected default value?
       'media-cache-folder' => '/articles/media',
       'enable-download-for-media-sizing' => FALSE,
       'default-media-width' => 380,
       'default-media-height' => 240,
       'media-sizes' => array(),
       'publisher' => array(),
     */
    private $properties = array();

    /**
     * @var Observer The instance for Observing and Hooking system for extensions
     */
    private $observer;

    private $dateFormat = AMPArticle::DEFAULT_DATE_FORMAT;
    private $logo;

    private $articleCustomCSSRules;
    private $customCSSElement;
    private $ampHeader;

    private function __construct($instantArticle, $properties, $observer)
    {
        $this->instantArticle = $instantArticle;
        $this->properties = $properties;
        $this->observer = $observer;
    }

    public static function create($instantArticleString, $properties = array(), $observer = null)
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

        if ($observer == null) {
            $observer = Observer::create();
        }

        return new self($instant_article, $properties, $observer);
    }

    public function getObserver()
    {
        return $this->observer;
    }

    public function getInstantArticle()
    {
        return $this->instantArticle;
    }

    public function render($doctype = '<!doctype html>', $format = true)
    {
        $doctype = is_null($doctype) ? '<!doctype html>' : $doctype;
        $rendered = parent::render($doctype, $format);

        // Makes empty value attribute definition, since we use DOMDocument::saveXML()
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
        } else {
            $prefix = self::DEFAULT_CSS_PREFIX;
        }
        $context = AMPContext::create($document, $this->instantArticle, $prefix);

        $ampDocument = $this->observer->applyFilters('AMP_DOCUMENT', $this->transformInstantArticle($context), $context);
        return $ampDocument;
    }

    public function transformInstantArticle($context)
    {
        $this->articleCustomCSSRules = array();

        // Builds and appends head to the HTML document
        $html = $context->createElement('html', null, null, array("amp" => ""));
        if ($context->getInstantArticle()->isRTLEnabled()) {
            $html->setAttribute('dir', 'rtl');
        }
        if (isset($this->properties['lang'])) {
            $html->setAttribute('lang', $this->properties['lang']);
        }
        $context->withHtml($html);

        $head = $this->observer->applyFilters('AMP_HEAD', $this->transformMetaInfoHead($context), $context);
        $context->withHead($head);

        // Build and append body and article tags to the HTML document
        $body = $this->observer->applyFilters('AMP_BODY', $this->buildBody($context), $context);
        $context->withBody($body);

        $header = $this->observer->applyFilters('AMP_HEADER', $this->transformArticleHeader($context), $context);
        $context->withHeader($header);

        $article = $this->observer->applyFilters('AMP_ARTICLE', $this->transformArticleContent($context), $context);
        $context->withArticle($article);

        $footer = $this->observer->applyFilters('AMP_FOOTER', $this->transformArticleFooter($context), $context);
        //$context->withFooter($footer);

        // Set the Custom CSS content
        $cssDeclarations = $this->getCustomCSS($context);
        $cssTextContent = $context->getDocument()->createTextNode($cssDeclarations);
        $this->customCSSElement->appendChild($cssTextContent);

        // Create the logo image, if set
        $this->ampHeader->genHeaderLogo($this->logo);
        // Create the text element for the published date using parsed format from styles
        $this->ampHeader->genArticlePublishDate($this->dateFormat);

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

        // Creates the cover content for the cover and appends to the header
        if ($context->getInstantArticle()->getHeader()->getCover()) {
            $headerCover = $this->buildCover($context->getInstantArticle()->getHeader()->getCover(), $context);
            $header->appendChild($headerCover);
        }

        $this->ampHeader = new AMPHeader($header, $context, $this->dateFormat);
        return $this->ampHeader->build();
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
            )
        );

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
        // Save element, so we can add the custom CSS content after the whole article is processed
        $this->customCSSElement = $ampCustomCSS;

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
        $containsVideo = false;

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
                    $childElement = $this->observer->applyFilters('IA_PARAGRAPH', $this->buildRegularDomElement($context, $child, 'p'), $child, $context);
                } else if (Type::is($child, Blockquote::getClassName())) {
                    $childElement = $this->observer->applyFilters('IA_BLOCKQUOTE', $this->buildRegularDomElement($context, $child, 'blockquote'), $child, $context);
                } else if (Type::is($child, H1::getClassName())) {
                    $childElement = $this->observer->applyFilters('IA_H1', $this->buildRegularDomElement($context, $child, 'h1'), $child, $context);
                } else if (Type::is($child, H2::getClassName())) {
                    $childElement = $this->observer->applyFilters('IA_H2', $this->buildRegularDomElement($context, $child, 'h2'), $child, $context);
                } else if (Type::is($child, ListElement::getClassName())) {
                    $childElement = $this->observer->applyFilters('IA_LIST', $this->buildRegularDomElement($context, $child, 'list'), $child, $context);
                } else if (Type::is($child, Pullquote::getClassName())) {
                    $childElement = $this->observer->applyFilters('IA_PULLQUOTE', $this->buildRegularDomElement($context, $child, 'pullquote'), $child, $context);
                } else if (Type::is($child, Image::getClassName())) {
                    $childElement = $this->observer->applyFilters('IA_IMAGE', $this->buildImage($child, $context, 'image'), $child, $context);
                } else if (Type::is($child, AnimatedGIF::getClassName())) {
                    $childElement = $this->observer->applyFilters('IA_GIF', $this->buildGIF($child, $context, 'gif'), $child, $context);
                } else if (Type::is($child, Video::getClassName())) {
                    if (!$containsVideo) {
                        $containsVideo = true;
                        $context->getHead()->appendChild($this->buildCustomElementScriptEntry('amp-video', 'https://cdn.ampproject.org/v0/amp-video-0.1.js', $context));
                    }
                    $childElement = $this->observer->applyFilters('IA_VIDEO', $this->buildVideo($child, $context, 'video'), $child, $context);
                } else if (Type::is($child, Audio::getClassName())) {
                    if (!$containsAudio) {
                        $containsAudio = true;
                        $context->getHead()->appendChild($this->buildCustomElementScriptEntry('amp-audio', 'https://cdn.ampproject.org/v0/amp-audio-0.1.js', $context));
                    }
                    $childElement = $this->observer->applyFilters('IA_AUDIO', $this->buildAudio($child, $context, 'audio'), $child, $context);
                } else if (Type::is($child, Slideshow::getClassName())) {
                    if (!$containsSlideshow) {
                        $containsSlideshow = true;
                        $context->getHead()->appendChild($this->buildCustomElementScriptEntry('amp-carousel', 'https://cdn.ampproject.org/v0/amp-carousel-0.1.js', $context));
                    }
                    $childElement = $this->observer->applyFilters('IA_SLIDESHOW', $this->buildSlideshow($child, $context, 'slideshow'), $child, $context);
                } else if (Type::is($child, Interactive::getClassName()) || Type::is($child, SocialEmbed::getClassName())) {
                    if (!$containsIframe) {
                        $containsIframe = true;
                        $context->getHead()->appendChild($this->buildCustomElementScriptEntry('amp-iframe', 'https://cdn.ampproject.org/v0/amp-iframe-0.1.js', $context));
                    }
                    $childElement = $this->observer->applyFilters('IA_INTERACTIVE', $this->buildIframe($child, $context, 'interactive', true), $child, $context);
                } else if (Type::is($child, Map::getClassName())) {
                    if (!$containsIframe) {
                        $containsIframe = true;
                        $context->getHead()->appendChild($this->buildCustomElementScriptEntry('amp-iframe', 'https://cdn.ampproject.org/v0/amp-iframe-0.1.js', $context));
                    }
                    $childElement = $this->observer->applyFilters('IA_MAP', $this->buildMaps($child, $context, 'map'), $child, $context);
                } else if (Type::is($child, RelatedArticles::getClassName())) {
                    $childElement->setAttribute('class', $context->buildCssClass('related-articles'));
                    // TODO RelatedArticles
                } else if (Type::is($child, Ad::getClassName())) {
                    $childElement = $this->observer->applyFilters('IA_ANALYTICS', $this->buildAnalytics($child, $context, 'analytics'), $child, $context);
                } else if (Type::is($child, Ad::getClassName())) {
                    $childElement = $this->observer->applyFilters('IA_AD', $this->buildAd($child, $context, 'ad'), $child, $context);
                } else {
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

    public function transformArticleFooter($context)
    {
        $footer = $this->instantArticle->getFooter();
        if ($footer && $footer->isValid()) {
            $ampFooter = $context->createElement('footer', null, 'footer');
            $context->getArticle()->appendChild($ampFooter);

            // Credits
            $credits = $footer->getCredits();
            if ($credits) {
                $ampCredits = $context->createElement('aside', $ampFooter);
                if (is_array($credits)) {
                    foreach ($credits as $paragraph) {
                        $ampCredits->appendChild($paragraph->toDOMElement($context->getDocument()));
                    }
                } else {
                    $ampCredits->appendChild($context->getDocument()->createTextNode($credits));
                }
            }

            // Copyright
            $copyright = $footer->getCopyright();
            if ($copyright) {
                $ampCopyright = $context->createElement('small', $ampFooter);
                $ampCopyright->appendChild($copyright->textToDOMDocumentFragment($context->getDocument()));
            }

            return $ampFooter;
        }
        return null;
    }

    private function buildCover($media, $context)
    {
        if (Type::is($media, Image::getClassName())) {
            return $this->buildImage($media, $context, 'cover-image', true, true);
        } else if (Type::is($media, Slideshow::getClassName())) {
            return $this->buildSlideshow($media, $context, 'cover-slideshow');
        } else if (Type::is($media, Video::getClassName())) {
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

    private function buildImage($image, $context, $cssClass, $withContainer = true, $enforceAspectRatio = false)
    {
        if ($withContainer) {
            $ampImgContainer = $context->createElement('div', null, $cssClass);
        } else {
            // If we are enforcing the aspect ratio we need the container
            $enforceAspectRatio = false;
        }

        $ampImg = $context->getDocument()->createElement('amp-img');
        $imageURL = $image->getUrl();

        $imageDimensions = $this->getMediaDimensions($imageURL, self::MEDIA_TYPE_IMAGE);
        $imageWidth = $imageDimensions[0];
        $imageHeight = $imageDimensions[1];

        if ($enforceAspectRatio) {
            $horizontalScale = self::DEFAULT_WIDTH / $imageWidth;
            $verticalScale = self::DEFAULT_HEIGHT / $imageHeight;
            $maxScale = max($horizontalScale, $verticalScale);

            $translateX = (int) (-($imageWidth * $maxScale - self::DEFAULT_WIDTH) / 2);
            $translateY = (int) (-($imageHeight * $maxScale - self::DEFAULT_HEIGHT) / 2);

            $imageCSSClass = $context->buildCssClass('header-cover-img');
            $ampImg->setAttribute('class', $imageCSSClass);

            $this->articleCustomCSSRules["amp-img.$imageCSSClass"] = array(
                'transform' => "translate({$translateX}px, {$translateY}px)",
            );
            $containerCSSClass = $ampImgContainer->getAttribute('class');
            $this->articleCustomCSSRules["div.$containerCSSClass"] = array(
                'width' => self::DEFAULT_WIDTH . 'px',
                'height' => self::DEFAULT_HEIGHT . 'px',
                'overflow' => 'hidden',
            );

            $imageWidth = (int) ($imageWidth * $maxScale);
            $imageHeight = (int) ($imageHeight * $maxScale);
        } else {
            // Somehow the full width on mobile is 380, so I resize image height on same ratio
            $resizedWidthFactor = (double) (self::DEFAULT_WIDTH / (int) $imageWidth);
            $imageWidth = self::DEFAULT_WIDTH;
            $imageHeight = (int) ($imageHeight * $resizedWidthFactor);
        }

        $ampImg->setAttribute('src', $imageURL);
        $ampImg->setAttribute('width', (string) $imageWidth);
        $ampImg->setAttribute('height', (string) $imageHeight);

        $caption = $image->getCaption();
        if ($caption) {
            $ampFigure = $this->buildCaption($caption, $context, $ampImg);

            // Replaces the top level image element with the figure
            $ampImg = $ampFigure;
        }

        if ($withContainer) {
            $ampImgContainer->appendChild($ampImg);
        }

        return ($withContainer) ? $ampImgContainer : $ampImg;
    }

    private function buildGIF($image, $context, $cssClass, $withContainer = true)
    {
        if ($withContainer) {
            $ampImgContainer = $context->createElement('div', null, $cssClass);
        }

        $ampImg = $context->getDocument()->createElement('amp-anim');
        $imageURL = $image->getUrl();

        $imageDimensions = $this->getMediaDimensions($imageURL, self::MEDIA_TYPE_IMAGE);
        $imageWidth = $imageDimensions[0];
        $imageHeight = $imageDimensions[1];

        $ampImg->setAttribute('src', $imageURL);
        $ampImg->setAttribute('width', $imageWidth);
        $ampImg->setAttribute('height', $imageHeight);

        $caption = $image->getCaption();
        if ($caption) {
            $ampFigure = $this->buildCaption($caption, $context, $image);

            // Replaces the top level image with the figure
            $ampImg = $ampFigure;
        }

        if ($withContainer) {
            $ampImgContainer->appendChild($ampImg);
        }

        return ($withContainer) ? $ampImgContainer : $ampImg;
    }

    private function buildVideo($video, $context, $cssClass)
    {
        $ampVideoContainer = $context->createElement('div', null, $cssClass);

        $ampVideo = $context->getDocument()->createElement('amp-video');
        $videoUrl = $video->getUrl();

        $videoDimensions = $this->getMediaDimensions($videoUrl, self::MEDIA_TYPE_VIDEO);
        $videoWidth = $videoDimensions[0];
        $videoHeight = $videoDimensions[1];

        $ampVideo->setAttribute('src', $this->ensureHttps($context, $videoUrl));
        $ampVideo->setAttribute('width', $videoWidth);
        $ampVideo->setAttribute('height', $videoHeight);

        $caption = $video->getCaption();
        if ($caption) {
            $ampFigure = $this->buildCaption($caption, $context, $ampVideo);

            // Replaces the top level video with the figure
            $ampVideo = $ampFigure;
        }

        $ampVideoContainer->appendChild($ampVideo);

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

        foreach ($slideshow->getArticleImages() as $image) {
            $ampImage = $this->buildImage($image, $context, 'slideshow-image', true);
            $ampCarousel->appendChild($ampImage);

            if (!isset($imageWidth) && !isset($imageHeight)) {
                $imageUrl = $image->getUrl();
                $imageDimensions = $this->getMediaDimensions($imageUrl, self::MEDIA_TYPE_IMAGE);
                $imageWidth = $imageDimensions[0];
                $imageHeight = $imageDimensions[1];
            }
        }
        $ampCarousel->setAttribute('width', (string) $imageWidth);
        $ampCarousel->setAttribute('height', (string) $imageHeight);

        $caption = $slideshow->getCaption();
        if ($caption) {
            $ampFigure = $this->buildCaption($caption, $context, $ampCarousel);

            // Replaces the top level carousel with the figure
            $ampCarousel = $ampFigure;
        }

        $ampCarouselContainer->appendChild($ampCarousel);

        $context->withPreviousElementIdentifier($cssClass);

        return $ampCarouselContainer;
    }

    private function buildCaption($caption, $context, $ampCaptionedElement)
    {
        $container = $context->createElement('figure', null, 'figure');

        $fontSize = $caption->getFontSize();
        $cssClass = 'figcaption-' . ($fontSize ? $fontSize : 'small');

        $ampCaption = $context->createElement('figcaption', $container, $cssClass);

        $position = $caption->getPosition();
        if (!$position) {
            $position = Caption::POSITION_BELOW;
        }

        if ($position === Caption::POSITION_BELOW) {
            $container->appendChild($ampCaptionedElement);
            $container->appendChild($ampCaption);
        } else {
            $container->appendChild($ampCaption);
            $container->appendChild($ampCaptionedElement);
        }

        // Title
        $title = $caption->getTitle();
        if ($title) {
            $ampCaptionTitle = $context->createElement('h1', $ampCaption);
            $ampTitleText = $title->textToDOMDocumentFragment($context->getDocument());
            $ampCaptionTitle->appendChild($ampTitleText);
        }

        // SubTitle
        $subTitle = $caption->getSubTitle();
        if ($subTitle) {
            $ampCaptionSubTitle = $context->createElement('h2', $ampCaption);
            $ampSubTitleText = $subTitle->textToDOMDocumentFragment($context->getDocument());
            $ampCaptionSubTitle->appendChild($ampSubTitleText);
        }

        // Text
        $ampCaptionText = $caption->textToDOMDocumentFragment($context->getDocument());
        $ampCaption->appendChild($ampCaptionText);

        // Credit
        $credit = $caption->getCredit();
        if ($credit) {
            $ampCaptionCredit = $context->createElement('cite', $ampCaption);
            $ampCreditText = $credit->textToDOMDocumentFragment($context->getDocument());
            $ampCaptionCredit->appendChild($ampCreditText);
        }

        $ampCSSClasses = array();
        $ampCSSClasses[] = $context->buildCssClass('figcaption');

        if ($caption->getFontSize()) {
            $ampCSSClasses[] = $context->buildCssClass($caption->getFontSize());
        } else {
            $ampCSSClasses[] = $context->buildCssClass(Caption::SIZE_SMALL);
        }
        if ($caption->getTextAlignment()) {
            $ampCSSClasses[] = $context->buildCssClass($caption->getTextAlignment());
        }
        if ($caption->getPosition()) {
            $ampCSSClasses[] = $context->buildCssClass($caption->getPosition());
        }
        if ($caption->getVerticalAlignment()) {
            $ampCSSClasses[] = $context->buildCssClass($caption->getVerticalAlignment());
        }

        $ampCaption->setAttribute('class', implode(' ', $ampCSSClasses));

        return $container;
    }

    private function buildIframe($interactive, $context, $cssClass, $isCaptionable)
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
        $ampIframe->setAttribute('src', $this->ensureHttps($context, $srcUrl));
        $ampIframe->setAttribute('width', self::DEFAULT_WIDTH);
        $ampIframe->setAttribute('height', self::DEFAULT_HEIGHT);
        $ampIframe->setAttribute('sandbox', 'allow-scripts allow-same-origin');
        $ampIframe->setAttribute('layout', 'responsive');
        $ampIframe->setAttribute('frameborder', '0');

        if ($isCaptionable) {
            $caption = $interactive->getCaption();
            if ($caption) {
                $ampFigure = $this->buildCaption($caption, $context, $ampIframe);

                // Replaces the top level iframe with the figure
                $ampIframe = $ampFigure;
            }
        }

        $iframeContainer->appendChild($ampIframe);

        return $iframeContainer;
    }

    private function buildAnalytics($analytics, $context, $cssClass)
    {
        $srcUrl = $analytics->getSource();

        $iframeContainer = $context->createElement('div', null, $cssClass);

        $ampIframe = $context->getDocument()->createElement('amp-iframe');
        $ampIframe->setAttribute('src', $this->ensureHttps($context, $srcUrl));
        $ampIframe->setAttribute('width', 1);
        $ampIframe->setAttribute('height', 1);
        $ampIframe->setAttribute('sandbox', 'allow-scripts allow-same-origin');
        $ampIframe->setAttribute('layout', 'responsive');
        $ampIframe->setAttribute('frameborder', '0');

        $iframeContainer->appendChild($ampIframe);

        $context->addWarning(
            'This article uses Analytics code, and you didnt implemented a custom analytics code. This might not be the most accurate way of tracking your code. See this documentation at https://www.ampproject.org/docs/reference/components/amp-analytics on how to build your analytics component. To extend component implementations use https://developers.facebook.com/docs/instant-articles/other-formats documentation.',
            $analytics
        );

        return $iframeContainer;
    }

    private function buildAd($ad, $context, $cssClass)
    {
        $srcUrl = $ad->getSource();
        $html = $ad->getHtml();
        $height = $ad->getHeight();
        $width = $ad->getWidth();

        $ampAdContainer = $context->createElement('div', null, $cssClass);
        $ampAd = $context->createElement('amp-iframe', $ampAdContainer);

        if (!Type::isTextEmpty($srcUrl)) {
            $ampAd->setAttribute('src', $this->ensureHttps($context, $srcUrl));
        }
        $ampAd->setAttribute('width', $width ? $width : self::DEFAULT_WIDTH);
        $ampAd->setAttribute('height', $height ? $height : self::DEFAULT_HEIGHT);
        $ampAd->setAttribute('sandbox', 'allow-scripts allow-same-origin');
        $ampAd->setAttribute('layout', 'responsive');
        $ampAd->setAttribute('frameborder', '0');

        if ($html) {
            $iframeBody = $context->getDocument()->importNode($html, true);
            $ampAd->appendChild($iframeBody);
        }

        return $ampAdContainer;
    }

    private function buildMaps($map, $context, $cssClass)
    {
        $geoTag = $map->getGeotag();
        if (!$geoTag) {
            $context->addWarning('Map::getGeotag() returned an empty map definition.', $map);
            return $context->createElement('div');
        }

        $googleAPIKey = isset($this->properties[self::GOOGLE_MAPS_KEY]) ? $this->properties[self::GOOGLE_MAPS_KEY] : null;
        $googleAPIKey = $this->getObserver()->applyFilters('GET_GOOGLE_MAPS_KEY', $googleAPIKey, $this->properties, $context);

        if (Type::isTextEmpty($googleAPIKey)) {
            $context->addWarning('Map by default converts Facebook Instant Article Maps into Google Maps. To accomplish that, you will need to inform into your $properties parameter the "google_maps_key" => "<your key>". Find more here how to get your Google Maps key: https://developers.google.com/maps/documentation/javascript/get-api-key', $map);
            return $context->createElement('div');
        }

        $coordinates = $this->extractCoordinatesFromGeotag($geoTag->getScript());
        if (!$coordinates || empty($coordinates)) {
            $context->addWarning('Map::getGeotag invalid or incompatible. We could not extract latitud and/or longitud from it.', $geoTag->getScript());
            return $context->createElement('div');
        }
        $latitud = $coordinates[0];
        $longitud = $coordinates[1];

        // By default it will use Google Maps as mapping system
        // The URL should be: https://www.google.com/maps/embed/v1/place?key=<API_GOOGLE_KEY>q=%2244.0,122.0%22
        $srcUrl = "https://www.google.com/maps/embed/v1/place?key=$googleAPIKey&q=%22$latitud,$longitud%22";

        // <amp-iframe
        //   width="600"
        //   height="400"
        //   layout="responsive"
        //   sandbox="allow-scripts allow-same-origin allow-popups"
        //   frameborder="0"
        //   src="https://www.google.com/maps/embed/v1/place?key=<key>&q="44.0,122.0">
        // </amp-iframe>

        $ampMap = $context->createElement('div', null, $cssClass);

        $ampIframe = $context->createElement('amp-iframe', $ampMap);
        $ampIframe->setAttribute('src', $srcUrl);
        $ampIframe->setAttribute('width', self::DEFAULT_WIDTH);
        $ampIframe->setAttribute('height', self::DEFAULT_HEIGHT);
        $ampIframe->setAttribute('sandbox', 'allow-scripts allow-same-origin allow-popups');
        $ampIframe->setAttribute('layout', 'responsive');
        $ampIframe->setAttribute('frameborder', '0');

        $caption = $map->getCaption();
        if ($caption) {
            // Replace the top level map with the wrapped figure with caption
            $ampMap = $this->buildCaption($caption, $context, $ampMap);
        }

        return $ampMap;
    }

    /**
     * Extracts latitud and longitud from Geotag json.
     * Example json expected on $mapJson:
     * <code>
     *      {
     *          "type": "Feature",
     *          "geometry": {
     *               "type": "Point",
     *               "coordinates": [23.166667, 89.216667]   // This is the content we are looking for.
     *          },
     *          "properties": {
     *               "title": "Jessore, Bangladesh",
     *               "radius": 750000,
     *               "pivot": true,
     *               "style": "satellite",
     *           }
     *       }
     * </code>
     * @param string $mapJson The geotag format json string. It will look for the geometry->coordinates attribute.
     */
    private function extractCoordinatesFromGeotag($mapJson)
    {
        $geotag = json_decode($mapJson, true);
        if (isset($geotag['type'])) {
            if ($geotag['type'] === 'FeatureCollection' && isset($geotag['features'])) {
                $features = $geotag['features'];
                foreach ($features as $feature) {
                    if (isset($feature['geometry']) && isset($feature['geometry']['coordinates'])) {
                        return $feature['geometry']['coordinates'];
                    }
                }
            } else if (isset($geotag['geometry']) && isset($geotag['geometry']['coordinates'])) {
                return $geotag['geometry']['coordinates'];
            }
        }
        return null;
    }

    private function buildCustomCSS($context)
    {
        $ampCustomCSS = $context->getDocument()->createElement('style');
        $ampCustomCSS->setAttribute('amp-custom', '');
      // Note: Custom CSS content will be generated after the whole article is processed
        return $ampCustomCSS;
    }

    public function getMediaDimensions($mediaURL, $mediaType = null)
    {
        if (array_key_exists(self::MEDIA_SIZES_KEY, $this->properties) &&
                array_key_exists($mediaURL, $this->properties[self::MEDIA_SIZES_KEY])) {
            return $this->properties[self::MEDIA_SIZES_KEY][$mediaURL];
        }

        $mediaDimensions = $this->getMediaDimensionsFromCache($mediaURL);
        if ($mediaDimensions) {
            return $mediaDimensions;
        }

        if ($mediaType === self::MEDIA_TYPE_IMAGE &&
            array_key_exists(self::ENABLE_DOWNLOAD_FOR_MEDIA_SIZING_KEY, $this->properties) &&
                $this->properties[self::ENABLE_DOWNLOAD_FOR_MEDIA_SIZING_KEY] === true) {
            $retrievedSizes = getimagesize($mediaURL);
            if ($retrievedSizes && !empty($retrievedSizes) && $retrievedSizes[0] !== 0) {
                return $retrievedSizes;
            }
        }

        $width = array_key_exists(self::DEFAULT_MEDIA_WIDTH_KEY, $this->properties)
            ? $this->properties[self::DEFAULT_MEDIA_WIDTH_KEY]
            : self::DEFAULT_WIDTH;
        $height = array_key_exists(self::DEFAULT_MEDIA_HEIGHT_KEY, $this->properties)
            ? $this->properties[self::DEFAULT_MEDIA_HEIGHT_KEY]
            : self::DEFAULT_HEIGHT;

        return array($width, $height);
    }

    private function getMediaDimensionsFromCache($mediaURL)
    {
        if (!array_key_exists(self::MEDIA_CACHE_FOLDER_KEY, $this->properties)) {
            return null;
        }

        $mediaCacheFolder = $this->properties[self::MEDIA_CACHE_FOLDER_KEY];
        if (!file_exists($mediaCacheFolder)) {
            return null;
        }

        $fileName = basename($mediaURL);
        if (!$fileName) {
            return null;
        }

        $cachedFile = $mediaCacheFolder . DIRECTORY_SEPARATOR . $fileName;
        if (!file_exists($cachedFile)) {
            return null;
        }

        return getimagesize($cachedFile);
    }

    public function getCustomCSS($context)
    {
        $stylesFolder = (array_key_exists(AMPArticle::STYLES_FOLDER_KEY, $this->properties)
            ? $this->properties[AMPArticle::STYLES_FOLDER_KEY]
            : __DIR__) . '/';

        $styleName = $this->instantArticle->getStyle();
        if ($styleName == null) {
            $styleName = 'default';
        }
        // Try to get the Instant Articles styles from properties
        if (array_key_exists(AMPArticle::OVERRIDE_STYLES_KEY, $this->properties)) {
            $styles = $this->properties[AMPArticle::OVERRIDE_STYLES_KEY];
        } else {
            if (!file_exists($stylesFolder . $styleName . '.style.json')) {
                $stylesFile = file_get_contents(__DIR__ . '/configuration/default-amp.style.json');
                $styles = json_decode($stylesFile, true);
            } else {
                $stylesFile = file_get_contents($stylesFolder . $styleName . '.style.json');
                $styles = json_decode($stylesFile, true);
            }
        }

        // TODO: Refactor this logic for custom CSS (global and style specific)
        if (file_exists($stylesFolder . 'global.amp-custom.css')) {
            $globalCSSFile = file_get_contents($stylesFolder . 'global.amp-custom.css');
            $globalCSSFile = str_replace(array("\r", "\n"), ' ', $globalCSSFile);
        }

        if (file_exists($stylesFolder . $styleName . '.amp-custom.css')) {
            $customCSSFile = file_get_contents($stylesFolder . $styleName . '.amp-custom.css');
            $customCSSFile = str_replace(array("\r", "\n"), ' ', $customCSSFile);
        }

        if (!isset($globalCSSFile) && !isset($customCSSFile)) {
            $defaultCSSFile = file_get_contents(__DIR__ . '/configuration/global.amp.css');
            $defaultCSSFile = str_replace(array("\r", "\n"), ' ', $defaultCSSFile);
        }

        return $this->articleColorsStyles($styles, $context) .
            $this->articleHeadStyles($styles, $context) .
            $this->articleBodyStyles($styles, $context) .
            $this->articleQuoteStyles($styles, $context) .
            $this->articleCaptionStyles($styles, $context) .
            $this->articleFooterStyles($styles, $context) .
            $this->articleCustomCSSStyles() .
            (isset($globalCSSFile) ? $globalCSSFile : '').
            (isset($customCSSFile) ? $customCSSFile : '').
            (!isset($globalCSSFile) && !isset($customCSSFile) ? $defaultCSSFile : '');
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
            $context->buildCssSelector('header-category') => 'kicker',
            $context->buildCssSelector('header-h1') => 'title',
            $context->buildCssSelector('header-h2') => 'subtitle',
            $context->buildCssSelector('header h3') => 'byline'
        );

        // TODO: Move to constant/static
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

    private function tryGetColor($sourceStyles, $colorStylePropertyName)
    {
        if (array_key_exists($colorStylePropertyName, $sourceStyles)) {
            $color = $sourceStyles[$colorStylePropertyName];
            if ($color) {
                return self::toRGB($color);
            }
        }

        return null;
    }

    private function articleLogo($styles, $context)
    {
        $headerStyles = $styles['header'];

        $barRules = array();

        $backgroundColor = $this->tryGetColor($headerStyles, 'background_color');
        if ($backgroundColor) {
            $cssSelector = "." . $context->buildCssClass('header-bar');
            $barRules['background-color'] = $backgroundColor;
        }

        $barColor = $this->tryGetColor($headerStyles, 'bar_color');
        if ($barColor) {
            $cssSelector= '.' . $context->buildCssClass('spacing') . '.after-header-bar';
            $barRules['border-top-color'] = $barColor;
            $barRules['border-top-style'] = 'solid';
            $barRules['border-top-width'] = '1px';
        }

        $barDeclarationMapping = array($cssSelector => $barRules);
        $barStyles = $this->buildCSSRulesFromArray($barDeclarationMapping);

        // TODO: Should we move the code below to another place?
        // It is not really generating any CSS as the width and height are required fields of amp-image

        if (!array_key_exists('logo', $headerStyles)) {
            return '';
        }
        $logoStyles = $headerStyles['logo'];

        $dataURL = array_key_exists('dataURL', $logoStyles)
            ? $logoStyles['dataURL']
            : null;
        $fullResURL = $logoStyles['full_resolution_url'];

        $defaultLogoHeight = $this->observer->applyFilters('DEFAULT_LOGO_HEIGHT', self::DEFAULT_LOGO_HEIGHT);
        $defaultLogoWidth = $this->observer->applyFilters('DEFAULT_LOGO_WIDTH', self::DEFAULT_LOGO_WIDTH);
        $logoWidth = $logoStyles['full_resolution_width'];
        $logoHeight = $logoStyles['full_resolution_height'];
        $resizeScale = $headerStyles['logo_scale'] || 1.0;
        $resizeScale *= min(
            $defaultLogoHeight / $logoHeight,
            $defaultLogoWidth / $logoWidth
        );

        $logoURL = $dataURL ? $dataURL : $fullResURL;
        $scaledLogoWidth = (int) ($logoWidth * $resizeScale);
        $scaledLogoHeight = (int) ($logoHeight * $resizeScale);
        $this->logo = new AMPImage($logoURL, $scaledLogoWidth, $scaledLogoHeight);

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
            '.ia2amp-op-small h1' => 'caption_title_small',
            '.ia2amp-op-small h2' => 'caption_description_small',

            '.ia2amp-op-medium h1' => 'caption_title',
            '.ia2amp-op-medium h2' => 'caption_description',

            '.ia2amp-op-large h1' => 'caption_title_large',
            '.ia2amp-op-large h2' => 'caption_description_large',

            '.ia2amp-op-extra-large h1' => 'caption_title_extra_large',
            '.ia2amp-op-extra-large h2' => 'caption_description_extra_large',

            '.ia2amp-figcaption cite' => 'caption_credit',
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

    private function articleCustomCSSStyles()
    {
        return $this->buildCSSRulesFromArray($this->articleCustomCSSRules);
    }

    private function buildCSSRulesFromArray($ruleMappings)
    {
        if (!$ruleMappings) {
            return null;
        }

        $rules = '';
        foreach ($ruleMappings as $cssSelector => $cssProperties) {
            $declarations = array();
            foreach ($cssProperties as $cssKey => $cssValue) {
                $declarations[] = $this->buildCSSDeclaration($cssKey, $cssValue);
            }

            $rules = $rules . ' ' . $this->buildCSSRule($cssSelector, $this->buildCSSDeclarationBlock($declarations));
        }

        return $rules;
    }

    private function buildTextCSSDeclarationBlock($textStyles, $textType, $context)
    {
        // TODO: Move to constant
        $mappings = array(
            'font-family' => 'font',
            'text-align' => 'text_alignment',
            'display' => 'display',
        );
        $filteredMappings = AMPArticle::filterMappings($mappings, $textStyles);

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

    private static function getSpacing($spacingMappings, $spacingDirectionStyles)
    {
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
            } else {
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
        if ($color[0] == '#') {
            $color = substr($color, 1);
        }

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

    public function buildSchemaOrgMetadata($context)
    {
        $header = $this->instantArticle->getHeader();
        $published = $header->getPublished();
        $modified = $header->getModified();

        $metadata = array(
            '@context' => 'http://schema.org',
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
        foreach ($authors as $author) {
            $metadata['author'] = array(
                '@type' => 'Person',
                'name' => $author->getName(),
            );
            break;
        }

        $cover = $this->observer->applyFilters('AMP_GETMETADATAIMAGE', $this->getMetadataImage($this->properties), $this->properties);
        if ($cover) {
            $metadata['image'] = $cover;
        }

        $publisher = $this->observer->applyFilters('AMP_GETPUBLISHER', $this->getPublisher($this->properties), $this->properties);
        if ($publisher) {
            $metadata['publisher'] = $publisher;
        }

        // Prevent URL slashes to be escaped
        return json_encode($metadata, JSON_UNESCAPED_SLASHES);
    }

    public function getPublisher($properties)
    {
        $publisher = array_key_exists(self::PUBLISHER_KEY, $properties) ? $properties[self::PUBLISHER_KEY] : null;

        if ($publisher && Type::is($publisher, Type::STRING)) {
            // String values will be treated as organization names
            $publisher = array(
                '@type' => 'Organization',
                'name' => $publisher,
            );
        }

        return $publisher;
    }

    public function getMetadataImage($properties)
    {
        $imageURL = null;
        $header = $this->instantArticle->getHeader();

        $cover = $header->getCover();
        if ($cover) {
            $imageURL = $this->getImageURLFromElement($cover);
        }
        if (!$imageURL) {
            // Article does not have cover image, look for the first suitable children
            foreach ($this->instantArticle->getChildren() as $child) {
                $imageURL = $this->getImageURLFromElement($child);
                if ($imageURL) {
                    break;
                }
            }
        }

        if ($imageURL) {
            $imageDimensions = $this->getMediaDimensions($imageURL, self::MEDIA_TYPE_IMAGE);

            return array(
                '@type' => 'ImageObject',
                'url' => $imageURL,
                'width' => $imageDimensions[0],
                'height' => $imageDimensions[1],
            );
        }

        return null;
    }

    private function getImageURLFromElement($element)
    {
        if (Type::is($element, Image::getClassName())) {
            return $element->getUrl();
        } else if (Type::is($element, Slideshow::getClassName())) {
            foreach ($element->getArticleImages() as $articleImage) {
                if ($articleImage->isValid()) {
                    return $articleImage->getUrl();
                }
            }
        }

        return null;
    }

    private function ensureHttps($context, $url)
    {
        if (strpos($url , 'http:') !== false) {
            $context->addWarning('URLs for videos, iframes, analytics and ads should be HTTPS. Double check if this one is still valid using HTTPS protocol', $url);
        }
        return Type::isTextEmpty($url) ? $url : preg_replace("/^http:/i", "https:", $url);
    }
}
