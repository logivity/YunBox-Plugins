<?php

if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

class C_NGG_Admin_Overview
{

    /**
     * Shows important server configuration details.
     * @author GamerZ (http://www.lesterchan.net)
     *
     * @return void
     */

    public function server_info()
    {
        global $wpdb, $ngg;

        // Get MYSQL Version
        $sqlversion = $wpdb->get_var("SELECT VERSION() AS version");

        // GET SQL Mode
        $mysqlinfo = $wpdb->get_results("SHOW VARIABLES LIKE 'sql_mode'");
        if (is_array($mysqlinfo)) $sql_mode = $mysqlinfo[0]->Value;
        if (empty($sql_mode)) $sql_mode = __('Not set', 'nggallery');

        // Get PHP Safe Mode
        if(ini_get('safe_mode')) $safe_mode = __('On', 'nggallery');
        else $safe_mode = __('Off', 'nggallery');

        // Get PHP allow_url_fopen
        if(ini_get('allow_url_fopen')) $allow_url_fopen = __('On', 'nggallery');
        else $allow_url_fopen = __('Off', 'nggallery');

        // Get PHP Max Upload Size
        if (function_exists('wp_max_upload_size')) $upload_max = strval(round((int) wp_max_upload_size() / (1024 * 1024))) . 'M';
        else if(ini_get('upload_max_filesize')) $upload_max = ini_get('upload_max_filesize');
        else $upload_max = __('N/A', 'nggallery');

        // Get PHP Output buffer Size
        if(ini_get('pcre.backtrack_limit')) $backtrack_limit = ini_get('pcre.backtrack_limit');
        else $backtrack_limit = __('N/A', 'nggallery');

        // Get PHP Max Post Size
        if(ini_get('post_max_size')) $post_max = ini_get('post_max_size');
        else $post_max = __('N/A', 'nggallery');

        // Get PHP Max execution time
        if(ini_get('max_execution_time')) $max_execute = ini_get('max_execution_time');
        else $max_execute = __('N/A', 'nggallery');

        // Get PHP Memory Limit
        if(ini_get('memory_limit')) $memory_limit = $ngg->memory_limit;
        else $memory_limit = __('N/A', 'nggallery');

        // Get actual memory_get_usage
        if (function_exists('memory_get_usage')) $memory_usage = round(memory_get_usage() / 1024 / 1024, 2) . __(' MByte', 'nggallery');
        else $memory_usage = __('N/A', 'nggallery');

        // required for EXIF read
        if (is_callable('exif_read_data')) $exif = __('Yes', 'nggallery'). " (V" . substr(phpversion('exif'),0,4) . ")" ;
        else $exif = __('No', 'nggallery');

        // required for meta data
        if (is_callable('iptcparse')) $iptc = __('Yes', 'nggallery');
        else $iptc = __('No', 'nggallery');

        // required for meta data
        if (is_callable('xml_parser_create')) $xml = __('Yes', 'nggallery');
        else $xml = __('No', 'nggallery');

        ?>
        <li><?php _e('Operating System', 'nggallery'); ?> : <span><?php echo PHP_OS; ?>&nbsp;(<?php echo (PHP_INT_SIZE * 8) ?>&nbsp;Bit)</span></li>
        <li><?php _e('Server', 'nggallery'); ?> : <span><?php echo $_SERVER["SERVER_SOFTWARE"]; ?></span></li>
        <li><?php _e('Memory usage', 'nggallery'); ?> : <span><?php echo $memory_usage; ?></span></li>
        <li><?php _e('MYSQL Version', 'nggallery'); ?> : <span><?php echo $sqlversion; ?></span></li>
        <li><?php _e('SQL Mode', 'nggallery'); ?> : <span><?php echo $sql_mode; ?></span></li>
        <li><?php _e('PHP Version', 'nggallery'); ?> : <span><?php echo PHP_VERSION; ?></span></li>
        <li><?php _e('PHP Safe Mode', 'nggallery'); ?> : <span><?php echo $safe_mode; ?></span></li>
        <li><?php _e('PHP Allow URL fopen', 'nggallery'); ?> : <span><?php echo $allow_url_fopen; ?></span></li>
        <li><?php _e('PHP Memory Limit', 'nggallery'); ?> : <span><?php echo $memory_limit; ?></span></li>
        <li><?php _e('PHP Max Upload Size', 'nggallery'); ?> : <span><?php echo $upload_max; ?></span></li>
        <li><?php _e('PHP Max Post Size', 'nggallery'); ?> : <span><?php echo $post_max; ?></span></li>
        <li><?php _e('PCRE Backtracking Limit', 'nggallery'); ?> : <span><?php echo $backtrack_limit; ?></span></li>
        <li><?php _e('PHP Max Script Execute Time', 'nggallery'); ?> : <span><?php echo $max_execute; ?>s</span></li>
        <li><?php _e('PHP Exif support', 'nggallery'); ?> : <span><?php echo $exif; ?></span></li>
        <li><?php _e('PHP IPTC support', 'nggallery'); ?> : <span><?php echo $iptc; ?></span></li>
        <li><?php _e('PHP XML support', 'nggallery'); ?> : <span><?php echo $xml; ?></span></li>
        <?php
    }

