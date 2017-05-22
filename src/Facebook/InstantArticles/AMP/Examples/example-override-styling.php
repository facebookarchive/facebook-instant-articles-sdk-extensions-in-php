<?php
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */

require_once dirname(dirname(dirname(dirname(dirname(__DIR__))))).'/vendor/autoload.php';

use Facebook\InstantArticles\AMP\AMPArticle;

/**
 * This is a small simple environment set so you can run it from any prompt command
 * and get a clean run.
 */
function setDefaultEnvironmentSettings()
{
    \Logger::configure(
        [
            'rootLogger' => [
                'appenders' => ['facebook-instantarticles-traverser']
            ],
            'appenders' => [
                'facebook-instantarticles-traverser' => [
                    'class' => 'LoggerAppenderConsole',
                    'threshold' => 'INFO',
                    'layout' => [
                        'class' => 'LoggerLayoutSimple'
                    ]
                ]
            ]
        ]
    );
    date_default_timezone_set('UTC');
}

setDefaultEnvironmentSettings();

 // Load instant article file into string
$instant_article_string = file_get_contents(__DIR__.'/instant-article-example.html');

// Loads from a file or even you could load from the graph API style Editor.
$styleGotFromSomewhereElse = file_get_contents(__DIR__.'/styles/style-got-from-somewhereelse.json');

$properties = array(
    // You can set the language your article have
    'lang' => 'en-US',

    // Where the styles are stored
    'styles-folder' => __DIR__.'/styles',

    // Overrides any style linked from the Instant Article, this might be useful if you want to apply same style to all your Instant Articles.
    'override-styles' => json_decode($styleGotFromSomewhereElse, true);
);

/*
  As the name of the style used within the Instant article refers to <code>"gray"</code>, the
  file within directory <code>/styles</code> will be the <code>/styles/gray.style.json.</code>
*/

// Converts it into AMP
$amp_string = AMPArticle::create($instant_article_string, $properties)->render();

print($amp_string);
