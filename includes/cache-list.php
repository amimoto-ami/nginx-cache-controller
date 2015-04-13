<?php
class NginxChampuruCacheList {
	private static $instance;
	private $list_table;

	private function __construct() {}

	public static function get_instance() {
		if( !isset( self::$instance ) ) {
			$c = __CLASS__;
			self::$instance = new $c();
		}
		return self::$instance;
	}

	public function add_hook() {
		add_action("admin_menu", array($this, "admin_menu"));
	}

	public function admin_menu() {
		add_submenu_page( "nginx-champuru", __("Cache List", "nginxchampuru"), __("Cache List", "nginxchampuru"), "administrator", "nginx-champuru-cache-list", array(&$this, "admin_panel") );
	}

	public function admin_panel() {
 ?>
<div class="wrap">
<h2><?php echo esc_html(  __("Cache List", "nginxchampuru") ); ?></h2>
<?php
$this->list_table = new NginxChampuruCacheListTable();
$this->list_table->prepare_items();
$this->list_table->views();
?>
<form id="entries-filter" method="get">
<input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
<?php $this->list_table->display(); ?>
</form>

</div>
<?
	}
}


if ( !class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . '/wp-admin/includes/class-wp-list-table.php' );
}
class NginxChampuruCacheListTable extends WP_List_Table {
	private $cache_list = array();

	public function __construct() {
		global $nginxchampuru;

		$items = $nginxchampuru->get_cached_objects();

		if ( !is_array($items) )
			return;

		foreach ( $items as $item ) {
			$this->cache_list[] = array(
									'cache_id'    => $item->cache_id,
									'post_type'   => $item->post_type,
									'cache_url'   => $item->cache_url,
									'cache_saved' => $item->cache_saved
								);
		}

		$this->cache_list = apply_filters( 'nginxchampuru_cache_list_data', $this->cache_list );
	}

	public function get_columns() {
		$colums = array(
					'cb'          => '<input type="checkbox" />',
					'cache_id'    => __("Cache ID", "nginxchampuru"),
					'post_type'   => __("Post Type"),
					'cache_url'   => __("Cache URL", "nginxchampuru"),
					'cache_saved' => __("Cache Saved", "nginxchampuru"),
						);
		return $colums;
	}

	public function get_sortable_columns() {
		$sortable_columns = array(
								'cache_id'    => array('cache_id',true),
								'post_type'   => array('post_type',true),
								'cache_url'   => array('cache_url',true),
								'cache_saved' => array('cache_saved',true),
							);
		return $sortable_columns;
	}

	public function get_bulk_actions() {
		$actions = array(
					'flush' => __( 'Flash Cache', "nginxchampuru" ),
				);
		return $actions;
	}
		
	public function process_bulk_action() {}
	
	public function column_cb( $item ) {
		return sprintf(
					'<input type="checkbox" name="%1$s[]" value="%2$s" />',
					$this->_args['singular'],
					$item['cache_id']
        );
	}
	
	public function column_cache_id( $item ) {
		$actions = array (
				'flush'     => sprintf( '<a href="">'.__( 'Flash Cache', "nginxchampuru" ).'</a>' )
			);
		return sprintf('%1$s %2$s',
						$item['cache_id'],
						$this->row_actions($actions)
					);
	}

	public function get_views() {
		$views = array();
		return $views;
	}

	public function column_default( $item, $column_name ) {
		return $item[$column_name];
	}

	public function prepare_items() {

		$per_page = 30;
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->process_bulk_action();

		$data = $this->cache_list;

		function usort_reorder($a,$b){
			$orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'cache_saved';
			$order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'desc';
			$result = strcmp($a[$orderby], $b[$orderby]);
			return ($order==='asc') ? $result : -$result;
		}
		usort($data, 'usort_reorder');

		$current_page = $this->get_pagenum();
		$total_items = count($data);
		$data = array_slice($data,(($current_page-1)*$per_page),$per_page);
		$this->items = $data;

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil($total_items/$per_page)
		) );
	}
}