<?php
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

//Get License key
$license_key = get_option('edd_' . $this->pluginSlug. '_license_key');

//Get License Status
$status = get_option('edd_' . $this->pluginSlug. '_license_status');

$sites = get_option('wdm_' . $this->pluginSlug. '_license_key_sites');

$renew_link = get_option('wdm_' . $this->pluginSlug. '_product_site');

include_once(QUOTEUP_PLUGIN_DIR . '/includes/class-quoteup-get-data.php');

$active_site = \Includes\QuoteupGetData::getSiteList($this->pluginSlug);

$display = "";

if (! empty($active_site) || $active_site != "") {
    $display = "<ul>" . $active_site . "</ul>";
}
?>
<div class="wrap">
	<?php
    $license_key = trim(get_option('edd_' . $this->pluginSlug. '_license_key'));
    //Handle Submission of inputs on license page
    if (isset($GLOBALS[ 'wdm_server_null_response' ]) && $GLOBALS[ 'wdm_server_null_response' ] == true) {
        add_settings_error(
            'wdm_' . $this->pluginSlug. '_errors',
            esc_attr('settings_updated'),
            __('No response from server. Please try again later.', 'quoteup'),
            'error'
        );
    } elseif (isset($GLOBALS[ 'wdm_license_activation_failed' ]) && $GLOBALS[ 'wdm_license_activation_failed' ] == true) {
        add_settings_error(
            'wdm_' . $this->pluginSlug. '_errors',
            esc_attr('settings_updated'),
            __('License Activation Failed. Please try again or contact support on support@wisdmlabs.com', 'quoteup'),
            'error'
        );
    } elseif (isset($_POST[ 'edd_' . $this->pluginSlug. '_license_key' ]) && empty($_POST[ 'edd_' . $this->pluginSlug. '_license_key' ])) {
        //If empty, show error message
        add_settings_error(
            'wdm_' . $this->pluginSlug. '_errors',
            esc_attr('settings_updated'),
            __('Please enter license key.', 'quoteup'),
            'error'
        );
    } elseif ($status !== false && $status == 'valid') { //Valid license key
        add_settings_error(
            'wdm_' . $this->pluginSlug. '_errors',
            esc_attr('settings_updated'),
            __('Your license key is activated.', 'quoteup'),
            'updated'
        );
    } elseif ($status !== false && $status == 'expired' && ( ! empty($display) || $display != "")) { //Expired license key
        add_settings_error(
            'wdm_' . $this->pluginSlug. '_errors',
            esc_attr('settings_updated'),
            __('Your license key has Expired. Please, Renew it. <br/>Your License Key is already activated at : ' . $display . ' Please deactivate the license from one of the above site(s) to successfully activate it on your current site.', 'quoteup'),
            'error'
        );
    } elseif ($status !== false && $status == 'expired') { //Expired license key
        add_settings_error(
            'wdm_' . $this->pluginSlug. '_errors',
            esc_attr('settings_updated'),
            __('Your license key has Expired. Please, Renew it.', 'quoteup'),
            'error'
        );
    } elseif ($status !== false && $status == 'disabled') { //Disabled license key
        add_settings_error(
            'wdm_' . $this->pluginSlug. '_errors',
            esc_attr('settings_updated'),
            __('Your license key is Disabled.', 'quoteup'),
            'error'
        );
    } elseif ($status == 'invalid' && ( ! empty($display) || $display != "")) { //Invalid license key   and site
        add_settings_error(
            'wdm_' . $this->pluginSlug. '_errors',
            esc_attr('settings_updated'),
            sprintf(__('Your License Key is already activated at : %s Please deactivate the license from one of the above site(s) to successfully activate it on your current site.', 'quoteup'), $display),
            'error'
        );
    } elseif ($status == 'invalid') { //Invalid license key
        add_settings_error(
            'wdm_' . $this->pluginSlug. '_errors',
            esc_attr('settings_updated'),
            __('Please enter valid license key.', 'quoteup'),
            'error'
        );
    } elseif ($status == 'site_inactive' && ( ! empty($display) || $display != "")) { //Invalid license key   and site inactive
        add_settings_error(
            'wdm_' . $this->pluginSlug. '_errors',
            esc_attr('settings_updated'),
            sprintf(__('Your License Key is already activated at : %s Please deactivate the license from one of the above site(s) to successfully activate it on your current site.', 'quoteup'), $display),
            'error'
        );
    } elseif ($status == 'site_inactive') { //Site is inactive
        add_settings_error(
            'wdm_' . $this->pluginSlug. '_errors',
            esc_attr('settings_updated'),
            __('Site inactive(Press Activate license to activate plugin)', 'quoteup'),
            'error'
        );
    } elseif ($status == 'deactivated') { //Site is inactive
        add_settings_error(
            'wdm_' . $this->pluginSlug. '_errors',
            esc_attr('settings_updated'),
            __('License Key is deactivated', 'quoteup'),
            'updated'
        );
    }

    settings_errors('wdm_' . $this->pluginSlug. '_errors');
    ?>
	<h2><?php echo sprintf(__('%s License Options', 'quoteup'), $this->pluginName); ?></h2>

	<form method="post" action="">
		<table class="form-table">
			<tbody>
				<tr valign="top">   
					<th scope="row" valign="top">
	<?php _e('License Key', 'quoteup'); ?>
					</th>
					<td>
	<?php
    if (($status == "valid" || $status == "expired") && (empty($display) || $display == "")) {
        ?>
							<input id="<?php echo 'edd_' . $this->pluginSlug. '_license_key' ?>" name="<?php echo 'edd_' . $this->pluginSlug. '_license_key' ?>" type="text" class="regular-text" value="<?php esc_attr_e($license_key); ?>" readonly/>
		<?php
    } else {
        ?>
                    <input id="<?php echo 'edd_' . $this->pluginSlug. '_license_key' ?>" name="<?php echo 'edd_' . $this->pluginSlug. '_license_key' ?>" type="text" class="regular-text" value="<?php esc_attr_e($license_key); ?>" />
		<?php
    }
    ?><label class="description" for="<?php echo 'edd_' . $this->pluginSlug. '_license_key' ?>"></label>
					</td>
				</tr>

				<tr>
					<th scope="row" valign="top">
	<?php _e('License Status', 'quoteup'); ?>
					</th>
					<td>
	<?php if ($status !== false && $status == 'valid') { ?>
							<span style="color:green;"><?php _e('Active', 'quoteup'); ?></span>
		<?php
} elseif (get_option('edd_' . $this->pluginSlug. '_license_status') == 'site_inactive') {
    ?>
                        <span style="color:red;"><?php _e('Not Active', 'quoteup') ?></span>
		<?php
} elseif (get_option('edd_' . $this->pluginSlug. '_license_status') == 'expired' && ( ! empty($display) || $display != "")) {
    ?>
                        <span style="color:red;"><?php _e('Not Active', 'quoteup') ?></span>
	<?php
} elseif (get_option('edd_' . $this->pluginSlug. '_license_status') == 'expired') {
    ?>
                        <span style="color:green;"><?php _e('Active', 'quoteup') ?></span>
	<?php     } elseif (get_option('edd_' . $this->pluginSlug. '_license_status') == 'invalid') {
    ?>
							<span style="color:red;"><?php _e('Not Active', 'quoteup'); ?></span>
							<?php
} else {
    ?>
    <span style="color:red;"><?php _e('Not Active', 'quoteup'); ?></span>
							<?php
}
                        ?>
					</td>
				</tr>
				<tr valign="top">   
					<th scope="row" valign="top">
