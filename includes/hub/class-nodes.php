<?php
/**
 * Newspack Hub Nodes post type handling.
 *
 * @package Newspack
 */

namespace Newspack_Network\Hub;

use Newspack_Network\Crypto;
use Newspack_Network\Admin as Network_Admin;

/**
 * Class to handle Nodes post type
 */
class Nodes {

	/**
	 * POST_TYPE_SLUG for Newsletter Lists.
	 */
	const POST_TYPE_SLUG = 'newspack_hub_nodes';

	/**
	 * Initialize this class and register hooks
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'register_post_type' ] );
		add_action( 'save_post', [ __CLASS__, 'save_post' ] );
		add_filter( 'manage_' . self::POST_TYPE_SLUG . '_posts_columns', [ __CLASS__, 'posts_columns' ] );
		add_action( 'manage_' . self::POST_TYPE_SLUG . '_posts_custom_column', [ __CLASS__, 'posts_columns_values' ], 10, 2 );

	}

	/**
	 * Get a node by its URL
	 *
	 * @param string $url The URL to search for.
	 * @return ?Node
	 */
	public static function get_node_by_url( $url ) {
		$url   = untrailingslashit( $url );
		$nodes = get_posts(
			[
				'post_type'      => self::POST_TYPE_SLUG,
				'posts_per_page' => 1,
				'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					[
						'key'   => 'node-url',
						'value' => $url,
					],
				],
			]
		);
		if ( ! empty( $nodes ) ) {
			return new Node( $nodes[0] );
		}
	}

	/**
	 * Get all nodes
	 *
	 * @return ?Node
	 */
	public static function get_all_nodes() {
		$nodes  = get_posts(
			[
				'post_type'      => self::POST_TYPE_SLUG,
				'posts_per_page' => -1,
				'fields'         => 'ids',
			]
		);
		$result = [];
		foreach ( $nodes as $id ) {
			$result[] = new Node( $id );
		}
		return $result;
	}

	/**
	 * Disable Rich text editing from the editor
	 *
	 * @param array  $settings The settings to be filtered.
	 * @param string $editor_id The editor identifier.
	 * @return array
	 */
	public static function filter_editor_settings( $settings, $editor_id ) {
		if ( 'content' === $editor_id && get_current_screen()->post_type === self::POST_TYPE_SLUG ) {
			$settings['tinymce']       = false;
			$settings['quicktags']     = false;
			$settings['media_buttons'] = false;
		}

		return $settings;
	}

	/**
	 * Register the custom post type
	 *
	 * @return void
	 */
	public static function register_post_type() {

		$labels = array(
			'name'                  => _x( 'Nodes', 'Post Type General Name', 'newspack-network' ),
			'singular_name'         => _x( 'Node', 'Post Type Singular Name', 'newspack-network' ),
			'menu_name'             => __( 'Nodes', 'newspack-network' ),
			'name_admin_bar'        => __( 'Nodes', 'newspack-network' ),
			'archives'              => __( 'Nodes', 'newspack-network' ),
			'attributes'            => __( 'Nodes', 'newspack-network' ),
			'parent_item_colon'     => __( 'Parent Node', 'newspack-network' ),
			'all_items'             => __( 'Nodes', 'newspack-network' ),
			'add_new_item'          => __( 'Add new Node', 'newspack-network' ),
			'add_new'               => __( 'Add New', 'newspack-network' ),
			'new_item'              => __( 'New Node', 'newspack-network' ),
			'edit_item'             => __( 'Edit Node', 'newspack-network' ),
			'update_item'           => __( 'Update Node', 'newspack-network' ),
			'view_item'             => __( 'View Node', 'newspack-network' ),
			'view_items'            => __( 'View Nodes', 'newspack-network' ),
			'search_items'          => __( 'Search Node', 'newspack-network' ),
			'not_found'             => __( 'Not found', 'newspack-network' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'newspack-network' ),
			'featured_image'        => __( 'Featured Image', 'newspack-network' ),
			'set_featured_image'    => __( 'Set featured image', 'newspack-network' ),
			'remove_featured_image' => __( 'Remove featured image', 'newspack-network' ),
			'use_featured_image'    => __( 'Use as featured image', 'newspack-network' ),
			'insert_into_item'      => __( 'Insert into item', 'newspack-network' ),
			'uploaded_to_this_item' => __( 'Uploaded to this item', 'newspack-network' ),
			'items_list'            => __( 'Items list', 'newspack-network' ),
			'items_list_navigation' => __( 'Items list navigation', 'newspack-network' ),
			'filter_items_list'     => __( 'Filter items list', 'newspack-network' ),
		);
		$args   = array(
			'label'                => __( 'Nodes', 'newspack-network' ),
			'description'          => __( 'Newspack Nodes', 'newspack-network' ),
			'labels'               => $labels,
			'supports'             => array( 'title' ),
			'hierarchical'         => false,
			'public'               => false,
			'show_ui'              => true,
			'show_in_menu'         => Network_Admin::PAGE_SLUG,
			'can_export'           => false,
			'capability_type'      => 'page',
			'show_in_rest'         => false,
			'delete_with_user'     => false,
			'register_meta_box_cb' => [ __CLASS__, 'add_metabox' ],
		);
		register_post_type( self::POST_TYPE_SLUG, $args );
	}

	/**
	 * Adds post type metaboxes
	 *
	 * @param WP_Post $post The current post.
	 * @return void
	 */
	public static function add_metabox( $post ) {
		add_meta_box(
			'newspack-network-metabox',
			__( 'Node details' ),
			[ __CLASS__, 'metabox_content' ],
			self::POST_TYPE_SLUG,
			'normal',
			'core'
		);
	}

	/**
	 * Modify columns on post type table
	 *
	 * @param array $columns Registered columns.
	 * @return array
	 */
	public static function posts_columns( $columns ) {
		unset( $columns['date'] );
		unset( $columns['stats'] );
		$columns['links'] = __( 'Useful links', 'newspack-network' );
		return $columns;

	}

	/**
	 * Add content to the custom column
	 *
	 * @param string $column The current column.
	 * @param int    $post_id The current post ID.
	 * @return void
	 */
	public static function posts_columns_values( $column, $post_id ) {
		if ( 'links' === $column ) {
			?>
				<p>
					Coming soon...
				</p>
			<?php
		}
	}

	/**
	 * Outputs a dropdow for Node selection
	 *
	 * @param string $current_node The selected node id.
	 * @param string $name The name of the select element.
	 * @param string $empty_label The label for the empty option.
	 * @return void
	 */
	public static function nodes_dropdown( $current_node = '', $name = 'node_id', $empty_label = '' ) {
		if ( empty( $empty_label ) ) {
			$empty_label = __( 'All Nodes', 'newspack-network' );
		}
		$all_nodes = self::get_all_nodes();
		?>
		<select name="<?php echo esc_attr( $name ); ?>" id="node_id">
			<option value=""><?php echo esc_html( $empty_label ); ?></option>
			<option value="0" <?php selected( $current_node, '0' ); ?>><?php echo esc_html( __( 'Hub (this site)', 'newspack-network' ) ); ?></option>
			<?php foreach ( $all_nodes as $node ) : ?>
				<option value="<?php echo esc_attr( $node->get_id() ); ?>" <?php selected( $current_node, $node->get_id() ); ?>><?php echo esc_html( $node->get_url() ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Outputs metabox content
	 *
	 * @param WP_Post $post The current post.
	 * @return void
	 */
	public static function metabox_content( $post ) {

		wp_nonce_field( 'newspack_hub_save_node', 'newspack_hub_save_node_nonce' );

		$node_url   = get_post_meta( $post->ID, 'node-url', true );
		$secret_key = get_post_meta( $post->ID, 'secret-key', true );

		?>
		<div class="misc-pub-section">
			Node URL: <input type="text" name="newspack-node-url" value="<?php echo esc_attr( $node_url ); ?>" />
		</div>

		<?php if ( $secret_key ) : ?>

			<div class="misc-pub-section">
				Secret Key: <?php echo esc_attr( $secret_key ); ?>
			</div>

		<?php endif; ?>
		<?php
	}

	/**
	 * Save post callback
	 *
	 * @param int $post_id The ID of the post being saved.
	 * @return void
	 */
	public static function save_post( $post_id ) {

		$post_type = sanitize_text_field( $_POST['post_type'] ?? '' );

		if ( self::POST_TYPE_SLUG !== $post_type ) {
			return;
		}

		if ( ! isset( $_POST['newspack_hub_save_node_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( $_POST['newspack_hub_save_node_nonce'] ), 'newspack_hub_save_node' )
		) {
			return;
		}

		/*
		 * If this is an autosave, our form has not been submitted,
		 * so we don't want to do anything.
		 */
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$post_type_object = get_post_type_object( $post_type );

		if ( ! current_user_can( $post_type_object->cap->edit_post, $post_id ) ) {
			return;
		}

		if ( ! empty( $_POST['newspack-node-url'] ) && filter_var( $_POST['newspack-node-url'], FILTER_VALIDATE_URL ) ) {
			update_post_meta( $post_id, 'node-url', untrailingslashit( sanitize_text_field( $_POST['newspack-node-url'] ) ) );
		}

		$key = get_post_meta( $post_id, 'secret-key', true );
		if ( ! $key ) {
			$secret_key = Crypto::generate_secret_key();
			update_post_meta( $post_id, 'secret-key', $secret_key );
		}

	}

}
