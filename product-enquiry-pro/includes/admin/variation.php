<?php
/**
 * Overriding WooCommerce's Template for variation so that we can have control over the fields we need
 * to show on Edit Quote Page after getting variation data by Ajax. This template is loaded only on Edit Quote Page.
 *
 * This is a javascript-based template for single variations (see https://codex.wordpress.org/Javascript_Reference/wp.template).
 * The values will be dynamically replaced after selecting attributes.
 *
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<script type="text/template" id="tmpl-variation-template">
    <div class="quoteup-variation-price">
        {{{ data.variation.price_html }}}
    </div>
    <div class="quoteup-regular-price">
        {{{ data.variation.display_regular_price }}}
    </div>
    <div class="quoteup-price">
        {{{ data.variation.display_price }}}
    </div>
</script>
<script type="text/template" id="tmpl-unavailable-variation-template">
    <p><?php _e('Sorry, this product is unavailable. Please choose a different combination.', 'woocommerce');?></p>
</script>
