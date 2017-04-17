<?php
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */
namespace Facebook\InstantArticles\AMP;

use Facebook\InstantArticles\Elements\InstantArticle;

use Facebook\InstantArticles\Parser\Parser;
use Facebook\InstantArticles\Validators\Type;

class AMPContext
{
    private $document;
    private $instantArticle;

    private $html;
    private $head;
    private $body;
    private $article;
    private $header;
    private $headerBar;
    private $headerBarLogo;
    private $headerTitle;
    private $headerAuthor;
    private $headerKicker;
    private $headerDate;
    private $articleItems = array();
    private $footer;

    /**
     * Private constructor. Use self::create($document, $instantArticle)
     */
    private function __construct()
    {}

    /**
     * Factory method to create the AMPContext
     * @param DOMDocument $document The root document used on the context. If null informed, a new one will be created.
     * @param InstantArticle $instantArticle The Element InstantArticle that will be used during conversion.
     */
    public static function create($document, $instantArticle)
    {
        $context = new self();
        return $context->withDocument($document)
                       ->withInstantArticle($instantArticle);
    }

    /**
     * Sets the document. Private method since this should be unmodifiable.
     * @param DOMDocument $document The root document to be used.
     * @return $this reference.
     */
    private function withDocument($document)
    {
        if (!isset($document) || $document === null) {
            $document = new \DOMDocument();
        }
        Type::enforce($document, \DOMDocument::class);
        $this->document = $document;
        return $this;
    }

    /**
     * Gets the root document being used in this context.
     * @return DOMDocument $document The root document.
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * Creates an element named by $tagName using the $document.
     * @param string $tagName The tag name that will be used: <tagName>
     * @param array(<string>=><string>) $attributes mapped array of attributes to be set to this Element being created.
     * @param $DOMNode $container The container element where the new created element will be appended. Optional.
     * @return DOMElement <$tagName> element using the DOMDocument $document from context.
     */
    public function createElement($tagName, $container = null, $attributes = null)
    {
        $element = $this->getDocument()->createElement($tagName);
        if (isset($attributes) && $attributes) {
            foreach ($attributes as $name => $value) {
                $element->setAttribute($name, $value);
            }
        }

        if (isset($container) && $container) {
            Type::enforce($container, get_class(new \DOMNode()));
            $container->appendChild($element);
        }

        return $element;
    }

    /**
     * Sets the InstantArticle reference. Private method since this should be unmodifiable.
     * @param InstantArticle $instantArticle The element being used as conversion.
     * @return $this reference.
     */
    private function withInstantArticle($instantArticle)
    {
        Type::enforce($instantArticle, InstantArticle::class);
        $this->instantArticle = $instantArticle;
        return $this;
    }

    /**
     * Gets the InstantArticle being used in this context.
     * @return InstantArticle $instantArticle conversion instance.
     */
    public function getInstantArticle()
    {
        return $this->instantArticle;
    }

    /**
     * Sets the <html> full document.
     * WARNING: by setting this, will overwrite the full document, be sure to
     * have a valid AMP document while setting.
     * @param DOMElement $html The html tag, should be a <html> tag.
     * @throws InvalidArgumentException case not a DOMElement or not a <html> tag.
     * @return $this instance.
     */
    public function withHtml($html)
    {
        Type::enforceElementTag($html, 'html');
        $this->html = $html;
        return $this;
    }

    /**
     * Checks the existence of <html> tag.
     * @return boolean true if <html> tag was set, false otherwise.
     */
    public function hasHtml()
    {
        return isset($this->html) && $this->html !== null;
    }

    /**
     * Gets the <html> tag.
     * @return DOMElement $html The <html> tag.
     */
    public function getHtml()
    {
        return $this->html;
    }

    /**
     * Sets the <head> tag.
     * WARNING: by setting this, will overwrite the head from document, be sure to
     * have a valid AMP head while setting.
     * @param DOMElement $head The head tag, should be a <head> tag.
     * @throws InvalidArgumentException case not a DOMElement or not a <head> tag.
     * @return $this instance.
     */
    public function withHead($head)
    {
        Type::enforceElementTag($head, 'head');
        $this->head = $head;
        return $this;
    }

    /**
     * Checks the existence of <head> tag.
     * @return boolean true if <head> tag was set, false otherwise.
     */
    public function hasHead()
    {
        return isset($this->head) && $this->head !== null;
    }

    /**
     * Gets the <head> tag.
     * @return DOMElement $head The <head> tag.
     */
    public function getHead()
    {
        return $this->head;
    }