<?php _e('Activate License', 'quoteup'); ?>
					</th>
					<td>
<?php if ($status !== false && $status == 'valid') { ?>

	<?php wp_nonce_field('edd_' . $this->pluginSlug. '_nonce', 'edd_' . $this->pluginSlug. '_nonce'); ?>
							<input type="submit" class="button-primary" name="<?php echo 'edd_' . $this->pluginSlug. '_license_deactivate'; ?>" value="<?php _e('Deactivate License', 'quoteup'); ?>"/>

	<?php } elseif ($status == 'expired' && ( ! empty($display) || $display != "")) {
    ?>

							<?php wp_nonce_field('edd_' . $this->pluginSlug. '_nonce', 'edd_' . $this->pluginSlug. '_nonce'); ?>
							<input type="submit" class="button-primary" name="<?php echo 'edd_' . $this->pluginSlug. '_license_activate'; ?>" 
								   value="<?php _e('Activate License', 'quoteup'); ?>"/>
							<?php if (! empty($renew_link)) { ?>
								<input type="button" class="button-primary" name="<?php echo 'edd_' . $this->pluginSlug. '_license_renew'; ?>" 
									   value="<?php _e('Renew License', 'quoteup'); ?>" onclick="window.open( '<?php echo $renew_link; ?>' )"/> 
							<?php }
} elseif ($status == 'expired') {
     wp_nonce_field('edd_' . $this->pluginSlug. '_nonce', 'edd_' . $this->pluginSlug. '_nonce'); ?>
							<input type="submit" class="button-primary" name="<?php echo 'edd_' . $this->pluginSlug. '_license_deactivate'; ?>"
           value="<?php _e('Deactivate License', 'quoteup'); ?>"/>

							<input type="button" class="button-primary" name="<?php echo 'edd_' . $this->pluginSlug. '_license_renew'; ?>"
           value="<?php _e('Renew License', 'quoteup'); ?>" onclick="window.open( '<?php echo $renew_link; ?>' )"/>

							<?php
} else {
    wp_nonce_field('edd_' . $this->pluginSlug. '_nonce', 'edd_' . $this->pluginSlug. '_nonce');
    ?>
    <input type="submit" class="button-primary" name="<?php echo 'edd_' . $this->pluginSlug. '_license_activate'; ?>" value="<?php _e('Activate License', 'quoteup'); ?>"/>
							<?php                         }
                        ?>
					</td>
				</tr>
			</tbody>
		</table>
	</form>
</div>
