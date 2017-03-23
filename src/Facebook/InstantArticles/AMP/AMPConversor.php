<?php
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */
namespace Facebook\InstantArticles\AMP;

use Facebook\InstantArticles\Elements\Element;
use Facebook\InstantArticles\Elements\Container;
use Facebook\InstantArticles\Elements\InstantArticleInterface;
use Facebook\InstantArticles\Validators\Type;

class AMPConversor
{
    public static function convert($instantArticle)
    {
        $markup =
        "<!doctype html>".
        "<html amp" /*. self::get($instantArticle) */. ">".
          "<head>".
            "<meta charset=\"utf-8\">".
            "<meta name=\"viewport\" content=\"width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no\">".
            "<?php do_action( 'amp_post_template_head', ->this ); ?>".
            "<style amp-custom>".
              "<?php ->load_parts( array( 'style' ) ); ?>".
              "<?php do_action( 'amp_post_template_css', ->this ); ?>".
            "</style>".
          "</head>".

          "<body class=\"<?php echo esc_attr( ->get( 'body_class' ) ); ?>\">".

          "<?php ->load_parts( array( 'header-bar' ) ); ?>".

          "<article class=\"amp-wp-article\">".

            "<header class=\"amp-wp-article-header\">".
              "<h1 class=\"amp-wp-title\"><?php echo wp_kses_data( ->get( 'post_title' ) ); ?></h1>".
              "<?php ->load_parts( apply_filters( 'amp_post_article_header_meta', array( 'meta-author', 'meta-time' ) ) ); ?>".
            "</header>".

            "<?php ->load_parts( array( 'featured-image' ) ); ?>".

            "<div class=\"amp-wp-article-content\">".
              "<?php echo ->get( 'post_amp_content' ); // amphtml content; no kses ?>".
            "</div>".

            "<footer class=\"amp-wp-article-footer\">".
              "<?php ->load_parts( apply_filters( 'amp_post_article_footer_meta', array( 'meta-taxonomy', 'meta-comments-link' ) ) ); ?>".
            "</footer>".

          "</article>".

          "<?php ->load_parts( array( 'footer' ) ); ?>".

          "<?php do_action( 'amp_post_template_footer', ->this ); ?>".

          "</body>".
        "</html>";

        return $markup;
    }
}
