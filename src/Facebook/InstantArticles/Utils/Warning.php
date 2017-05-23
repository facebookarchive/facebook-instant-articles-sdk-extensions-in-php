<?php
/**
 * Copyright (c) 2017-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */
namespace Facebook\InstantArticles\Utils;

use Facebook\InstantArticles\Validators\Type;

class Warning
{
    private $exception;
    private $message;
    private $context;

    public function __construct($message, $context = null, $exception = null)
    {
        $this->message = $message;
        $this->context = $context;
        $this->exception = $exception;
    }

    public function __toString()
    {
        $finalMessage = $this->message;

        if ($this->context !== null) {
            $finalMessage = $finalMessage."\nObject in the context: ".Type::stringify($this->context);
        }

        if ($this->exception) {
            $finalMessage = $finalMessage."\nException cause: ".Type::stringify($this->exception);
        }

        return $finalMessage;
    }
}
