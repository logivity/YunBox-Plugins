<?php

namespace Includes\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class QuoteupDisplayEnquiryCartBubble {

	/**
	 * @var Singleton The reference to *Singleton* instance of this class
	 */
	private static $instance;

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return Singleton The *Singleton* instance.
	 */
	public static function getInstance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	protected function __construct() {
		add_action( 'wp_head', array( $this, 'displayBubble' ), 11 );
	}

	/**
	 * This function is used to get and return cart url
	 * @param  [array] $form_data [settings stored in database]
	 * @return [type]            [description]
	 */
	public function getCartUrl($form_data)
	{
		if ( isset( $form_data[ 'mpe_cart_page' ] ) ) {
			$url = get_permalink( $form_data[ 'mpe_cart_page' ] );
		} else {
			$url = '';
		}
		return $url;
	}

	public function getClass($form_data)
	{
		$class		 = '';
		if ( ! isset( $_SESSION[ 'wdm_product_count' ] ) || $_SESSION[ 'wdm_product_count' ] <= 0 || $form_data[ 'enable_disable_mpe' ] != 1 ) {
			$class = 'style="display:none"';
		}
		return $class;
	}

	public function displayBubble() {
		@session_start();
		$displayBubble	 = true;
		$displayBubble	 = apply_filters( 'quoteup_display_bubble', $displayBubble );

		if ( $displayBubble != true ) {
			return;
		}
		$form_data = get_option( 'wdm_form_data' );
		if ( isset( $form_data[ 'enable_disable_mpe' ] ) && $form_data[ 'enable_disable_mpe' ] != 1 ) {
			return;
		}

		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_style( 'wdm-quoteup-icon', QUOTEUP_PLUGIN_URL . '/css/public/wdm-quoteup-icon.css' );
		wp_enqueue_style( 'wdm-mini-cart-css', QUOTEUP_PLUGIN_URL . '/css/common.css' );
		$url         = $this->getCartUrl($form_data);
		$class 		 = $this->getClass($form_data);
		if ( isset( $form_data[ 'enable_disable_mpe' ] ) && $form_data[ 'enable_disable_mpe' ] == 1 ) {

			wp_enqueue_script( 'wdm-draggable', QUOTEUP_PLUGIN_URL . '/js/public/enquiry-cart-bubble.js', array( 'jquery-ui-draggable' ) );
		}
		?>

		<div id="wdm-cart-count" <?php echo $class; ?>>
			<a href='<?php echo $url ?>' class='wdm-cart-count-link' title="<?php
			$count_val = (int) $_SESSION[ 'wdm_product_count' ];
			echo $count_val . ' product';
			if ( $count_val > 1 ) {
				echo '\'s';
			}
			echo ' added for enquiry';
			?>"><span class='wdm-quoteupicon wdm-quoteupicon-list'></span><span class='wdm-quoteupicon-count'><?php echo $count_val; ?></span></a></div>

		<?php

	}

}
QuoteupDisplayEnquiryCartBubble::getInstance();
	