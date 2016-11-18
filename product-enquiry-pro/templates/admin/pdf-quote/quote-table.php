<table align="center" class="quote_table">
    <?php quoteupGetAdminTemplatePart('pdf-quote/quote-table-header',"",$args); ?>
    <?php
        $products    = unserialize($enquiry_details->product_details);
        $total_price = 0;
        foreach ($quotation as $quoteProduct) {
            $_product    = wc_get_product($quoteProduct['product_id']);
            if (empty($_product)) {
                continue;
            }
            $price       = $quoteProduct['oldprice'];
            $total_price = $total_price + $quoteProduct['newprice'] * $quoteProduct['quantity']     ;
            $args['quoteProduct'] = $quoteProduct;
            $args['_product'] = $_product;
            $args['price'] = $price;
            quoteupGetAdminTemplatePart('pdf-quote/quote-table-body',"",$args);
        }
    ?>
    <tr border="1">
        <td align="right" <?php echo ($show_price == 1) ? 'colspan="5"' : 'colspan="4"' ?>> 
            <?php _e('TOTAL', 'quoteup'); ?> 
        </td>
        <td align="right" >
            <?php echo wc_price($total_price); ?>
        </td>
    </tr>
</table>
 