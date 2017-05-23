<?php
/**
 * Copyright (c) 2017-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */
namespace Facebook\InstantArticles\AMP;

use Facebook\InstantArticles\Validators\Type;
use Facebook\InstantArticles\Elements\Image;
use Facebook\InstantArticles\Elements\Slideshow;
use Facebook\InstantArticles\Elements\Video;

class AMPCover
{
    /**
     * @var Context the conversion context holder
     */
    private $context;

    /**
     * @var Image|Video|Slideshow The element cover
     */
    private $coverElement;

    /**
     * @var DOMNode The html tag holding the final element;
     */
    private $coverTag;

    /**
     * @param Context $context Conversion context var.
     * @param Image|Video|Slideshow $coverElement The element from SDK that will generate the cover.
     */
    public function __construct($context, $coverElement)
    {
        $this->context = $context;
        $this->coverElement = $coverElement;
    }

    private function genContainer()
    {
        if (Type::is($this->coverElement, Image::getClassName())) {
            $this->coverTag = AMPCoverImage::create($this->coverElement, $this->context, 'cover-image')->build();
        } else if (Type::is($this->coverElement, Slideshow::getClassName())) {
            //return $this->buildSlideshow($this->coverElement, $context, 'cover-slideshow');
        } else if (Type::is($this->coverElement, Video::getClassName())) {
            //return $this->buildVideo($this->coverElement, $context, 'cover-video');
        }
    }

    public function build()
    {
        $this->genContainer();
        return $this->coverTag;
    }
}
