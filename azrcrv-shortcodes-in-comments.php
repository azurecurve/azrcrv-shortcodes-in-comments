<?php
/**
 * ------------------------------------------------------------------------------
 * Plugin Name:		Shortcodes in Comments
 * Description:		Allows shortcodes to be used in comments
 * Version:			1.2.5
 * Requires CP:		1.0
 * Author:			azurecurve
 * Author URI:		https://development.azurecurve.co.uk/classicpress-plugins/
 * Plugin URI:		https://development.azurecurve.co.uk/classicpress-plugins/shortcodes-in-comments/
 * Donate link:		https://development.azurecurve.co.uk/support-development/
 * Text Domain:		shortcodes-in-comments
 * Domain Path:		/languages
 * License:			GPLv2 or later
 * License URI:		http://www.gnu.org/licenses/gpl-2.0.html
 * ------------------------------------------------------------------------------
 * This is free software released under the terms of the General Public License,
 * version 2, or later. It is distributed WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. Full
 * text of the license is available at https://www.gnu.org/licenses/gpl-2.0.html.
 * ------------------------------------------------------------------------------
 */

// Prevent direct access.
if (!defined('ABSPATH')){
	die();
}

// include plugin menu
require_once(dirname( __FILE__).'/pluginmenu/menu.php');
add_action('admin_init', 'azrcrv_create_plugin_menu_sic');

// include update client
require_once(dirname(__FILE__).'/libraries/updateclient/UpdateClient.class.php');

/**
 * Setup actions and filters.
 *
 * @since 1.0.0
 *
 */
// add actions
add_action('admin_menu', 'azrcrv_sic_create_admin_menu');
add_action('admin_post_azrcrv_sic_save_options', 'azrcrv_sic_save_options');
add_action('network_admin_menu', 'azrcrv_sic_create_network_admin_menu');
add_action('network_admin_edit_azrcrv_sic_save_network_options', 'azrcrv_sic_save_network_options');
add_action('plugins_loaded', 'azrcrv_sic_load_languages');

// add filters
add_filter('plugin_action_links', 'azrcrv_sic_add_plugin_action_link', 10, 2);
add_filter('comments_template', 'azrcrv_sic_remove_unallowed_shortcodes');
add_filter('comment_text', 'do_shortcode');
add_filter('dynamic_sidebar', 'azrcrv_sic_restore_all_shortcodes');
add_filter('codepotent_update_manager_image_path', 'azrcrv_sic_custom_image_path');
add_filter('codepotent_update_manager_image_url', 'azrcrv_sic_custom_image_url');

/**
 * Load language files.
 *
 * @since 1.0.0
 *
 */
function azrcrv_sic_load_languages() {
    $plugin_rel_path = basename(dirname(__FILE__)).'/languages';
    load_plugin_textdomain('shortcodes-in-comments', false, $plugin_rel_path);
}

/**
 * Custom plugin image path.
 *
 * @since 1.2.0
 *
 */
function azrcrv_sic_custom_image_path($path){
    if (strpos($path, 'azrcrv-shortcodes-in-comments') !== false){
        $path = plugin_dir_path(__FILE__).'assets/pluginimages';
    }
    return $path;
}

/**
 * Custom plugin image url.
 *
 * @since 1.2.0
 *
 */
function azrcrv_sic_custom_image_url($url){
    if (strpos($url, 'azrcrv-shortcodes-in-comments') !== false){
        $url = plugin_dir_url(__FILE__).'assets/pluginimages';
    }
    return $url;
}

/**
 * Get options including defaults.
 *
 * @since 1.2.0
 *
 */
function azrcrv_sic_get_option($option_name){
 
	$defaults = array(
						'allowed-shortcodes' => 'b,i,u,center,centre,strike,quote,color,size,img,url,link,ol,ul,li,code',
					);

	$options = get_option($option_name, $defaults);

	$options = wp_parse_args($options, $defaults);

	return $options;

}

/**
 * Add Shortcodes in Comments action link on plugins page.
 *
 * @since 1.0.0
 *
 */
