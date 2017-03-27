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

        $rendered = str_replace('amp=""', 'amp', $rendered);

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
        $head = $document->createElement('head');
        if ($this->instantArticle->getCharset()) {
            $charset = $document->createElement('meta');
            $charset->setAttribute('charset', $this->instantArticle->getCharset());
            $head->appendChild($charset);
        }

        $html->appendChild($head);

        $link = $document->createElement('link');
        $link->setAttribute('rel', 'canonical');
        $link->setAttribute('href', $this->instantArticle->getCanonicalURL());
        $head->appendChild($link);

        // $this->addMetaProperty('op:markup_version', $this->instantArticle->getMarkupVersion());
        // if ($this->header && count($this->instantArticle->getHeader()->getAds()) > 0) {
        //     $this->addMetaProperty(
        //         'fb:use_automatic_ad_placement',
        //         $this->instantArticle->isAutomaticAdPlaced() ? 'true' : 'false'
        //     );
        // }

        // if ($this->instantArticle->getStyle()) {
        //     $this->addMetaProperty('fb:article_style', $this->instantArticle->getStyle());
        // }

        // Adds all meta properties
        // foreach ($this->instantArticle->getMetaProperties() as $property_name => $property_content) {
        //     $head->appendChild(
        //         $this->createMetaElement(
        //             $document,
        //             $property_name,
        //             $property_content
        //         )
        //     );
        // }

        // Build and append body and article tags to the HTML document
        $body = $document->createElement('body');
        $article = $document->createElement('article');
        $body->appendChild($article);
        $html->appendChild($body);
        if ($this->instantArticle->getHeader() && $this->instantArticle->getHeader()->isValid()) {
            $article->appendChild($this->instantArticle->getHeader()->toDOMElement($document));
        }
        if ($this->instantArticle->getChildren()) {
            foreach ($this->instantArticle->getChildren() as $child) {
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
                $article->appendChild($child->toDOMElement($document));
            }
            if ($this->instantArticle->getFooter() && $this->instantArticle->getFooter()->isValid()) {
                $article->appendChild($this->instantArticle->getFooter()->toDOMElement($document));
            }
        } else {
            $article->appendChild($document->createTextNode(''));
        }

        return $html;
    }
}
