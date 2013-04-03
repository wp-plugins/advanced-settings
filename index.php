<?php
/*
Plugin Name: Advanced Settings
Plugin URI: http://tutzstyle.com/portfolio/advanced-settings/
Description: Some advanced settings that are not provided by WordPress by default
Version: 1.4
Author: Arthur Araújo
Author URI: http://tutzstyle.com
*/

# TO IMPLEMENT
// Allow HTML in user profiles  
// remove_filter('pre_user_description', 'wp_filter_kses');  

# SETUP CONFIGS
if( $_POST && is_admin() ) {
	require_once ABSPATH.'/wp-includes/pluggable.php';
	
	if( wp_verify_nonce( $_POST['_wpnonce'], 'pc' ) )
		update_option( 'powerconfigs', $_POST );
}

# MENU
add_action('admin_menu', '__advanced_settings_menu');
function __advanced_settings_menu() {
	add_options_page('Advanced settings', 'Advanced', 'manage_options', 'advanced-settings', '__advanced_settings_page');
}

# Add plugin option in Plugins page
function __advsettings_plugin_action_links( $links, $file ) {
	if ( $file == plugin_basename( basename(dirname(__FILE__)).'/index.php' ) ) {
		$links[] = '<a href="options-general.php?page=advanced-settings">'.__('Settings').'</a>';
	}

	return $links;
}
add_filter( 'plugin_action_links', '__advsettings_plugin_action_links', 10, 2 );

$configs = get_option('powerconfigs');
#print_r($configs);

# Remove admin menu
if( isset($configs['remove_menu']) )
	add_filter('show_admin_bar' , '__return_false'); // Remove admin menu

# Configure FeedBurner
if( isset($configs['feedburner']) ) {
	function appthemes_custom_rss_feed( $output, $feed ) {
		$configs = get_option('powerconfigs');
		
		if ( strpos( $output, 'comments' ) )
			return $output;
		
		if( strpos($configs['feedburner'], '/')===FALSE )
			return esc_url( 'http://feeds.feedburner.com/'.$configs['feedburner'] );
		else
			return esc_url( $configs['feedburner'] );
	}
	add_action( 'feed_link', 'appthemes_custom_rss_feed', 10, 2 );
}

# Favicon
if( isset($configs['favicon']) ) {
	
	function __advsettings_favicon() {
		if( file_exists(TEMPLATEPATH.'/favicon.ico') )
			echo '<link rel="shortcut icon" href="'.get_bloginfo('template_url').'/favicon.ico'.'">'."\r\n";
		elseif( file_exists(TEMPLATEPATH.'/favicon.png') )
			echo '<link rel="shortcut icon" type="image/png" href="'.get_bloginfo('template_url').'/favicon.png'.'">'."\r\n";
	}
	add_action( 'wp_head', '__advsettings_favicon' );
}

# Add blog description meta tag
if( isset($configs['description']) ) {
	function __advsettings_blog_description() {
		$configs = get_option('powerconfigs');
		if(is_home() || !isset($configs['single_metas']))
			echo '<meta name="description" content="'.get_bloginfo('description').'" />'."\r\n";
	}
	add_action( 'wp_head', '__advsettings_blog_description' );
}

# Add description and keyword meta tag in posts
if( isset($configs['single_metas']) ) {
	function __advsettings_single_metas() {
		global $post;
		if( is_single() || is_page() ) {
			
			$tag_list = get_the_terms( $post->ID, 'post_tag' );
			
			if( $tag_list ) {
				foreach( $tag_list as $tag )
					$tag_array[] = $tag->name;
				echo '<meta name="keywords" content="'.implode(', ', $tag_array).'" />'."\r\n";
			}
				
			$excerpt = strip_tags($post->post_content);
			$excerpt = strip_shortcodes($excerpt);
			$excerpt = str_replace(array('\n', '\r', '\t'), ' ', $excerpt);
			$excerpt = substr($excerpt, 0, 125);
			if( !empty($excerpt) )
				echo '<meta name="description" content="'.$excerpt.'" />'."\r\n";
		}
	}
	add_action( 'wp_head', '__advsettings_single_metas' );
}

# Remove header generator
if( isset($configs['remove_generator']) )
	remove_action('wp_head', 'wp_generator');

# Remove WLW
if( isset($configs['remove_wlw']) )
	remove_action('wp_head', 'wlwmanifest_link');

# Remove update message from admin
if( isset($configs['remove_update_msg']) ) {
	null;
}

# Thumbnails support
if( isset($configs['add_thumbs']) )
	add_theme_support( 'post-thumbnails' );

# JPEG Quality
if( isset($configs['jpeg_quality']) && $_SERVER['HTTP_HOST']!='localhost' ) {
	add_filter('jpeg_quality', '____jpeg_quality');
	function ____jpeg_quality(){ $configs = get_option('powerconfigs'); return $configs['jpeg_quality']; }
}

