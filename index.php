<?php
/*
Plugin Name: Advanced Settings
Plugin URI: http://tutzstyle.com/portfolio/advanced-settings/
Description: Some of settings to WordPress.
Version: 0.9
Author: Arthur Araújo
Author URI: http://tutzstyle.com
*/

# SETUP CONFIGS
if( $_POST && is_admin() ) {
	require_once ABSPATH.'/wp-includes/pluggable.php';
	
	if( wp_verify_nonce( $_POST['_wpnonce'], 'pc' ) )
		update_option( 'powerconfigs', $_POST );
}

$configs = get_option('powerconfigs');
#print_r($configs);

# Remove admin menu
if( $configs['remove_menu'] )
	add_filter('show_admin_bar' , '__return_false'); // Remove admin menu

# Remove header generator
if( $configs['remove_generator'] )
	remove_action('wp_head', 'wp_generator');

# Remove WLW
if( $configs['remove_wlw'] )
	remove_action('wp_head', 'wlwmanifest_link');

# Remove update message from admin
if( $configs['remove_update_msg'] ) {
	null;
}

# Thumbnails support
if( $configs['add_thumbs'] )
	add_theme_support( 'post-thumbnails' );

# JPEG Quality
if( $configs['jpeg_quality'] && $_SERVER['HTTP_HOST']!='localhost' ) {
	add_filter('jpeg_quality', '____jpeg_quality');
	function ____jpeg_quality(){ $configs = get_option('powerconfigs'); return $configs['jpeg_quality']; }
}

# REL External
if( $configs['rel_external'] ) {
	function ____replace_targets( $content ) {
		$content = str_replace('target="_self"', '', $content);
		return str_replace('target="_blank"', 'rel="external"', $content);
	}
	add_filter( 'the_content', '____replace_targets' );
}

# REL External
if( $configs['post_type_pag'] ) {
	# following are code adapted from Custom Post Type Category Pagination Fix by jdantzer
	function fix_category_pagination($qs){
		if(isset($qs['category_name']) && isset($qs['paged'])){
			$qs['post_type'] = get_post_types($args = array(
				'public'   => true,
				'_builtin' => false
			));
			array_push($qs['post_type'],'post');
		}
		return $qs;
	}
	add_filter('request', 'fix_category_pagination');
}

# Filtering the code
if( $configs['compress'] || $configs['remove_comments'] ) {
	add_action('template_redirect','____template');
	function ____template() { ob_start('____template2'); }
	function ____template2($code) {
		$configs = get_option('powerconfigs');
		
		if( $configs['remove_comments'] )
			$code = preg_replace('/<!--(.|\s)*?-->/', '', $code); 

		if( $configs['compress'] )
			$code = trim_code( $code );

		/* Acentos */
		#$code = str_encode( $code );

		return $code;
		
	}
}

# Google Analytics
if( $configs['analytics'] ) {
	add_action('wp_footer', '____analytics'); // Load custom styles
	function ____analytics(){ 
		$configs = get_option('powerconfigs');
		echo '<script type="text/javascript">

var _gaq = _gaq || [];
_gaq.push([\'_setAccount\', \''.$configs['analytics'].'\']);
_gaq.push([\'_trackPageview\']);

(function() {
var ga = document.createElement(\'script\'); ga.type = \'text/javascript\'; ga.async = true;
ga.src = (\'https:\' == document.location.protocol ? \'https://ssl\' : \'http://www\') + \'.google-analytics.com/ga.js\';
var s = document.getElementsByTagName(\'script\')[0]; s.parentNode.insertBefore(ga, s);
})();

</script>';
	}
}

# MENU
add_action('admin_menu', 'powerconfigs_menu');
function powerconfigs_menu() {
	add_options_page('Advanced settings', 'Advanced', 'manage_options', 'power-configs-plugin', 'powerconfigs_page');
}

# THE PAGE
function powerconfigs_page() { $configs = get_option('powerconfigs'); ?>
	
	<div id="icon-options-general" class="icon32"><br></div>
	<h2>Advanced settings</h2>
	
	<form action="" method="post">
		
		<?php wp_nonce_field('pc'); ?>
		
<h3 class="title">Header</h3>
		
		<label for="remove_menu">

			<input name="remove_menu" type="checkbox" id="remove_menu" value="1" <?php if($configs['remove_menu']) echo 'checked="checked"' ?>>
			Hide admin menu </label>
		
		<!--br />
		<label for="remove_update_msg">
			<input name="remove_update_msg" type="checkbox" id="remove_update_msg" value="1" <?php if($configs['remove_update_msg']) echo 'checked="checked"' ?> />
			<span style="color:red">NÃO FUNCIONA</span> Hide update message from admin</label-->
		
		<br />
		<label for="remove_generator">
			<input name="remove_generator" type="checkbox" id="remove_generator" value="1" <?php if($configs['remove_generator']) echo 'checked="checked"' ?> />
			Remove header WordPress generator tag (html)</label>
		
		<br />
		<label for="remove_wlw">
			<input name="remove_wlw" type="checkbox" id="remove_wlw" value="1" <?php if($configs['remove_wlw']) echo 'checked="checked"' ?> />
			Remove header WLW Manifest tag (html)</label>
		
		<br />
<h3 class="title">Images</h3>
		
		<label for="add_thumbs">
			<input name="add_thumbs" type="checkbox" id="add_thumbs" value="1" <?php if($configs['add_thumbs']) echo 'checked="checked"' ?> />
			Add thumbnail support</label>
		
		<br />
		<label for="jpeg_quality">
			Set JPEG quality to <input name="jpeg_quality" type="text" size="2" maxlength="3" id="jpeg_quality" value="<?php echo (int)$configs['jpeg_quality'] ?>" /> <i style="color:#999">(when send and resize images)</i></label>
		
		<!--br />
<h3 class="title">Contents</h3>
		
		<label for="rel_external">
			<input name="rel_external" type="checkbox" id="rel_external" value="1" <?php if($configs['rel_external']) echo 'checked="checked"' ?> />
			<span style="color:red">COLOCAR JAVASCRIPT</span> Replaces <span style="color:red">target="_blank"</span> to <span style="color:red">rel="external"</span> <i style="color:#999">(this is for W3C validator, a javascript code replace to target="_blank" again)</i>
			</label-->
		
		<br />
<h3 class="title">System</h3>
		
		<label for="post_type_pag">
			<input name="post_type_pag" type="checkbox" id="post_type_pag" value="1" <?php if($configs['post_type_pag']) echo 'checked="checked"' ?> />
			Fix post type pagination
			</label>
		
		<br />
<h3 class="title">HTML Code output</h3>
		
		<label for="compress">
			<input name="compress" type="checkbox" id="compress" value="1" <?php if($configs['compress']) echo 'checked="checked"' ?> />
			Compress all code
			</label>
		
		<br />
		<label for="remove_comments">
			<input name="remove_comments" type="checkbox" id="remove_comments" value="1" <?php if($configs['remove_comments']) echo 'checked="checked"' ?> />
			Remove all HTML comments
			</label>
		
		<br />
<h3 class="title">Footer</h3>
		
		<label for="analytics">
			Google Analytics ID: <input name="analytics" type="text" size="12" id="analytics" value="<?php echo $configs['analytics'] ?>" />
			<i style="color:#999">(insert a javascript code in the footer)</i>
			</label>
		
		<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="<?= _('Save changes') ?>"></p>
	</form>
	
	<?
}

?>

