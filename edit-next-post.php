<?php

/**
 * The Edit Next Post Plugin
 * 
 * @package Edit Next Post
 * @subpackage Main
 */

/**
 * Plugin Name:       Edit Next Post
 * Description:       Skip easily to next/previous when editing posts in the admin
 * Plugin URI:        https://github.com/lmoffereins/edit-next-post/
 * Version:           1.0.1
 * Author:            Laurens Offereins
 * Author URI:        https://github.com/lmoffereins/
 * Text Domain:       edit-next-post
 * Domain Path:       /languages/
 * GitHub Plugin URI: lmoffereins/edit-next-post
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Edit_Next_Post' ) ) :
/**
 * The main plugin class
 *
 * @since 1.0.0
 */
final class Edit_Next_Post {

	/**
	 * Setup and return the singleton pattern
	 *
	 * @since 1.0.0
	 *
	 * @uses Edit_Next_Post::setup_globals()
	 * @uses Edit_Next_Post::setup_actions()
	 * @return The single Edit_Next_Post
	 */
	public static function instance() {

		// Store instance locally
		static $instance = null;

		if ( null === $instance ) {
			$instance = new Edit_Next_Post;
			$instance->setup_globals();
			$instance->setup_actions();
		}

		return $instance;
	}

	/**
	 * Prevent the plugin class from being loaded more than once
	 */
	private function __construct() { /* Nothing to do */ }

	/** Private methods *************************************************/

	/**
	 * Setup default class globals
	 *
	 * @since 1.0.0
	 */
	private function setup_globals() {

		/** Versions **********************************************************/

		$this->version      = '1.0.1';

		/** Paths *************************************************************/

		// Setup some base path and URL information
		$this->file         = __FILE__;
		$this->basename     = plugin_basename( $this->file );
		$this->plugin_dir   = plugin_dir_path( $this->file );
		$this->plugin_url   = plugin_dir_url ( $this->file );

		// Includes
		$this->includes_dir = trailingslashit( $this->plugin_dir . 'includes' );
		$this->includes_url = trailingslashit( $this->plugin_url . 'includes' );

		// Languages
		$this->lang_dir     = trailingslashit( $this->plugin_dir . 'languages' );

		/** Misc **************************************************************/

		$this->extend       = new stdClass();
		$this->domain       = 'edit-next-post';
	}

	/**
	 * Setup default actions and filters
	 *
	 * @since 1.0.0
	 */
	private function setup_actions() {

		// Load textdomain
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Metabox
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ), 0 );
	}

	/** Plugin **********************************************************/

	/**
	 * Load the translation file for current language. Checks the languages
	 * folder inside the plugin first, and then the default WordPress
	 * languages folder.
	 *
	 * Note that custom translation files inside the plugin folder will be
	 * removed on plugin updates. If you're creating custom translation
	 * files, please use the global language folder.
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'plugin_locale' with {@link get_locale()} value
	 * @uses load_textdomain() To load the textdomain
	 * @uses load_plugin_textdomain() To load the textdomain
	 */
	public function load_textdomain() {

		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale', get_locale(), $this->domain );
		$mofile        = sprintf( '%1$s-%2$s.mo', $this->domain, $locale );

		// Setup paths to current locale file
		$mofile_local  = $this->lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/edit-next-post/' . $mofile;

		// Look in global /wp-content/languages/edit-next-post folder
		load_textdomain( $this->domain, $mofile_global );

		// Look in local /wp-content/plugins/edit-next-post/languages/ folder
		load_textdomain( $this->domain, $mofile_local );

		// Look in global /wp-content/languages/plugins/
		load_plugin_textdomain( $this->domain );
	}

	/** Public methods **************************************************/

	/**
	 * Register the metabox
	 *
	 * @since 1.0.0
	 *
	 * @uses add_meta_box()
	 */
	public function add_metabox() {

		// Bail when this is not a post's page
		if ( ! $post = get_post() )
			return;

		// Don't add metabox for attachments
		if ( 'attachment' == $post->post_type )
			return;

		add_meta_box(
			'edit-next-post',
			__( 'Edit Next/Previous Post', 'edit-next-post' ),
			array( $this, 'display_metabox' ),
			null,
			'side',
			'high'
		);

		wp_add_inline_style( 'wp-admin', "
			#edit-next-post .handlediv, #edit-next-post .hndle { display: none; }
			#edit-next-post .inside { height: 36px; margin: 0; padding: 0; letter-spacing: -4px; }
			#edit-next-post .inside > div { display: inline-block; width: 50%; letter-spacing: 0; }
			#edit-next-post .inside h2 { padding: 0; }
			#edit-next-post .inside h2 a { display: inline-block; width: 100%; box-sizing: border-box; padding: 8px 7px 5px; color: inherit; font-weight: 600; color: #72777c; text-decoration: none; }
			#edit-next-post .inside h2 a:hover { color: inherit; }
			#edit-next-post .inside h2 a span { display: inline-block; width: calc( 100% - 20px ); overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
			#edit-next-post .inside .next-post-title { text-align: right; }
			
			@media screen and (max-width: 782px) {
				#edit-next-post .inside { height: 44px; }
				#edit-next-post .inside h2 a { padding: 12px 7px; }
			}
			"
		);
	}

	/**
	 * Output the metabox contents
	 *
	 * @since 1.0.0
	 *
	 * @uses get_previous_post()
	 * @uses get_next_post()
	 * @uses get_the_title()
	 */
	public function display_metabox() {

		// Get adjacent posts
		$prev = get_previous_post();
		$next = get_next_post();

		// Bail when neither previous or next post exist
		if ( ! $prev && ! $next )
			return;

		?>

		<div class="prev-post">
			<?php if ( $prev ) : ?>
			<h2 class="prev-post-title"><?php printf( '<a href="%2$s" title="%3$s"><i class="dashicons-before dashicons-arrow-left"></i><span class="link-title">%1$s</span></a>', get_the_title( $prev->ID ), add_query_arg( array( 'post' => $prev->ID, 'action' => 'edit' ), self_admin_url( 'post.php' ) ), esc_attr__( 'Edit the previous post', 'edit-next-post' ) ); ?></h2>
			<?php endif; ?>
		</div>
		<div class="next-post">
			<?php if ( $next ) : ?>
			<h2 class="next-post-title"><?php printf( '<a href="%2$s" title="%3$s"><span class="link-title">%1$s</span><i class="dashicons-before dashicons-arrow-right"></i></a>', get_the_title( $next->ID ), add_query_arg( array( 'post' => $next->ID, 'action' => 'edit' ), self_admin_url( 'post.php' ) ), esc_attr__( 'Edit the next post', 'edit-next-post' ) ); ?></h2>
			<?php endif; ?>
		</div>

		<?php
	}
}

/**
 * Return single instance of this main plugin class
 *
 * @since 1.0.0
 * 
 * @return Edit_Next_Post
 */
function edit_next_post() {
	return Edit_Next_Post::instance();
}

// Initiate
edit_next_post();

endif; // class_exists
