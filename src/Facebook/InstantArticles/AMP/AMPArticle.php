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
use Facebook\InstantArticles\Elements\Container;
use Facebook\InstantArticles\Elements\TextContainer;
use Facebook\InstantArticles\Elements\InstantArticleInterface;
use Facebook\InstantArticles\Parser\Parser;
use Facebook\InstantArticles\Validators\Type;

class AMPArticle extends Element implements InstantArticleInterface
{
    private $instantArticle;
    /*
       'lang' => 'en-US',
       'css-selector-prefix' => 'ia2amp-',
       'header-logo-image-url' => 'http://domain.com/someimage.png',
     */
    private $properties = array();

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

        return $rendered;
    }

    public function toDOMElement($document = null)
    {
        if (!$document) {
            $document = new \DOMDocument();
        }

        // Builds and appends head to the HTML document
        $html = $document->createElement('html');
        $html->setAttribute('amp','');
        if ($this->instantArticle->isRTLEnabled()) {
            $html->setAttribute('dir', 'rtl');
        }
        if (isset($this->properties['lang'])) {
            $html->setAttribute('lang', $this->properties['lang']);
        }

        // Builds the Head
        $head = $document->createElement('head');
        $html->appendChild($head);

        // Builds meta charset and append to head
        if ($this->instantArticle->getCharset()) {
            $charset = $document->createElement('meta');
            $charset->setAttribute('charset', $this->instantArticle->getCharset());
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
        $link->setAttribute('href', $this->instantArticle->getCanonicalURL());

        // Builds custon css style and append to head
        $ampCustomCSS = $this->buildCustomCSS($document);
        $head->appendChild($ampCustomCSS);
        $head->appendChild($link);

        // Builds title and append to head
        $title = $document->createElement('title');
        $head->appendChild($title);
        $titleText = $this->instantArticle->getHeader()->getTitle()->textToDOMDocumentFragment($document);
        $title->appendChild($titleText);

        // Build and append body and article tags to the HTML document
        $body = $document->createElement('body');
        $html->appendChild($body);
        $body->setAttribute('class', $this->buildClassName('body'));

        // Builds the content Header, with proper colors and image, adding to body
        $header = $document->createElement('header');
        $body->appendChild($header);
        $body->setAttribute('class', $this->buildClassName('header'));
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

            // Creates the Logo image and appends to header
            $imageContainer = $document->createElement('div');
            $header->appendChild($imageContainer);
            $imageContainer->setAttribute('class', $this->buildClassName('header-img'));
            $ampImage = $document->createElement('amp-img');
            $imageContainer->appendChild($ampImage);
            $ampImage->setAttribute('src', $imageURL);
            $ampImage->setAttribute('width', $imageWidth);
            $ampImage->setAttribute('height', $imageHeight);
        }

        // $article = $document->createElement('article');
        // $body->appendChild($article);

        // if ($this->instantArticle->getHeader() && $this->instantArticle->getHeader()->isValid()) {
        //     $article->appendChild($this->instantArticle->getHeader()->toDOMElement($document));
        // }
        // if ($this->instantArticle->getChildren()) {
        //     foreach ($this->instantArticle->getChildren() as $child) {
        //         if (Type::is($child, TextContainer::getClassName())) {
        //             if (count($child->getTextChildren()) === 0) {
        //                 continue;
        //             } elseif (count($child->getTextChildren()) === 1) {
        //                 if (Type::is($child->getTextChildren()[0], Type::STRING) &&
        //                     trim($child->getTextChildren()[0]) === '') {
        //                     continue;
        //                 }
        //             }
        //         }
        //         $article->appendChild($child->toDOMElement($document));
        //     }
        //     if ($this->instantArticle->getFooter() && $this->instantArticle->getFooter()->isValid()) {
        //         $article->appendChild($this->instantArticle->getFooter()->toDOMElement($document));
        //     }
        // } else {
        //     $article->appendChild($document->createTextNode(''));
        // }

        return $html;
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

    private function getCustomCSS()
    {
        // TODO: Move to settings
        $stylesFolder = __DIR__ . '/../../../Facebook/InstantArticles/AMP/';

        $styleName = $this->instantArticle->getStyle();
        $stylesFile = file_get_contents($stylesFolder . $styleName . '.style.json');
        $styles = json_decode($stylesFile, true);

        return AMPArticle::articleColorsStyles($styles) .
            AMPArticle::articleHeadStyles($styles) .
            AMPArticle::articleBodyStyles($styles) .
            // TODO: Quotes
            // TODO: Captions
            // TODO: Additional Caption Sizes
            AMPArticle::articleFooterStyles($styles);
    }

    private static function articleColorsStyles($styles)
    {
      $backgroundColor = $styles['background_color'];
      return "html {background-color: $backgroundColor;}";
    }

    private static function articleHeadStyles($styles)
    {
        $mappings = array(
            // TODO: Logo
            // TODO: Kicker --> header h3.op-kicker
            'header h1' => 'title',
            'header h2' => 'subtitle',
            // TODO: Byline --> header address
            // TODO: Date --> header time
        );
        return AMPArticle::buildCSSRulesFromMappings($mappings, $styles);
    }

    private static function articleBodyStyles($styles)
    {
        $mappings = array(
            'h1' => 'primary_heading',
            'h2' => 'secondary_heading',
            'p' => 'body_text',
            'a' => 'inline_link',
        );
        return AMPArticle::buildCSSRulesFromMappings($mappings, $styles);
    }

    private static function articleFooterStyles($styles)
    {
      // TODO: Implement
      return '';
    }

    private static function buildTextCSSDeclarationBlock($textStyles)
    {
        $mappings = array(
            'font-family' => 'font',
            'color' => 'color',
            'background-color' => 'background_color',
            'text-align' => 'text_alignment',
            'display' => 'display',
            // TODO: Implement
        );
        $filteredMappings = AMPArticle::filterMappings($mappings, $textStyles);

        $filteredMappings['font-size'] = ($textStyles['text_size_scale'] * 100) . '%';
        $filteredMappings['line-height'] = ($textStyles['line_height_scale'] * 100) . '%';

        $textTransformMappings = array(
            'ALL_CAPS' => 'uppercase',
            'ALL_LOWER_CASE' => 'lowercase',
            'NONE' => 'none',
        );
        $filteredMappings['text-transform'] = $textTransformMappings[$textStyles['capitalization']];

        if (array_key_exists('underline', $textStyles) && $textStyles['underline'] != 'NONE') {
            $filteredMappings['text-decoration'] = 'underline';
        }

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

    private static function buildCSSDeclaration($filteredKey, $cssValue)
    {
        return "$filteredKey: $cssValue;";
    }

    private static function buildCSSDeclarationBlock($cssDeclarations)
    {
        return '{' . implode(' ', $cssDeclarations) . '}';
    }

    private static function buildCSSRule($cssSelector, $cssDeclarationBlock)
    {
        return "$cssSelector $cssDeclarationBlock";
    }

    private static function buildCSSRulesFromMappings($mappings, $styles)
    {
        $rule = '';
        foreach ($mappings as $selector => $objectKey) {
            if (array_key_exists($objectKey, $styles)) {
                $declarationBlock = AMPArticle::buildTextCSSDeclarationBlock($styles[$objectKey]);
                $rule = $rule . AMPArticle::buildCssRule($selector, $declarationBlock);
            }
        }
        return $rule;
    }
}
