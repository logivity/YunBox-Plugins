<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
/**
 * This function is used to show display settings on settings page
 * @param  [array] $form_data [Settings stored previously in database]
 * @return [type]            [description]
 */
function displaySettings($form_data)
{
    ?>
<div id='wdm_display'>
<?php
    require_once('display/quoteup-styling-options.php');
    do_action('quoteup_display_settings', $form_data);
?>
        
</div>
<?php
}

