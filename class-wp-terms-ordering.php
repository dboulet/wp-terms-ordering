<?php
/**
 * Class WP_Terms_Ordering
 *
 * @package wp-terms-ordering
 * @since 1.0.0
 */
class WP_Terms_Ordering {
	/**
	 * Instance property
	 *
	 * @var object $instance Used in the singleton pattern.
	 */
	private static $instance;

	/**
	 * Taxonomies property
	 *
	 * @var array $taxonomies Holds the names of the taxonomies which have support for term ordering.
	 */
	private static $taxonomies = array( 'category' );

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 5 );
		add_action( 'after_setup_theme', array( $this, 'after_setup_theme' ), 5 );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_filter( 'terms_clauses', array( $this, 'terms_clauses' ), 10, 3 );
		add_action( 'created_term', array( $this, 'created_term' ), 10, 3 );
	}

	/**
	 * Singleton pattern
	 *
	 * @return WP_Terms_Ordering
	 * @since 1.0.0
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			$class_name     = __CLASS__;
			self::$instance = new $class_name();
		}

		return self::$instance;
	}

	/**
	 * Add custom ordering support to one or more taxonomies
	 *
	 * @param string|array $taxonomy The taxonomy slug or an array of slugs.
	 * @since 1.0.0
	 */
	public static function add_taxonomy_support( $taxonomy ) {
		$taxonomies       = (array) $taxonomy;
		self::$taxonomies = array_merge( self::$taxonomies, $taxonomies );
	}

	/**
	 * Remove custom ordering support from one or more taxonomies
	 *
	 * @param string|array $taxonomy The taxonomy slug or an array of slugs.
	 * @since 1.0.0
	 */
	public static function remove_taxonomy_support( $taxonomy ) {
		$key = array_search( $taxonomy, self::$taxonomies, true );
		if ( false !== $key ) {
			unset( self::$taxonomies[ $key ] );
		}
	}

	/**
	 * Run tasks after plugins have loaded
	 *
	 * @since 1.0.0
	 */
	public function plugins_loaded() {
		/**
		 * Apply term_ordering_default_taxonomies hook
		 *
		 * @param array $taxonomies An array of taxonomy slugs.
		 * @since 1.0.0
		 */
		self::$taxonomies = apply_filters( 'term_ordering_default_taxonomies', self::$taxonomies );
		load_plugin_textdomain( 'wp-terms-ordering', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Run tasks after theme setup
	 *
	 * @since 1.0.0
	 */
	public function after_setup_theme() {
		/**
		 * Apply term_ordering_taxonomies hook
		 *
		 * @param array $taxonomies An array of taxonomy slugs.
		 * @since 1.0.0
		 */
		self::$taxonomies = apply_filters( 'term_ordering_taxonomies', self::$taxonomies );
	}

	/**
	 * Run tasks after admin init
	 *
	 * @since 1.0.0
	 */
	public function admin_init() {
		// Load needed scripts to order terms.
		add_action( 'admin_footer-edit-tags.php', array( $this, 'admin_enqueue_scripts' ), 10 );
		add_action( 'admin_print_styles-edit-tags.php', array( $this, 'admin_css' ), 1 );

		// Httpr hadler for drag and drop ordering.
		add_action( 'wp_ajax_terms-ordering', array( $this, 'terms_ordering_httpr' ) );
	}

	/**
	 * Load needed scripts to order categories in admin
	 *
	 * @since 1.0.0
	 */
	public function admin_enqueue_scripts() {
		$screen = get_current_screen();

		if ( ! isset( $screen->taxonomy ) || ! self::has_support( $screen->taxonomy ) ) {
			return;
		}

		wp_register_script( 'wp-terms-ordering', plugins_url( 'javascript/terms-ordering.min.js', __FILE__ ), array( 'jquery-ui-sortable' ), '1.0', true );
		wp_enqueue_script( 'wp-terms-ordering' );
		$data = array(
			'taxonomy' => $screen->taxonomy,
			'nonce'    => wp_create_nonce( 'wp-terms-ordering' ),
		);
		wp_add_inline_script( 'wp-terms-ordering', 'const wpTermsOrdering = ' . wp_json_encode( $data ), 'before' );
		wp_print_scripts( 'wp-terms-ordering' );
	}

	/**
	 * Check whether a taxonomy has support for ordering
	 *
	 * @param  string $taxonomy The taxonomy slug.
	 * @return boolean          Whether the supplied taxonomy supports ordering.
	 * @since 1.0.0
	 */
	public static function has_support( $taxonomy ) {
		if ( in_array( $taxonomy, self::$taxonomies, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Print CSS for the admin
	 *
	 * @since 1.0.0
	 */
	public function admin_css() {
		$screen = get_current_screen();

		if ( ! isset( $screen->taxonomy ) || ! self::has_support( $screen->taxonomy ) ) {
			return;
		}

		?>
		<style>
			.widefat .product-cat-placeholder {
				height: 60px;
				outline: 1px dotted #21759B;
			}
		</style>
		<?php
	}

	/**
	 * Httpr handler for terms ordering
	 *
	 * @since 1.0.0
	 */
	public function terms_ordering_httpr() {
		global $wpdb;

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'wp-terms-ordering' ) ) {
			die( 0 );
		}

		if ( ! isset( $_POST['id'] ) ) {
			die( 0 );
		}

		$id       = (int) $_POST['id'];
		$next_id  = isset( $_POST['nextid'] ) && (int) $_POST['nextid'] ? (int) $_POST['nextid'] : null;
		$taxonomy = ! empty( $_POST['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_POST['taxonomy'] ) ) : null;
		$term     = get_term_by( 'id', $id, $taxonomy );

		if ( ! $term ) {
			die( 0 );
		}

		$this->place_term( $term, $taxonomy, $next_id );

		$children = get_terms( $taxonomy, "child_of=$id&menu_order=ASC&hide_empty=0" );

		if ( $term && count( $children ) ) {
			'children';
			die;
		}
	}

	/**
	 * Move a term before a given element of its hierachy level
	 *
	 * @param object $the_term The term object.
	 * @param string $taxonomy The taxonomy slug.
	 * @param int    $next_id  The id of the next slibling element in save hierachy level.
	 * @param int    $index    The term’s index.
	 * @param array  $terms    An array of terms.
	 */
	private function place_term( $the_term, $taxonomy, $next_id, $index = 0, $terms = null ) {
		if ( ! $terms ) {
			$terms = get_terms( $taxonomy, 'menu_order=ASC&hide_empty=0&parent=0' );
		}
		if ( empty( $terms ) ) {
			return $index;
		}

		$id            = $the_term->term_id;
		$term_in_level = false; // Flag: is our term to order in this level of terms.

		foreach ( $terms as $term ) {
			if ( $term->term_id === $id ) { // Our term to order, we skip.
				$term_in_level = true;
				continue; // Our term to order, we skip.
			}
			// The nextid of our term to order, lets move our term here.
			if ( null !== $next_id && $term->term_id === $next_id ) {
				$index = $this->set_term_order( $id, $taxonomy, $index + 1, true );
			}

			// Set order.
			$index = $this->set_term_order( $term->term_id, $taxonomy, $index + 1 );

			// If that term has children we walk thru them.
			$children = get_terms( $taxonomy, "parent={$term->term_id}&menu_order=ASC&hide_empty=0" );
			if ( ! empty( $children ) ) {
				$index = $this->place_term( $the_term, $taxonomy, $next_id, $index, $children );
			}
		}

		// No nextid meaning our term is in last position.
		if ( $term_in_level && null === $next_id ) {
			$index = $this->set_term_order( $id, $taxonomy, $index + 1, true );
		}

		return $index;
	}

	/**
	 * Set the sort order of a term
	 *
	 * @param int    $term_id   The term ID.
	 * @param string $taxonomy  The taxonomy slug.
	 * @param int    $index     The term index.
	 * @param bool   $recursive Whether to set the order recursively.
	 */
	private function set_term_order( $term_id, $taxonomy, $index, $recursive = false ) {
		global $wpdb;

		$term_id = (int) $term_id;
		$index   = (int) $index;

		update_metadata( 'term', $term_id, 'order', $index );

		if ( ! $recursive ) {
			return $index;
		}

		$children = get_terms( $taxonomy, "parent=$term_id&menu_order=ASC&hide_empty=0" );

		foreach ( $children as $term ) {
			$index++;
			$index = $this->set_term_order( $term->term_id, $taxonomy, $index, true );
		}

		return $index;
	}

	/**
	 * Add term ordering suport to get_terms, set it as default
	 *
	 * It enables the support a 'menu_order' parameter to get_terms for the configured taxonomy.
	 * By default it is 'ASC'. It accepts 'DESC', too.
	 *
	 * To disable it, set it ot false (or 0).
	 *
	 * @param array $clauses    An array of clauses.
	 * @param array $taxonomies An array of taxonomy slugs.
	 * @param array $args       An array of arguments.
	 * @since 1.0.0
	 */
	public function terms_clauses( $clauses, $taxonomies, $args ) {
		global $wpdb;

		$taxonomies = (array) $taxonomies;
		if ( count( $taxonomies ) === 1 ) {
			$taxonomy = array_shift( $taxonomies );
		} else {
			return $clauses;
		}

		if ( ! $this->has_support( $taxonomy ) ) {
			return $clauses;
		}

		// Fields.
		if ( strpos( 'COUNT(*)', $clauses['fields'] ) === false ) {
			$clauses['fields'] .= ', tm.meta_key, tm.meta_value ';
		}

		// Join.
		$clauses['join'] .= " LEFT JOIN {$wpdb->termmeta} AS tm ON (t.term_id = tm.term_id AND tm.meta_key = 'order') ";

		// Order.
		if ( isset( $args['menu_order'] ) && ! $args['menu_order'] ) {
			return $clauses;
		} // menu_order is false whe do not add order clause.

		// Default to ASC.
		if ( ! isset( $args['menu_order'] ) || ! in_array( strtoupper( $args['menu_order'] ), array( 'ASC', 'DESC' ), true ) ) {
			$args['menu_order'] = 'ASC';
		}

		$order = 'ORDER BY CAST(tm.meta_value AS SIGNED)';

		if ( $clauses['orderby'] ) {
			$clauses['orderby'] = str_replace( 'ORDER BY', $order . ' ' . $args['menu_order'] . ',', $clauses['orderby'] );
		} else {
			$clauses['orderby'] = $order;
			$clauses['order']   = $args['menu_order'];
		}

		return $clauses;
	}

	/**
	 * Reorder on term insertion
	 *
	 * @param int    $term_id  The term ID.
	 * @param int    $tt_id    The term taxonomy ID.
	 * @param string $taxonomy The taxonomy slug.
	 * @since 1.0.0
	 */
	public function created_term( $term_id, $tt_id, $taxonomy ) {
		if ( ! $this->has_support( $taxonomy ) ) {
			return;
		}

		$next_id = null;
		$term    = get_term( $term_id, $taxonomy );

		// Gets the sibling terms.
		$siblings = get_terms( $taxonomy, "parent={$term->parent}&menu_order=ASC&hide_empty=0" );

		foreach ( $siblings as $sibling ) {
			if ( $sibling->term_id === $term_id ) {
				continue;
			}
			$next_id = $sibling->term_id; // First sibling term of the hierachy level.
			break;
		}

		// Reorder.
		$this->place_term( $term, $taxonomy, $next_id );
	}
}
