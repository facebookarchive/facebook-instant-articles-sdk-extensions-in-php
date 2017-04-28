<?php
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */
namespace Facebook\InstantArticles\AMP;

class AMPLogo {
  public $logoURL;
  public $logoWidth;
  public $logoHeight;

  function __construct($url, $width, $height)
  {
    $this->logoURL = $url;
    $this->logoWidth = $width;
    $this->logoHeight = $height;
  }
}
