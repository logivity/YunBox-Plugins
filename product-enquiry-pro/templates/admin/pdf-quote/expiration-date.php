<?php if (! empty($expiration_date)) : ?>
    <div class="expiration-info">
        <div class="expiration-title">
            <?php _e('Expiration Date', 'quoteup'); ?>
        </div> <!-- .expiration-title ends here -->
        <div class="expiration-data">
            <?php echo $expiration_date . "<br>"; ?>
        </div> <!-- .expiration-data ends here -->
    </div> <!-- .expiration-info ends here -->
<?php endif; ?>