# REL External
if( isset($configs['rel_external']) ) {
	function ____replace_targets( $content ) {
		$content = str_replace('target="_self"', '', $content);
		return str_replace('target="_blank"', 'rel="external"', $content);
	}
	add_filter( 'the_content', '____replace_targets' );
}

# Fix post type pagination
if( isset($configs['post_type_pag']) ) {
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

# REL External
if( isset($configs['disable_auto_save']) ) {
	function __advsettings_disable_auto_save(){  
		wp_deregister_script('autosave');  
	}  
	add_action( 'wp_print_scripts', '__advsettings_disable_auto_save' );  
}

# Remove wptexturize
if( isset($configs['remove_wptexturize']) ) {
	remove_filter('the_content', 'wptexturize');
	remove_filter('comment_text', 'wptexturize');
	remove_filter('the_excerpt', 'wptexturize');
}

# Filtering the code
if( isset($configs['compress']) || isset($configs['remove_comments']) ) {
	add_action('template_redirect','____template');
	function ____template() { ob_start('____template2'); }
	function ____template2($code) {
		$configs = get_option('powerconfigs');
		
		if( $configs['remove_comments'] )
			$code = preg_replace('/<!--(.|\s)*?-->/', '', $code); 

		if( $configs['compress'] )
			$code = trim( preg_replace( '/\s+/', ' ', $code ) );

		/* Acentos */
		#$code = str_encode( $code );

		return $code;
		
	}
}

# Google Analytics
if( isset($configs['analytics']) ) {
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

# Remove admin menu
if( isset($configs['show_query_num']) ) {
	function __show_sql_query_num(){
		
		if( !current_user_can('manage_options') )
			return;
		
		global $wpdb;
		
		echo '<div style="font-size:10px;text-align:center">'.
				$wpdb->num_queries.' '.__('SQL queries have been executed to show this page in ').
				timer_stop().__('seconds').
			'</div>';
	}
	add_action('wp_footer', '__show_sql_query_num');
}

# Remove [...] from the excerpt
/*if( $configs['remove_etc'] ) {
	function __trim_excerpt( $text ) {
		return rtrim( $text, '[...]' );
	}
	add_filter('get_the_excerpt', '__trim_excerpt');
}*/

# author_bio
if( isset($configs['author_bio']) ) {
	function __get_author_bio ($content=''){
		return  '<div id="entry-author-info">
					<div id="author-avatar">
						'. get_avatar( get_the_author_meta( 'user_email' ), apply_filters( 'twentyten_author_bio_avatar_size', 60 ) ) .'
					</div>
					<div id="author-description">
						<h2>'. sprintf( __( 'About %s' ), get_the_author() ) .'</h2>
						'. get_the_author_meta( 'description' ) .'
						<div id="author-link">
							<a href="'. get_author_posts_url( get_the_author_meta( 'ID' ) ) .'">
								'. sprintf( __( 'View all posts by %s <span class="meta-nav">&rarr;</span>', 'twentyten' ), get_the_author() ) .'
							</a>
						</div>
					</div>
				</div>';
	}
	add_filter('the_content', '__get_author_bio');
}


// -----------------------------------------------------------------------


# THE PAGE
function __advanced_settings_page() { $configs = get_option('powerconfigs'); ?>
	
	<div class="wrap">

		<div style="float:right;width:400px">
			<div style="float:right; margin-top:10px">
				 <iframe src="http://www.facebook.com/plugins/like.php?href=<?php echo urlencode('http://wordpress.org/extend/plugins/advanced-settings/') ?>&amp;layout=button_count&amp;show_faces=false&amp;width=450&amp;action=like&amp;font=arial&amp;colorscheme=light&amp;height=21" scrolling="no" frameborder="0" style="overflow:hidden; width:90px; height:21px; margin:0 0 0 10px; float:right" allowTransparency="true"></iframe> <strong style="line-height:25px;"><?php echo __('Do you like Advanced Settings Plugin? '); ?></strong>
			</div>
		</div>

		<div id="icon-options-general" class="icon32"><br></div>
		<h2>Advanced settings</h2>
		
		<form action="" method="post">
			
			<?php wp_nonce_field('pc'); ?>
			
	<h3 class="title">Header</h3>
			
			<label for="remove_menu">

				<input name="remove_menu" type="checkbox" id="remove_menu" value="1" <?php if( isset($configs['remove_menu'])) echo 'checked="checked"' ?>>
				Hide admin menu </label>
			
			<br />
			<label for="favicon">
				<input name="favicon" type="checkbox" id="favicon" value="1" <?php if(isset($configs['favicon'])) echo 'checked="checked"' ?> />
				Automatically add a FavIcon <i style="color:#999">(when there is a favicon.ico or favicon.png file in the template folder)</i></label>
				</label>
			
			<br />
			<label for="description">
				<input name="description" type="checkbox" id="description" value="1" <?php if(isset($configs['description'])) echo 'checked="checked"' ?> />
				Get the blog description and add a description meta tag
				</label>
			
			<br />
			<label for="single_metas">
				<input name="single_metas" type="checkbox" id="single_metas" value="1" <?php if(isset($configs['single_metas'])) echo 'checked="checked"' ?> />
				Add description and keywords meta tags in posts (SEO)
				</label>
			
			<br />
			<label for="remove_generator">
				<input name="remove_generator" type="checkbox" id="remove_generator" value="1" <?php if(isset($configs['remove_generator'])) echo 'checked="checked"' ?> />
				Remove header WordPress generator meta tag (html)</label>
			
			<br />
			<label for="remove_wlw">
				<input name="remove_wlw" type="checkbox" id="remove_wlw" value="1" <?php if(isset($configs['remove_wlw'])) echo 'checked="checked"' ?> />
				Remove header WLW Manifest meta tag (Windows Live Writer link)</label>
			
			<br />
	<h3 class="title">Images</h3>
			
			<label for="add_thumbs">
				<input name="add_thumbs" type="checkbox" id="add_thumbs" value="1" <?php if(isset($configs['add_thumbs'])) echo 'checked="checked"' ?> />
				Add thumbnail support</label>
			
			<br />
			<label for="jpeg_quality">
				Set JPEG quality to <input name="jpeg_quality" type="text" size="2" maxlength="3" id="jpeg_quality" value="<?php echo (int)$configs['jpeg_quality'] ?>" /> <i style="color:#999">(when send and resize images)</i></label>
			
			<br />
			
	<h3 class="title">Contents</h3>
			
			<label for="author_bio">
				<input name="author_bio" type="checkbox" id="author_bio" value="1" <?php if(isset($configs['author_bio'])) echo 'checked="checked"' ?> />
				Insert author bio on each post</label>			
			
			<!--br />
			<label for="remove_etc">
				<input name="remove_etc" type="checkbox" id="remove_etc" value="1" <?php if(isset($configs['remove_etc'])) echo 'checked="checked"' ?> />
				Remove the [...] from the excerpt</label>
			
			<!--br />
	<h3 class="title">Contents</h3>
			
			<label for="rel_external">
				<input name="rel_external" type="checkbox" id="rel_external" value="1" <?php if(isset($configs['rel_external'])) echo 'checked="checked"' ?> />
				<span style="color:red">COLOCAR JAVASCRIPT</span> Replaces <span style="color:red">target="_blank"</span> to <span style="color:red">rel="external"</span> <i style="color:#999">(this is for W3C validator, a javascript code replace to target="_blank" again)</i>
				</label-->
			
			<br />
	<h3 class="title">System</h3>
			
			<label for="post_type_pag">
				<input name="post_type_pag" type="checkbox" id="post_type_pag" value="1" <?php if(isset($configs['post_type_pag'])) echo 'checked="checked"' ?> />
				Fix post type pagination
				</label>
			
			<br />
			<label for="disable_auto_save">
				<input name="disable_auto_save" type="checkbox" id="disable_auto_save" value="1" <?php if(isset($configs['disable_auto_save'])) echo 'checked="checked"' ?> />
				Disable Posts Auto Saving
				</label>
			
			<br />
			<label for="feedburner">
				FeedBurner: <input name="feedburner" type="text" size="12" id="feedburner" value="<?php if(isset($configs['feedburner'])) echo $configs['feedburner'] ?>" />
				</label>
			
			<br />
			
	<h3 class="title">HTML Code output</h3>
			
			<label for="compress">
				<input name="compress" type="checkbox" id="compress" value="1" <?php if(isset($configs['compress'])) echo 'checked="checked"' ?> />
				Compress all code
				</label>
			
			<br />
			<label for="remove_wptexturize">
				<input name="remove_wptexturize" type="checkbox" id="remove_wptexturize" value="1" <?php if(isset($configs['remove_wptexturize'])) echo 'checked="checked"' ?> />
				Remove "texturize" <i style="color:#999">(transformations of quotes to smart quotes, apostrophes, dashes, ellipses, the trademark symbol, and the multiplication symbol)</i>
				</label>
			
			<br />
			<label for="remove_comments">
				<input name="remove_comments" type="checkbox" id="remove_comments" value="1" <?php if(isset($configs['remove_comments'])) echo 'checked="checked"' ?> />
				Remove all HTML comments
				</label>
			
			<br />
	<h3 class="title">Footer</h3>
			
			<label for="show_query_num">
				<input name="show_query_num" type="checkbox" id="show_query_num" value="1" <?php if(isset($configs['show_query_num'])) echo 'checked="checked"' ?> />
				Display total number of executed SQL queries and page loading time <i style="color:#999">(only admin users can see this)</i>
				</label>
			
			<br />
			<label for="analytics">
				Google Analytics ID: <input name="analytics" type="text" size="12" id="analytics" value="<?php echo $configs['analytics'] ?>" />
				<i style="color:#999">(inserts a javascript code in the footer)</i>
				</label>
			
			<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="<?= _('Save changes') ?>"></p>
		</form>
	</div>
	<?
}

?>