function azrcrv_sic_add_plugin_action_link($links, $file){
	static $this_plugin;

	if (!$this_plugin){
		$this_plugin = plugin_basename(__FILE__);
	}

	if ($file == $this_plugin){
		$settings_link = '<a href="'.admin_url('admin.php?page=azrcrv-sic').'"><img src="'.plugins_url('/pluginmenu/images/logo.svg', __FILE__).'" style="padding-top: 2px; margin-right: -5px; height: 16px; width: 16px;" alt="azurecurve" />'.esc_html__('Settings' ,'shortcodes-in-comments').'</a>';
		array_unshift($links, $settings_link);
	}

	return $links;
}

/**
 * Add to plugin menu.
 *
 * @since 1.0.0
 *
 */
function azrcrv_sic_create_admin_menu(){
	//global $admin_page_hooks;
	
	add_submenu_page("azrcrv-plugin-menu"
						,esc_html__("Shortcodes in Comments Settings", "shortcodes-in-comments")
						,esc_html__("Shortcodes in Comments", "shortcodes-in-comments")
						,'manage_options'
						,'azrcrv-sic'
						,'azrcrv_sic_settings');
}

/**
 * Display Settings page.
 *
 * @since 1.0.0
 *
 */
function azrcrv_sic_settings(){
	if (!current_user_can('manage_options')){
		$error = new WP_Error('not_found', esc_html__('You do not have sufficient permissions to access this page.' , 'shortcodes-in-comments'), array('response' => '200'));
		if(is_wp_error($error)){
			wp_die($error, '', $error->get_error_data());
		}
	}
	
	// Retrieve plugin configuration options from database
	$options = get_option('azrcrv-sic');
	?>
	
	<div id="azrcrv-sic-general" class="wrap">
		<fieldset>
			<h1>
				<?php
					echo '<a href="https://development.azurecurve.co.uk/classicpress-plugins/"><img src="'.plugins_url('/pluginmenu/images/logo.svg', __FILE__).'" style="padding-right: 6px; height: 20px; width: 20px;" alt="azurecurve" /></a>';
					esc_html_e(get_admin_page_title());
				?>
			</h1>
			<?php if(isset($_GET['settings-updated'])){ ?>
				<div class="notice notice-success is-dismissible">
					<p><strong><?php esc_html_e('Settings have been saved.', 'shortcodes-in-comments'); ?></strong></p>
				</div>
			<?php } ?>
			
			<form method="post" action="admin-post.php">
				<input type="hidden" name="action" value="azrcrv_sic_save_options" />
				<input name="page_options" type="hidden" value="use-network,allowed-shortcodes" />
				
				<!-- Adding security through hidden referrer field -->
				<?php wp_nonce_field('azrcrv-sic', 'azrcrv-sic-nonce'); ?>
				
				<table class="form-table">
				
				<tr><th scope="row" colspan=2>
					<?php esc_html_e('This plugin allows shortcodes to be used in comments.', 'shortcodes-in-comments'); ?>
				</th></tr>
				
				<?php
					if (!get_site_option('azrcrv-sic')){
				?>
				<tr><th scope="row"><?php esc_html_e('Use Network Settings', 'shortcodes-in-comments'); ?></th><td>
					<fieldset><legend class="screen-reader-text"><span>Use Network Settings</span></legend>
					<label for="use-network"><input name="use-network" type="checkbox" id="use_network" value="1" <?php checked('1', $options['use-network']); ?> /><?php esc_html_e('Settings below will be ignored in preference of network settings', 'shortcodes-in-comments'); ?></label>
					</fieldset>
				</td></tr>
				<?php
				}
				?>	
				
				<tr><th scope="row"><?php esc_html_e('Allowed Shortcodes', 'shortcodes-in-comments'); ?></th><td>
					<textarea name="allowed-shortcodes" rows="10" cols="50" id="allowed-shortcodes" class="large-text code"><?php echo esc_textarea(stripslashes($options['allowed-shortcodes'])) ?></textarea>
					<p class="description"><?php esc_html_e('Enter shortcodes, separated by commas, which are to be allowed in comments.', 'shortcodes-in-comments'); ?></em>
					</p>
				</td></tr>
				
				<tr><th scope="row">&nbsp;</th>
				<td>
					azurecurve <?php esc_html_e('has a sister plugin to this one which allows shortcodes to be used in widgets:', 'shortcodes-in-comments'); ?>
					<ul class='azrcrv-plugin-index'>
						<li>
							<?php
							if (azrcrv_sic_is_plugin_active('azrcrv-shortcodes-in-widgets/azrcrv-shortcodes-in-widgets.php')){
								echo "<a href='admin.php?page=azrcrv-siw' class='azrcrv-plugin-index'>Shortcodes in Widgets</a>";
							}else{
								echo "<a href='https://development.azurecurve.co.uk/classicpress-plugins/shortcodes-in-widgets/' class='azrcrv-plugin-index'>Shortcodes in Widgets</a>";
							}
							?>
						</li>
					</ul>
				</td></tr>
				
				</table>
				
				<input type="submit" value="<?php esc_html_e('Submit', 'shortcodes-in-comments'); ?>" class="button-primary"/>
			</form>
		</fieldset>
	</div>
	<?php
}

