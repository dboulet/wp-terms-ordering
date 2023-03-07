<?php
/**
 * Plugin Name: WP Terms Ordering
 * Plugin URI: https://github.com/dboulet/wp-terms-ordering
 * Description: Order your categories, tags or any other taxonomy of your WordPress website.
 * Version: 1.0.0
 * Author: Dan Boulet
 * Author URI: https://www.danboulet.com
 * Text Domain: wp-terms-ordering
 * Domain Path: /languages
 * Licence: GPL
 *
 * @package wp-terms-ordering
 *
 * Copyright 2011  Gecka Apps (email : contact@gecka-apps.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Require the WP_Terms_Ordering class.
 */
require 'class-wp-terms-ordering.php';

$wp_term_ordering = WP_Terms_Ordering::get_instance();

if ( ! function_exists( 'add_term_ordering_support' ) ) {
	/**
	 * Add term ordering support to a taxonomy
	 *
	 * @param string|array $taxonomy A term slug or an array of term slugs.
	 * @since 1.0.0
	 */
	function add_term_ordering_support( $taxonomy ) {
		WP_Terms_Ordering::add_taxonomy_support( $taxonomy );
	}
}

if ( ! function_exists( 'remove_term_ordering_support' ) ) {
	/**
	 * Remove custom ordering support from one or more taxonomies
	 *
	 * @param string|array $taxonomy A term slug or an array of term slugs.
	 * @since 1.0.0
	 */
	function remove_term_ordering_support( $taxonomy ) {
		WP_Terms_Ordering::remove_taxonomy_support( $taxonomy );
	}
}

if ( ! function_exists( 'has_term_ordering_support' ) ) {
	/**
	 * Check whether a taxonomy has support for ordering
	 *
	 * @param  string $taxonomy The taxonomy slug.
	 * @return boolean          Whether the supplied taxonomy supports ordering.
	 * @since 1.0.0
	 */
	function has_term_ordering_support( $taxonomy ) {
		return WP_Terms_Ordering::has_support( $taxonomy );
	}
}
