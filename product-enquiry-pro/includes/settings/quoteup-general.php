<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * This function is used to show General settings on settings page
 * @param  [array] $form_data [Settings stored previously in database]
 * @return [type]            [description]
 */
function generalSettings($form_data)
{
    ?>
<div id="wdm_general">
            <?php
            require_once('general/quoteup-enquiry-and-quote-options.php');
            require_once('general/quoteup-multiproduct-enquiry-and-quote-options.php');
            require_once('general/quoteup-migrate-enquiries.php');
            require_once('general/quoteup-redirection-page-url.php');
            require_once('general/quoteup-form-options.php');
            do_action('quoteup_general_settings', $form_data);
            ?>
</div>
            <?php
}
