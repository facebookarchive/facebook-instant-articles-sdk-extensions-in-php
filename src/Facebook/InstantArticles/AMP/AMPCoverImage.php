<?php
/**
 * Copyright (c) 2017-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */
namespace Facebook\InstantArticles\AMP;

class AMPCoverImage
{
    // Constructor setup
    private $image;
    private $context;
    private $cssClass;

    // Generator fill in
    private $containerTag;
    private $ampImgTag;

    private function __construct($image, $context, $cssClass)
    {
        $this->image = $image;
        $this->context = $context;
        $this->cssClass = $cssClass;
    }

    public static function create($image, $context, $cssClass)
    {
        return new self($image, $context, $cssClass);
    }

    private function genContainer()
    {
        $this->containerTag = $this->context->createElement('div', null, $this->cssClass);
    }

    private function genCaptionContainer()
    {
        $caption = $this->image->getCaption();
        if ($caption) {
            $this->ampImgTag =
                AMPCaption::create($caption, $this->context, $this->ampImgTag)->build();
        }
    }

    private function genAmpImage()
    {
        $this->ampImgTag = $this->context->createElement('amp-img', null, 'header-cover-img');
        $imageURL = $this->image->getUrl();

        $imageDimensions = $this->context->getMediaDimensions($imageURL, AMPContext::MEDIA_TYPE_IMAGE);
        $imageWidth = $imageDimensions[0];
        $imageHeight = $imageDimensions[1];

        $horizontalScale = AMPContext::DEFAULT_WIDTH / $imageWidth;
        $verticalScale = AMPContext::DEFAULT_HEIGHT / $imageHeight;
        $maxScale = max($horizontalScale, $verticalScale);

        $translateX = (int) (-($imageWidth * $maxScale - AMPContext::DEFAULT_WIDTH) / 2);
        $translateY = (int) (-($imageHeight * $maxScale - AMPContext::DEFAULT_HEIGHT) / 2);

        $imageWidth = (int) ($imageWidth * $maxScale);
        $imageHeight = (int) ($imageHeight * $maxScale);

        $this->ampImgTag->setAttribute('src', $imageURL);
        $this->ampImgTag->setAttribute('width', (string) $imageWidth);
        $this->ampImgTag->setAttribute('height', (string) $imageHeight);

        $imageCSSClass = $this->ampImgTag->getAttribute('class');
        $containerCSSClass = $this->containerTag->getAttribute('class');
        $this->context->getCssBuilder()
            ->addProperty("amp-img.$imageCSSClass", 'transform', "translate({$translateX}px, {$translateY}px)")
            ->addProperty("div.$containerCSSClass", 'width', AMPContext::DEFAULT_WIDTH.'px')
            ->addProperty("div.$containerCSSClass", 'height', AMPContext::DEFAULT_HEIGHT.'px')
            ->addProperty("div.$containerCSSClass", 'overflow', 'hidden');

    }

    public function build()
    {
        $this->genContainer();
        $this->genAmpImage();
        $this->genCaptionContainer();

        $this->containerTag->appendChild($this->ampImgTag);
        return $this->containerTag;
    }
}