/**
 * Save settings.
 *
 * @since 1.0.0
 *
 */
function azrcrv_sic_save_options(){
	// Check that user has proper security level
	if (!current_user_can('manage_options')){
		wp_die(esc_html__('You do not have permissions to perform this action', 'shortcodes-in-comments'));
	}
	// Check that nonce field created in configuration form is present
	if (! empty($_POST) && check_admin_referer('azrcrv-sic', 'azrcrv-sic-nonce')){
	
		// Retrieve original plugin options array
		$options = get_option('azrcrv-sic');
		
		$option_name = 'use-network';
		if (isset($_POST[$option_name])){
			$options[$option_name] = 1;
		}else{
			$options[$option_name] = 0;
		}
	
		$option_name = 'allowed-shortcodes';
		if (isset($_POST[$option_name])){
			$options[$option_name] = implode("\n", array_map('sanitize_text_field', explode("\n", $_POST[$option_name])));
		}
		
		// Store updated options array to database
		update_option('azrcrv-sic', $options);
		
		// Redirect the page to the configuration form that was processed
		wp_redirect(add_query_arg('page', 'azrcrv-sic&settings-updated', admin_url('admin.php')));
		exit;
	}
}

/**
 * Add to Network menu.
 *
 * @since 1.0.0
 *
 */
function azrcrv_sic_create_network_admin_menu(){
	if (function_exists('is_multisite') && is_multisite()){
		add_submenu_page(
						'settings.php'
						,esc_html__("Shortcodes in Comments Settings", "shortcodes-in-comments")
						,esc_html__("Shortcodes in Comments", "shortcodes-in-comments")
						,'manage_network_options'
						,'azrcrv-sic'
						,'azrcrv_sic_network_settings'
						);
	}
}

/**
 * Display network settings.
 *
 * @since 1.0.0
 *
 */
function azrcrv_sic_network_settings(){
	if (!current_user_can('manage_options')){
		$error = new WP_Error('not_found', esc_html__('You do not have sufficient permissions to access this page.' , 'azrcrv-rssf'), array('response' => '200'));
		if(is_wp_error($error)){
			wp_die($error, '', $error->get_error_data());
		}
	}
	
	// Retrieve plugin configuration options from database
	$options = get_site_option('azrcrv-sic');
	?>
	
	<div id="azrcrv-sic-general" class="wrap">
		<fieldset>
			<h1>
				<?php
					echo '<a href="https://development.azurecurve.co.uk/classicpress-plugins/"><img src="'.plugins_url('/pluginmenu/images/logo.svg', __FILE__).'" style="padding-right: 6px; height: 20px; width: 20px;" alt="azurecurve" /></a>';
					esc_html_e(get_admin_page_title());
				?>
			</h1>
			<?php if(isset($_GET['settings-updated'])){ ?>
				<div class="notice notice-success is-dismissible">
					<p><strong><?php esc_html_e('Settings have been saved.', 'shortcodes-in-comments'); ?></strong></p>
				</div>
			<?php } ?>
			
			<form method="post" action="admin-post.php">
				<input type="hidden" name="action" value="azrcrv_sic_save_network_options" />
				<input name="page_options" type="hidden" value="allowed-shortcodes" />
				
				<!-- Adding security through hidden referrer field -->
				<?php wp_nonce_field('azrcrv-sic', 'azrcrv-sic-nonce'); ?>
				
				<table class="form-table">
				
				<tr><th scope="row" colspan=2>
					<?php esc_html_e('This plugin allows shortcodes to be used in comments.', 'shortcodes-in-comments'); ?>
				</th></tr>
				
				<tr><th scope="row"><?php esc_html_e('Allowed Shortcodes', 'shortcodes-in-comments'); ?></th><td>
					<textarea name="allowed-shortcodes" rows="10" cols="50" id="allowed-shortcodes" class="large-text code"><?php echo esc_textarea(stripslashes($options['allowed-shortcodes'])) ?></textarea>
					<p class="description"><?php esc_html_e('Enter shortcodes, separated by commas, which are to be allowed in comments.', 'shortcodes-in-comments'); ?></em>
					</p>
				</td></tr>
				
				<tr><th scope="row" colspan=2>
					azurecurve <?php esc_html_e('has a sister plugin to this one which allows shortcodes to be used in widgets:', 'shortcodes-in-comments'); ?>
					<ul class='azrcrv-plugin-index'>
						<li>
							<?php
							if (azrcrv_sic_is_plugin_active('azrcrv-shortcodes-in-widgets/azrcrv-shortcodes-in-widgets.php')){
								echo "<a href='admin.php?page=azrcrv-sic' class='azrcrv-plugin-index'>Shortcodes in Widgets</a>";
							}else{
								echo "<a href='https://development.azurecurve.co.uk/classicpress-plugins/shortcodes-in-widgets/' class='azrcrv-plugin-index'>Shortcodes in Widgets</a>";
							}
							?>
						</li>
					</ul>
				</th></tr>
				
				<input type="submit" value="Save Changes" class="button-primary"/>
			</form>
		</fieldset>
	</div>
	<?php
}

