<?php
/*
Plugin Name: Slim Stat Analytics
Plugin URI: http://wordpress.org/plugins/wp-slimstat/
Description: The leading web analytics plugin for WordPress
Version: 4.4.5
Author: Jason Crouse
Author URI: http://www.wp-slimstat.com/
Text Domain: wp-slimstat
Domain Path: /languages
*/

if ( !empty( wp_slimstat::$settings ) ) {
	return true;
}

class wp_slimstat {
	public static $version = '4.4.5';
	public static $settings = array();
	public static $options = array(); // To be removed, here just for backward compatibility

	public static $wpdb = '';
	public static $maxmind_path = '';
	public static $browscap_path = '';
	public static $advanced_cache_exists = false;
	public static $update_checker = array();
	public static $raw_post_array = array();

	protected static $data_js = array( 'id' => 0 );
	protected static $stat = array();
	protected static $settings_signature = '';
	protected static $browser = array();
	protected static $heuristic_key = 0;
	protected static $date_i18n_filters = array();

	/**
	 * Initializes variables and actions
	 */
	public static function init() {

		// Load all the settings
		if ( is_network_admin() && ( empty($_GET[ 'page' ] ) || strpos( $_GET[ 'page' ], 'slimview' ) === false ) ) {
			self::$settings = get_site_option( 'slimstat_options', array() );
		}
		else {
			self::$settings = get_option( 'slimstat_options', array() );
		}

		self::$settings = array_merge( self::init_options(), self::$settings );

		// Allow third party tools to edit the options
		self::$settings = apply_filters( 'slimstat_init_options', self::$settings );

		// To be removed - Backward compatibility
		self::$options = self::$settings;

		// Determine the options' signature: if it hasn't changed, there's no need to update/save them in the database
		self::$settings_signature = md5( serialize( self::$settings ) );

		// Allow third-party tools to use a custom database for Slimstat
		self::$wpdb = apply_filters( 'slimstat_custom_wpdb', $GLOBALS[ 'wpdb' ] );

		// Hook a DB clean-up routine to the daily cronjob
		add_action( 'wp_update_plugins', array( __CLASS__, 'wp_slimstat_purge' ) );

		// Allow external domains on CORS requests
		add_filter( 'allowed_http_origins', array(__CLASS__, 'open_cors_admin_ajax' ) );

		// Define the folder where to store the geolocation database (shared among sites in a network, by default)
		self::$maxmind_path = wp_upload_dir();
		if ( is_multisite() && ! ( is_main_network() && is_main_site() && defined( 'MULTISITE' ) ) ) {
			self::$maxmind_path = str_replace( '/sites/' . get_current_blog_id(), '', self::$maxmind_path );
		}
		self::$maxmind_path = self::$maxmind_path[ 'basedir' ] . '/wp-slimstat/maxmind.dat';
		self::$maxmind_path = apply_filters( 'slimstat_maxmind_path', self::$maxmind_path );

		// Path to the Browscap Database file
		self::$browscap_path = dirname( self::$maxmind_path ) . '/browscap-db.php';
		self::$browscap_path = apply_filters( 'slimstat_browscap_path', self::$browscap_path );

		// Path to wp-content folder, used to detect caching plugins via advanced-cache.php
		if ( file_exists( dirname( dirname( plugin_dir_path( __FILE__ ) ) ) . '/advanced-cache.php' ) ) {
			self::$advanced_cache_exists = true;
		}

		// Enable the tracker (both server- and client-side)
		if ( !is_admin() || self::$settings[ 'track_admin_pages' ] == 'yes' ) {
			
			// Allow add-ons to turn off the tracker based on other conditions
			$is_tracking_filter = apply_filters( 'slimstat_filter_pre_tracking', strpos( self::get_request_uri(), 'wp-admin/admin-ajax.php' ) === false );
			$is_tracking_filter_js = apply_filters( 'slimstat_filter_pre_tracking_js', true );

			// Is server-side tracking active?
			if ( self::$settings[ 'javascript_mode' ] != 'yes' && self::$settings[ 'is_tracking' ] == 'yes' && $is_tracking_filter ) {
				add_action( is_admin() ? 'admin_init' : 'wp', array( __CLASS__, 'slimtrack' ), 5 );

				if ( self::$settings[ 'track_users' ] == 'yes' ) {
					add_action( 'login_init', array( __CLASS__, 'slimtrack' ), 10 );
				}
			}

			// Slimstat tracks screen resolutions, outbound links and other client-side information using javascript
			if ((self::$settings['enable_javascript'] == 'yes' || self::$settings['javascript_mode'] == 'yes') && self::$settings['is_tracking'] == 'yes' && $is_tracking_filter_js){
				add_action( is_admin() ? 'admin_enqueue_scripts' : 'wp_enqueue_scripts' , array(__CLASS__, 'wp_slimstat_enqueue_tracking_script'), 15);
				if ( self::$settings[ 'track_users' ] == 'yes' ) {
					add_action( 'login_enqueue_scripts', array( __CLASS__, 'wp_slimstat_enqueue_tracking_script' ), 10 );
				}
			}
		}

		// Shortcodes
		add_shortcode('slimstat', array(__CLASS__, 'slimstat_shortcode'), 15);

		if ( is_user_logged_in() ) {
			include_once ( plugin_dir_path( __FILE__ ) . 'admin/wp-slimstat-admin.php' );
			add_action( 'init', array( 'wp_slimstat_admin', 'init' ), 60 );
		}

		// Include our browser detector library
		include_once( plugin_dir_path( __FILE__ ) . 'browscap/browser.php' );
		add_action( 'init', array( 'slim_browser', 'init' ) );

		// If add-ons are installed, check for updates
		add_action( 'wp_loaded', array( __CLASS__, 'update_checker' ) );

		// Update the options before shutting down
		add_action( 'shutdown', array( __CLASS__, 'slimstat_save_options' ), 100 );
	}
	// end init

	/**
	 * Ajax Tracking
	 */
	public static function slimtrack_ajax() {

		// This function also initializes self::$data_js and removes the checksum from self::$data_js['id']
		self::_check_data_integrity( self::$raw_post_array );

		// Is this a request to record a new pageview?
		if ( self::$data_js[ 'op' ] == 'add' || empty( self::$data_js[ 'pos' ] ) ) {

			// Track client-side information (screen resolution, plugins, etc)
			if ( !empty( self::$data_js[ 'bw' ] ) ) {
				self::$stat[ 'resolution' ] = strip_tags( trim( self::$data_js[ 'bw' ] . 'x' . self::$data_js[ 'bh' ] ) );
			}
			if ( !empty( self::$data_js[ 'sw' ] ) ) {
				self::$stat[ 'screen_width' ] = intval( self::$data_js[ 'sw' ] );
			}
			if ( !empty( self::$data_js[ 'sh' ] ) ) {
				self::$stat[ 'screen_height' ] = intval( self::$data_js[ 'sh' ] );
			}
			if ( !empty( self::$data_js[ 'pl' ] ) ) {
				self::$stat[ 'plugins' ] = strip_tags( trim( self::$data_js[ 'pl' ] ) );
			}
			if ( !empty( self::$data_js[ 'sl' ] ) && self::$data_js[ 'sl' ] > 0 && self::$data_js[ 'sl' ] < 60000 ) {
				self::$stat[ 'server_latency' ] = intval( self::$data_js[ 'sl' ] );
			}
			if ( !empty( self::$data_js[ 'pp' ] ) && self::$data_js[ 'pp' ] > 0 && self::$data_js[ 'pp' ] < 60000 ) {
				self::$stat[ 'page_performance' ] = intval( self::$data_js[ 'pp' ] );
			}
		}

		if ( self::$data_js[ 'op' ] == 'add' ) {
			self::slimtrack();
		}
		else {
			// Update an existing pageview with client-based information (resolution, plugins installed, etc)
			self::_set_visit_id( true );

			// ID of the pageview to update
			self::$stat[ 'id' ] = abs( intval( self::$data_js[ 'id' ] ) );

			// Visitor is still on this page, record the timestamp in the corresponding field
			self::toggle_date_i18n_filters( false );
			self::$stat['dt_out'] = date_i18n( 'U' );
			self::toggle_date_i18n_filters( true );

			// Are we tracking an outbound click?
			if ( !empty( self::$data_js[ 'res' ] ) ) {
				$outbound_resource = strip_tags( trim( base64_decode( self::$data_js[ 'res' ] ) ) );
				$outbound_host = parse_url( $outbound_resource, PHP_URL_HOST );
				$site_host = parse_url( get_site_url(), PHP_URL_HOST );
				if ( $outbound_host != $site_host ) {
					$existing_outbound_resource = self::$wpdb->get_var( self::$wpdb->prepare( "
						SELECT outbound_resource
						FROM {$GLOBALS['wpdb']->prefix}slim_stats
						WHERE id = %d", self::$stat[ 'id' ]
					) );

					self::$stat[ 'outbound_resource' ] = $outbound_resource;
					if ( !empty( $existing_outbound_resource ) ) {
						self::$stat[ 'outbound_resource' ] = $existing_outbound_resource . ';;;' .$outbound_resource;
					}
				}
			}

			self::update_row( self::$stat, $GLOBALS[ 'wpdb' ]->prefix . 'slim_stats' );
		}

		// Was this pageview tracked?
		if ( self::$stat[ 'id' ] <= 0 ) {
			$abs_error_code = abs( self::$stat[ 'id' ] );
			do_action( 'slimstat_track_exit_' . $abs_error_code, self::$stat );
			self::slimstat_save_options();
			exit( self::_get_id_with_checksum( self::$stat[ 'id' ] ) );
		}

		// Is an event associated to this request?
		if ( !empty( self::$data_js[ 'pos' ] ) ) {
			self::toggle_date_i18n_filters( false );
			$event_info = array(
				'position' => strip_tags( trim( self::$data_js[ 'pos' ] ) ),
				'id' => self::$stat[ 'id' ],
				'dt' => date_i18n( 'U' )
			);
			self::toggle_date_i18n_filters( true );
			
			if ( !empty( self::$data_js[ 'ty' ] ) ) {
				$event_info[ 'type' ] = abs( intval( self::$data_js[ 'ty' ] ) );
			}
			if ( !empty( self::$data_js[ 'des' ] ) ) {
				$event_info[ 'event_description' ] = strip_tags( trim( base64_decode( self::$data_js[ 'des' ] ) ) );
			}
			if ( !empty( self::$data_js[ 'no' ] ) ) {
				$event_info[ 'notes' ] = strip_tags( trim( base64_decode( self::$data_js[ 'no' ] ) ) );
			}

			self::insert_row( $event_info, $GLOBALS[ 'wpdb' ]->prefix . 'slim_events' );
		}

		// Send the ID back to Javascript to track future interactions
		do_action( 'slimstat_track_success' );
		
		// If we tracked an internal download, we return the original ID, not the new one
		if ( self::$data_js[ 'op' ] == 'add' && !empty( self::$data_js[ 'pos' ] ) ) {
			exit( self::_get_id_with_checksum( self::$data_js[ 'id' ] ) );
		}
		else{
			exit( self::_get_id_with_checksum( self::$stat[ 'id' ] ) );
		}
	}

