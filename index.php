<?php
/*
Plugin Name: Advanced Settings
Plugin URI: http://tutzstyle.com/portfolio/advanced-settings/
Description: Some advanced settings that are not provided by WordPress
Author: Arthur Araújo
Author URI: http://tutzstyle.com
Version: 1.5
*/

# TO IMPLEMENT
// Allow HTML in user profiles  
// remove_filter('pre_user_description', 'wp_filter_kses');  

if( is_admin() ) {

	# Admin menu
	add_action('admin_menu', 'advset_menu');

	# Add plugin option in Plugins page
	add_filter( 'plugin_action_links', 'advset_plugin_action_links', 10, 2 );

	// update settings
	if( isset($_POST['option_page']) && $_POST['option_page']=='advanced-settings' ) {
	
		function advset_update() {
			
			$_POST['powerconfigs'] = $_POST;
			unset(
				$_POST['powerconfigs']['option_page'],
				$_POST['powerconfigs']['action'],
				$_POST['powerconfigs']['_wpnonce'],
				$_POST['powerconfigs']['_wp_http_referer'],
				$_POST['powerconfigs']['submit']
			);
			
			if( $_POST['powerconfigs']['auto_thumbs'] )
				$_POST['powerconfigs']['add_thumbs'] = '1';
			
			// save fields
			register_setting( 'advanced-settings', 'powerconfigs' );
			
		}
		add_action( 'admin_init', 'advset_update' );
	}
	
}

function advset_option( $option_name, $default='' ) {
	global $advset_options;
	
	if( !isset($advset_options) )
		$advset_options = get_option('powerconfigs');
	
	if( isset($advset_options[$option_name]) )
		return $advset_options[$option_name];
	else
		return $default;
}

function advset_check_if( $option_name ) {
	if ( advset_option( $option_name, 0 ) )
		echo ' checked="checked"';
}

function __show_sqlnum() {
	global $wpdb, $user_ID;
	if($user_ID==2)
		echo $wpdb->num_queries;
}

# ADMIN MENU
function advset_menu() {
	add_options_page('Advanced settings', 'Advanced', 'manage_options', 'advanced-settings', 'advset_page');
}

# Add plugin option in Plugins page
function advset_plugin_action_links( $links, $file ) {
	if ( $file == plugin_basename( basename(dirname(__FILE__)).'/index.php' ) ) {
		$links[] = '<a href="options-general.php?page=advanced-settings">'.__('Settings').'</a>';
	}

	return $links;
}

//$configs = get_option('powerconfigs');
#print_r($configs);

# Remove admin menu
if( advset_option('remove_menu') )
	add_filter('show_admin_bar' , '__return_false'); // Remove admin menu

# Configure FeedBurner
if( advset_option('feedburner') ) {
	function appthemes_custom_rss_feed( $output, $feed ) {
		//$configs = get_option('powerconfigs');
		
		if ( strpos( $output, 'comments' ) )
			return $output;
		
		if( strpos(advset_option('feedburner'), '/')===FALSE )
			return esc_url( 'http://feeds.feedburner.com/'.advset_option('feedburner') );
		else
			return esc_url( advset_option('feedburner') );
	}
	add_action( 'feed_link', 'appthemes_custom_rss_feed', 10, 2 );
}

# Favicon
if( advset_option('favicon') ) {
	
	function __advsettings_favicon() {
		if( file_exists(TEMPLATEPATH.'/favicon.ico') )
			echo '<link rel="shortcut icon" href="'.get_bloginfo('template_url').'/favicon.ico'.'">'."\r\n";
		elseif( file_exists(TEMPLATEPATH.'/favicon.png') )
			echo '<link rel="shortcut icon" type="image/png" href="'.get_bloginfo('template_url').'/favicon.png'.'">'."\r\n";
	}
	add_action( 'wp_head', '__advsettings_favicon' );
}

# Add blog description meta tag
if( advset_option('description') ) {
	function __advsettings_blog_description() {
		//$configs = get_option('powerconfigs');
		if(is_home() || !advset_option('single_metas'))
			echo '<meta name="description" content="'.get_bloginfo('description').'" />'."\r\n";
	}
	add_action( 'wp_head', '__advsettings_blog_description' );
}

