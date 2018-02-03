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

// Loads from a file or even you could load from the graph API style Editor.
$styleGotFromSomewhereElse = file_get_contents(__DIR__.'/styles/style-got-from-somewhereelse.json');

$properties = array(
    // Overrides any style linked from the Instant Article, this might be useful if you want to apply a different style than the one in specified Instant Articles.
    'override-styles' => json_decode($styleGotFromSomewhereElse, true)
);

// Converts it into AMP
$amp_string = AMPArticle::create($instant_article_string, $properties)->render();

print($amp_string);
