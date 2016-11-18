<html>
    <body>
        <div id='header'>
            <?php quoteupGetAdminTemplatePart('pdf-quote/company-logo', "", $args); ?>
            <div class="content">
                <h2> <?php _e('Quote', 'quoteup'); ?>  </h2>
                <?php quoteupGetAdminTemplatePart('pdf-quote/sender-info',"",$args); ?>
                <div class="clear"></div>
                <?php quoteupGetAdminTemplatePart('pdf-quote/recipient-info',"",$args); ?>
                <div class="clear"></div>
                <?php quoteupGetAdminTemplatePart('pdf-quote/expiration-date',"",$args);?>
                <div class="clear"></div>
            </div> <!-- .content ends here -->
        </div> <!-- #header ends here -->
        <div id="head">
            <h2>
                <?php _e('Quote Request', 'quoteup'); ?> #<?php echo "$enquiry_id"; ?>
            </h2>
        </div> <!-- #head ends here -->
        <div id="Enquiry">
            <?php 
                quoteupGetAdminTemplatePart('pdf-quote/quote-table',"",$args);
                quoteupGetAdminTemplatePart('pdf-quote/tax-shipping-note',"",$args);
            ?>
        </div> <!-- #Enquiry ends here -->
        <?php quoteupGetAdminTemplatePart('pdf-quote/quote-link',"",$args); ?>
    </body>
</html>