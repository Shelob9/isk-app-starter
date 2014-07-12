<?php
/*
Plugin Name: isk-app-starter-control
Plugin URI:
Description:
Version: 0.0.1
Author:
Author URI:
License:
License URI:
*/
define( 'ISK_ASP_SLUG', plugin_basename( __FILE__ ) );
define( 'ISK_ASP_URL', plugin_dir_url( __FILE__ ) );
define( 'ISK_ASP_DIR', plugin_dir_path( __FILE__ ) );
define( 'ISK_ASP_VER', '1.0.0' );

class isk_app_starter {

	public static $type = 'whatisknown';
	public static $templates = null;
	public static $facet_page = 127;

	public static $debug = false;

	function __construct() {

		/**
		 * App starter Setup
		 */
		add_filter( 'app_starter_use_off_canvas_right', '__return_false');
		add_filter( 'app_starter_no_sidebar', '__return_true' );
		add_filter( 'app_starter_use_main_js', '__return_false' );
		add_filter( 'app_starter_tab_bar_middle', array( $this, 'tab_bar_middle') );
		add_filter( 'app_starter_content_part_view', function( $view ) {
			$view = trailingslashit( ISK_ASP_DIR ).'isk-view.php';

			return $view;
		} );

		//Preload Pods Template based views
		add_action( 'init', array( $this, 'templates' ) );

		//output facets
		if ( class_exists( 'FacetWP' ) ) {
			add_action( 'app_starter_after_off_canvas_left', array( $this, 'facets' ) );
		}
		else {
			add_filter( 'the_content', array( $this, 'front_content') );
		}

		//add custom post type to search
		add_action( 'pre_get_posts', array( $this, 'search_query' ) );

		//add CSS/JS
		add_action( 'wp_enqueue_scripts', array( $this, 'rez' ), 999 );

		//debug in footer if in internal debug mode
		add_action( 'wp_footer', array( $this, 'debug' ) );


	}

	/**
	 * Add main post type to search results
	 *
	 * @param $query
	 */
	function search_query($query) {
	  if  ( $query->is_search()  ) {

		  $query->set( 'post_type', 'whatisknown' );

	  }

	}


	/**
	 * The front page content. Fallback for when FacetWP isn't there.
	 *
	 * @todo loose this?
	 *
	 * @param $content
	 *
	 * @return mixed
	 */
	function front_content( $content ) {
		if ( is_front_page() || is_home() ) {

			global $post;
			$id = $post->ID;
			if ( isset( $things[ $id ]) ) {
				$content =  self::$templates[ $id ];
			}
		}

		return $content;

	}

	/**
	 * Build and cache templates
	 *
	 * @todo Do by page
	 */
	function templates() {
		if ( false === ( $things = pods_transient_get( 'isk_things' ) ) ) {

			$pods = $this->pod();
			if ( $pods->total() > 0 ) {
				while ( $pods->fetch() ) {
					$pods->id = $pods->id();
					$things[ $pods->id() ] = $pods->template( 'single' );
				}

				if ( ! self::$debug ) {
					pods_transient_set( 'isk_things', $things, app_starter_cache_expires() );
				}
			}
		}

		self::$templates = $things;

	}

	/**
	 * Get the Pods object for the post type
	 *
	 * @return bool|Pods
	 */
	function pod() {
		$params = array (
			'limit' => 15,
			'expires' => app_starter_cache_expires(),
			'cache_mode' => app_starter_cache_mode(),
		);
		$pods = pods( 'whatisknown', $params );

		return $pods;

	}

	/**
	 * Output the Facets
	 */
	function facets() {
		if ( $this->is_isk( true )  ) {
			foreach ( $this->the_facets() as $facet ) {
				echo '<div class="facet-label">'.ucfirst( $facet ).'</div>';
				echo do_shortcode( '[facetwp facet="'.$facet.'"]' );
			}
		}
	}

	static function mfacet( $id ) { ?>
		<a href="#isk-<?php echo $id; ?>-modal" class="open-popup-link"><?php the_title(); echo self::author( $id ); ?></a>
		<div id="isk-<?php echo $id; ?>-modal" class="white-popup mfp-hide">

			<?php echo self::facet_content( $id ); ?>
		</div>
		<?php
	}

	/**
	 * Output Facet shortcode.
	 *
	 * @TODO Need this?
	 *
	 * @param $content
	 *
	 * @return string
	 */
	function facet_template( $content ) {
		if ( $this->is_isk() ) {
			$content = do_shortcode( '[facetwp template="itisknown"]' );
		}

		return $content;
	}