    /**
     * Sets the <body> tag.
     * WARNING: by setting this, will overwrite the body from document, be sure to
     * have a valid AMP body while setting.
     * @param DOMElement $body The body tag, should be a <body> tag.
     * @throws InvalidArgumentException case not a DOMElement or not a <body> tag.
     * @return $this instance.
     */
    public function withBody($body)
    {
        Type::enforceElementTag($body, 'body');
        $this->body = $body;
        return $this;
    }

    /**
     * Checks the existence of <body> tag.
     * @return boolean true if <body> tag was set, false otherwise.
     */
    public function hasBody()
    {
        return isset($this->body) && $this->body !== null;
    }

    /**
     * Gets the <body> tag.
     * @return DOMElement $body The <body> tag.
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Sets the <article> tag.
     * WARNING: by setting this, will overwrite the article from document, be sure to
     * have a valid AMP article while setting.
     * @param DOMElement $article The article tag, should be a <article> tag.
     * @throws InvalidArgumentException case not a DOMElement or not a <article> tag.
     * @return $this instance.
     */
    public function withArticle($article)
    {
        Type::enforceElementTag($article, 'article');
        $this->article = $article;
        return $this;
    }

    /**
     * Checks the existence of <article> tag.
     * @return boolean true if <article> tag was set, false otherwise.
     */
    public function hasArticle()
    {
        return isset($this->article) && $this->article !== null;
    }

    /**
     * Gets the <article> tag.
     * @return DOMElement $article The <article> tag.
     */
    public function getArticle()
    {
        return $this->article;
    }

    /**
     * Sets the <header> tag.
     * WARNING: by setting this, will overwrite the header from document, be sure to
     * have a valid AMP header while setting.
     * @param DOMElement $header The header tag, should be a <header> tag.
     * @throws InvalidArgumentException case not a DOMElement or not a <header> tag.
     * @return $this instance.
     */
    public function withHeader($header)
    {
        Type::enforceElementTag($header, 'header');
        $this->header = $header;
        return $this;
    }

    /**
     * Checks the existence of <header> tag.
     * @return boolean true if <header> tag was set, false otherwise.
     */
    public function hasHeader()
    {
        return isset($this->header) && $this->header !== null;
    }

    /**
     * Gets the <header> tag.
     * @return DOMElement $header The <header> tag.
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * Sets the headerBar <div> tag.
     * WARNING: by setting this, will overwrite the headerBar from document, be sure to
     * have a valid AMP headerBar while setting.
     * @param DOMElement $headerBar The headerBar tag, should be a <div> tag.
     * @throws InvalidArgumentException case not a DOMElement or not a <div> tag.
     * @return $this instance.
     */
    public function withHeaderBar($headerBar)
    {
        Type::enforceElementTag($headerBar, 'div');
        $this->headerBar = $headerBar;
        return $this;
    }

    /**
     * Checks the existence of headerBar <div> tag.
     * @return boolean true if headerBar <div> tag was set, false otherwise.
     */
    public function hasHeaderBar()
    {
        return isset($this->headerBar) && $this->headerBar !== null;
    }

    /**
     * Gets the <headerBar> tag.
     * @return DOMElement $headerBar The <headerBar> tag.
     */
    public function getHeaderBar()
    {
        return $this->headerBar;
    }

    /**
     * Sets the headerBarLogo <div> tag.
     * WARNING: by setting this, will overwrite the headerBarLogo from document, be sure to
     * have a valid AMP headerBarLogo while setting.
     * @param DOMElement $headerBarLogo The headerBarLogo tag, should be a <div> tag.
     * @throws InvalidArgumentException case not a DOMElement or not a <div> tag.
     * @return $this instance.
     */
    public function withHeaderBarLogo($headerBarLogo)
    {
        Type::enforceElementTag($headerBarLogo, 'div');
        $this->headerBarLogo = $headerBarLogo;
        return $this;
    }

    /**
     * Checks the existence of headerBarLogo <div> tag.
     * @return boolean true if headerBarLogo <div> tag was set, false otherwise.
     */
    public function hasHeaderBarLogo()
    {
        return isset($this->headerBarLogo) && $this->headerBarLogo !== null;
    }

    /**
     * Gets the <headerBarLogo> tag.
     * @return DOMElement $headerBarLogo The <headerBarLogo> tag.
     */
    public function getHeaderBarLogo()
    {
        return $this->headerBarLogo;
    }

    /**
     * Sets the <h1> title tag.
     * WARNING: by setting this, will overwrite the title from document, be sure to
     * have a valid AMP title while setting.
     * @param DOMElement $h1 The title tag, should be a <h1> tag.
     * @throws InvalidArgumentException case not a DOMElement or not a <h1> tag.
     * @return $this instance.
     */
    public function withHeaderTitle($h1)
    {
        Type::enforceElementTag($h1, 'h1');
        $this->headerTitle = $h1;
        return $this;
    }

