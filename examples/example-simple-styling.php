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

// Prevents the logger from dumping too much info, check the file for details
include __DIR__ . '/quiet_logger.php';

 // Load instant article file into string
$instant_article_string = file_get_contents(__DIR__.'/instant-article-example.html');
$properties = array(
  'lang' => 'en-US',                   // You can set the language your article have
  'styles-folder' => __DIR__.'/styles' // Where the styles are stored
);

/*
  As the name of the style used within the Instant article refers to <code>"gray"</code>, the
  file within directory <code>/styles</code> will be the <code>/styles/gray.style.json.</code>
*/

// Converts it into AMP
$amp_string = AMPArticle::create($instant_article_string, $properties)->render();

print($amp_string);
