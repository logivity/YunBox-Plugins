<?php

namespace Templates\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class QuoteupSingleProductModal {

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
		
	}

	/**
	 * @function  to_display_modal
	 * @Description To Display modal
	 *
	 * @param int    $prod_id    Product id
	 * @param int    $manual_css Product id
	 * @param string $color
	 * @param int    $price      price of product
	 */
	public function displayModal( $prod_id, $price, $btn_class, $QuoteUpDisplayQuoteButtonObj ) {
		// if (! isSimpleProduct($prod_id)) {
		//     return;
		// }
		$url		 = get_permalink( $prod_id );
		$img_url	 = wp_get_attachment_url( get_post_thumbnail_id( $prod_id ) );
		$form_data	 = get_option( 'wdm_form_data' );
		$color		 = $QuoteUpDisplayQuoteButtonObj->getDialogColor( $form_data );
		$pcolor		 = $QuoteUpDisplayQuoteButtonObj->getDialogTitleColor( $form_data );

		$email	 = $QuoteUpDisplayQuoteButtonObj->getUserEmail();
		$name	 = $QuoteUpDisplayQuoteButtonObj->getUserName();

		$manual_css = 0;
		if ( isset( $form_data[ 'button_CSS' ] ) && $form_data[ 'button_CSS' ] == 'manual_css' ) {
			$manual_css = 1;
		}
		$title		 = get_the_title( $prod_id );
		$product_id	 = $prod_id;

		//Append CSS added in the settings page
		if ( isset( $form_data[ 'user_custom_css' ] ) ) {
			wp_add_inline_style( 'modal_css1', $form_data[ 'user_custom_css' ] );
		}

		global $wpdb;
		$query	 = "select user_email from {$wpdb->posts} as p join {$wpdb->users} as u on p.post_author=u.ID where p.ID=%d";
		$uemail	 = $wpdb->get_var( $wpdb->prepare( $query, $product_id ) );
		$this->cssHTML( $manual_css, $prod_id, $color, $title, $uemail, $img_url, $price, $url, $product_id, $form_data, $QuoteUpDisplayQuoteButtonObj );
		?>


		<?php
		if ( isset( $form_data[ 'show_powered_by_link' ] ) ) {
			$enable_opt = $form_data[ 'show_powered_by_link' ];
		} else {
			$enable_opt = 0;
		}
		if ( $enable_opt == 1 ) {
			?>
			<div class="wdm-modal-footer">
				<a href='https://wisdmlabs.com/' class="wdm-poweredby">Powered by &copy; WisdmLabs</a>
			</div><!--/modal footer-->
			<?php
		}
		?>
		</div><!--/modal-content-->
		</div><!--/modal-dialog-->
		</div><!--/modal-->
		<!--/New modal-->

		<!--contact form or btn-->
		<div class="quote-form">

			<!-- Button trigger modal -->
			<?php
			if ( isset( $form_data[ 'show_button_as_link' ] ) && $form_data[ 'show_button_as_link' ] == 1 ) {
				?>
				<a id="wdm-quoteup-trigger-<?php echo $prod_id ?>" data-toggle="wdm-quoteup-modal" data-target="#wdm-quoteup-modal" href='#' style='font-weight: bold;
				<?php
				if ( $form_data[ 'button_text_color' ] ) {
					echo 'color: ' . $form_data[ 'button_text_color' ] . ';';
				}
				?>'>
					   <?php echo $QuoteUpDisplayQuoteButtonObj->returnButtonText( $form_data ); ?>
				</a>
				<?php
			} else {
				?>

				<button class="<?php echo $btn_class ?>" id="wdm-quoteup-trigger-<?php echo $prod_id ?>"  data-toggle="wdm-quoteup-modal" data-target="#wdm-quoteup-modal" <?php echo ($manual_css == 1) ? getManualCSS( $form_data ) : ''; ?>>
					<?php echo $QuoteUpDisplayQuoteButtonObj->returnButtonText( $form_data );
					?>
				</button>
				<?php
			}
			?>
		</div><!--/contact form or btn-->
		<?php
		unset( $pcolor );
		unset( $email );
		unset( $name );
	}

	private function cssHTML( $manual_css, $prod_id, $color, $title, $uemail, $img_url, $price, $url, $product_id, $form_data, $QuoteUpDisplayQuoteButtonObj ) {
		if ( isset( $form_data[ 'button_CSS' ] ) ) {
			if ( $form_data[ 'button_CSS' ] == 'manual_css' ) {
				$manual_css				 = 1;
				$dialogue_product_color	 = $form_data[ 'dialog_product_color' ];
				$dialogue_text_color	 = $form_data[ 'dialog_text_color' ];
				$color					 = $form_data[ 'dialog_color' ];
			} else {
				$color					 = '#FFFFFF';
				$dialogue_product_color	 = '#999';
				$dialogue_text_color	 = '#000000';
			}
		} else {
			$color					 = '#FFFFFF';
			$dialogue_product_color	 = '#999';
			$dialogue_text_color	 = '#000000';
		}
		?>
		<!--New modal-->
		<div class="wdm-modal wdm-fade" id="wdm-quoteup-modal-<?php echo $prod_id ?>" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display:none">
			<div class="wdm-modal-dialog">
				<div class="wdm-modal-content"
				<?php
				if ( ! empty( $color ) ) {
					echo ' style= "background-color:' . $color . '";';
				}
				?>>
					<div class="wdm-modal-header">
						<button type="button" class="close" data-dismiss="wdm-modal" aria-hidden="true">&times;</button>
						<h4 class="wdm-modal-title" id="myModalLabel"
						<?php
						if ( isset( $dialogue_text_color ) ) {
							echo " style='color: " . $dialogue_text_color . ";'";
						}
						?>>
							<span><?php echo _e( 'Send Enquiry for', 'quoteup' ); ?></span>
							<span class='pr_name'><?php echo $title ?></span>
						</h4>
					</div>
					<div class="wdm-modal-body">
						<form method='post' id='frm_enquiry' name='frm_enquiry' class="wdm-quoteup-form" >
							<?php $ajax_nonce	 = wp_create_nonce( 'nonce_for_enquiry' );
							?>
							<input type='hidden' name='ajax_nonce' id='ajax_nonce' value='<?php echo $ajax_nonce ?>'>
							<input type='hidden' name='submit_value' id='submit_value'>

							<input type='hidden' name="product_name_<?php echo $prod_id ?>"
								   id="product_name_<?php echo $prod_id ?>"
								   value='<?php echo get_the_title() ?>'>
							<input type='hidden' name="product_type_<?php echo $prod_id;
							?>"
								   id="product_type_<?php echo $prod_id;
							?>">
							<input type='hidden' name="variation_<?php echo $prod_id;
							?>"
								   id="variation_<?php echo $prod_id;
							?>">
							<input type='hidden' name='product_id_<?php echo $prod_id ?>'
								   id='product_id_<?php echo $prod_id ?>'
								   value='<?php echo $prod_id ?>'>
							<input type='hidden' name='author_email' id='author_email' value='<?php echo $uemail ?>'>
							<input type='hidden' name='product_img_<?php echo $prod_id ?>' id='product_img_<?php echo $prod_id ?>' value='<?php echo $img_url ?>'>
							<input type='hidden' name='product_price_<?php echo $prod_id ?>' id='product_price_<?php echo $prod_id ?>' value='<?php echo $price ?>'>
							<input type='hidden' name='product_url_<?php echo $prod_id ?>' id='product_url_<?php echo $prod_id ?>' value='<?php echo $url ?>'>
							<input type='hidden' name='site_url' id='site_url' value='<?php echo admin_url() ?>'>
							<input type="hidden" name="tried" id="tried" value="yes" />
							<?php
							do_action( 'quoteup_add_hidden_fields_in_form', $product_id );
							do_action( 'pep_add_hidden_fields_in_form', $product_id );
							?>
							<!--<div class='ck_msg wdm-enquiry-form-indication'><sup class='req'>*</sup> <?php _e( 'Indicates required fields', 'quoteup' ) ?></div>-->
							<div id="error" class="error" >

							</div>
							<div id="nonce_error" style="text-align: center; background-color: #f2dede; ">
								<div  class='wdmquoteup-err-display' style='background-color:transparent;'>
									<span class="wdm-quoteupicon wdm-quoteupicon-exclamation-circle"></span><?php _e( 'Unauthorized enquiry', 'quoteup' ) ?>
								</div>
							</div>
							<div class="wdm-quoteup-form-inner">
								<?php
								do_action( 'quoteup_add_custom_field_in_form' );
								do_action( 'pep_add_custom_field_in_form' );
								?>

								<?php
								$enable_mc	 = '';
								if ( isset( $form_data[ 'enable_send_mail_copy' ] ) ) {
									$enable_mc = $form_data[ 'enable_send_mail_copy' ];
								}
								$this->displaySendMeACopy( $enable_mc );
								?>
							</div>
							<div class="form_input btn_div wdm-enquiryform-btn-wrap wdm-quoteupform-btn-wrap"><div class='form-wrap'><input type='submit' value='<?php _e( 'Send', 'quoteup' );
								?>' name="btnSend"  id="btnSend_<?php echo $prod_id ?>" class="button_example" <?php echo ($manual_css == 1) ? getManualCSS( $form_data ) : ''; ?>><div class="wdmquoteup-loader" style='display: none'><?php $url = QUOTEUP_PLUGIN_URL . '/images/loading.gif'; ?><img src='<?php echo $url ?>' ></div></div></div>
							<div class='form-errors-wrap single-enquiry-form'>
								<div class="form-errors">
									<ul class="error-list">
									</ul>
								</div>
							</div>
						</form>

						<div id="success_<?php echo $prod_id ?>" class="wdmquoteup-success-wrap">

							<div class='success_msg'>
								<span class="wdm-quoteupicon wdm-quoteupicon-done"></span> <strong><?php _e( 'Enquiry email sent successfully!', 'quoteup' ) ?></strong>
							</div>
						</div>

					</div><!--/modal body-->
					<?php
				}

				private function displaySendMeACopy( $enable_mc ) {
					if ( $enable_mc == 1 ) {
						?>
						<div class='ck form_input'>
							<label class='contact-cc-wrap'
							<?php
							if ( isset( $dialogue_text_color ) ) {
								echo " style=' color: " . $dialogue_text_color . ";'";
							}
							?>><input type='checkbox' id='contact-cc'  name='cc' value='yes'  /><span class='contact-cc-txt'><?php _e( 'Send me a copy', 'quoteup' ) ?></span></label>
						</div>
						<?php
					}
				}

			}

			$quoteupSingleProductModal = QuoteupSingleProductModal::getInstance();
			