    /**
     * Show GD Library version information
     *
     * @return void
     */
    function gd_info()
    {
        if (function_exists("gd_info"))
        {
            $info = gd_info();
            $keys = array_keys($info);
            for ($i = 0; $i < count($keys); $i++) {
                if (is_bool($info[$keys[$i]]))
                    echo "<li> " . $keys[$i] . " : <span>" . ($info[$keys[$i]] ? __('Yes', 'nggallery') : __('No', 'nggallery')) . "</span></li>\n";
                else
                    echo "<li> " . $keys[$i] . " : <span>" . $info[$keys[$i]] . "</span></li>\n";
            }
        }
        else {
            echo '<h4>'.__('No GD support', 'nggallery').'!</h4>';
        }
    }

    // Display File upload quota on dashboard
    function dashboard_quota()
    {
        if (get_site_option('upload_space_check_disabled'))
            return;

        if (!wpmu_enable_function('wpmuQuotaCheck'))
            return;

        $settings = C_NextGen_Settings::get_instance();
        $fs = C_Fs::get_instance();
        $dir = $fs->join_paths($fs->get_document_root('content'), $settings->gallerypath);

        $quota = get_space_allowed();
        $used = get_dirsize($dir) / 1024 / 1024;

        if ($used > $quota)
            $percentused = '100';
        else
            $percentused = ($used / $quota) * 100;

        $used_color = ($percentused < 70) ? (($percentused >= 40) ? 'yellow' : 'green') : 'red';
        $used = round($used, 2);
        $percentused = number_format($percentused);

        ?>
        <p><?php print __('Storage Space'); ?></p>
        <ul>
            <li><?php printf(__('%1$sMB Allowed', 'nggallery'), $quota); ?></li>
            <li class="<?php print $used_color; ?>"><?php printf(__('%1$sMB (%2$s%%) Used', 'nggallery'), $used, $percentused); ?></li>
        </ul>
        <?php
    }

}

/**
 * nggallery_admin_overview()
 *
 * Add the admin overview the dashboard style
 * @return mixed content
 */
function nggallery_admin_overview()
{
    $NGG_Admin_Overview = new C_NGG_Admin_Overview();

    global $wpdb;
    $images    = intval($wpdb->get_var("SELECT COUNT(*) FROM $wpdb->nggpictures"));
    $galleries = intval($wpdb->get_var("SELECT COUNT(*) FROM $wpdb->nggallery"));
    $albums    = intval($wpdb->get_var("SELECT COUNT(*) FROM $wpdb->nggalbum"));

    ?>

    <div class="wrap about-wrap">
        <h1><?php //_e( 'Welcome to NextGEN Gallery', 'nggallery' ); ?></h1>

        <div class="about-text"><?php //printf( __( "Congrats! You're now running the most popular WordPress gallery plugin of all time. So far you've added %s images, %s galleries, and %s albums." ), $images, $galleries, $albums); ?></div>
        <div class="wp-badge"></div>

        <h2 class="nav-tab-wrapper wp-clearfix" id="ngg-tabs-wrapper">
            <?php if (!is_multisite() || is_super_admin()) { ?>
                <a href="#top#details" class="nav-tab nav-tab-active" id="details-link"><?php _e( 'Site Details' ); ?></a>
            <?php } ?>
            <a href="#top#freedoms" class="nav-tab" id="freedoms-link"><?php _e( 'Freedoms' ); ?></a>
        </h2>

        <?php if (!is_multisite() || is_super_admin()) { ?>
            <div id="details" class="ngg-tab">
                <h2><?php _e( 'Site Details' ); ?></h2>
                <p class="about-text centered"><?php _e( 'When contacting support, consider copying and pasting this information in your support request. It helps us troubleshoot more quickly.', 'nggallery' ); ?>
                </p>
                <div class="two-col">
                    <div class="col">
                        <?php if (is_multisite()) $NGG_Admin_Overview->dashboard_quota(); ?>
                    </div>
                    <div class="col">
                        <p><strong><?php print __('Server Settings', 'nggallery'); ?></strong></p>
                        <ul>
                            <?php $NGG_Admin_Overview->server_info(); ?>
                        </ul>
                    </div>
                </div>
                <div class="two-col">
                    <div class="col">
                            <p><strong><?php print __('Graphic Library', 'nggallery'); ?></strong></p>
                            <ul>
                                <?php $NGG_Admin_Overview->gd_info(); ?>
                            </ul>
                    </div>
                </div>
            </div>
        <?php } ?>

        <div id="freedoms" class="ngg-tab">

            <p class="about-description"><?php printf( __( 'NextGEN Gallery is Free and open source software, built by a small but dedicated team as well as community code contributions. It comes with awesome rights courtesy of its <a href="%s" target="_blank">license</a>, the GPL.' ), 'https://wordpress.org/about/license/' ); ?></p>

            <ol start="0">
                <li><p><?php _e( 'You have the freedom to run the program, for any purpose.' ); ?></p></li>
                <li><p><?php _e( 'You have access to the source code, the freedom to study how the program works, and the freedom to change it to make it do what you wish.' ); ?></p></li>
                <li><p><?php _e( 'You have the freedom to redistribute copies of the original program so you can help your neighbor.' ); ?></p></li>
                <li><p><?php _e( 'You have the freedom to distribute copies of your modified versions to others. By doing this you can give the whole community a chance to benefit from your changes.' ); ?></p></li>
            </ol>

            <p><?php _e( 'NextGEN Gallery grows because people like you tell your friends and website visitors about it. We thank you for doing so.' ); ?></p>

        </div>

    </div>

    <?php
}
