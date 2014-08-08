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
	public static $facet_page = 160;

	public static $debug = false;

	function __construct() {

		/**
		 * App starter Setup
		 */
		add_filter( 'app_starter_use_off_canvas_right', '__return_false');
		add_filter( 'app_starter_no_sidebar', '__return_true' );
		add_filter( 'app_starter_use_main_js', '__return_false' );
		add_filter( 'app_starter_tab_bar_middle', array( $this, 'tab_bar_middle' ) );
		//add_action( 'init', array( $this, 'remove_widgets' )  );
		add_filter( 'app_starter_content_part_view', function( $view ) {
			$view = trailingslashit( ISK_ASP_DIR ).'isk-view.php';

			return $view;
		} );

		add_filter( 'the_content', array( $this, 'front_content') );


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
	 *
	 *
	 * @param $content
	 *
	 * @return mixed
	 */
	function front_content( $content ) {
		global $post;
		if ( $this->is_isk( true ) && $post->ID == self::$facet_page ) {
			$content = $this->facets( true ).$content;

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
	 *
	 * @param bool $return Optional. Whether to return or echo. Default is false, which echos.
	 */
	function facets( $return = false ) {
		if ( $this->is_isk( true )  ) {
			$out = '';
			foreach ( $this->the_facets() as $facet => $label ) {

				$out .= '<div class="isk-facet-select" id="facet-'.$facet.'">';
				$out .= '<div class="facet-label">'.$label.'</div>';
				$out .= do_shortcode( '[facetwp facet="'.$facet.'"]' );
				$out .= '</div><!--id="facet-'.$facet.'-->';


			}

		}

		if ( $out ) {
			if ( $return ) {
				return $out;
			}
			else {
				echo $out;
			}
		}
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
		return array(
			'topics' => 'Topics',
			'author' => 'Authors',
			'search' => 'Keyword Search',
		);
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
			if ( is_page( self::$facet_page) || is_front_page() || is_home() ) {
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
		$middle = '<h2 class="site-description">'. get_bloginfo( 'description' ).'</h2>';
		$middle .= '<span class="header-social">';
		$middle .= $this->social();
		$middle .='</span>';
		$middle .= '<a href="'.esc_url( home_url( '/' ) ).'" rel="home"><span class="tab-bar-home genericon genericon-home"></span></a>';

		return $middle;

	}

	/**
	 * Scripts and styles
	 */
	function rez() {
		wp_enqueue_style( 'isk-asp', trailingslashit( ISK_ASP_URL ).'css/isk.css', false, ISK_ASP_VER );
		//wp_deregister_script( 'app-starter');
		wp_enqueue_script( 'app-starter', trailingslashit( ISK_ASP_URL ).'js/app-starter-isk.js', array( 'jquery', 'foundation' ), ISK_ASP_VER, true );
		wp_deregister_script( 'foundation' );
		wp_enqueue_script( 'foundation', trailingslashit( ISK_ASP_URL ).'js/foundation.min.js', array( 'jquery' ), ISK_ASP_VER, true );
		//wp_enqueue_script( 'mag-popup', trailingslashit( ISK_ASP_URL).'js/jquery.magnific-popup.min.js', array( 'jquery' ), ISK_ASP_VER, true );
		//wp_enqueue_style( 'mag-popup', trailingslashit( ISK_ASP_URL).'css/magnific-popup.css' );
	}

	/**
	 * Facet content
	 *
	 * @param $id
	 *
	 * @return mixed
	 */
	public static function facet_content( $id ) {
		$out =  '<a href="'.get_the_permalink( $id ).'" class="button isk-facet-button">'.get_the_title( $id ).'</a>';
		//$author = isk_app_starter::author( $id );
		$author = false;
		if ( $author ) {
			$author = '<span class="isk-author">By: '.$author;
			$author = $author.'</span>';
			$out = $out.$author;
		}

		$out = '<div class="isk-facet-result">'.$out.'</div>';

		return $out;
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
		$instance = self::init();
		$pods = $instance->pod();
		$pods->fetch( $id );

		return $pods->display( 'source_author.post_title' );

	}

	/**
	 * Social Links
	 */
	function social() {
		$social = '
			<a href="http://JoshPress.net" title="Website" class="genericon genericon-wordpress"></a>
			<a href="mailto:JPollock412@gmail.com" class="genericon genericon-mail" ></a>
			<a href="https://plus.google.com/u/0/108295629672902361491" title="Google Plus Profile" class="genericon genericon-googleplus" rel="me"></a>
			<a href="http://github.com/shelob9" title="Github" class="genericon genericon-github"></a>
			<a href="http://www.linkedin.com/pub/josh-pollock/5/900/978" class="genericon genericon-linkedin-alt" title="Linkedin Profile"></a>
			<a href="http://twitter.com/Josh412" class="genericon genericon-twitter"></a>';

		return $social;

	}

	function remove_widgets() {
		remove_action( 'app_starter_after_off_canvas_left', 'app_starter_off_canvas_widgets_left' );
	}

	/**
	 * Holds the instance of this class.
	 *
	 *
	 * @access private
	 * @var    object
	 */
	private static $instance;


	/**
	 * Returns the instance.
	 *
	 * @since  0.0.1
	 * @access public
	 * @return object
	 */
	public static function init() {

		if ( !self::$instance )
			self::$instance = new self;

		return self::$instance;

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
	return $template;

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