	/**
	 * Core tracking functionality
	 */
	public static function slimtrack( $_argument = '' ) {
		self::toggle_date_i18n_filters( false );
		self::$stat[ 'dt' ] = date_i18n( 'U' );
		self::$stat[ 'notes' ] = array();
		self::toggle_date_i18n_filters( true );

		// Allow third-party tools to initialize the stat array
		self::$stat = apply_filters('slimstat_filter_pageview_stat_init', self::$stat);

		// Third-party tools can decide that this pageview should not be tracked, by setting its datestamp to zero
		if ( empty( self::$stat ) || empty( self::$stat[ 'dt' ] ) ) {
			self::$stat[ 'id' ] = -300;
			self::_set_error_array( __( 'Pageview filtered by third-party code', 'wp-slimstat' ), true );
			return $_argument;
		}

		if ( !empty( self::$data_js[ 'ref' ] ) ) { 
			self::$stat[ 'referer' ] = base64_decode( self::$data_js[ 'ref' ] );
		}
		else if ( !empty( $_SERVER[ 'HTTP_REFERER' ] ) ) {
			self::$stat[ 'referer' ] = $_SERVER[ 'HTTP_REFERER' ];
		}

		if ( !empty( self::$stat[ 'referer' ] ) ) {
		
			// Is this a 'seriously malformed' URL?
			$referer = parse_url( self::$stat[ 'referer' ] );
			if ( !$referer ) {
				self::$stat[ 'id' ] = -201;
				self::_set_error_array( sprintf( __( 'Malformed URL %s', 'wp-slimstat' ), self::$stat[ 'referer' ] ), false );
				return $_argument;
			}

			$parsed_site_url = parse_url( get_site_url(), PHP_URL_HOST );
			if ( $referer[ 'host' ] == $parsed_site_url ) {
				unset( self::$stat[ 'referer' ] );
			}
			else {
				// Fix Google Images referring domain
				if ( strpos(self::$stat[ 'referer' ], 'www.google' ) !== false ) { 
					if ( strpos( self::$stat[ 'referer' ], '/imgres?' ) !== false ) {
						self::$stat[ 'referer' ] = str_replace( 'www.google', 'images.google', self::$stat[ 'referer' ] );
					}
					if ( strpos( self::$stat[ 'referer' ], '/url?' ) !== false ) {
						self::$stat[ 'referer' ] = str_replace( '/url?', '/search?', self::$stat[ 'referer' ] );
					}
				}

				// Is this referer blacklisted?
				foreach ( self::string_to_array( self::$settings[ 'ignore_referers' ] ) as $a_filter ) {
					$pattern = str_replace( array( '\*', '\!' ) , array( '(.*)', '.' ), preg_quote( $a_filter, '/' ) );
					if ( preg_match( "@^$pattern$@i", self::$stat[ 'referer' ] ) ) {
						self::$stat[ 'id' ] = -301;
						self::_set_error_array( sprintf( __( 'Referrer %s is blacklisted', 'wp-slimstat' ), self::$stat[ 'referer' ] ), true );
						return $_argument;
					}
				}
			}
		}

		$content_info = self::_get_content_info();

		// Did we receive data from an Ajax request?
		if ( !empty( self::$data_js[ 'id' ] ) ) {

			// Are we tracking a new pageview? (pos is empty = no event was triggered)
			if ( empty( self::$data_js[ 'pos' ] ) ) {
				$content_info = unserialize( base64_decode( self::$data_js[ 'id' ] ) );
				if ( $content_info === false || empty( $content_info[ 'content_type' ] ) ) {
					$content_info = array();
				}
			}
			
			// If pos is not empty and slimtrack was called, it means we are tracking a new internal download
			else if ( !empty( self::$data_js[ 'res' ] ) ) {
				$download_url = base64_decode( self::$data_js[ 'res' ] );
				if ( is_string( $download_url ) ) {
					if ( !empty( self::$data_js[ 'ty' ] ) && self::$data_js[ 'ty' ] == 1 ) {
						unset( self::$stat[ 'id' ] );
						$content_info = array( 'content_type' => 'download' );
					}
				}
			}
		}

		self::$stat = self::$stat + $content_info;

		// We want to record both hits and searches (performed through the site search form)
		if ( self::$stat[ 'content_type' ] == 'external' ) {
			self::$stat[ 'resource' ] = $_SERVER[ 'HTTP_REFERER' ];
			self::$stat[ 'referer' ] = '';
		}
		else if ( is_array( self::$data_js ) && isset( self::$data_js[ 'res' ] ) ) {
			$parsed_permalink = parse_url( base64_decode( self::$data_js[ 'res' ] ) );
			self::$stat['searchterms'] = self::_get_search_terms($referer);

			// Was this an internal search?
			if ( empty( self::$stat[ 'searchterms' ] ) ) {
				self::$stat[ 'searchterms' ] = self::_get_search_terms( $parsed_permalink );

			}

			self::$stat['resource'] = !is_array( $parsed_permalink ) ? self::$data_js[ 'res' ] : urldecode( $parsed_permalink[ 'path' ] ) . ( !empty( $parsed_permalink[ 'query' ] ) ? '?' . urldecode( $parsed_permalink[ 'query' ] ) : '' );
		}
		elseif ( empty( $_REQUEST[ 's' ] ) ) {
			if ( !empty( $referer ) ) {
				self::$stat[ 'searchterms' ] = self::_get_search_terms( $referer );
			}
			self::$stat[ 'resource' ] = self::get_request_uri();
		}
		else{
			self::$stat[ 'searchterms' ] = str_replace( '\\', '', $_REQUEST[ 's' ] );
			self::$stat[ 'referer' ] = self::get_request_uri();
			if ( isset( $GLOBALS['wp_query']->found_posts ) ) {
				self::$stat[ 'notes' ][] = 'results:' . intval( $GLOBALS['wp_query']->found_posts );
			}
		}

		// Don't store empty values in the database
		if ( empty( self::$stat[ 'searchterms' ] ) ) {
			unset( self::$stat[ 'searchterms' ] );
		}

		// Do not track report pages in the admin
		if ( ( !empty( self::$stat[ 'resource' ] ) && strpos( self::$stat[ 'resource' ], 'wp-admin/admin-ajax.php' ) !== false ) || ( !empty( $_GET[ 'page' ] ) && strpos( $_GET[ 'page' ], 'slimview' ) !== false ) ) {
			self::$stat = array();
			return $_argument;
		}

		// Is this resource blacklisted?
		if ( !empty( self::$stat[ 'resource' ] ) ) {
			foreach ( self::string_to_array( self::$settings[ 'ignore_resources' ] ) as $a_filter ) {
				$pattern = str_replace( array('\*', '\!') , array('(.*)', '.'), preg_quote($a_filter, '/'));
				if ( preg_match( "@^$pattern$@i", self::$stat[ 'resource' ] ) ) {
					self::$stat['id'] = -302;
					self::_set_error_array( sprintf( __( 'Permalink %s is blacklisted', 'wp-slimstat' ), self::$stat[ 'resource' ] ), true );
					return $_argument;
				}
			}
		}

		// User's IP address
		list ( self::$stat[ 'ip' ], self::$stat[ 'other_ip' ] ) = self::_get_remote_ip();

		if ( empty( self::$stat[ 'ip' ] ) || self::$stat[ 'ip' ] == '0.0.0.0' ) {
			self::$stat[ 'id' ] = -202;
			self::_set_error_array( __( 'Empty or not supported IP address format (IPv6)', 'wp-slimstat' ), false );
			return $_argument;
		}

		// Should we ignore this user?
		if ( !empty( $GLOBALS[ 'current_user' ]->ID ) ) {
			// Don't track logged-in users, if the corresponding option is enabled
			if ( self::$settings[ 'track_users' ] == 'no' ) {
				self::$stat[ 'id' ] = -303;
				self::_set_error_array( sprintf( __( 'Logged in user %s not tracked', 'wp-slimstat' ), $GLOBALS[ 'current_user' ]->data->user_login ), true );
				return $_argument;
			}

			// Don't track users with given capabilities
			foreach ( self::string_to_array( self::$settings[ 'ignore_capabilities' ] ) as $a_capability ) {
				if ( array_key_exists( strtolower( $a_capability ), $GLOBALS[ 'current_user' ]->allcaps ) ) {
					self::$stat[ 'id' ] = -304;
					self::_set_error_array( sprintf( __( 'User with capability %s not tracked', 'wp-slimstat' ), $a_capability ), true );
					return $_argument;
				}
			}

			// Is this user blacklisted?
			foreach ( self::string_to_array( self::$settings[ 'ignore_users' ] ) as $a_filter ) {
				$pattern = str_replace( array( '\*', '\!' ) , array( '(.*)', '.' ), preg_quote( $a_filter, '/' ) );
				if ( preg_match( "~^$pattern$~i", $GLOBALS[ 'current_user' ]->data->user_login ) ) {
					self::$stat['id'] = -305;
					self::_set_error_array( sprintf( __( 'User %s is blacklisted', 'wp-slimstat' ), $GLOBALS[ 'current_user' ]->data->user_login ), true );
					return $_argument;
				}
			}

			self::$stat['username'] = $GLOBALS['current_user']->data->user_login;
			self::$stat['notes'][] = 'user:' . $GLOBALS[ 'current_user' ]->data->ID;
			$not_spam = true;
		}
		elseif (isset($_COOKIE['comment_author_'.COOKIEHASH])){
			// Is this a spammer?
			$spam_comment = self::$wpdb->get_row( self::$wpdb->prepare( "
				SELECT comment_author, COUNT(*) comment_count
				FROM `" . DB_NAME . "`.{$GLOBALS['wpdb']->comments}
				WHERE comment_author_IP = %s AND comment_approved = 'spam'
				GROUP BY comment_author
				LIMIT 0,1", self::$stat[ 'ip' ] ), ARRAY_A );

			if ( !empty( $spam_comment[ 'comment_count' ] ) ) {
				if ( self::$settings[ 'ignore_spammers' ] == 'yes' ){
					self::$stat[ 'id' ] = -306;
					self::_set_error_array( sprintf( __( 'Spammer %s not tracked', 'wp-slimstat' ), $spam_comment[ 'comment_author' ] ), true );
					return $_argument;
				}
				else{
					self::$stat['notes'][] = 'spam:yes';
					self::$stat['username'] = $spam_comment['comment_author'];
				}
			}
			else
				self::$stat['username'] = $_COOKIE['comment_author_'.COOKIEHASH];
		}

		// Should we ignore this IP address?
		foreach ( self::string_to_array( self::$settings[ 'ignore_ip' ] ) as $a_ip_range ) {
			$ip_to_ignore = $a_ip_range;

			if ( strpos( $ip_to_ignore, '/' ) !== false ) {
				list( $ip_to_ignore, $cidr_mask ) = explode( '/', trim( $ip_to_ignore ) );
			}
			else{
				$cidr_mask = self::get_mask_length( $ip_to_ignore );
			}

			$long_masked_ip_to_ignore = substr( self::dtr_pton( $ip_to_ignore ), 0, $cidr_mask );
			$long_masked_user_ip = substr( self::dtr_pton( self::$stat[ 'ip' ] ), 0, $cidr_mask );
			$long_masked_user_other_ip = substr( self::dtr_pton( self::$stat[ 'other_ip' ] ), 0 , $cidr_mask );

			if ( $long_masked_user_ip === $long_masked_ip_to_ignore || $long_masked_user_other_ip === $long_masked_ip_to_ignore ) {
				self::$stat['id'] = -307;
				self::_set_error_array( sprintf( __('IP address %s is blacklisted', 'wp-slimstat'), self::$stat[ 'ip' ] . ( !empty( self::$stat[ 'other_ip' ] ) ? ' (' . self::$stat[ 'other_ip' ] . ')' : '' ) ), true );
				return $_argument;
			}
		}

		// Country and Language
		self::$stat['language'] = self::_get_language();
		self::$stat['country'] = self::get_country(self::$stat[ 'ip' ]);

		// Anonymize IP Address?
		if ( self::$settings[ 'anonymize_ip' ] == 'yes' ) {
			// IPv4 or IPv6
			$needle = '.';
			$replace = '.0';
			if ( self::get_mask_length( self::$stat['ip'] ) == 128 ) {
				$needle = ':';
				$replace = ':0000';
			}

			self::$stat[ 'ip' ] = substr( self::$stat[ 'ip' ], 0, strrpos( self::$stat[ 'ip' ], $needle ) ) . $replace;

			if ( !empty( self::$stat[ 'other_ip' ] ) ) {
				self::$stat[ 'other_ip' ] = substr( self::$stat[ 'other_ip' ], 0, strrpos( self::$stat[ 'other_ip' ], $needle ) ) . $replace;
			}
		}

		// Is this country blacklisted?
		if ( is_string( self::$settings[ 'ignore_countries' ] ) && stripos( self::$settings[ 'ignore_countries' ], self::$stat[ 'country' ] ) !== false ) {
			self::$stat['id'] = -308;
			self::_set_error_array( sprintf( __('Country %s is blacklisted', 'wp-slimstat'), self::$stat[ 'country' ] ), true );
			return $_argument;
		}

		// Mark or ignore Firefox/Safari prefetching requests (X-Moz: Prefetch and X-purpose: Preview)
		if ( ( isset( $_SERVER[ 'HTTP_X_MOZ' ] ) && ( strtolower( $_SERVER[ 'HTTP_X_MOZ' ] ) == 'prefetch' ) ) ||
			( isset( $_SERVER[ 'HTTP_X_PURPOSE' ] ) && ( strtolower( $_SERVER[ 'HTTP_X_PURPOSE' ] ) == 'preview' ) ) ) {
			if ( self::$settings[ 'ignore_prefetch' ] == 'yes' ) {
				self::$stat[ 'id' ] = -309;
				self::_set_error_array( __( 'Prefetch requests are ignored', 'wp-slimstat' ), true );
				return $_argument;
			}
			else{
				self::$stat[ 'notes' ][] = 'pre:yes';
			}
		}

		// Detect user agent
		if ( empty( self::$browser ) ) {
			self::$browser = slim_browser::get_browser();
		}

		// Are we ignoring bots?
		if ( ( self::$settings[ 'javascript_mode' ] == 'yes' || self::$settings[ 'ignore_bots' ] == 'yes' ) && self::$browser[ 'browser_type' ] % 2 != 0 ) {
			self::$stat[ 'id' ] = -310;
			self::_set_error_array( __( 'Bot not tracked', 'wp-slimstat' ), true );
			return $_argument;
		}

		// Is this browser blacklisted?
		foreach ( self::string_to_array( self::$settings[ 'ignore_browsers' ] ) as $a_filter ) {
			$pattern = str_replace( array( '\*', '\!' ) , array( '(.*)', '.' ), preg_quote( $a_filter, '/' ) );
			if ( preg_match( "~^$pattern$~i", self::$browser[ 'browser' ] . '/' . self::$browser[ 'version' ] ) || preg_match( "~^$pattern$~i", self::$browser[ 'browser' ] ) || preg_match( "~^$pattern$~i", self::$browser[ 'user_agent' ] ) ) {
				self::$stat[ 'id' ] = -311;
				self::_set_error_array( sprintf( __( 'Browser %s is blacklisted', 'wp-slimstat' ), self::$browser[ 'browser' ] ), true );
				return $_argument;
			}
		}

		self::$stat = self::$stat + self::$browser;

		// Do we need to assign a visit_id to this user?
		$cookie_has_been_set = self::_set_visit_id( false );

		// Allow third-party tools to modify all the data we've gathered so far
		self::$stat = apply_filters( 'slimstat_filter_pageview_stat', self::$stat );
		do_action( 'slimstat_track_pageview', self::$stat );

		// Third-party tools can decide that this pageview should not be tracked, by setting its datestamp to zero
		if (empty(self::$stat) || empty(self::$stat['dt'])){
			self::$stat['id'] = -300;
			self::_set_error_array( __( 'Pageview filtered by third-party code', 'wp-slimstat' ), true );
			return $_argument;
		}

		// Implode the notes
		if ( !empty( self::$stat[ 'notes' ] ) ) {
			self::$stat[ 'notes' ] = implode( ';', self::$stat[ 'notes' ] );
		}
		else {
			unset( self::$stat[ 'notes' ] );
		}

		// Now let's save this information in the database
		self::$stat[ 'id' ] = self::insert_row( self::$stat, $GLOBALS[ 'wpdb' ]->prefix . 'slim_stats' );

		// Something went wrong during the insert
		if ( empty( self::$stat[ 'id' ] ) ) {

			// Attempt to init the environment (plugin just activated on a blog in a MU network?)
			include_once ( plugin_dir_path( __FILE__ ) . 'admin/wp-slimstat-admin.php' );
			wp_slimstat_admin::init_environment( true );

			// Now let's try again
			self::$stat['id'] = self::insert_row(self::$stat, $GLOBALS['wpdb']->prefix.'slim_stats');

			if ( empty( self::$stat[ 'id' ] ) ) {
				self::$stat[ 'id' ] = -200;
				self::_set_error_array( self::$wpdb->last_error, false );
				return $_argument;
			}
		}

		// Is this a new visitor?
		$is_set_cookie = apply_filters( 'slimstat_set_visit_cookie', true );
		if ( $is_set_cookie ) {
			if ( empty( self::$stat[ 'visit_id' ] ) && !empty( self::$stat[ 'id' ] ) ) {
				// Set a cookie to track this visit (Google and other non-human engines will just ignore it)
				@setcookie(
					'slimstat_tracking_code',
					self::_get_id_with_checksum( self::$stat[ 'id' ] . 'id' ),
					time() + 2678400, // one month
					COOKIEPATH
				);
			}
			elseif ( !$cookie_has_been_set && self::$settings[ 'extend_session' ] == 'yes' && self::$stat[ 'visit_id' ] > 0 ) {
				@setcookie(
					'slimstat_tracking_code',
					self::_get_id_with_checksum( self::$stat[ 'visit_id' ] ),
					time() + self::$settings[ 'session_duration' ],
				 	COOKIEPATH
				);
			}
		}

		return $_argument;
	}
	// end slimtrack

	/**
	 * Searches for the country code associated to a given IP address
	 */
	public static function get_country( $_ip_address = '0.0.0.0' ){
		$float_ipnum = (float) sprintf( "%u", bindec( self::dtr_pton( $_ip_address ) ) );
		$country_output = 'xx';

		// Is this a RFC1918 (local) IP?
		if ($float_ipnum == 2130706433 || // 127.0.0.1
			($float_ipnum >= 167772160 && $float_ipnum <= 184549375) || // 10.0.0.1 - 10.255.255.255
			($float_ipnum >= 2886729728 && $float_ipnum <= 2887778303) || // 172.16.0.1 - 172.31.255.255
			($float_ipnum >= 3232235521 && $float_ipnum <= 3232301055) ){ // 192.168.0.1 - 192.168.255.255
				$country_output = 'xy';
		}
		else {
			$country_codes = array("","ap","eu","ad","ae","af","ag","ai","al","am","cw","ao","aq","ar","as","at","au","aw","az","ba","bb","bd","be","bf","bg","bh","bi","bj","bm","bn","bo","br","bs","bt","bv","bw","by","bz","ca","cc","cd","cf","cg","ch","ci","ck","cl","cm","cn","co","cr","cu","cv","cx","cy","cz","de","dj","dk","dm","do","dz","ec","ee","eg","eh","er","es","et","fi","fj","fk","fm","fo","fr","sx","ga","gb","gd","ge","gf","gh","gi","gl","gm","gn","gp","gq","gr","gs","gt","gu","gw","gy","hk","hm","hn","hr","ht","hu","id","ie","il","in","io","iq","ir","is","it","jm","jo","jp","ke","kg","kh","ki","km","kn","kp","kr","kw","ky","kz","la","lb","lc","li","lk","lr","ls","lt","lu","lv","ly","ma","mc","md","mg","mh","mk","ml","mm","mn","mo","mp","mq","mr","ms","mt","mu","mv","mw","mx","my","mz","na","nc","ne","nf","ng","ni","nl","no","np","nr","nu","nz","om","pa","pe","pf","pg","ph","pk","pl","pm","pn","pr","ps","pt","pw","py","qa","re","ro","ru","rw","sa","sb","sc","sd","se","sg","sh","si","sj","sk","sl","sm","sn","so","sr","st","sv","sy","sz","tc","td","tf","tg","th","tj","tk","tm","tn","to","tl","tr","tt","tv","tw","tz","ua","ug","um","us","uy","uz","va","vc","ve","vg","vi","vn","vu","wf","ws","ye","yt","rs","za","zm","me","zw","a1","a2","o1","ax","gg","im","je","bl","mf","bq","ss","o1");
			if ( file_exists( self::$maxmind_path ) && ( $handle = fopen( self::$maxmind_path, "rb" ) ) ) {

				// Do we need to update the file?
				if (false !== ($file_stat = stat(self::$maxmind_path))){
					
					// Is the database more than 30 days old?
					if ((date('U') - $file_stat['mtime'] > 2629740)){
						fclose($handle);

						add_action('shutdown', array(__CLASS__, 'download_maxmind_database'));

						if (false === ($handle = fopen(self::$maxmind_path, "rb"))){
							return apply_filters( 'slimstat_get_country', 'xx', $_ip_address );
						}
					}
				}

				$offset = 0;
				for ($depth = 31; $depth >= 0; --$depth) {
					if (fseek($handle, 6 * $offset, SEEK_SET) != 0){
						break;
					}
					$buf = fread($handle, 6);
					$x = array(0,0);
					for ($i = 0; $i < 2; ++$i) {
						for ($j = 0; $j < 3; ++$j) {
							$x[$i] += ord(substr($buf, 3 * $i + $j, 1)) << ($j * 8);
						}
					}

					if ( $float_ipnum & ( 1 << $depth ) ) {
						if ($x[1] >= 16776960 && !empty($country_codes[$x[1] - 16776960])) {
							$country_output = $country_codes[$x[1] - 16776960];
							break;
						}
						$offset = $x[1];
					} else {
						if ($x[0] >= 16776960 && !empty($country_codes[$x[0] - 16776960])) {
							$country_output = $country_codes[$x[0] - 16776960];
							break;
						}
						$offset = $x[0];
					}
				}
				fclose($handle);
			}
		}

		return apply_filters( 'slimstat_get_country', $country_output, $_ip_address );
	}
	// end get_country

	/**
	 * Decodes the permalink
	 */
	public static function get_request_uri(){
		if (isset($_SERVER['REQUEST_URI'])){
			return urldecode($_SERVER['REQUEST_URI']);
		}
		elseif (isset($_SERVER['SCRIPT_NAME'])){
			return isset($_SERVER['QUERY_STRING'])?$_SERVER['SCRIPT_NAME']."?".$_SERVER['QUERY_STRING']:$_SERVER['SCRIPT_NAME'];
		}
		else{
			return isset($_SERVER['QUERY_STRING'])?$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']:$_SERVER['PHP_SELF'];
		}
	}
	// end get_request_uri

	/**
	 * Stores the information (array) in the appropriate table and returns the corresponding ID
	 */
	public static function insert_row($_data = array(), $_table = ''){
		if ( empty( $_data ) || empty( $_table ) ) {
			return -1;
		}

		// Remove unwanted characters (SQL injections, anyone?)
		$data_keys = array();
		foreach (array_keys($_data) as $a_key){
			$data_keys[] = sanitize_key($a_key);
		}

		self::$wpdb->query(self::$wpdb->prepare("
			INSERT IGNORE INTO $_table (".implode(", ", $data_keys).') 
			VALUES ('.substr(str_repeat('%s,', count($_data)), 0, -1).")", $_data));

		return intval(self::$wpdb->insert_id);
	}
	// end insert_row

	/**
	 * Updates an existing row
	 */
	public static function update_row($_data = array(), $_table = ''){
		if (empty($_data) || empty($_table)){
			return -1;
		}

		// Move the ID at the end of the array
		$id = $_data['id'];
		unset($_data['id']);

		// Remove unwanted characters (SQL injections, anyone?)
		$data_keys = array();
		foreach (array_keys($_data) as $a_key){
			$data_keys[] = sanitize_key($a_key);
		}

		// Add the id at the end
		$_data['id'] = $id;

		self::$wpdb->query(self::$wpdb->prepare("
			UPDATE IGNORE $_table 
			SET ".implode(' = %s, ', $data_keys)." = %s
			WHERE id = %d", $_data));

		return 0;
	}

	/**
	 * Tries to find the user's REAL IP address
	 */
	protected static function _get_remote_ip(){
		$ip_array = array( '', '' );

		if ( !empty( $_SERVER[ 'REMOTE_ADDR' ] ) && filter_var( $_SERVER[ 'REMOTE_ADDR' ], FILTER_VALIDATE_IP ) !== false ) {
			$ip_array[ 0 ] = $_SERVER["REMOTE_ADDR"];
		}

		$originating_ip_headers = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_FORWARDED', 'HTTP_X_FORWARDED' );
		foreach ( $originating_ip_headers as $a_header ) {
			if ( !empty( $_SERVER[ $a_header ] ) ) {
				foreach ( explode( ',', $_SERVER[ $a_header ] ) as $a_ip ) {
					if ( filter_var( $a_ip, FILTER_VALIDATE_IP ) !== false && $a_ip != $ip_array[ 0 ] ) {
						$ip_array[ 1 ] = $a_ip;
						break;
					}
				}
			}
		}

		return $ip_array;
	}
	// end _get_remote_ip

	/**
	 * Extracts the accepted language from browser headers
	 */
	protected static function _get_language(){
		if(isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])){

			// Capture up to the first delimiter (, found in Safari)
			preg_match("/([^,;]*)/", $_SERVER["HTTP_ACCEPT_LANGUAGE"], $array_languages);

			// Fix some codes, the correct syntax is with minus (-) not underscore (_)
			return str_replace( "_", "-", strtolower( $array_languages[0] ) );
		}
		return 'xx';  // Indeterminable language
	}
	// end _get_language

	/**
	 * Sniffs out referrals from search engines and tries to determine the query string
	 */
	protected static function _get_search_terms( $_url = array() ) {
		if ( empty( $_url ) || !isset( $_url[ 'host' ] ) ) {
			return '';
		}

		// Engines with different character encodings should always be listed here, regardless of their query string format
		$query_formats = array(
			'baidu.com' => 'wd',
			'bing' => 'q',
			'dogpile.com' => 'q',
			'duckduckgo' => 'q',
			'eniro' => 'search_word',
			'exalead.com' => 'q',
			'excite' => 'q',
			'gigablast' => 'q',
			'google' => 'q',
			'hotbot' => 'q',
			'maktoob' => 'p',
			'mamma' => 'q',
			'naver' => 'query',
			'qwant' => 'q',
			'rambler' => 'query',
			'seznam' => 'oq',
			'soso.com' => 'query',
			'virgilio' => 'qs',
			'voila' => 'rdata',
			'yahoo' => 'p',
			'yam' => 'k',
			'yandex' => 'text',
			'yell' => 'keywords',
			'yippy' => 'query',
			'youdao' => 'q'
		);

		$charsets = array( 'baidu' => 'EUC-CN' );
		$regex_match = implode( '|', array_keys( $query_formats ) );
		$searchterms = '';

		if ( !empty( $_url[ 'query' ] ) ) {
			parse_str( $_url[ 'query' ], $query );
		}

		if ( !empty( $_url[ 'host' ] ) ) {
			preg_match( "/($regex_match)./i", $_url[ 'host' ], $matches );
		}

		if ( !empty( $matches[ 1 ] ) ) {
			// Let's remember that this is a search engine, regardless of the URL containing searchterms (thank you, NSA)
			$searchterms = '_';
			if ( !empty( $query[ $query_formats[ $matches[ 1 ] ] ] ) ) {
				$searchterms = str_replace( '\\', '', trim( urldecode( $query[ $query_formats[ $matches[ 1 ] ] ] ) ) );
				// Test for encodings different from UTF-8
				if ( function_exists( 'mb_check_encoding' ) && !mb_check_encoding( $query[ $query_formats[ $matches[ 1 ] ] ], 'UTF-8' ) && !empty( $charsets[ $matches[ 1 ] ] ) ) {
					$searchterms = mb_convert_encoding( urldecode( $query[ $query_formats[ $matches[ 1 ] ] ] ), 'UTF-8', $charsets[ $matches[ 1 ] ] );
				}
			}
		}
		else {
			// We weren't lucky, but there's still hope
			foreach( array( 'q','s','k','qt' ) as $a_format ) {
				if ( !empty( $query[ $a_format ] ) ) {
					$searchterms = str_replace( '\\', '', trim( urldecode( $query[ $a_format ] ) ) );
					break;
				}
			}
		}

		return $searchterms;
	}
	// end _get_search_terms

	/**
	 * Returns details about the resource being accessed
	 */
	protected static function _get_content_info(){
		$content_info = array( 'content_type' => 'unknown' );

		// Mark 404 pages
		if ( is_404() ) {
			$content_info[ 'content_type' ] = '404';
		}

		// Type
		else if ( is_single() ) {
			if ( ( $post_type = get_post_type() ) != 'post' ) {
				$post_type = 'cpt:' . $post_type;
			}

			$content_info[ 'content_type' ] = $post_type;
			$content_info_array = array();
			foreach ( get_object_taxonomies( $GLOBALS[ 'post' ] ) as $a_taxonomy ) {
				$terms = get_the_terms( $GLOBALS[ 'post' ]->ID, $a_taxonomy );
				if ( is_array( $terms ) ) {
					foreach ( $terms as $a_term ) {
						$content_info_array[] = $a_term->term_id;
					}
					$content_info[ 'category' ] = implode( ',', $content_info_array );
				}
			}
			$content_info[ 'content_id' ] = $GLOBALS[ 'post' ]->ID;
		}
		else if ( is_page() ) {
			$content_info[ 'content_type' ] = 'page';
			$content_info[ 'content_id' ] = $GLOBALS[ 'post' ]->ID;
		}
		elseif (is_attachment()){
			$content_info['content_type'] = 'attachment';
		}
		elseif (is_singular()){
			$content_info['content_type'] = 'singular';
		}
		elseif (is_post_type_archive()){
			$content_info['content_type'] = 'post_type_archive';
		}
		elseif (is_tag()){
			$content_info['content_type'] = 'tag';
			$list_tags = get_the_tags();
			if (is_array($list_tags)){
				$tag_info = array_pop($list_tags);
				if (!empty($tag_info)) $content_info['category'] = "$tag_info->term_id";
			}
		}
		elseif (is_tax()){
			$content_info['content_type'] = 'taxonomy';
		}
		elseif (is_category()){
			$content_info['content_type'] = 'category';
			$list_categories = get_the_category();
			if (is_array($list_categories)){
				$cat_info = array_pop($list_categories);
				if (!empty($cat_info)) $content_info['category'] = "$cat_info->term_id";
			}
		}
		else if (is_date()){
			$content_info['content_type']= 'date';
		}
		else if (is_author()){
			$content_info['content_type'] = 'author';
		}
		else if ( is_archive() ) {
			$content_info['content_type'] = 'archive';
		}
		else if ( is_search() ) {
			$content_info[ 'content_type' ] = 'search';
		}
		else if ( is_feed() ) {
			$content_info[ 'content_type' ] = 'feed';
		}
		else if ( is_home() || is_front_page() ) {
			$content_info['content_type'] = 'home';
		}
		else if ( !empty( $GLOBALS[ 'pagenow' ] ) && $GLOBALS[ 'pagenow' ] == 'wp-login.php' ) {
			$content_info[ 'content_type' ] = 'login';
		}
		else if ( !empty( $GLOBALS['pagenow'] ) && $GLOBALS['pagenow'] == 'wp-register.php' ) {
			$content_info[ 'content_type' ] = 'registration';
		}
		// WordPress sets is_admin() to true for all ajax requests ( front-end or admin-side )
		elseif ( is_admin() && ( !defined( 'DOING_AJAX' ) || !DOING_AJAX ) ) {
			$content_info[ 'content_type' ] = 'admin';
		}

		if (is_paged()){
			$content_info[ 'content_type' ] .= ',paged';
		}

		// Author
		if ( is_singular() ) {
			$author = get_the_author_meta( 'user_login', $GLOBALS[ 'post' ]->post_author );
			if ( !empty( $author ) ) {
				$content_info[ 'author' ] = $author;
			}
		}

		return $content_info;
	}
	// end _get_content_info

	/**
	 * Reads the cookie to get the visit_id and sets the variable accordingly
	 */
	protected static function _set_visit_id($_force_assign = false){
		$is_new_session = true;
		$identifier = 0;

		if ( isset( $_COOKIE[ 'slimstat_tracking_code' ] ) ) {
			// Make sure only authorized information is recorded
			$identifier = self::_separate_id_from_checksum( $_COOKIE[ 'slimstat_tracking_code' ] );
			if ( $identifier === false ) {
				return false;
			}

			$is_new_session = ( strpos( $identifier, 'id' ) !== false );
			$identifier = intval( $identifier );
		}

		// User doesn't have an active session
		if ( $is_new_session && ( $_force_assign || self::$settings[ 'javascript_mode' ] == 'yes' ) ) {
			if ( empty( self::$settings[ 'session_duration' ] ) ) self::$settings[ 'session_duration' ] = 1800;

			self::$stat[ 'visit_id' ] = get_option( 'slimstat_visit_id', -1 );
			if ( self::$stat[ 'visit_id' ] == -1 ) {
				self::$stat[ 'visit_id' ] = intval( self::$wpdb->get_var( "SELECT MAX( visit_id ) FROM {$GLOBALS[ 'wpdb' ]->prefix}slim_stats" ) );
			}
			self::$stat[ 'visit_id' ]++;
			update_option( 'slimstat_visit_id', self::$stat[ 'visit_id' ] );

			$is_set_cookie = apply_filters( 'slimstat_set_visit_cookie', true );
			if ( $is_set_cookie ) {
				@setcookie(
					'slimstat_tracking_code',
					self::_get_id_with_checksum( self::$stat[ 'visit_id' ] ),
					time() + self::$settings[ 'session_duration' ],
					COOKIEPATH
				);
			}

		}
		elseif ( $identifier > 0 ){
			self::$stat[ 'visit_id' ] = $identifier;
		}

		if ( $is_new_session && $identifier > 0 ) {
			self::$wpdb->query( self::$wpdb->prepare( "
				UPDATE {$GLOBALS['wpdb']->prefix}slim_stats
				SET visit_id = %d
				WHERE id = %d AND visit_id = 0", self::$stat[ 'visit_id' ], $identifier
			) );
		}
		return ( $is_new_session && ( $_force_assign || self::$settings[ 'javascript_mode' ] == 'yes' ) );
	}
	// end _set_visit_id

	/**
	 * Makes sure that the data received from the client is well-formed (and that nobody is trying to do bad stuff)
	 */
	protected static function _check_data_integrity( $_data = '' ) {
		// Parse the information we received
		self::$data_js = apply_filters( 'slimstat_filter_pageview_data_js', $_data );

		// Do we have an id for this request?
		if ( empty( self::$data_js[ 'id' ] ) || empty( self::$data_js[ 'op' ] ) ) {
			do_action( 'slimstat_track_exit_102' );
			self::$stat[ 'id' ] = -100;
			self::_set_error_array( __( 'Invalid payload string. Try clearing your WordPress cache.', 'wp-slimstat' ), false );
			self::slimstat_save_options();
			exit( self::_get_id_with_checksum( self::$stat[ 'id' ] ) );
		}

		// Make sure that the control code is valid
		self::$data_js[ 'id' ] = self::_separate_id_from_checksum( self::$data_js[ 'id' ] );
		if ( self::$data_js['id'] === false ) {
			do_action( 'slimstat_track_exit_103' );
			self::$stat[ 'id' ] = -101;
			self::_set_error_array( __( 'Invalid data signature. Try clearing your WordPress cache.', 'wp-slimstat' ), false );
			self::slimstat_save_options();
			exit( self::_get_id_with_checksum( self::$stat[ 'id' ] ) );
		}

		$intval_id = intval( self::$data_js[ 'id' ] );
		if ( $intval_id < 0 ) {
			do_action( 'slimstat_track_exit_' . abs( $intval_id ) );
			self::$stat[ 'id' ] = $intval_id;
			self::slimstat_save_options();
			exit( self::_get_id_with_checksum( self::$stat[ 'id' ] ) );
		}
	}
	// end _check_data_integrity

	protected static function _set_error_array( $_error_message = '', $_is_notice = false ) {
		$error_code = abs( self::$stat[ 'id' ] );
		self::toggle_date_i18n_filters( false );
		if ( $_is_notice ) {
			self::$settings[ 'last_tracker_notice' ] = array( $error_code, $_error_message, date_i18n( 'U' ) );
		}
		else {
			self::$settings[ 'last_tracker_error' ] = array( $error_code, $_error_message, date_i18n( 'U' ) );
		}
		self::toggle_date_i18n_filters( true );
	}

	protected static function _get_id_with_checksum( $_id = 0 ) {
		return $_id . '.' . md5( $_id . self::$settings[ 'secret' ] );
	}

	protected static function _separate_id_from_checksum( $_id_with_checksum = '' ) {
		list( $id, $checksum ) = explode( '.', $_id_with_checksum );

		if ( $checksum === md5( $id . self::$settings[ 'secret' ] ) ) {
			return $id;
		}

		return false;
	}

	public static function dtr_pton( $ip ){
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$unpacked = unpack( 'A4', inet_pton( $ip ) );
		}
		else if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$unpacked = unpack( 'A16', inet_pton( $ip ) );
		}

		$binary_ip = '';
		if ( !empty( $unpacked ) ) {
			$unpacked = str_split( $unpacked[ 1 ] );
			foreach ( $unpacked as $char ) {
				$binary_ip .= str_pad( decbin( ord( $char ) ), 8, '0', STR_PAD_LEFT );
			}
		}
		return $binary_ip;
	}
	
	public static function get_mask_length( $ip ){
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			return 32;
		}
		else if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			return 128;
		}

		return false;
	}

	/**
	 * Opens given domains during CORS requests to admin-ajax.php
	 */
	public static function open_cors_admin_ajax( $_allowed_origins ){
		$exploded_domains = self::string_to_array( self::$settings[ 'external_domains' ] );

		if ( !empty( $exploded_domains ) && !empty( $exploded_domains[ 0 ] ) ) {
			$_allowed_origins = array_merge( $_allowed_origins, $exploded_domains );
		}

		return $_allowed_origins;
	}

	/**
	 * Downloads the MaxMind geolocation database from their repository
	 */
	public static function download_maxmind_database(){
		// Create the folder, if it doesn't exist
		if ( !file_exists( dirname( self::$maxmind_path ) ) ) {
			mkdir( dirname( self::$maxmind_path ) );
		}

		// Download the most recent database directly from MaxMind's repository
		if ( !function_exists( 'download_url' ) ) {
			require_once(ABSPATH . 'wp-admin/includes/file.php');
		}
		$maxmind_tmp = download_url( 'http://geolite.maxmind.com/download/geoip/database/GeoLiteCountry/GeoIP.dat.gz', 5 );

		if (is_wp_error($maxmind_tmp)){
			return __('There was an error downloading the MaxMind Geolite DB:', 'wp-slimstat').' '.$maxmind_tmp->get_error_message();
		}

		$zh = false;

		if ( !function_exists( 'gzopen' ) ) {
			if ( function_exists( 'gzopen64' ) ) {
				if ( false === ( $zh = gzopen64( $maxmind_tmp, 'rb' ) ) ) {
					return __( 'There was an error opening the zipped MaxMind Geolite DB.', 'wp-slimstat' );
				}
			}
			else {
				return __( 'Function gzopen not defined. Aborting.', 'wp-slimstat' );
			}
		}
		else{
			if ( false === ( $zh = gzopen( $maxmind_tmp, 'rb' ) ) ) {
				return __( 'There was an error opening the zipped MaxMind Geolite DB.', 'wp-slimstat' );
			}
		}

		if ( false === ( $fh = fopen( self::$maxmind_path, 'wb' ) ) ) {
			return __('There was an error opening the unzipped MaxMind Geolite DB.','wp-slimstat');
		}

		while(($data = gzread($zh, 4096)) != false){
			fwrite($fh, $data);
		}

		@gzclose($zh);
		@fclose($fh);

		@unlink($maxmind_tmp);

		return '';
	}

	/**
	 * Downloads the Browscap User Agent database from our repository
	 */
	public static function update_browscap_database( $_force_download = false ) {
		// Create the folder, if it doesn't exist
		if ( !file_exists( dirname( self::$browscap_path ) ) ) {
			mkdir( dirname( self::$browscap_path ) );
		}

		$download_flag = $_force_download;

		// Check for updates once a week ( 604800 seconds )
		if ( false !== ( $file_stat = @stat( self::$browscap_path ) ) && ( date( 'U' ) - $file_stat[ 'mtime' ] > 604800 ) && false !== ( $handle = @fopen( self::$browscap_path, "rb" ) ) ) {
			
			// Find the version of the local data file
			while ( ( $buffer = fgets( $handle, 4096 ) ) !== false ) {
				if ( strpos( $buffer, 'source_version' ) !== false ) {
					$local_version = filter_var( $buffer, FILTER_SANITIZE_NUMBER_INT );
					break;
				}
			}

			// Now check the version number on the server
			$remote_version = file_get_contents( 'http://s3.amazonaws.com/browscap/version.txt' );
			if ( intval( $local_version ) != intval( $remote_version ) ) {
				$download_flag = true;
			}
			else {
				touch( self::$browscap_path );
			}

			fclose( $handle );
		}

		// Download the most recent version of our pre-processed Browscap database
		if ( $download_flag ) {
			$response = wp_safe_remote_get( 'http://s3.amazonaws.com/browscap/browscap-db.php', array( 'timeout' => 300, 'stream' => true, 'filename' => self::$browscap_path ) );
			if ( is_wp_error( $response ) || 200 != wp_remote_retrieve_response_code( $response ) ) {
				@unlink( self::$browscap_path );
				return array( 1, __( 'There was an error downloading the Browscap data file from our server. Please try again later.', 'wp-slimstat' ) );
			}

			if ( !file_exists( self::$browscap_path ) ) {
				return array( 2, __( 'There was an error saving the Browscap data file on your server. Please check your server permissions.', 'wp-slimstat' ) );
			}
		}

		return array( 0, __( 'The Browscap data file has been installed on your server.', 'wp-slimstat' ) );
	}

	public static function slimstat_shortcode( $_attributes = '', $_content = '' ){
		extract( shortcode_atts( array(
			'f' => '',		// recent, popular, count
			'w' => '',		// column to use
			's' => ' ',		// separator
			'o' => 0		// offset for counters
		), $_attributes));

		$output = $where = '';
		$s = "<span class='slimstat-item-separator'>$s</span>";

		// Load the database library
		include_once( dirname(__FILE__) . '/admin/view/wp-slimstat-db.php' );

		// Load the localization files (for languages, operating systems, etc)
		load_plugin_textdomain( 'wp-slimstat', WP_PLUGIN_DIR .'/wp-slimstat/languages', '/wp-slimstat/languages' );

		// Look for required fields
		if ( empty( $f ) || empty( $w ) ) {
			return '<!-- Slimstat Shortcode Error: missing parameter -->';
		}

		if ( strpos ( $_content, 'WHERE:' ) !== false ) {
			$where = html_entity_decode( str_replace( 'WHERE:', '', $_content ), ENT_QUOTES, 'UTF-8' );
			wp_slimstat_db::init();
		}
		else{
			wp_slimstat_db::init( html_entity_decode( $_content, ENT_QUOTES, 'UTF-8' ) );
		}

		switch( $f ) {
			case 'count':
			case 'count-all':
				$output = wp_slimstat_db::count_records( $w, $where, strpos( $f, 'all') === false ) + $o;
				break;

			case 'recent':
			case 'recent-all':
			case 'top':
			case 'top-all':
				$function = 'get_' . str_replace( '-all', '', $f );

				if ( $w == '*' ) {
					$w = 'id';
				}

				$w = self::string_to_array( $w );

				// Some columns are 'special' and need be removed from the list
				$w_clean = array_diff( $w, array( 'count', 'hostname', 'post_link', 'dt' ) );

				// The special value 'post_list' requires the permalink to be generated
				if ( in_array( 'post_link', $w ) ) {
					$w_clean[] = 'resource';
				}

				// Retrieve the data
				$results = wp_slimstat_db::$function( implode( ', ', $w_clean ), $where, '', strpos( $f, 'all') === false );

				// No data? No problem!
				if ( empty( $results ) ) {
					return '<!--  Slimstat Shortcode: No Data -->';
				}

				// Are nice permalinks enabled?
				$permalinks_enabled = get_option('permalink_structure');

				// Format results
				$output = array();
				foreach( $results as $result_idx => $a_result ) {
					foreach( $w as $a_column ) {
						$output[ $result_idx ][ $a_column ] = "<span class='col-$a_column'>";

						if ( $permalinks_enabled && !empty( $a_result[ 'resource' ] ) ) {
							$a_result[ 'resource' ] = strtok( $a_result[ 'resource' ], '?' );
						}

						switch( $a_column ) {
							case 'count':
								$output[ $result_idx ][ $a_column ] .= $a_result[ 'counthits' ];
								break;

							case 'country':
								$output[ $result_idx ][ $a_column ] .= __( 'c-' . $a_result[ $a_column ], 'wp-slimstat' );
								break;

							case 'dt':
								$output[ $result_idx ][ $a_column ] .= date_i18n( self::$settings[ 'date_format' ] . ' ' . self::$settings[ 'time_format' ], $a_result[ 'dt' ] );
								break;

							case 'hostname':
								$output[ $result_idx ][ $a_column ] .= gethostbyaddr( $a_result[ 'ip' ] );
								break;

							case 'language':
								$output[ $result_idx ][ $a_column ] .= __( 'l-' . $a_result[ $a_column ], 'wp-slimstat' );
								break;

							case 'platform':
								$output[ $result_idx ][ $a_column ] .= __( $a_result[ $a_column ], 'wp-slimstat' );

							case 'post_link':
								$post_id = url_to_postid( $a_result[ 'resource' ] );
								if ($post_id > 0) {
									$output[ $result_idx ][ $a_column ] .= "<a href='{$a_result[ 'resource' ]}'>" . get_the_title( $post_id ) . '</a>'; 
								}
								else {
									$output[ $result_idx ][ $a_column ] .= $a_result[ 'resource' ];
								}
								break;

							default:
								$output[ $result_idx ][ $a_column ] .= $a_result[ $a_column ];
								break;
						}
						$output[ $result_idx ][ $a_column ] .= '</span>';
					}
					$output[ $result_idx ] = '<li>' . implode( $s, $output[ $result_idx ] ). '</li>';
				}

				$output = '<ul class="slimstat-shortcode ' . $f . implode( '-', $w ). '">' . implode( '', $output ) . '</ul>';
				break;

			default:
				break;
		}

		return $output;
	}

	/**
	 * Converts a series of comma separated values into an array
	 */
	public static function string_to_array( $_option = '' ) {
		if ( empty( $_option ) || !is_string( $_option ) ) {
			return array();
		}
		else {
			return array_filter( array_map( 'trim', explode( ',', $_option ) ) );
		}
	}

	/**
	 * Toggles WordPress filters on date_i18n function
	 */
	public static function toggle_date_i18n_filters( $_turn_on = true ) {
		if ( $_turn_on && !empty( self::$date_i18n_filters ) && is_array( self::$date_i18n_filters ) ) {
			foreach ( self::$date_i18n_filters as $i18n_priority => $i18n_func_list ) {
				foreach ( $i18n_func_list as $func_name => $func_args ) {
					add_filter( 'date_i8n', $func_args[ 'function' ], $i18n_priority, $func_args[ 'accepted_args' ] );
				}
			}
		}
		else if ( !empty( $GLOBALS[ 'wp_filter' ][ 'date_i18n' ] ) ) {
			self::$date_i18n_filters = $GLOBALS[ 'wp_filter' ][ 'date_i18n' ];
			remove_all_filters( 'date_i18n' );
		}
	}

	/**
	 * Imports all the 'old' options into the new array, and saves them
	 */
	public static function init_options(){
		return array(
			'version' => self::$version,
			'secret' => wp_hash( uniqid( time(), true ) ),
			'show_admin_notice' => 0,

			// General
			'is_tracking' => 'yes',
			'javascript_mode' => 'yes',
			'enable_javascript' => 'yes',
			'track_admin_pages' => 'no',
			'use_separate_menu' => 'yes',
			'add_posts_column' => 'no',
			'posts_column_day_interval' => 30,
			'posts_column_pageviews' => 'yes',
			'add_dashboard_widgets' => 'yes',
			'hide_addons' => 'no',
			'auto_purge' => 0,
			'auto_purge_delete' => 'yes',

			// Tracker
			'enable_outbound_tracking' => 'yes',
			'track_internal_links' => 'no',
			'ignore_outbound_classes_rel_href' => '',
			'do_not_track_outbound_classes_rel_href' => 'noslimstat,ab-item',
			'session_duration' => 1800,
			'extend_session' => 'no',
			'enable_cdn' => 'yes',
			'extensions_to_track' => 'pdf,doc,xls,zip',
			'external_domains' => '',

			// Filters
			'track_users' => 'yes',
			'ignore_users' => '',
			'ignore_ip' => '',
			'ignore_capabilities' => '',
			'ignore_spammers' => 'yes',
			'ignore_bots' => 'no',
			'ignore_resources' => '',
			'ignore_countries' => '',
			'ignore_browsers' => '',
			'ignore_referers' => '',
			'anonymize_ip' => 'no',
			'ignore_prefetch' => 'yes',

			// Reports
			'use_european_separators' => 'yes',
			'date_format' => 'm-d-y',
			'time_format' => 'h:i a',
			'show_display_name' => 'no',
			'convert_resource_urls_to_titles' => 'yes',
			'convert_ip_addresses' => 'no',
			'async_load' => 'no',
			'use_slimscroll' => 'yes',
			'expand_details' => 'no',
			'rows_to_show' => '20',
			'limit_results' => '1000',
			'ip_lookup_service' => 'http://www.infosniper.net/?ip_address=',
			'mozcom_access_id' => '',
			'mozcom_secret_key' => '',
			'refresh_interval' => '60',
			'number_results_raw_data' => '50',
			'custom_css' => '',
			'chart_colors' => '',
			'show_complete_user_agent_tooltip' => 'no',
			'enable_sov' => 'no',

			// Access Control
			'restrict_authors_view' => 'yes',
			'capability_can_view' => 'activate_plugins',
			'can_view' => '',
			'capability_can_admin' => 'activate_plugins',
			'can_admin' => '',

			// Maintenance
			'last_tracker_error' => array( 0, '', 0 ),
			'show_sql_debug' => 'no',
			'no_maxmind_warning' => 'no',
			'no_browscap_warning' => 'no',

			// Network-wide Settings
			'locked_options' => ''
		);
	}
	// end init_options

	/**
	 * Saves the options in the database, if necessary
	 */
	public static function slimstat_save_options() {
		// Allow third-party functions to manipulate the options right before they are saved
		self::$settings = apply_filters( 'slimstat_save_options', self::$settings );

		if ( self::$settings_signature === md5( serialize( self::$settings ) ) ) {
			return true;
		}

		if ( !is_network_admin() ) {
			update_option( 'slimstat_options', self::$settings );
		}
		else {
			update_site_option( 'slimstat_options', self::$settings );
		}

		return true;
	}

	/**
	 * Enqueue a javascript to track users' screen resolution and other browser-based information
	 */
	public static function wp_slimstat_enqueue_tracking_script() {
		if ( self::$settings[ 'enable_cdn' ] == 'yes' ) {
			$schema = is_ssl() ? 'https' : 'http';
			wp_register_script( 'wp_slimstat', $schema . '://cdn.jsdelivr.net/wp/wp-slimstat/trunk/wp-slimstat.min.js', array(), null, true );
		}
		else{
			wp_register_script('wp_slimstat', plugins_url('/wp-slimstat.min.js', __FILE__), array(), null, true);
		}

		// Pass some information to Javascript
		$params = array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
		);

		if ( self::$settings[ 'enable_outbound_tracking' ] == 'no' ) {
			$params[ 'disable_outbound_tracking' ] = 'true';
		}
		if ( self::$settings[ 'track_internal_links' ] == 'yes' ) {
			$params[ 'track_internal_links' ] = 'true';
		}
		if ( !empty( self::$settings[ 'extensions_to_track' ] ) ) {
			$params[ 'extensions_to_track' ] = str_replace( ' ', '', self::$settings[ 'extensions_to_track' ] );
		}
		if ( !empty( self::$settings[ 'ignore_outbound_classes_rel_href' ] ) ) {
			$params[ 'outbound_classes_rel_href_to_ignore' ] = str_replace( ' ', '', self::$settings[ 'ignore_outbound_classes_rel_href' ] );
		}
		if ( !empty( self::$settings[ 'do_not_track_outbound_classes_rel_href' ] ) ) {
			$params[ 'outbound_classes_rel_href_to_not_track' ] = str_replace( ' ', '', self::$settings[ 'do_not_track_outbound_classes_rel_href' ] );
		}

		if ( self::$settings[ 'javascript_mode' ] != 'yes' ) {
			if ( !empty( self::$stat[ 'id' ] ) ) {
				$params[ 'id' ] = self::_get_id_with_checksum( self::$stat[ 'id' ] );
			}
			else {
				$params[ 'id' ] = self::_get_id_with_checksum( '-300' );
			}
		}
		else {
			$encoded_ci = base64_encode( serialize( self::_get_content_info() ) );
			$params[ 'ci' ] = self::_get_id_with_checksum( $encoded_ci );
		}
		
		$params = apply_filters( 'slimstat_js_params', $params );

		wp_enqueue_script( 'wp_slimstat' );
		wp_localize_script( 'wp_slimstat', 'SlimStatParams', $params );
	}

	/**
	 * Removes old entries from the main table and performs other daily tasks
	 */
	public static function wp_slimstat_purge() {
		$autopurge_interval = intval( self::$settings[ 'auto_purge' ] );
		if ( $autopurge_interval <= 0 ) {
			return;
		}

		self::toggle_date_i18n_filters( false );
		$days_ago = strtotime( date_i18n( 'Y-m-d H:i:s' ) . " -$autopurge_interval days" );
		self::toggle_date_i18n_filters( true );

		// Copy entries to the archive table, if needed
		if ( self::$settings[ 'auto_purge_delete' ] != 'no' ) {
			$is_copy_done = self::$wpdb->query("
				INSERT INTO {$GLOBALS['wpdb']->prefix}slim_stats_archive (id, ip, other_ip, username, country, referer, resource, searchterms, plugins, notes, visit_id, server_latency, page_performance, browser, browser_version, browser_type, platform, language, user_agent, resolution, screen_width, screen_height, content_type, category, author, content_id, outbound_resource, dt_out, dt)
				SELECT id, ip, other_ip, username, country, referer, resource, searchterms, plugins, notes, visit_id, server_latency, page_performance, browser, browser_version, browser_type, platform, language, user_agent, resolution, screen_width, screen_height, content_type, category, author, content_id, outbound_resource, dt_out, dt
				FROM {$GLOBALS[ 'wpdb' ]->prefix}slim_stats
				WHERE dt < $days_ago");
				
			if ( $is_copy_done !== false ) {
				self::$wpdb->query("DELETE ts FROM {$GLOBALS[ 'wpdb' ]->prefix}slim_stats ts WHERE ts.dt < $days_ago");
			}

			$is_copy_done = self::$wpdb->query("
				INSERT INTO {$GLOBALS['wpdb']->prefix}slim_events_archive (type, event_description, notes, position, id, dt)
				SELECT type, event_description, notes, position, id, dt
				FROM {$GLOBALS[ 'wpdb' ]->prefix}slim_events
				WHERE dt < $days_ago");
	
			if ( $is_copy_done !== false ) {
				self::$wpdb->query("DELETE te FROM {$GLOBALS[ 'wpdb' ]->prefix}slim_events te WHERE te.dt < $days_ago");
			}
		}
		else {
			// Delete old entries
			self::$wpdb->query("DELETE ts FROM {$GLOBALS[ 'wpdb' ]->prefix}slim_stats ts WHERE ts.dt < $days_ago");
			self::$wpdb->query("DELETE te FROM {$GLOBALS[ 'wpdb' ]->prefix}slim_events te WHERE te.dt < $days_ago");
		}

		// Optimize tables
		self::$wpdb->query( "OPTIMIZE TABLE {$GLOBALS[ 'wpdb' ]->prefix}slim_stats" );
		self::$wpdb->query( "OPTIMIZE TABLE {$GLOBALS[ 'wpdb' ]->prefix}slim_stats_archive" );
		self::$wpdb->query( "OPTIMIZE TABLE {$GLOBALS[ 'wpdb' ]->prefix}slim_events" );
		self::$wpdb->query( "OPTIMIZE TABLE {$GLOBALS[ 'wpdb' ]->prefix}slim_events_archive" );
	}

	/**
	 * Checks for add-on updates
	 */
	public static function update_checker() {
		if ( empty( self::$update_checker ) || !is_admin() ) {
			return true;
		}

		$update_checker_objects = array();
		foreach ( self::$update_checker as $a_slug ) {
			$a_clean_slug = str_replace( array( 'wp_slimstat_', '_' ), array( '', '-' ), $a_slug );
			if ( !empty( self::$settings[ 'addon_licenses' ][ 'wp-slimstat-' . $a_clean_slug ] ) ) {
				include_once( plugin_dir_path( __FILE__ ) . 'admin/update-checker/plugin-update-checker.php' );
				$update_checker_objects[] = PucFactory::buildUpdateChecker( 'http://www.wp-slimstat.com/update-checker/?slug=' . $a_clean_slug . '&key=' . urlencode( wp_slimstat::$settings[ 'addon_licenses' ][ 'wp-slimstat-' . $a_clean_slug ] ), dirname( dirname( __FILE__ ) ) . '/wp-slimstat-' . $a_clean_slug . '/index.php', 'wp-slimstat-' . $a_clean_slug );
			}
		}
	}
}
// end of class declaration

// Ok, let's go, Sparky!
if ( function_exists( 'add_action' ) ) {
	// Since we use sendBeacon, this function sends raw POST data, which does not populate the $_POST variable automatically
	if ( ( !empty( $_SERVER[ 'HTTP_CONTENT_TYPE' ] ) || !empty( $_SERVER[ 'CONTENT_TYPE' ] ) ) && empty( $_POST ) ) {
		$raw_post_string = file_get_contents( 'php://input' );
		parse_str( $raw_post_string, wp_slimstat::$raw_post_array );
	}
	else if ( !empty( $_POST ) ) {
		wp_slimstat::$raw_post_array = $_POST;
	}

	// Init the Ajax listener
	if ( !empty( wp_slimstat::$raw_post_array[ 'action' ] ) && wp_slimstat::$raw_post_array[ 'action' ] == 'slimtrack' ) {

		// This is needed because admin-ajax.php is reading $_REQUEST to fire the corresponding action
		if ( empty( $_POST[ 'action' ] ) ) {
			$_POST[ 'action' ] = wp_slimstat::$raw_post_array[ 'action' ];
		}

		add_action( 'wp_ajax_nopriv_slimtrack', array( 'wp_slimstat', 'slimtrack_ajax' ) );
		add_action( 'wp_ajax_slimtrack', array( 'wp_slimstat', 'slimtrack_ajax' ) ); 
	}

	// From the codex: You can't call register_activation_hook() inside a function hooked to the 'plugins_loaded' or 'init' hooks (or any other hook). These hooks are called before the plugin is loaded or activated.
	if ( is_admin() ) {
		include_once( WP_PLUGIN_DIR . '/wp-slimstat/admin/wp-slimstat-admin.php' );
		register_activation_hook( __FILE__, array( 'wp_slimstat_admin', 'init_environment' ) );
		register_deactivation_hook( __FILE__, array( 'wp_slimstat_admin', 'deactivate' ) );
	}

	// Add the appropriate actions
	add_action( 'plugins_loaded', array( 'wp_slimstat', 'init' ), 20 );
}