# Add description and keyword meta tag in posts
if( advset_option('single_metas') ) {
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
if( advset_option('remove_generator') )
	remove_action('wp_head', 'wp_generator');

# Remove WLW
if( advset_option('remove_wlw') )
	remove_action('wp_head', 'wlwmanifest_link');

# Thumbnails support
if( advset_option('add_thumbs') ) {
	add_theme_support( 'post-thumbnails' );
	if( !current_theme_supports('post-thumbnails') )
		define( 'ADVSET_THUMBS', '1' );
}

# JPEG Quality
if( advset_option('jpeg_quality', 0)>0 && $_SERVER['HTTP_HOST']!='localhost' ) {
	add_filter('jpeg_quality', '____jpeg_quality');
	function ____jpeg_quality(){ return (int) advset_option('jpeg_quality'); }
}

# REL External
if( advset_option('rel_external') ) {
	function ____replace_targets( $content ) {
		$content = str_replace('target="_self"', '', $content);
		return str_replace('target="_blank"', 'rel="external"', $content);
	}
	add_filter( 'the_content', '____replace_targets' );
}

# Fix post type pagination
if( advset_option('post_type_pag') ) {
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
if( advset_option('disable_auto_save') ) {
	function __advsettings_disable_auto_save(){  
		wp_deregister_script('autosave');  
	}  
	add_action( 'wp_print_scripts', '__advsettings_disable_auto_save' );  
}

# Remove wptexturize
if( advset_option('remove_wptexturize') ) {
	remove_filter('the_content', 'wptexturize');
	remove_filter('comment_text', 'wptexturize');
	remove_filter('the_excerpt', 'wptexturize');
}

# Filtering the code
if( advset_option('compress') || advset_option('remove_comments') ) {
	add_action('template_redirect','____template');
	function ____template() { ob_start('____template2'); }
	function ____template2($code) {
		
		if( advset_option('remove_comments') )
			$code = preg_replace('/<!--(.|\s)*?-->/', '', $code); 

		if( advset_option('compress') )
			$code = trim( preg_replace( '/\s+(?![^<>]*<\/pre>)/', ' ', $code ) );

		/* Acentos */
		#$code = str_encode( $code );

		return $code;
		
	}
}

# Remove comments system
if( advset_option('remove_comments_system') ) {
	function __av_comments_close( $open, $post_id ) {

		#$post = get_post( $post_id );
		#if ( 'page' == $post->post_type )
			#$open = false;

		return false;
	}
	add_filter( 'comments_open', '__av_comments_close', 10, 2 );
	
	function __av_empty_comments_array( $open, $post_id ) {
		return array();
	}
	add_filter( 'comments_array', '__av_empty_comments_array', 10, 2 );

	// Removes from admin menu
	function __av_remove_admin_menus() {
		remove_menu_page( 'edit-comments.php' );
	}
	add_action( 'admin_menu', '__av_remove_admin_menus' );
	
	// Removes from admin bar
	function __av_admin_bar_render() {
		global $wp_admin_bar;
		$wp_admin_bar->remove_menu('comments');
	}
	add_action( 'wp_before_admin_bar_render', '__av_admin_bar_render' );
}
	
# Google Analytics
if( advset_option('analytics') ) {
	add_action('wp_footer', '____analytics'); // Load custom styles
	function ____analytics(){ 
		//$configs = get_option('powerconfigs');
		echo '<script type="text/javascript">
var _gaq = _gaq || [];_gaq.push([\'_setAccount\', \''.advset_option('analytics').'\']);_gaq.push([\'_trackPageview\']);
(function() {
var ga = document.createElement(\'script\'); ga.type = \'text/javascript\'; ga.async = true;
ga.src = (\'https:\' == document.location.protocol ? \'https://ssl\' : \'http://www\') + \'.google-analytics.com/ga.js\';
var s = document.getElementsByTagName(\'script\')[0]; s.parentNode.insertBefore(ga, s);
})();
</script>';
	}
}

# Remove admin menu
if( advset_option('show_query_num') ) {
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
if( advset_option('author_bio') ) {
	function advset_author_bio ($content=''){
		return $content.' <div id="entry-author-info">
					<div id="author-avatar">
						'. get_avatar( get_the_author_meta( 'user_email' ), apply_filters( 'author_bio_avatar_size', 100 ) ) .'
					</div>
					<div id="author-description">
						<h2>'. sprintf( __( 'About %s' ), get_the_author() ) .'</h2>
						'. get_the_author_meta( 'description' ) .'
						<div id="author-link">
							<a href="'. get_author_posts_url( get_the_author_meta( 'ID' ) ) .'">
								'. sprintf( __( 'View all posts by %s <span class="meta-nav">&rarr;</span>' ), get_the_author() ) .'
							</a>
						</div>
					</div>
				</div>';
	}
	add_filter('the_content', 'advset_author_bio');
}

# author_bio
if( advset_option('author_bio_html') )
	remove_filter('pre_user_description', 'wp_filter_kses');

# auto post thumbnails
if( advset_option('auto_thumbs') ) {
	
	// based on "auto posts plugin" 3.3.2
	
	// check post status
	function advset_check_post_status( $new_status='' ) {
		global $post_ID;
		
		if ('publish' == $new_status)
			advset_publish_post($post_ID);
	}
	
	//
	function advset_publish_post( $post_id ) {
		global $wpdb;

		// First check whether Post Thumbnail is already set for this post.
		if (get_post_meta($post_id, '_thumbnail_id', true) || get_post_meta($post_id, 'skip_post_thumb', true))
			return;

		$post = $wpdb->get_results("SELECT * FROM {$wpdb->posts} WHERE id = $post_id");

		// Initialize variable used to store list of matched images as per provided regular expression
		$matches = array();

		// Get all images from post's body
		preg_match_all('/<\s*img [^\>]*src\s*=\s*[\""\']?([^\""\'>]*)/i', $post[0]->post_content, $matches);

		if (count($matches)) {
			foreach ($matches[0] as $key => $image) {
				/**
				 * If the image is from wordpress's own media gallery, then it appends the thumbmail id to a css class.
				 * Look for this id in the IMG tag.
				 */
				preg_match('/wp-image-([\d]*)/i', $image, $thumb_id);
				$thumb_id = $thumb_id[1];

				// If thumb id is not found, try to look for the image in DB. Thanks to "Erwin Vrolijk" for providing this code.
				if (!$thumb_id) {
					$image = substr($image, strpos($image, '"')+1);
					$result = $wpdb->get_results("SELECT ID FROM {$wpdb->posts} WHERE guid = '".$image."'");
					$thumb_id = $result[0]->ID;
				}

				// Ok. Still no id found. Some other way used to insert the image in post. Now we must fetch the image from URL and do the needful.
				if (!$thumb_id) {
					$thumb_id = advset_generate_post_thumbnail($matches, $key, $post[0]->post_content, $post_id);
				}

				// If we succeed in generating thumg, let's update post meta
				if ($thumb_id) {
					update_post_meta( $post_id, '_thumbnail_id', $thumb_id );
					break;
				}
			}
		}
	}
	
	
	function advset_generate_post_thumbnail( $matches, $key, $post_content, $post_id ) {
		// Make sure to assign correct title to the image. Extract it from img tag
		$imageTitle = '';
		preg_match_all('/<\s*img [^\>]*title\s*=\s*[\""\']?([^\""\'>]*)/i', $post_content, $matchesTitle);

		if (count($matchesTitle) && isset($matchesTitle[1])) {
			$imageTitle = $matchesTitle[1][$key];
		}

		// Get the URL now for further processing
		$imageUrl = $matches[1][$key];

		// Get the file name
		$filename = substr($imageUrl, (strrpos($imageUrl, '/'))+1);

		if ( !(($uploads = wp_upload_dir(current_time('mysql')) ) && false === $uploads['error']) )
			return null;

		// Generate unique file name
		$filename = wp_unique_filename( $uploads['path'], $filename );

		// Move the file to the uploads dir
		$new_file = $uploads['path'] . "/$filename";

		if (!ini_get('allow_url_fopen'))
			$file_data = curl_get_file_contents($imageUrl);
		else
			$file_data = @file_get_contents($imageUrl);

		if (!$file_data) {
			return null;
		}

		file_put_contents($new_file, $file_data);

		// Set correct file permissions
		$stat = stat( dirname( $new_file ));
		$perms = $stat['mode'] & 0000666;
		@ chmod( $new_file, $perms );

		// Get the file type. Must to use it as a post thumbnail.
		$wp_filetype = wp_check_filetype( $filename, $mimes );

		extract( $wp_filetype );

		// No file type! No point to proceed further
		if ( ( !$type || !$ext ) && !current_user_can( 'unfiltered_upload' ) ) {
			return null;
		}

		// Compute the URL
		$url = $uploads['url'] . "/$filename";

		// Construct the attachment array
		$attachment = array(
			'post_mime_type' => $type,
			'guid' => $url,
			'post_parent' => null,
			'post_title' => $imageTitle,
			'post_content' => '',
		);

		$thumb_id = wp_insert_attachment($attachment, $file, $post_id);
		if ( !is_wp_error($thumb_id) ) {
			require_once(ABSPATH . '/wp-admin/includes/image.php');

			// Added fix by misthero as suggested
			wp_update_attachment_metadata( $thumb_id, wp_generate_attachment_metadata( $thumb_id, $new_file ) );
			update_attached_file( $thumb_id, $new_file );

			return $thumb_id;
		}

		return null;
   	}

	add_action('transition_post_status', 'advset_check_post_status');
	
	if( !function_exists('curl_get_file_contents') ) {
		
		function curl_get_file_contents($URL) {
			$c = curl_init();
			curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($c, CURLOPT_URL, $URL);
			$contents = curl_exec($c);
			curl_close($c);

			if ($contents) {
				return $contents;
			}

			return FALSE;
		}
		
	}
	
}


# author_bio
if( $_POST && (advset_option('max_image_size_w')>0 || advset_option('max_image_size_h')>0) ) {
	
	// From "Resize at Upload Plus" 1.3
	
	/* This function will apply changes to the uploaded file */
	function advset_resize_image( $array ) { 
	  // $array contains file, url, type
	  if ($array['type'] == 'image/jpeg' OR $array['type'] == 'image/gif' OR $array['type'] == 'image/png') {
		// there is a file to handle, so include the class and get the variables
		require_once( dirname(__FILE__).'/class.resize.php' );
		$maxwidth = advset_option('max_image_size_w');
		$maxheight = advset_option('max_image_size_h');
		$imagesize = getimagesize($array['file']); // $imagesize[0] = width, $imagesize[1] = height
		
		if ( $maxwidth == 0 OR $maxheight == 0) {
			if ($maxwidth==0) {
				$objResize = new RVJ_ImageResize($array['file'], $array['file'], 'H', $maxheight);
			}
			if ($maxheight==0) {
				$objResize = new RVJ_ImageResize($array['file'], $array['file'], 'W', $maxwidth);
			}
		} else {	
			if ( ($imagesize[0] >= $imagesize[1]) AND ($maxwidth * $imagesize[1] / $imagesize[0] <= $maxheight) )  {
				$objResize = new RVJ_ImageResize($array['file'], $array['file'], 'W', $maxwidth);
			} else {
				$objResize = new RVJ_ImageResize($array['file'], $array['file'], 'H', $maxheight);
			}
		}
	  } // if
	  return $array;
	} // function
	add_action('wp_handle_upload', 'advset_resize_image');
	
}


// translate to pt_BR
if( is_admin() && defined('WPLANG') && WPLANG=='pt_BR' ) {
	add_filter( 'gettext', 'advset_translate', 10, 3 );
	global $advset_ptbr;
	
	$advset_ptbr = array(
		'Save changes' => 'Salvar alterações',
		'width' => 'largura',
		'height' => 'altura',
		'Contents' => 'Conteúdo',
		'System' => 'Sistema',
		'HTML Code output' => 'Saída do código HTML',
		'Hide top admin menu' => 'Esconde menu de administrador do topo',
		'Automatically add a FavIcon' => 'Adicionar um FavIcon automático para a página',
		'when there is a favicon.ico or favicon.png file in the template folder' => 'sempre que houver um arquivo favicon.ico ou favicon.png na pasta do modelo',
		'Add a description meta tag using the blog description' => 'Adicionar uma meta tag de descrição usando a descrição do blog',
		'Add description and keywords meta tags in each posts' => 'Adicionar uma meta tags de descrição e palavras-chave em cada post',
		'Remove header WordPress generator meta tag' => 'Remover meta tag de "gerado pelo WordPress"',
		'Remove header WLW Manifest meta tag' => 'Remover meta tag WLW Manifest',
		'Current theme already has post thumbnail support' => 'Tema atual já tem suporte a imagem destacada (thumbnails)',
		'Automatically generate the Post Thumbnail' => 'Gerar imagem destacada automaticamente',
		'from the first image in post' => 'gera a partir da primeira imagem encontrada no post',
		'Set JPEG quality to' => 'Alterar qualidade do JPEG para',
		'when send and resize images' => 'no momento em que envia ou redimensiona imagens',
		'Resize image at upload to max size' => 'Redimensionar a imagem no upload no tamanho máximo',
		'if zero resize to max height or dont resize if both is zero' => 'Se zero, redimenciona para largura máxima ou nada faz se os dois valores forem zero',
		'if zero resize to max width or dont resize if both is zero' => 'Se zero, redimenciona para altura máxima ou nada faz se os dois valores forem zero',
		'Insert author bio in each post' => 'Adicionar descrição do autor em cada post',
		'Remove comments system' => 'Remover sistema de comentários',
		'Fix post type pagination' => 'Corrige paginação de "post types"',
		'Disable Posts Auto Saving' => 'Desabilita função de auto-salvar',
		'Compress all code' => 'Comprime todo o código',
		'transformations of quotes to smart quotes, apostrophes, dashes, ellipses, the trademark symbol, and the multiplication symbol' => 'estilização de áspas, apóstrofos, elípses, traços, e multiplicação dos símbolos',
		'Remove all HTML comments' => 'Remover todos os comentários em HTML',
		'Display total number of executed SQL queries and page loading time' => 'Mostrar o total de SQLs executadas e o tempo de carregamento da página',
		'only admin users can see this' => 'apenas administradores poderão ver',
		'inserts a javascript code in the footer' => 'adicionar um código em javascript no final do código HTML',
		'Allow HTML in user profiles' => 'Permitir códigos HTML na descrição de perfil dos usuários',
		//'' => '',
	);
}

function advset_translate( $text ) {
	
	global $advset_ptbr;
	
	$array = $advset_ptbr;
	
    if( isset($array[$text]) )
		return $array[$text];
	else
		return $text;
}


// -----------------------------------------------------------------------


# THE AMIND PAGE
function advset_page() { //$configs = get_option('powerconfigs'); ?>
	
	<div class="wrap">
		
		<?php
			$external_plugin_name = 'Advanced Settings';
			$external_plugin_url = 'http://tutzstyle.com/portfolio/advanced-settings/';
		?>
		<div style="float:right;width:400px">
			<div style="float:right; margin-top:10px">
				 <iframe src="http://www.facebook.com/plugins/like.php?href=<?php echo urlencode($external_plugin_url) ?>&amp;layout=box_count&amp;show_faces=false&amp;width=450&amp;action=like&amp;font=arial&amp;colorscheme=light&amp;height=21"
					scrolling="no" frameborder="0" style="overflow:hidden; width:90px; height:61px; margin:0 0 0 10px; float:right" allowTransparency="true"></iframe>
					<strong style="line-height:25px;">
						<?php echo __("Do you like <a href=\"{$external_plugin_url}\" target=\"_blank\">{$external_plugin_name}</a> Plugin? "); ?>
					</strong>
			</div>
		</div>
		
		<div id="icon-options-general" class="icon32"><br></div>
		<h2>Advanced settings</h2>
		
		<form action="options.php" method="post">
			
			<?php #wp_nonce_field('pc'); ?>
			<?php settings_fields( 'advanced-settings' ); ?>
			
			<table class="form-table">
				
				<tr valign="top">
					<th scope="row"><?php _e('Header'); ?></th>
					<td>
						<label for="remove_menu">

							<input name="remove_menu" type="checkbox" id="remove_menu" value="1" <?php advset_check_if('remove_menu') ?>>
							<?php _e('Hide top admin menu') ?> </label>
						
						<br />
						<label for="favicon">
							<input name="favicon" type="checkbox" id="favicon" value="1" <?php advset_check_if('favicon') ?> />
							<?php _e('Automatically add a FavIcon') ?> <i style="color:#999">(<?php _e('when there is a favicon.ico or favicon.png file in the template folder') ?>)</i></label>
							</label>
						
						<br />
						<label for="description">
							<input name="description" type="checkbox" id="description" value="1" <?php advset_check_if('description') ?> />
							<?php _e('Add a description meta tag using the blog description') ?> (SEO)
							</label>
						
						<br />
						<label for="single_metas">
							<input name="single_metas" type="checkbox" id="single_metas" value="1" <?php advset_check_if('single_metas') ?> />
							<?php _e('Add description and keywords meta tags in each posts') ?> (SEO)
							</label>
						
						<br />
						<label for="remove_generator">
							<input name="remove_generator" type="checkbox" id="remove_generator" value="1" <?php advset_check_if('remove_generator') ?> />
							<?php _e('Remove header WordPress generator meta tag') ?> (html)</label>
						
						<br />
						<label for="remove_wlw">
							<input name="remove_wlw" type="checkbox" id="remove_wlw" value="1" <?php advset_check_if('remove_wlw') ?> />
							<?php _e('Remove header WLW Manifest meta tag (Windows Live Writer link)') ?></label>
					</td>
					
				<tr valign="top">
					<th scope="row"><?php _e('Images'); ?></th>
					<td>
				
						<label for="add_thumbs">
							<?php
							if( current_theme_supports('post-thumbnails') && !defined('ADVSET_THUMBS') ) {
								echo '<i style="color:#999">['.__('Current theme already has post thumbnail support').']</i>';
							} else {
									?>
								<input name="add_thumbs" type="checkbox" id="add_thumbs" value="1" <?php advset_check_if( 'add_thumbs' ) ?> />
								<?php _e('Add thumbnail support') ?>
							<?php } ?>
						</label>
						
						<br />
						<label for="auto_thumbs">
							<input name="auto_thumbs" type="checkbox" id="auto_thumbs" value="1" <?php advset_check_if( 'auto_thumbs' ) ?> />
							<?php _e('Automatically generate the Post Thumbnail') ?> <i style="color:#999">(<?php _e('from the first image in post') ?>)</i></label>
						
						<br />
						<label for="jpeg_quality">
							<?php _e('Set JPEG quality to') ?> <input name="jpeg_quality" type="text" size="2" maxlength="3" id="jpeg_quality" value="<?php echo (int) advset_option( 'jpeg_quality', 0) ?>" /> <i style="color:#999">(<?php _e('when send and resize images') ?>)</i></label>
						
						<br />
						
							<?php _e('Resize image at upload to max size') ?>:
							<br />
							<label for="max_image_size_w">
							&nbsp; &nbsp; &bull; <?php _e('width') ?> (px) <input name="max_image_size_w" type="text" size="2" maxlength="3" id="max_image_size_w" value="<?php echo (int) advset_option( 'max_image_size_w', 0) ?>" />
								<i style="color:#999">(<?php _e('if zero resize to max height or dont resize if both is zero') ?>)</i></label>
							<label for="max_image_size_h">
							<br />
							&nbsp; &nbsp; &bull; <?php _e('height') ?> (px) <input name="max_image_size_h" type="text" size="2" maxlength="3" id="max_image_size_h" value="<?php echo (int) advset_option( 'max_image_size_h', 0) ?>" />
								<i style="color:#999">(<?php _e('if zero resize to max width or dont resize if both is zero') ?>)</i></label>
						
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row"><?php _e('Contents'); ?></th>
					<td>
						<label for="author_bio">
							<input name="author_bio" type="checkbox" id="author_bio" value="1" <?php advset_check_if('author_bio') ?> />
							<?php _e('Insert author bio in each post') ?></label>			
						
						<br />
						
						<label for="author_bio_html">
							<input name="author_bio_html" type="checkbox" id="author_bio_html" value="1" <?php advset_check_if('author_bio_html') ?> />
							<?php _e('Allow HTML in user profiles') ?></label>			
						
						<br />
						
						<label for="remove_comments_system">
							<input name="remove_comments_system" type="checkbox" id="remove_comments_system" value="1" <?php advset_check_if('remove_comments_system') ?> />
							<?php _e('Remove comments system') ?></label>
						
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row"><?php _e('System'); ?></th>
					<td>
						<?php /*if( !defined('EMPTY_TRASH_DAYS') ) { ?>
						<label for="empty_trash">
							<?php _e('Posts stay in the trash for ') ?>
							<input name="empty_trash" type="text" size="2" id="empty_trash" value="<?php echo advset_option('empty_trash') ?>" />
							<?php _e('days') ?> <i style="color:#999">(<?php _e('To disable trash set the number of days to zero') ?>)</i>
							</label>
						
						<br />
						<? } else echo EMPTY_TRASH_DAYS;*/ ?>
						
						<label for="post_type_pag">
							<input name="post_type_pag" type="checkbox" id="post_type_pag" value="1" <?php advset_check_if('post_type_pag') ?> />
							<?php _e('Fix post type pagination') ?>
							</label>
						
						<br />
						<label for="disable_auto_save">
							<input name="disable_auto_save" type="checkbox" id="disable_auto_save" value="1" <?php advset_check_if('disable_auto_save') ?> />
							<?php _e('Disable Posts Auto Saving') ?>
							</label>
						
						<br />
						<label for="feedburner">
							FeedBurner: <input name="feedburner" type="text" size="12" id="feedburner" value="<?php echo advset_option('feedburner') ?>" />
							</label>
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row"><?php _e('HTML Code output'); ?></th>
					<td>
						<label for="compress">
							<input name="compress" type="checkbox" id="compress" value="1" <?php advset_check_if('compress') ?> />
							<?php _e('Compress all code') ?>
							</label>
						
						<br />
						<label for="remove_wptexturize">
							<input name="remove_wptexturize" type="checkbox" id="remove_wptexturize" value="1" <?php advset_check_if('remove_wptexturize') ?> />
							<?php _e('Remove "texturize"') ?> <i style="color:#999">(<?php _e('transformations of quotes to smart quotes, apostrophes, dashes, ellipses, the trademark symbol, and the multiplication symbol') ?>)</i>
							</label>
						
						<br />
						<label for="remove_comments">
							<input name="remove_comments" type="checkbox" id="remove_comments" value="1" <?php advset_check_if('remove_comments') ?> />
							<?php _e('Remove all HTML comments') ?>
							</label>
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row"><?php _e('Footer'); ?></th>
					<td>
						<label for="show_query_num">
							<input name="show_query_num" type="checkbox" id="show_query_num" value="1" <?php advset_check_if('show_query_num') ?> />
							<?php _e('Display total number of executed SQL queries and page loading time <i style="color:#999">(only admin users can see this)') ?></i>
							</label>
						
						<br />
						<label for="analytics">
							<?php _e('Google Analytics ID:') ?> <input name="analytics" type="text" size="12" id="analytics" value="<?php echo advset_option('analytics') ?>" />
							<i style="color:#999">(<?php _e('inserts a javascript code in the footer') ?>)</i>
							</label>

					</td>
				</tr>
				
			</table>
			
			<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="<?php _e('Save changes') ?>"></p>	
		</form>
	</div>
	<?
}

?>
