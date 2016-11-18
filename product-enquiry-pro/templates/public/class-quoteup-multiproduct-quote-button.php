<?php
namespace Templates\Frontend;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class QuoteupMultiproductQuoteButton
{

    /**
     * @var Singleton The reference to *Singleton* instance of this class
     */
    private static $instance;

    /**
     * Returns the *Singleton* instance of this class.
     *
     * @return Singleton The *Singleton* instance.
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    protected function __construct()
    {
        add_action('woocommerce_before_variations_form', array($this,'quoteupAddVariationID'));
    }

    function quoteupAddVariationID()
    {
        if (!is_admin()): ?>
            <input type="hidden" name="variation_id" class="variation_id" value="0" />
    <?php
       endif;
    }

	public function displayQuoteButton( $prod_id, $btn_class, $QuoteUpDisplayQuoteButtonObj ) {
        @session_start();
        // if (!isSimpleProduct($prod_id)) {
        //     return;
        // }

        $status = isSoldIndividually($prod_id);
        if ($status == true) {
            if (isset($_SESSION[ 'wdm_product_info' ])) {
                $productsAdded = $_SESSION[ 'wdm_product_info' ];
            }

            if (isset($productsAdded)) {
                foreach ($productsAdded as $products) {
                    foreach ($products as $element) {
                        if ($prod_id == $element[ 'id' ]) {
                            return;
                        }
                    }
                }
            }
        }

        $default_vals    = array( 'show_after_summary'       => 1,
        'button_CSS'                 => 0,
        'pos_radio'                  => 0,
        'show_powered_by_link'       => 0,
        'enable_send_mail_copy'      => 0,
        'enable_telephone_no_txtbox' => 0,
        'only_if_out_of_stock'       => 0,
        'dialog_product_color'       => '#999',
        'dialog_text_color'          => '#333',
        'dialog_color'               => '#fff',
        );
        $form_data       = get_option('wdm_form_data', $default_vals);
        // $color = getDialogColor($form_data);
        $title           = get_the_title($prod_id);
		$pcolor			 = $QuoteUpDisplayQuoteButtonObj->getDialogTitleColor( $form_data );
        $manual_css      = 0;
        if ($form_data[ 'button_CSS' ] == 'manual_css') {
            $manual_css = 1;
        }
        if (isset($form_data[ 'user_custom_css' ])) {
            wp_add_inline_style('modal_css1', $form_data[ 'user_custom_css' ]);
        }

        $cart_link = get_permalink($form_data[ 'mpe_cart_page' ]);
		$this->cssHTML( $btn_class, $manual_css, $form_data, $prod_id, $title, $cart_link, $QuoteUpDisplayQuoteButtonObj );
        ?>
        <?php
        unset($pcolor);
    }

	private function cssHTML( $btn_class, $manual_css, $form_data, $prod_id, $title, $cart_link, $QuoteUpDisplayQuoteButtonObj ) {
        ?>
        <div class="quote-form">         <!-- Button trigger modal -->
        <?php
		$this->showAddToQuoteButton( $form_data, $manual_css, $prod_id, $btn_class, $QuoteUpDisplayQuoteButtonObj );
        ?>
        </div><!--/contact form or btn-->
        <?php
    }

		private function showAddToQuoteButton( $form_data, $manual_css, $prod_id, $btn_class, $QuoteUpDisplayQuoteButtonObj ) {
        global $product;
        if ($product->product_type == 'variable') {
            if (isset($form_data[ 'show_button_as_link' ]) && $form_data[ 'show_button_as_link' ] == 1) {
                ?>
                <a id="wdm-quoteup-trigger-<?php echo $prod_id ?>" data-toggle="wdm-quoteup-modal" data-target="#wdm-quoteup-modal" href='#' style='font-weight: bold;
            <?php
            if (! empty($form_data[ 'button_text_color' ])) {
                echo "color: " . $form_data[ 'button_text_color' ] . ";";
            }
            ?>'>
				<?php echo $QuoteUpDisplayQuoteButtonObj->returnButtonText( $form_data ); ?>
            </a>
            <?php
            } else {
                if (!is_singular('product')) {
                
                    ?>
                    <a href="<?php echo get_permalink($prod_id) ?>"><button class="<?php echo $btn_class ?>"
                <?php
                if ($manual_css == 1) {
                    echo getManualCSS($form_data);
                }
                ?>>
						<?php echo $QuoteUpDisplayQuoteButtonObj->returnButtonText( $form_data );
                ?>
                </button></a>
                <?php
                } else {
                    ?>
                    <button class="<?php echo $btn_class ?>" id="wdm-quoteup-trigger-<?php echo $prod_id ?>"  data-toggle="wdm-quoteup-modal" data-target="#wdm-quoteup-modal"
            <?php
            if ($manual_css == 1) {
                echo getManualCSS($form_data);
            }
            ?>>
					<?php echo $QuoteUpDisplayQuoteButtonObj->returnButtonText( $form_data );
            ?>
            </button>
                <?php
                }
            }
        } else {
            if (isset($form_data[ 'show_button_as_link' ]) && $form_data[ 'show_button_as_link' ] == 1) {
            ?>
            <a id="wdm-quoteup-trigger-<?php echo $prod_id ?>" data-toggle="wdm-quoteup-modal" data-target="#wdm-quoteup-modal" href='#' style='font-weight: bold;
            <?php
            if (! empty($form_data[ 'button_text_color' ])) {
                echo "color: " . $form_data[ 'button_text_color' ] . ";";
            }
            ?>'>
				<?php echo $QuoteUpDisplayQuoteButtonObj->returnButtonText( $form_data ); ?>
            </a>
            <?php
            } else {
                ?>
                <button class="<?php echo $btn_class ?>" id="wdm-quoteup-trigger-<?php echo $prod_id ?>"  data-toggle="wdm-quoteup-modal" data-target="#wdm-quoteup-modal"
            <?php
            if ($manual_css == 1) {
                echo getManualCSS($form_data);
            }
            ?>>
				<?php echo $QuoteUpDisplayQuoteButtonObj->returnButtonText( $form_data );
            ?>
            </button>
            <?php
            }
        }
        global $wpdb;
        $query   = "select user_email from {$wpdb->posts} as p join {$wpdb->users} as u on p.post_author=u.ID where p.ID=%d";
        $uemail  = $wpdb->get_var($wpdb->prepare($query, $prod_id));
        ?>
        <input type='hidden' name='author_email' id='author_email' value='<?php echo $uemail ?>'>
        <?php
    }
}

            $quoteupMultiproductQuoteButton = QuoteupMultiproductQuoteButton::getInstance();