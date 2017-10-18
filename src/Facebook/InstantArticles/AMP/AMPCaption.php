<?php
/**
 * Copyright (c) 2017-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */
namespace Facebook\InstantArticles\AMP;

use Facebook\InstantArticles\Elements\Caption;

class AMPCaption
{
    /**
     * @var Context the conversion context holder
     */
    private $context;

    /**
     * @var Caption the Element Caption
     */
    private $caption;

    /**
     * @var DOMNode The DOMNode html element being captionized
     */
    private $ampTag;

    /**
     * @var DOMNode The Final container for Caption
     */
    private $container;

    /**
     * @var DOMNode The figcaption DOMNode that will hold the text content.
     */
    private $ampCaption;


    /**
     * @param Caption $caption The element from SDK that contains all Caption data.
     * @param Context $context Conversion context var.
     */
    public function __construct($caption, $context, $ampCaptionedElement)
    {
        $this->caption = $caption;
        $this->context = $context;
        $this->ampTag = $ampCaptionedElement;
    }

    public static function create($caption, $context, $ampCaptionedElement)
    {
        return new AMPCaption($caption, $context, $ampCaptionedElement);
    }

    private function genContainer()
    {
        $this->container = $this->context->createElement('figure', null, 'figure');
    }

    private function appendCaptioned()
    {
        $fontSize = $this->caption->getFontSize();
        $cssClass = 'figcaption-' . ($fontSize ? $fontSize : 'small');

        $this->ampCaption = $this->context->createElement('figcaption', null, $cssClass);

        $position = $this->caption->getPosition();
        if (!$position) {
            $position = Caption::POSITION_BELOW;
        }

        if ($position === Caption::POSITION_BELOW) {
            $this->container->appendChild($this->ampTag);
            $this->container->appendChild($this->ampCaption);
        } else {
            $this->container->appendChild($this->ampCaption);
            $this->container->appendChild($this->ampTag);
        }
    }

    private function genTitle()
    {
        // Title
        $title = $this->caption->getTitle();
        if ($title) {
            $ampTitle = $this->context->createElement('h1', $this->ampCaption);
            $ampTitleText = $title->textToDOMDocumentFragment($this->context->getDocument());
            $ampTitle->appendChild($ampTitleText);
        }
    }

    private function genSubtitle()
    {
        // SubTitle
        $subTitle = $this->caption->getSubTitle();
        if ($subTitle) {
            $ampSubTitle = $this->context->createElement('h2', $this->ampCaption);
            $ampSubTitleText = $subTitle->textToDOMDocumentFragment($this->context->getDocument());
            $ampSubTitle->appendChild($ampSubTitleText);
        }
    }

    private function genText()
    {
        // Text
        $ampText = $this->caption->textToDOMDocumentFragment($this->context->getDocument());
        $this->ampCaption->appendChild($ampText);
    }

    private function genCredit()
    {
        // Credit
        $credit = $this->caption->getCredit();
        if ($credit) {
            $ampCredit = $this->context->createElement('cite', $this->ampCaption);
            $ampCreditText = $credit->textToDOMDocumentFragment($this->context->getDocument());
            $ampCredit->appendChild($ampCreditText);
        }
    }

    private function applyStyleClasses()
    {
        $ampCSSClasses = array();
        $ampCSSClasses[] = $this->context->buildCssClass('figcaption');

        if ($this->caption->getFontSize()) {
            $ampCSSClasses[] = $this->context->buildCssClass($this->caption->getFontSize());
        } else {
            $ampCSSClasses[] = $this->context->buildCssClass(Caption::SIZE_SMALL);
        }
        if ($this->caption->getTextAlignment()) {
            $ampCSSClasses[] = $this->context->buildCssClass($this->caption->getTextAlignment());
        }
        if ($this->caption->getPosition()) {
            $ampCSSClasses[] = $this->context->buildCssClass($this->caption->getPosition());
        }
        if ($this->caption->getVerticalAlignment()) {
            $ampCSSClasses[] = $this->context->buildCssClass($this->caption->getVerticalAlignment());
        }

        $this->ampCaption->setAttribute('class', implode(' ', $ampCSSClasses));
    }

    public function build()
    {
        $this->genContainer();
        $this->appendCaptioned();
        $this->genTitle();
        $this->genSubtitle();
        $this->genText();
        $this->genCredit();
        $this->applyStyleClasses();

        return $this->container;
    }
}