	/**
	 * List the Facets
	 *
	 * @return array
	 */
	function the_facets() {
		return array( 'topics', 'author' );
	}

	/**
	 * Test if we are on a "isk" page
	 *
	 * @param bool $just_main
	 *
	 * @return bool
	 */
	function is_isk( $just_main = false) {
		if ( $just_main ) {
			if ( is_page( self::$facet_page) || is_front_page() ) {
				return true;
			}
		}
		else {
			if ( is_page( self::$facet_page ) || is_front_page() || is_home() || is_singular( self::$type ) || is_post_type_archive( self::$type )  ) {
				return true;
			}
		}
	}

	/**
	 * Append the site tagline to title
	 *
	 * @param $middle
	 *
	 * @return string
	 */
	function tab_bar_middle( $middle ) {
		return $middle.'<h2 class="site-description">'. get_bloginfo( 'description' ).'</h2><a href="'.esc_url( home_url( '/' ) ).'" rel="home"><span class="tab-bar-home genericon genericon-home"></span></a>';
	}

	/**
	 * Scripts and styles
	 */
	function rez() {
		wp_enqueue_style( 'isk-asp', trailingslashit( ISK_ASP_URL ).'css/isk.css', false, ISK_ASP_VER );
		//wp_deregister_script( 'app-starter');
		wp_enqueue_script( 'app-starter', trailingslashit( ISK_ASP_URL ).'js/app-starter-isk.js', array( 'jquery', 'foundation', 'mag-popup' ), ISK_ASP_VER, true );
		wp_deregister_script( 'foundation' );
		wp_enqueue_script( 'foundation', trailingslashit( ISK_ASP_URL ).'js/oundation.min.js', array( 'jquery' ), ISK_ASP_VER, true );
		wp_enqueue_script( 'mag-popup', trailingslashit( ISK_ASP_URL).'js/jquery.magnific-popup.min.js', array( 'jquery' ), ISK_ASP_VER, true );
		wp_enqueue_style( 'mag-popup', trailingslashit( ISK_ASP_URL).'css/magnific-popup.css' );
	}

	/**
	 * Facet content
	 *
	 * @param $id
	 *
	 * @return mixed
	 */
	public static function facet_content( $id ) {
		if ( isset( self::$templates[ $id ] ) )
			return self::$templates[ $id ];



	}

	/**
	 * Output debug info with this method
	 */
	function debug() {
		//print_c3( self::$templates );
	}

	/**
	 * Get article's Source Author.
	 *
	 * @param $id
	 *
	 * @return false|null|string
	 */
	static function author( $id ) {
		$pods = self::pod();
		$pods->fetch( $id );

		return $pods->display( 'source_author.post_title' );

	}




} 

new isk_app_starter();

/**
 * Output facet content
 *
 * @param $id
 *
 * @return mixed
 */
function isk_facet_content( $id ) {
	$template = isk_app_starter::facet_content( $id );
	if ( 1==1 || ! empty( $template ) ) {
		return $template;
	}

}

function isk_mfacet( $id ) {
	return isk_app_starter::mfacet( $id );
}

/**
 * Output Source Author
 *
 * @param $id
 *
 * @return false|null|string
 */
function isk_facet_author( $id ) {
	$author = isk_app_starter::author( $id );
	if ( $author ) {
		$author = '<span class="isk-author">By: '.$author;
		$author = $author.'</span>';
	}

	return $author;

}
/**
 * Better debug functions
 */

	//better print_r
	if ( !function_exists( 'print_r2' ) ) :
		function print_r2( $val, $return = false ){
			echo '<pre>';
			print_r( $val, $return );
			echo  '</pre>';
		}
	endif;

	//better var_export
	if ( !function_exists( 'print_x2' ) ) :
		function print_x2( $val, $return = false){
			echo '<pre>';
			var_export($val, $return );
			echo  '</pre>';
		}
	endif;

	//debugging in 6 billion forms of communication
	if (!function_exists( 'print_c3' ) ) :
		function print_c3( $val, $r = true, $return = false ) {
			if ( !is_null( $val ) && $val !== false ) {
				if ( $r ) {
					print_r2( $val, $return );
				}
				else {
					print_x2( $val, $return );
				}

			}
			else {
				var_dump( $val );
			}
		}
	endif;



