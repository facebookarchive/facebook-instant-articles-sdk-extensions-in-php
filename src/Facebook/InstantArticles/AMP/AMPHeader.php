<?php
/**
 * Copyright (c) 2017-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */
namespace Facebook\InstantArticles\AMP;

class AMPHeader
{
    private $header;
    private $context;
    private $publishDateElement;

    public function __construct($context)
    {
        $this->context = $context;
    }

    private function iaHeader()
    {
        return $this->context->getInstantArticle()->getHeader();
    }

    private function genKicker()
    {
        if ($this->iaHeader()->getKicker()) {
            $kicker = $this->context->createElement('h2', $this->header, 'header-category');
            $kicker->appendChild($this->context->getInstantArticle()
            ->getHeader()
            ->getKicker()
            ->textToDOMDocumentFragment($this->context->getDocument()));
            $this->context->buildSpacingDiv($this->header);
        }
    }

    private function genTitle()
    {
        $iaTitle = $this->iaHeader()->getTitle();
        if ($iaTitle) {
            $h1 = $this->context->createElement('h1', $this->header, 'header-h1');
            $h1->appendChild($iaTitle->textToDOMDocumentFragment($this->context->getDocument()));
            $this->context->buildSpacingDiv($this->header);
        }
    }

    private function genHeaderBar()
    {
        $this->headerBar = $this->context->createElement('div', $this->header, 'header-bar');
        $this->context->buildSpacingDiv($this->header);
        // Note: The logo will be added after the whole article is processed
    }

    private function genSubtitle()
    {
        if ($this->iaHeader()->getSubtitle()) {
            $iaHeaderSubtitle = $this->iaHeader()->getSubtitle()->textToDOMDocumentFragment($this->context->getDocument());
            $subtitle = $this->context->createElement('h2', $this->header, 'header-h2');
            $subtitle->appendChild($iaHeaderSubtitle);

            $this->context->buildSpacingDiv($this->header);
        }
    }

    private function genArticlePublishDateElement()
    {
        $this->publishDateElement = $this->context->createElement('h3', $this->header, 'header-date');
        // Note: The published date will be added after the whole article is processed
        $this->context->buildSpacingDiv($this->header);
    }

    private function genAuthors()
    {
        $authors = $this->context->createElement('h3', $this->header, 'header-author');
        $authorsElement = $this->iaHeader()->getAuthors();
        $authorsString = [];
        foreach ($authorsElement as $author) {
            $authorsString[] = $author->getName();
        }
        $authors->appendChild($this->context->getDocument()->createTextNode('By '.implode($authorsString, ', ')));
        $this->context->buildSpacingDiv($this->header);
    }

    private function genContainer()
    {
        // Builds the content Header, with proper colors and image, adding to body
        $this->header = $this->context->createElement('header', $this->context->getBody(), 'header');
        // Creates the cover content for the cover and appends to the header
        if ($this->context->getInstantArticle()->getHeader()->getCover()) {
            $ampCover = new AMPCover($this->context, $this->context->getInstantArticle()->getHeader()->getCover());
            $this->header->appendChild($ampCover->build());
        }
    }

    public function genHeaderLogo($logo)
    {
        if (!isset($logo->url)) {
            return;
        }

        $ampImageContainer = $this->context->createElement(
            'div',
            $this->headerBar,
            'header-bar-img-container'
        );
        $ampImage = $this->context->createElement(
            'amp-img',
            $ampImageContainer,
            null,
            array(
                'src' => $logo->url,
                'width' => $logo->width,
                'height' => $logo->height
            )
        );
    }

    public function genArticlePublishDate($dateFormat)
    {
        $published = $this->iaHeader()->getPublished();
        if ($published) {
            $datetime = $published->getDatetime();
            $this->publishDateElement->appendChild(
                $this->context->getDocument()->createTextNode(
                    date_format($datetime, $dateFormat)
                )
            );
        }
    }

    public function build()
    {
        $this->genContainer();
        $this->genHeaderBar();
        $this->genKicker();
        $this->genTitle();
        $this->genSubtitle();
        $this->genAuthors();
        $this->genArticlePublishDateElement();

        return $this->header;
    }
}
