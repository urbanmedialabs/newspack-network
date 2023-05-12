<?php
/**
 * Newspack Hub Event Log List Table
 *
 * @package Newspack
 */

namespace Newspack_Hub\Admin;

use Newspack_Hub\Admin;
use Newspack_Hub\Nodes;
use Newspack_Hub\Stores\Event_Log as Event_Log_Store;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * The Event Log List Table
 */
class Event_Log_List_Table extends \WP_List_Table {
	
	
	/**
	 * Get the table columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'id'          => __( 'ID', 'newspack-network-hub' ),
			'date'        => __( 'Date', 'newspack-network-hub' ),
			'summary'     => __( 'Summary', 'newspack-network-hub' ),
			'node'        => __( 'Node', 'newspack-network-hub' ),
			'action_name' => __( 'Action name', 'newspack-network-hub' ),
			'data'        => __( 'Data', 'newspack-network-hub' ),
		];
		return $columns;
	}

	/**
	 * Prepare items to be displayed
	 */
	public function prepare_items() {

		$args = [];

        // phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['s'] ) ) {
			$args['search'] = sanitize_text_field( $_GET['s'] );
		}

		if ( isset( $_GET['action_name'] ) ) {
			$args['action_name'] = sanitize_text_field( $_GET['action_name'] );
		}

		if ( isset( $_GET['node_id'] ) ) { 
			$args['node_id'] = sanitize_text_field( $_GET['node_id'] );
		}
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
		
		$columns               = $this->get_columns();
		$primary               = 'id';
		$this->_column_headers = array( $columns, [], [], $primary );

		$per_page     = $this->get_items_per_page( 'elements_per_page', 10 );
		$current_page = $this->get_pagenum();
		$total_items  = Event_Log_Store::get_total_items( $args );

		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			]
		);

		$this->items = Event_Log_Store::get( $args, $per_page, $current_page );
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @param string $which Which table nave, top or bottom.
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}
		$current_action = $_GET['action_name'] ?? '';
		$current_node   = $_GET['node_id'] ?? '';
		$all_actions    = array_keys( Event_Log_Store::get_registered_event_log_items() );
		$all_nodes      = Nodes::get_all_nodes();
		?>

		<select name="action_name" id="action_name">
			<option value=""><?php _e( 'All Actions', 'newspack-network-hub' ); ?></option>
			<?php foreach ( $all_actions as $action ) : ?>
				<option value="<?php echo esc_attr( $action ); ?>" <?php selected( $current_action, $action ); ?>><?php echo esc_html( $action ); ?></option>
			<?php endforeach; ?>
		</select>

		<select name="node_id" id="node_id">
			<option value=""><?php _e( 'All Nodes', 'newspack-network-hub' ); ?></option>
			<?php foreach ( $all_nodes as $node ) : ?>
				<option value="<?php echo esc_attr( $node->get_id() ); ?>" <?php selected( $current_node, $node->get_id() ); ?>><?php echo esc_html( $node->get_url() ); ?></option>
			<?php endforeach; ?>
		</select>

		<input type="submit" name="filter_action" class="button" value="<?php esc_attr_e( 'Filter', 'newspack-network-hub' ); ?>">

		<?php
	}

	/**
	 * Get the value for each column
	 *
	 * @param Abstract_Event_Log_Item $item The line item.
	 * @param string                  $column_name The column name.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
				return $item->get_id();
			case 'date':
				return gmdate( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $item->get_timestamp() );
			case 'summary':
				return $item->get_summary();
			case 'node':
				return $item->get_node_url();
			case 'action_name':
				return $item->get_action_name();
			case 'data':
				return $item->get_raw_data();
			default:
				return '';
		}
	}
}