    /**
     * Checks the existence of <h1> title tag.
     * @return boolean true if <h1> title tag was set, false otherwise.
     */
    public function hasHeaderTitle()
    {
        return isset($this->headerTitle) && $this->headerTitle !== null;
    }

    /**
     * Gets the <h1> title tag.
     * @return DOMElement $headerTitle The <h1> title tag.
     */
    public function getHeaderTitle()
    {
        return $this->headerTitle;
    }

    /**
     * Sets the <h3> author tag.
     * WARNING: by setting this, will overwrite the author from document, be sure to
     * have a valid AMP author while setting.
     * @param DOMElement $h3 The author tag, should be a <h3> tag.
     * @throws InvalidArgumentException case not a DOMElement or not a <h3> tag.
     * @return $this instance.
     */
    public function withHeaderAuthor($h3)
    {
        Type::enforceElementTag($h3, 'h3');
        $this->headerAuthor = $h3;
        return $this;
    }

    /**
     * Checks the existence of <h3> author tag.
     * @return boolean true if <h3> author tag was set, false otherwise.
     */
    public function hasHeaderAuthor()
    {
        return isset($this->headerAuthor) && $this->headerAuthor !== null;
    }

    /**
     * Gets the <h3> author tag.
     * @return DOMElement $headerAuthor The <h3> tag.
     */
    public function getHeaderAuthor()
    {
        return $this->headerAuthor;
    }

    /**
     * Sets the <h2> kicker tag.
     * WARNING: by setting this, will overwrite the kicker from document, be sure to
     * have a valid AMP kicker while setting.
     * @param DOMElement $h2 The kicker tag, should be a <h2> tag.
     * @throws InvalidArgumentException case not a DOMElement or not a <h2> tag.
     * @return $this instance.
     */
    public function withHeaderKicker($h2)
    {
        Type::enforceElementTag($h2, 'h2');
        $this->headerKicker = $h2;
        return $this;
    }

    /**
     * Checks the existence of <h2> kicker tag.
     * @return boolean true if <h2> kicker tag was set, false otherwise.
     */
    public function hasHeaderKicker()
    {
        return isset($this->headerKicker) && $this->headerKicker !== null;
    }

    /**
     * Gets the <h2> kicker tag.
     * @return DOMElement $headerKicker The <h2> kicker tag.
     */
    public function getHeaderKicker()
    {
        return $this->headerKicker;
    }

    /**
     * Sets the <h3> date tag.
     * WARNING: by setting this, will overwrite the date from document, be sure to
     * have a valid AMP date while setting.
     * @param DOMElement $h3 The date tag, should be a <h3> tag.
     * @throws InvalidArgumentException case not a DOMElement or not a <h3> tag.
     * @return $this instance.
     */
    public function withHeaderDate($h3)
    {
        Type::enforceElementTag($h3, 'h3');
        $this->headerDate = $h3;
        return $this;
    }

    /**
     * Checks the existence of <h3> date tag.
     * @return boolean true if <h3> date tag was set, false otherwise.
     */
    public function hasHeaderDate()
    {
        return isset($this->headerDate) && $this->headerDate !== null;
    }

    /**
     * Gets the <h3> date tag.
     * @return DOMElement $headerDate The <h3> tag.
     */
    public function getHeaderDate()
    {
        return $this->headerDate;
    }

    /**
     * Add new items to the document
     */
    public function addItem($item) {
        $element = new \DOMElement('dummy');
        Type::enforce($item, get_class($element));
        $this->articleItems[] = $item;
    }

    /**
     * Sets the <footer> tag.
     * WARNING: by setting this, will overwrite the footer from document, be sure to
     * have a valid AMP footer while setting.
     * @param DOMElement $footer The footer tag, should be a <footer> tag.
     * @throws InvalidArgumentException case not a DOMElement or not a <footer> tag.
     * @return $this instance.
     */
    public function withFooter($footer)
    {
        Type::enforceElementTag($footer, 'footer');
        $this->footer = $footer;
        return $this;
    }

    /**
     * Checks the existence of <footer> tag.
     * @return boolean true if <footer> tag was set, false otherwise.
     */
    public function hasFooter()
    {
        return isset($this->footer) && $this->footer !== null;
    }

    /**
     * Gets the <footer> tag.
     * @return DOMElement $footer The <footer> tag.
     */
    public function getFooter()
    {
        return $this->footer;
    }

}
