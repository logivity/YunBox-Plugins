<?php

namespace Includes\Settings;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class QuoteupSettings
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
        add_action('admin_init', array( $this, 'registerSetting' ));

    }

    public function registerSetting()
    {
        register_setting('wdm_form_options', 'wdm_form_data');
    }

    public function enqueueScripts()
    {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        //Default CSS Of woocommerce
        wp_enqueue_style('woocommerce_admin_styles', plugin_dir_url(dirname(dirname(dirname(__FILE__)))) . 'woocommerce/assets/css/admin.css');
        wp_enqueue_style('quoteup_db_style', QUOTEUP_PLUGIN_URL . '/css/admin/settings.css');
        wp_enqueue_script('hashchange', QUOTEUP_PLUGIN_URL . '/js/admin/jquery.hashchange.min.js');
        wp_enqueue_script('tabs', QUOTEUP_PLUGIN_URL . '/js/admin/jquery.easytabs.min.js');
        wp_enqueue_script('jquery-tiptip', array( 'jquery' ));
        wp_enqueue_script('quoteup-settings', QUOTEUP_PLUGIN_URL . '/js/admin/settings.js', array( 'jquery', 'wp-color-picker' ));

        wp_localize_script(
            'quoteup-settings',
            'data',
            array(
            'name_req'       => __('Please enter email address', 'quoteup'),
            'valid_name'     => __('Please enter valid email address. Multiple email addresses must be comma separated.', 'quoteup'),
            'ajax_admin_url' => admin_url('admin-ajax.php'),
            'could_not_migrate_enquiries'   => __('Could not migrate enquiries due to security issue.', 'quoteup'),
            'enable'    => __('Enable', 'quoteup'),
            'disable'   => __('Disable', 'quoteup'),
            'quoteup_for_all_products' => __('enquiry system for all products', 'quoteup'),
            'add_to_cart_for_all_products' => __('add to cart for all products', 'quoteup'),
            'price_for_all_products' => __('price for all products', 'quoteup'),
            'show'  => __('Show', 'quoteup'),
            'hide'  => __('Hide', 'quoteup'),
            'applying_global_settings' => __('Applying Global Settings will do following things', 'quoteup'),
            )
        );

        // This function loads in the required media files for the media manager.
        wp_enqueue_media();

        // Register, localize and enqueue our custom JS.
        wp_register_script('tgm-nmp-media', QUOTEUP_PLUGIN_URL . '/js/admin/media-uploader.js');
        wp_localize_script(
            'tgm-nmp-media',
            'tgm_nmp_media',
            array(
            'title'  => __('Upload or Choose Your Custom Image File', 'quoteup'), // This will be used as the default title
            'button' => __('Insert Image into Input Field', 'quoteup')   // This will be used as the default button text
            )
        );
        wp_enqueue_script('tgm-nmp-media');
    }

    public function displaySettings()
    {
        $this->enqueueScripts();
        include_once 'quoteup-general.php';
        include_once 'quoteup-email.php';
        include_once 'quoteup-display.php';
        include_once 'settings-tabs.php';
        include_once 'quoteup-quote.php';
        ?>
        <!--dashboard settings design-->
        <div class='wrap'>

        <?php // echo '<h2>' . __( 'QuoteUp Settings', 'quoteup' ) . '</h2>'; ?>

        <?php
        if ($_SERVER[ 'QUERY_STRING' ] == 'page=quoteup-for-woocommerce&settings-updated=true') {
            echo '<div id="save_notice" class="updated settings-error notice is-dismissible">';
            echo '<p><strong>' . __('Settings Saved', 'quoteup') . '</strong></p>';
            echo '</div>';
        }
        ?>
        <form name="ask_product_form" id="ask_product_form" class="form-table" method="POST" action="options.php">
            <?php
            settings_fields('wdm_form_options');
            $default_vals    = array( 'show_after_summary'   => 1,
                'button_CSS'             => 'theme_css',
                'pos_radio'              => 'show_after_summary',
                'show_powered_by_link'   => 0,
                'enable_send_mail_copy'  => 0,
                'only_if_out_of_stock'   => 0,
            );
            $form_data       = '';
            $form_data       = get_option('wdm_form_data', $default_vals);
            ?>
            <div id="tab-container" class="tab-container">


                <?php
                settingsTabs($form_data);
                generalSettings($form_data);
                emailSettings($form_data);
                displaySettings($form_data);
                quoteSettings($form_data);
                do_action('quoteup_add_product_enquiry_tab_content', $form_data);
                do_action('wdm_pep_add_product_enquiry_tab_content', $form_data);
                ?>
				</div>
				<input type="submit" class="wdm_wpi_input button-primary" value="<?php _e('Save changes', 'quoteup') ?>" id="wdm_ask_button" />

                </form>
            </div>
            <?php
    }
}

$this->displaySettingsPage = QuoteupSettings::getInstance();
