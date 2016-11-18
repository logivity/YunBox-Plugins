<?php
namespace Includes\Frontend;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
/**
 * Handles the cart activities on frontend part
 */

class QuoteupManageSession
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

    /**
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
    protected function __construct()
    {
        if (! defined('QUOTEUP_SESSION_COOKIE')) {
                define('QUOTEUP_SESSION_COOKIE', 'quoteup_wp_session');
        }
        if (! class_exists('\\Includes\\Frontend\\Libraries\\RecursiveArrayAccess')) {
                require_once QUOTEUP_PLUGIN_DIR . '/includes/public/libraries/class-recursive-arrayaccess.php';
        }
        if (! class_exists('\\Includes\\Frontend\\Libraries\\WP_Session')) {
            require_once QUOTEUP_PLUGIN_DIR . '/includes/public/libraries/class-wp-session.php';
            require_once QUOTEUP_PLUGIN_DIR . '/includes/public/libraries/wp-session.php';
        }
        add_filter('quoteup_session_expiration_variant', array( $this, 'setExpirationVariantTime' ), 99999);
        add_filter('quoteup_session_expiration', array( $this, 'setExpirationTime' ), 99999);
        add_action('init', array( $this, 'init' ), 15);
    }

    /**
     * Setup the WP_Session instance
     *
     * @access public
     * @since 1.5
     * @return void
     */
    public function init()
    {
        $this->session = \Includes\Frontend\Libraries\WP_Session::get_instance();
        return $this->session;
    }

        /**
     * Retrieve session ID
     *
     * @access public
     * @since 1.6
     * @return string Session ID
     */
    public function getID()
    {
        return $this->session->session_id;
    }
    /**
     * Retrieve a session variable
     *
     * @access public
     * @since 1.5
     * @param string $key Session key
     * @return string Session variable
     */
    public function get($key)
    {
        $key = sanitize_key($key);
        return isset($this->session[ $key ]) ? maybe_unserialize($this->session[ $key ]) : false;
    }
    /**
     * Set a session variable
     *
     * @since 1.5
     *
     * @param string $key Session key
     * @param integer $value Session variable
     * @return string Session variable
     */
    public function set($key, $value)
    {
        $key = sanitize_key($key);
        if (is_array($value)) {
            $this->session[ $key ] = serialize($value);
        } else {
            $this->session[ $key ] = $value;
        }
        return $this->session[ $key ];
    }

    public function unsetSession()
    {
        \Includes\Frontend\Libraries\wp_session_regenerate_id(true);
    }

    /**
     * Force the cookie expiration variant time to 1 hour
     *
     * @access public
     * @since 2.0
     * @param int $exp Default expiration (1 hour)
     * @return int
     */
    public function setExpirationVariantTime($exp)
    {
        unset($exp);
        return 3600;
    }
    /**
     * Force the cookie expiration time to 1 hour
     *
     * @access public
     * @since 1.9
     * @param int $exp Default expiration (1 hour)
     * @return int
     */
    public function setExpirationTime($exp)
    {
        unset($exp);
        return 3600;
    }
}

$this->wcCartSession = QuoteupManageSession::getInstance();
