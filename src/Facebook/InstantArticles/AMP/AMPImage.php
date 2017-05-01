<?php
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */
namespace Facebook\InstantArticles\AMP;

class AMPImage {
  public $url;
  public $width;
  public $height;

  function __construct($url, $width, $height)
  {
    $this->url = $url;
    $this->width = $width;
    $this->height = $height;
  }
}
