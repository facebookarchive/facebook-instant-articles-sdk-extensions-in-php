<?php
/**
 * Copyright (c) 2017-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */
namespace Facebook\InstantArticles\Utils;

use Facebook\InstantArticles\Parser\Parser;
use PHPUnit\Framework;

abstract class FileUtilsPHPUnitTestCase extends Framework\TestCase
{
    /**
     * Loads HTML file using by default file_get_contents
     */
    public function loadHTMLFile($file)
    {
        return file_get_contents($file);
    }

    /**
     * Helper method that loads HTML file into DOMDocument instance, encoding as HTML-ENTITIES and using by default utf-8.
     * @param string $file The file name/path that will be loaded
     * @param string $encoding "utf-8" by default. Supports the format informed.
     */
    public function loadDOMDocument($file, $encoding = 'utf-8')
    {
        $fileContent = $this->loadHTMLFile($file, $encoding);
        return $this->loadDOMDocumentFromString($fileContent, $encoding);
    }

    /**
     * Helper method that loads HTML file into DOMDocument instance, encoding as HTML-ENTITIES and using by default utf-8.
     * @param string $fileContent The file content
     * @param string $encoding "utf-8" by default. Supports the format informed.
     */
    public function loadDOMDocumentFromString($fileContent, $encoding = 'utf-8')
    {
        libxml_use_internal_errors(true);
        $document = new \DOMDocument('1.0');
        $document->loadHTML('<?xml encoding="' . $encoding. '"?>'.$fileContent);
        libxml_use_internal_errors(false);
        return $document;
    }

    /**
     * Helper method that will load file, parse as Instant Article and return the element.
     * @param string $file File name to be loaded/parsed as InstantArticle.
     * @param string $encoding "utf-8" by default. Supports and loads the instant article treating file with the informed encoding.
     */
    public function loadInstantArticle($file, $encoding = 'utf-8')
    {
        $document = $this->loadDOMDocument($file, $encoding);
        $parser = new Parser();
        return $parser->parse($document);
    }

    protected function assertEqualsHtml($expected, $actual)
    {
        $from = ['/\>[^\S ]+/s', '/[^\S ]+\</s', '/(\s)+/s', '/> </s'];
        $to = ['>', '<', '\\1', '><'];
        $this->assertEquals(
            preg_replace($from, $to, $expected),
            preg_replace($from, $to, $actual)
        );
    }
}
