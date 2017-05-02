<?php
/**
 * Copyright (c) 2016-present, Facebook, Inc.
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
    private $logo;
    private $dateFormat = AMPArticle::DEFAULT_DATE_FORMAT;

    public function __construct($header, $logo, $context)
    {
        $this->header = $header;
        $this->context = $context;
        $this->logo = $logo;
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
        $iaTitle = $this->iaHeader()
                    ->getTitle()
                    ->textToDOMDocumentFragment($this->context->getDocument());

        $h1 = $this->context->createElement('h1', $this->header, 'header-h1');
        $h1->appendChild($iaTitle);
        $this->context->buildSpacingDiv($this->header);
    }

    private function genHeaderBar()
    {

        $headerBar = $this->context->createElement('div', $this->header, 'header-bar');
        $this->context->buildSpacingDiv($this->header);

        if (isset($this->logo->url)) {
            $ampImageContainer = $this->context->createElement(
                'div',
                $headerBar,
                'header-bar-img-container'
            );
            $ampImage = $this->context->createElement(
                'amp-img',
                $ampImageContainer,
                null,
                array(
                   'src' => $this->logo->url,
                   'width' => $this->logo->width,
                   'height' => $this->logo->height
                )
            );
        }
    }

    private function genSubtitle()
    {
        if ($this->iaHeader()->getSubtitle()) {
            $iaHeaderSubtitle = $this->iaHeader()->getSubtitle()->textToDOMDocumentFragment($this->context->getDocument());
            $subtitle = $this->context->createElement('h2', $this->header, 'header-subtitle');
            $subtitle->appendChild($iaHeaderSubtitle);

            $this->context->buildSpacingDiv($this->header);
        }
    }

    private function genArticlePublishDate()
    {
        $publishDate = $this->context->createElement('h3', $this->header, 'header-date');
        $datetime = $this->iaHeader()->getPublished()->getDatetime();
        $publishDate->appendChild($this->context->getDocument()->createTextNode(date_format($datetime, $this->dateFormat)));
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
        $authors->appendChild($this->context->getDocument()->createTextNode('BY '.implode($authorsString, ', ')));
        $this->context->buildSpacingDiv($this->header);
    }

    public function build()
    {
        $this->genHeaderBar();
        $this->genKicker();
        $this->genTitle();
        $this->genSubtitle();
        $this->genAuthors();
        $this->genArticlePublishDate();

        return $this->header;
    }
}
