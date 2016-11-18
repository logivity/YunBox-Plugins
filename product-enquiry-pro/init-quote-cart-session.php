<?php

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

add_action('wp_head', 'quoteupStartQuoteCartSession', 10);

function quoteupStartQuoteCartSession()
{
    ini_set('session.cookie_lifetime', 60 * 60 * 24 * 7);
    ini_set('session.gc-maxlifetime', 60 * 60 * 24 * 7);
    @session_start();
    session_set_cookie_params(604800);
    $_SESSION[ 'count_product' ]     = ( ! isset($_SESSION[ 'count_product' ]) || ! $_SESSION[ 'count_product' ]) ? 0 : $_SESSION[ 'count_product' ];
    $_SESSION[ 'wdm_product_count' ] = ( ! isset($_SESSION[ 'wdm_product_count' ]) || ! $_SESSION[ 'wdm_product_count' ]) ? 0 : $_SESSION[ 'wdm_product_count' ];
}
