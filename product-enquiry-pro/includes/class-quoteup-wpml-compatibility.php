<?php
namespace Includes;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class QuoteUpWPMLCompatibility
{
    public $current_language;

    public function __construct()
    {
        add_action('wdm_before_send_admin_email', array($this, 'wdmBeforeSendAdminEmail'));
        add_action('wdm_after_send_admin_email', array($this, 'wdmAfterSendAdminEmail'));
    }

    public function wdmBeforeSendAdminEmail($email)
    {
        if (!defined('ICL_SITEPRESS_VERSION') || ICL_PLUGIN_INACTIVE) {
            return;
        }

        global $sitepress;

        if (!$this->current_language) {
            $this->current_language = $sitepress->get_current_language();
        }

        $user = get_user_by('email', $email);
        if ($user) {
            $user_lang = $sitepress->get_user_admin_language($user->ID);
            $this->wdmSwitchLocale($user_lang);
        } else {
            global $sitepress_settings;
            $this->wdmSwitchLocale($sitepress_settings[ 'admin_default_language' ]);
        }
    }

    public function wdmAfterSendAdminEmail()
    {
        if (!defined('ICL_SITEPRESS_VERSION') || ICL_PLUGIN_INACTIVE) {
            return;
        }

        if ($this->current_language) {
            $this->wdmSwitchLocale($this->current_language);
        }
    }

    public function wdmSwitchLocale($lang)
    {
        global $sitepress;

        $sitepress->switch_lang($lang, true);
        unload_textdomain('quoteup');
        unload_textdomain('default');
        quoteupLoadTextDomain();
        load_default_textdomain();
        global $wp_locale;
        $wp_locale = new \WP_Locale();
    }
}

new QuoteUpWPMLCompatibility();