<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * This function is used to show email settings on settings page
 * @param  [array] $form_data [Settings stored previously in database]
 * @return [type]            [description]
 */
function emailSettings($form_data)
{
?>
<div id='wdm_email'>
    <?php
        require_once('email/quoteup-emailing-information.php');
        do_action('quoteup_email_settings', $form_data);
    ?>
</div>
<?php
}
?>