/**
 * Save network settings.
 *
 * @since 1.0.0
 *
 */
function azrcrv_sic_save_network_options(){     
	if(!current_user_can('manage_network_options')){
		wp_die(esc_html__('You do not have permissions to perform this action', 'shortcodes-in-comments'));
	}
	
	if (! empty($_POST) && check_admin_referer('azrcrv-sic', 'azrcrv-sic-nonce')){
		// Retrieve original plugin options array
		$options = get_site_option('azrcrv-sic');
	
		$option_name = 'allowed-shortcodes';
		if (isset($_POST[$option_name])){
			$options[$option_name] = implode("\n", array_map('sanitize_text_field', explode("\n", $_POST[$option_name])));
		}
		
		update_site_option('azrcrv-sic', $options);

		wp_redirect(network_admin_url('settings.php?page=azrcrv-sic&settings-updated'));
		exit;  
	}
}

/**
 * Check if function active (included due to standard function failing due to order of load).
 *
 * @since 1.0.0
 *
 */
function azrcrv_sic_is_plugin_active($plugin){
    return in_array($plugin, (array) get_option('active_plugins', array()));
}

/**
 * Remove shortcodes which haven't been allowed.
 *
 * @since 1.0.0
 *
 */
function azrcrv_sic_remove_unallowed_shortcodes(){
	// get registered shortcodes
	global $shortcode_tags;
	// create temp global shortcodes variable
	global $azrcrv_sic_shortcode_tags;
	// backup registered shortcodes into temp global shortcodes variable
	$azrcrv_sic_shortcode_tags = $shortcode_tags;
	
	// get site options
	$options = get_option('azrcrv-sic');
	if (isset($options['use-network']) AND $options['use-network'] == 1){
		// if using network options, get network options
		$options = get_site_option('azrcrv-sic');
	}
	
	//explode allowed-shortcodes from options into array
	$allowed_shortcodes = explode(',', $options['allowed-shortcodes']);
	// loop through registered shortcodes
	foreach ($shortcode_tags as $shortcode => $func){
		// check if shortcode is allowed
		if (!in_array($shortcode, $allowed_shortcodes)){
			// if shortcode not allowed, remove it
			remove_shortcode($shortcode);
		}
	}

}

/**
 * Restore registered shortcodes; called after comments, but before sidebar is displayed
 *
 * @since 1.0.0
 *
 */
function azrcrv_sic_restore_all_shortcodes() {
	
	global $shortcode_tags;
	global $azrcrv_sic_shortcode_tags;

	if (!empty($azrcrv_sic_shortcode_tags)) {
		$shortcode_tags = $azrcrv_sic_shortcode_tags;
		unset($azrcrv_sic_restore_all_shortcodes);
	}
}

?>