<?php
/**
Plugin Name: First Admin Theme
Plugin URI: http://www.thefox.cn/?p=2115
Description: Change Wordpress admin bar, menu, login, footer, icon and colors
Version: 1.0
Author: Flatfull.com
Author URI: http://www.thefox.cn/
Text Domain: first_admin_theme
Domain Path: /languages
*/

class First_Admin_Theme {

	private $menus,
					$submenus,
					$settings,
					$settings_name = 'first_admin_theme_option'
					;

	function __construct() {
		// ini_set('error_reporting', E_ALL);
		// add to menu and load basic css
		add_action( 'admin_menu', array( $this, 'add_menu' ) );		
		add_action( 'admin_init', array( $this, 'register_settings' ), 20 );		

		// get it work!
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 20 );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar'), 999 );
		add_action( 'admin_init', array( $this, 'admin_menu' ), 30 );
		add_filter( 'admin_footer_text', array( $this, 'admin_footer' ), 40 );
		add_filter( 'update_footer', array( $this, 'admin_footer' ), 40 );
		add_action( 'login_enqueue_scripts', array( $this, 'login' ), 50 );

		// remove the google webfont
		add_filter( 'gettext_with_context', array( $this, 'disable_open_sans' ), 888, 4 );
		// register_deactivation_hook( __FILE__, array($this, "deactivation"));
	}

	// add plugin to settings menu
	function add_menu() {
		add_submenu_page( 'options-general.php', 'First Admin Theme', 'First Admin Theme', 'manage_options', 'first-admin-theme', array( $this, 'settings' ) ); 
	}

	// register
	function register_settings() {
		global $menu;
		global $submenu;
		$this->menus = array_merge(array(), $menu);
		$this->submenus = array_merge(array(), $submenu);
		$this->settings = get_option( $this->settings_name );
		register_setting( 'first-admin-theme-group', $this->settings_name );
	}

	// get setting
	function get_setting($arg){
		return ( (isset( $this->settings[$arg] ) && trim($this->settings[$arg]) !== '') ? $this->settings[$arg] : NULL);
	}

	// settings
	function settings() {
		?>
		<script type="text/javascript">
			jQuery( document ).ready(function() {
				jQuery(document).on('click', '.box > h3, .box > h4, .toggle', function(){
					jQuery(this).next( ".hide" ).toggle();
				});
			});
		</script>
		<form method="post" id="form" action="options.php">
		<?php settings_fields( 'first-admin-theme-group' ); ?>
		<div class="wrap">
			<h2>First Admin Theme</h2>
			<div class="row m-t clearfix">
				<div class="col col-8">
					<h3 class="m-b"><span>Admin bar</span></h3>
					<p class="no-m-t text-sm">Change the admin bar on the top</p>
					<div class="row clearfix m-b">
						<div class="col col-6">
							<div class="box">
								<h4><span>Logo & Name</span></h4>
								<div class="box-body b-t hide">
									<p>
										<label>
											logo url (max height: 32px)
											<input name="<?php echo $this->settings_name; ?>[bar_logo]" type="text" value="<?php echo $this->get_setting('bar_logo'); ?>" class="widefat">
										</label>
									</p>
									<p>
										<label>
											Link
											<input name="<?php echo $this->settings_name; ?>[bar_name_link]" type="text" value="<?php echo $this->get_setting('bar_name_link'); ?>" class="widefat">
										</label>
									</p>
									<p>
										<label>
											Name
											<input name="<?php echo $this->settings_name; ?>[bar_name]" type="text" value="<?php echo $this->get_setting('bar_name'); ?>" class="widefat">
										</label>
									</p>
									<p>
										<label>
											<input name="<?php echo $this->settings_name; ?>[bar_name_hide]" type="checkbox" <?php if ( $this->get_setting('bar_name_hide') == true ) echo 'checked="checked" '; ?>> 
											Hide 'Name'
										</label>
									</p>
									<p><input type="submit" class="button button-primary m-b" value="<?php _e('Save') ?>" /></p>
								</div>
							</div>
						</div>
						<div class="col col-6">
							<div class="box">
								<h4><span>Quick Links</span></h4>
								<div class="box-body b-t hide">
									<p>
										<fieldset>
											<label>
												<input name="<?php echo $this->settings_name; ?>[bar_updates_hide]" type="checkbox" <?php if ($this->get_setting('bar_updates_hide') == true) echo 'checked="checked" '; ?>> 
												Remove 'Updates'
											</label>
											<br>
											<label>
												<input name="<?php echo $this->settings_name; ?>[bar_comments_hide]" type="checkbox" <?php if ($this->get_setting('bar_comments_hide') == true) echo 'checked="checked" '; ?>> 
												Remove 'Comments'
											</label>
											<br>
											<label>
												<input name="<?php echo $this->settings_name; ?>[bar_new_hide]" type="checkbox" <?php if ($this->get_setting('bar_new_hide') == true) echo 'checked="checked" '; ?>> 
												Remove 'New'
											</label>
										</fieldset>
									</p>
									<p><input type="submit" class="button button-primary m-b" value="<?php _e('Save') ?>" /></p>
								</div>
							</div>
						</div>
					</div>
					<h3 class="m-b"><span>Menu</span></h3>
					<p class="no-m-t  text-sm">Change the menu on the left.</p>
					<div class="row clearfix">
						<div class="col col-6">
							<?php
								$i = -1;
								$half = round(count($this->menus)/2);
								foreach ($this->menus as $k){
									$v = explode(' <span', $k[0]);
									$slug = 'menu_'.strtolower( str_replace( ' ','_',$v[0] ) );
									$slug_hide = $slug.'_hide';
									if($v[0] != NULL){
							?>
							<div class="box bg">
								<h4><span class="pull-right text-muted <?php if ($this->get_setting($slug_hide)) echo 'text-l-t'; ?>"><?php if($this->get_setting($slug) !== NULL ) echo $v[0]; ?></span><span><?php echo $this->get_setting($slug) ? $this->get_setting($slug) : $v[0]; ?></span></h4>
								<div class="box-body b-t hide">
									<p>
										<label>
											Title:
											<input name="<?php echo $this->settings_name.'['.$slug.']'; ?>" value="<?php echo $this->get_setting($slug); ?>" type="text" class="widefat">
										</label>
									</p>
									<p>
										<label>
											<input name="<?php echo $this->settings_name.'['.$slug_hide.']'; ?>" <?php if ($this->get_setting($slug_hide)) echo 'checked="checked" '; ?> type="checkbox"> 
											Remove from menu
										</label>
									</p>
									<p class="toggle">
										<a href="#admin" class="c-p">Submenu</a>										
									</p>
									<div class="hide">
										<?php
											$sub = isset($this->submenus[$k[2]]) ? $this->submenus[$k[2]] : array() ;
											foreach ($sub as $k){
												$v = explode(' <span', $k[0]);
												$slug_sub = $slug.'_'.strtolower( str_replace( ' ','_',$v[0] ) );
												$slug_sub_hide = $slug_sub.'_hide';
												if($v[0] != NULL){
										?>
										<div class="box">
											<h4 class="sm"><span class="pull-right text-muted <?php if ($this->get_setting($slug_sub_hide)) echo 'text-l-t'; ?>"><?php if($this->get_setting($slug_sub) !== NULL ) echo $v[0]; ?></span><span><?php echo $this->get_setting($slug_sub) ? $this->get_setting($slug_sub) : $v[0]; ?></span></h4>
											<div class="box-body b-t hide">
												<p>
													<label>
														Title:
														<input name="<?php echo $this->settings_name.'['.$slug_sub.']'; ?>" value="<?php echo $this->get_setting($slug_sub); ?>" type="text" class="widefat">
													</label>
												</p>
												<p>
													<label>
														<input name="<?php echo $this->settings_name.'['.$slug_sub_hide.']'; ?>" <?php if ($this->get_setting($slug_sub_hide)) echo 'checked="checked" '; ?> type="checkbox"> 
														Remove from menu
													</label>
												</p>
											</div>
										</div>
										<?php } }?>
									</div>
									<p><input type="submit" class="button button-primary m-b" value="<?php _e('Save') ?>" /></p>
								</div>
							</div>
							<?php
								} 
								$i++;
								if($i == $half){
									echo '</div><div class="col col-6">';
								}
							} ?>
						</div>
					</div>
				</div>
				<div class="col col-4">
					<h3 class="m-b"><span>Themes</span></h3>
					<p class="no-m-t text-sm">Change the icon, colors.</p>
					<div class="clearfix">
						<div class="box">
							<h4><span>Colors & Icons</span></h4>
							<div class="box-body b-t hide show">								
								<p>
									Colors:<br>
									<label>
										<input name="<?php echo $this->settings_name; ?>[theme_color]" type="radio" value="flat" <?php if ($this->get_setting('theme_color') == 'flat' || $this->get_setting('theme_color') !='default' ) echo 'checked="checked" '; ?>> 
										Flat
									</label>
									<br>
									<label>
										<input name="<?php echo $this->settings_name; ?>[theme_color]" type="radio" value="default" <?php if ($this->get_setting('theme_color') == 'default') echo 'checked="checked" '; ?>> 
										Default <a href="profile.php" title="Save to 'Default' then choose color">Choose</a>
									</label>
								</p>
								<p>
									Icons:<br>
									<label>
										<input name="<?php echo $this->settings_name; ?>[theme_icon]" type="radio" value="glyphicons" <?php if ($this->get_setting('theme_icon') == 'glyphicons' || $this->get_setting('theme_icon') !='default' ) echo 'checked="checked" '; ?>> 
										Glyphicons
									</label>
									<br>
									<label>
										<input name="<?php echo $this->settings_name; ?>[theme_icon]" type="radio" value="default" <?php if ($this->get_setting('theme_icon') == 'default') echo 'checked="checked" '; ?>> 
										Default
									</label>
								</p>
								<p><input type="submit" class="button button-primary m-b" value="<?php _e('Save') ?>" /></p>
							</div>
						</div>
					</div>					
					<h3 class="m-b"><span>Login</span></h3>
					<p class="no-m-t text-sm">Change the login page</p>
					<div class="clearfix">
						<div class="box">
							<h4><span>Login</span></h4>
							<div class="box-body b-t hide">
								<p>
									<label>Logo url
										<input name="<?php echo $this->settings_name; ?>[login_logo]" value="<?php echo $this->get_setting('login_logo'); ?>" type="text" class="widefat">
									</label>
								</p>
								<p><input type="submit" class="button button-primary m-b" value="<?php _e('Save') ?>" /></p>
							</div>
						</div>
					</div>
					<h3 class="m-b"><span>Footer</span></h3>
					<p class="no-m-t text-sm">Change the footer and version</p>
					<div class="clearfix">
						<div class="box">
							<h4><span>Footer</span></h4>
							<div class="box-body b-t hide">
								<p>
									<label>Text
										<input name="<?php echo $this->settings_name; ?>[footer_text]" value="<?php echo $this->get_setting('footer_text'); ?>" type="text" class="widefat">
									</label>
								</p>
								<p>
									<label>
										<input name="<?php echo $this->settings_name; ?>[footer_text_hide]" type="checkbox" <?php if ($this->get_setting('footer_text_hide') == true) echo 'checked="checked" '; ?>> 
										Hide 'Text'
									</label>
								</p>
								<p>
									<label>Version
										<input name="<?php echo $this->settings_name; ?>[footer_version]" value="<?php echo $this->get_setting('footer_version'); ?>" type="text" class="widefat">
									</label>
								</p>
								<p>
									<label>
										<input name="<?php echo $this->settings_name; ?>[footer_version_hide]" type="checkbox" <?php if ($this->get_setting('footer_version_hide') == true) echo 'checked="checked" '; ?>> 
										Hide 'Version'
									</label>
								</p>
								<p><input type="submit" class="button button-primary m-b" value="<?php _e('Save') ?>" /></p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		</form>
		<?php
	}

	// scripts
	function admin_scripts() {		
		wp_register_style( 'font', plugins_url( "css/font.css", __FILE__ ), array());
		wp_enqueue_style( 'font' );

		wp_register_style( 'style', plugins_url( "css/bar.css", __FILE__ ), array());
		wp_enqueue_style( 'style' );

		if( $this->get_setting('theme_icon') !== 'default' ){
			wp_register_style( 'icon', plugins_url( "css/icon.css", __FILE__ ), array());
			wp_enqueue_style( 'icon' );
		}

		if( $this->get_setting('theme_color') !== 'default' ){
			wp_register_style( 'color', plugins_url( "css/menu.css", __FILE__ ), array());
			wp_enqueue_style( 'color' );
		}
	}

	// admin menu
	function admin_menu() {
		global $menu;
		global $submenu;
		// update menu
		end( $menu );
		foreach ($menu as $k=>&$v){
			$id = explode(' <span', $v[0]);
			$slug = 'menu_'.strtolower( str_replace( ' ','_',$id[0] ) );
			$slug_hide = $slug.'_hide';
			if($id[0] != NULL && $this->get_setting($slug) !== NULL){
				$v[0] = $this->get_setting($slug). ( isset($id[1]) ? ' <span '.$id[1] : '' );
			}
			if( $this->get_setting($slug_hide) ){
				unset($menu[$k]);
			}
			// update the submenu
			if( isset($submenu[$v[2]]) ){
				foreach ($submenu[$v[2]] as $key=>&$val){				
					$id = explode(' <span', $val[0]);
					$slug_sub = $slug.'_'.strtolower( str_replace( ' ','_',$id[0] ) );
					$slug_sub_hide = $slug_sub.'_hide';
					if($id[0] != NULL && $this->get_setting($slug_sub) !== NULL){
						$val[0] = $this->get_setting($slug_sub). ( isset($id[1]) ? ' <span '.$id[1] : '' );
					}
					if( $this->get_setting($slug_sub_hide) ){						
						unset( $submenu[$v[2]][$key] );
					}
				}
			}
		}
	}

	// admin bar
	function admin_bar(){
		global $wp_admin_bar;

		$all_toolbar_nodes = $wp_admin_bar->get_nodes();

		foreach ( $all_toolbar_nodes as $node ) {
			$args = $node;
			if($args->id == "site-name"){
				$logo = $this->get_setting('bar_logo') ? sprintf('<img src="%s">', $this->get_setting('bar_logo')) : '';
				$hide = $this->get_setting('bar_name_hide') ? "hide" : "";
				$name = $this->get_setting('bar_name') ? $this->get_setting('bar_name') : $args->title;
				$args->title = sprintf('%s <span class="%s">%s</span>', $logo, $hide, $name);				
				$this->get_setting('bar_name_link') && ($args->href = $this->get_setting('bar_name_link'));
			}
			// update the Toolbar node
			$wp_admin_bar->add_node( $args );
		}
		// remove the wordpress logo
		$wp_admin_bar->remove_node( 'wp-logo' );
		$wp_admin_bar->remove_node( 'view-site' );

		if($this->get_setting('bar_updates_hide')){
				$wp_admin_bar->remove_node('updates');
		}
		if($this->get_setting('bar_comments_hide')){
				$wp_admin_bar->remove_node('comments');
		}
		if($this->get_setting('bar_new_hide')){
				$wp_admin_bar->remove_node('new-content');
		}
		if($this->get_setting('bar_new_hide')){
				$wp_admin_bar->remove_node('new-content');
		}
	}

	// admin footer
	function admin_footer( $default ){
		if(  strpos($default, 'wordpress') === false ){
			if( $this->get_setting('footer_version_hide') ){
				return '';
			}
			if( $this->get_setting('footer_version') ){
				return $this->get_setting('footer_version');
			}
		}else{
			if( $this->get_setting('footer_text_hide') ){
				return '';
			}
			if( $this->get_setting('footer_text') ){
				return $this->get_setting('footer_text');
			}
		}
		return $default;
	}

	// login
	function login() {
		add_filter( 'login_headerurl', array( $this, 'login_headerurl' ) );
		add_filter( 'login_headertitle', array( $this, 'login_headertitle' ) );

		$this->settings = get_option( $this->settings_name );
		if( $this->get_setting('login_logo') ){
			?>
	    <style type="text/css">
	      body.login div#login h1 a {
	        background-image: url(<?php echo $this->get_setting('login_logo'); ?>);
	      }
	    </style>
			<?php 
		}
	}

	function login_headerurl() {
		return esc_url( trailingslashit( get_bloginfo( 'url' ) ) );
	}

	function login_headertitle() {
		return esc_attr( get_bloginfo( 'name' ) );
	}


	// disable the google webfonts api
	function disable_open_sans( $translations, $text, $context, $domain ) {
		if ( 'Open Sans font: on or off' == $context && 'on' == $text ) {
			$translations = 'off';
		}
		return $translations;
	}

	// deactivation
	function deactivation() {
		delete_option( $this->settings_name );
	}

}

new First_Admin_Theme;