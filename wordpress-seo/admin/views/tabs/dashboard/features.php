<?php
/**
 * @package WPSEO\Admin\Views
 */

if ( ! defined( 'WPSEO_VERSION' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

$feature_toggles = array(
	(object) array(
		'name'    => __( 'Advanced settings pages', 'wordpress-seo' ),
		'setting' => 'enable_setting_pages',
		'label'   => __( 'The advanced settings include site-wide settings for your titles and meta descriptions, social metadata, sitemaps and much more.', 'wordpress-seo' ),
	),
	(object) array(
		'name'    => __( 'Admin bar menu', 'wordpress-seo' ),
		'setting' => 'enable_admin_bar_menu',
		/* translators: %1$s expands to Yoast SEO*/
		'label'   => sprintf( __( 'The %1$s admin bar menu contains useful links to third-party tools for analyzing pages and makes it easy to see if you have new notifications.', 'wordpress-seo' ), 'Yoast SEO' ),
	),
);

/**
 * Filter to add feature toggles from add-ons.
 *
 * @param array $feature_toggles Array with feature toggle objects where each object should have a `name`, `setting` and `label` property.
 */
$feature_toggles = apply_filters( 'wpseo_feature_toggles', $feature_toggles );

?>
<h2>Features</h2>

<?php echo esc_html( sprintf(
	__( '%1$s comes with a lot of features. You can enable / disable some of them below.', 'wordpress-seo' ),
	'Yoast SEO'
) ) ?>
<?php foreach ( $feature_toggles as $feature ) : ?>
<h3><?php echo esc_html( $feature->name ); ?></h3>
<p>
	<?php
		$yform->toggle_switch(
			$feature->setting,
			array(
				'on'  => __( 'Enabled', 'wordpress-seo' ),
				'off' => __( 'Disabled', 'wordpress-seo' ),
			),
			$feature->label
		);
	?>
</p>
<br />

<?php endforeach; ?>

<?php
	// Required to prevent our settings framework from saving the default because the field isn't explicitly set when saving the Dashboard page.
	$yform->hidden( 'show_onboarding_notice', 'wpseo_show_onboarding_notice' );
?>
