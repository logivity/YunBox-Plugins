<div class="from-info">
    <div class="from-title">
        <?php _e('From', 'quoteup'); ?>
    </div> <!-- .form-title ends -->
    <div class="from-data">
        <?php
            echo isset($pdfSetting[ 'company_name' ]) ? $pdfSetting[ 'company_name' ] . "<br>" : '';
            echo isset($pdfSetting[ 'company_address' ]) ? $pdfSetting[ 'company_address' ] . "<br>" : '';
            echo isset($pdfSetting[ 'company_email' ]) ? $pdfSetting[ 'company_email' ] . "<br>" : '';
        ?>
    </div> <!-- .form-data ends -->
</div> <!-- .from-info ends -->