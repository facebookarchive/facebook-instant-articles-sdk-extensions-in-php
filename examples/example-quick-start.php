<?php
/**
 * Copyright (c) 2017-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Facebook\InstantArticles\AMP\AMPArticle;

// Load instant article file into string
$instant_article_string = file_get_contents(__DIR__.'/instant-article-example.html');

// Instant articles have no width and height information for images and video on
// it's markup but the AMP spec requires everything to have explicit sizing.

// This will hold our custom properties
$properties = array();

// Videos require explicit sizing, if you fail to provide it, the default values
// may distort your Videos
$properties[AMPArticle::MEDIA_SIZES_KEY]
  ['http://mydomain.com/path/to/video.mp4'] = array(320, 240);

// Images can also be passed
// IMPORTANT: The image will not have fixed-width, but it will respect the
//            aspect ration provided here. They are using the responsive layout
$properties[AMPArticle::MEDIA_SIZES_KEY]
  ['http://mydomain.com/path/to/crazy.jpg'] = array(640, 480);

// The SDK can automatically download the images and get their sizes if they are not
// in the array above, if you enable the following option.
//
// WARNING: THIS MAY LEAD TO EXCESSIVE DOWNLOADING - USE WITH CAUTION
//          WE STRONGLY RECOMMEND YOU TO PASS THE IMAGE SIZING VIA THE
//          $properties[AMPArticle::MEDIA_SIZES_KEY] ARRAY
//
// You can check in the markup that the image fb_icon_325x325.png
// has the correct 325x325 size after enabling this option.
$properties[AMPArticle::ENABLE_DOWNLOAD_FOR_MEDIA_SIZING_KEY] = true;

// AMP has its own format for setting tracking pixels and/or analytics calls.
// You can provide a list of one or more strings containing the markup for
// the <amp-analytics> or <amp pixel> tags to the following option.
//
// See also the AMP specs for these tags:
// - https://www.ampproject.org/docs/reference/components/amp-analytics
// - https://www.ampproject.org/docs/reference/components/amp-pixel
$properties[AMPArticle::ANALYTICS_KEY] = array(
  '<amp-pixel src="http://mydomain.com/my_tracking_pixel.gif">',
  '<amp-analytics config="https://mydomain.com/analytics.config.json"></amp-analytics>'
);

// Converts it into AMP
$amp_string =
  AMPArticle::create($instant_article_string, $properties)->render();

print($amp_string);
