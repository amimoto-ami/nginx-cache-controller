<?php
class NginxChampuruCacheList {
	private static $instance;

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
		add_action("admin_init", array($this, "flush"));
		add_filter('set-screen-option', array($this, "set_screen_option"), 10, 3);
	}

	public function admin_menu() {
		global $ncccl_plugin_page;

		$ncccl_plugin_page = add_submenu_page( "nginx-champuru", __("Cache List", "nginxchampuru"), __("Cache List", "nginxchampuru"), "administrator", "nginx-champuru-cache-list", array(&$this, "admin_panel") );

		add_action("load-$ncccl_plugin_page", array( $this, 'screen_options' ));
	}

	public function set_screen_option($status, $option, $value) {
		if ( 'ncccl_plugin_per_page' == $option ) return $value;
	}
	
	public function screen_options() {
		global $ncccl_plugin_page;
 
		$screen = get_current_screen();

		if(!is_object($screen) || $screen->id != $ncccl_plugin_page)
			return;
 
		$args = array(
			'label' => __('Per Page'),
			'default' => 20,
			'option' => 'ncccl_plugin_per_page'
		);
		add_screen_option( 'per_page', $args );
	}

	public function admin_panel() {
		if ( isset($_GET['flushed']) && is_numeric($_GET['flushed']) ) {
			$updated_message = __( 'Flush Cached.', "nginxchampuru" );
		}
 ?>
<div class="wrap">
<h2><?php echo esc_html(  __("Cache List", "nginxchampuru") ); ?></h2>
<?php if ( isset($updated_message) ) : ?>
<div id="message" class="updated fade"><p><?php echo esc_html($updated_message); ?></p></div>
<?php endif; ?>
<?php
$list_table = new NginxChampuruCacheListTable();
$list_table->prepare_items();
$list_table->views();
?>
<form id="entries-filter" method="get">
<?php $list_table->display(); ?>
<?php wp_nonce_field( 'nginxchampuru-cache-flush', '_ncc_nonce' ); ?>
</form>

</div>
<?
	}

	public function flush() {

		$doaction = isset($_GET['action']) ? $_GET['action'] : false;
		$cache = isset($_GET['cache']) ? $_GET['cache'] : '';

		if ( $doaction &&  ( 'flush_cache' == $doaction || 'flush_caches' == $doaction ) ) {
			$cnt = 0;
			if ( $doaction == 'flush_cache' ) {
				check_admin_referer( 'nginxchampuru-cache-flush-'.$cache, '_ncc_nonce' );
				if ( !empty($cache) ) {
					NginxChampuru_FlushCache::flush_by_post($cache);
					$cnt++;
				}
			} elseif ( $doaction == 'flush_caches' ) {
				check_admin_referer( 'nginxchampuru-cache-flush', '_ncc_nonce' );
				if ( !empty($cache) && is_array($cache) ) {
					foreach ( $cache as $id ) {
						NginxChampuru_FlushCache::flush_by_post($id);
						$cnt++;
					}
				}
			}

			$sendback = remove_query_arg( array('action'), wp_get_referer() );
			$sendback = add_query_arg( 'flushed', $cnt, $sendback );
			
			wp_redirect($sendback);
			exit;
		}
	}
}

if ( !class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . '/wp-admin/includes/class-wp-list-table.php' );
}
class NginxChampuruCacheListTable extends WP_List_Table {
	private $cache_list = array();

	public function __construct() {
		global $nginxchampuru;
		
		parent::__construct( array(
			'singular' => 'cache',
			'plural'   => 'caches',
			'ajax'     => false	
		) );

		$items = $nginxchampuru->get_cached_objects();

		if ( !is_array($items) )
			return;

		foreach ( $items as $item ) {
			$expire = (int)$this->_get_expire($item->post_type);
			$cache_save_time = strtotime($item->cache_saved);
			$expire_time = $cache_save_time + $expire;
			
			if ( $expire_time <= current_time('timestamp') )
				continue;

			$expire_local_date = date_i18n('Y-m-d H:i:s', $expire_time);

			$this->cache_list[] = array(
									'cache_id'      => $item->cache_id,
									'cache_url'     => $item->cache_url,
									'cache_expired' => $expire_local_date,
									'post_type'     => $item->post_type,
								);
		}

		$this->cache_list = apply_filters( 'nginxchampuru_cache_list_data', $this->cache_list );
	}

	public function no_items() {
		_e( 'No matching caches were found.', 'nginxchampuru' );
	}

	public function get_columns() {
		$colums = array(
					'cb'            => '<input type="checkbox" />',
					'cache_url'     => __("Cache URL", "nginxchampuru"),
					'cache_expired' => __("Cache Expired", "nginxchampuru"),
					'post_type'     => __("Post Type"),
						);
		return $colums;
	}

	public function get_sortable_columns() {
		$sortable_columns = array(
								'cache_url'     => array('cache_url',true),
								'cache_expired' => array('cache_expired',true),
								'post_type'     => array('post_type',true),
							);
		return $sortable_columns;
	}

	public function get_bulk_actions() {
		$actions = array(
					'flush_caches' => __( 'Cache Flush', "nginxchampuru" ),
				);
		return $actions;
	}
		
	public function process_bulk_action() {
	}
	
	public function column_cb( $item ) {
		if ( $item['post_type'] != 'is_singular' )
			return '';

		$post_id = url_to_postid($item['cache_url']);

		if ( $post_id == 0 )
			return '';

		return sprintf(
					'<input type="checkbox" name="%1$s[]" value="%2$s" />',
					$this->_args['singular'],
					$post_id
		);
	}
	
	public function column_cache_url( $item ) {

		if ( $item['post_type'] != 'is_singular' )
			return $item['cache_url'];

		$post_id = url_to_postid($item['cache_url']);

		if ( $post_id == 0 )
			return $item['cache_url'];
		
		$flush_url = wp_nonce_url('admin.php?page=nginx-champuru-cache-list', 'nginxchampuru-cache-flush-'.$post_id, '_ncc_nonce' );
		$flush_url = add_query_arg( 'action', 'flush_cache', $flush_url );
		$flush_url = add_query_arg( 'cache', $post_id, $flush_url );

		$actions = array (
				'flush'     => sprintf( '<a href="%s">'.__( 'Cache Flush', "nginxchampuru" ).'</a>', $flush_url )
			);
		return sprintf('%1$s %2$s',
						$item['cache_url'],
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

		$user = get_current_user_id();
		$screen = get_current_screen();
		$screen_option = $screen->get_option('per_page', 'option');
		$per_page = get_user_meta($user, $screen_option, true);
		if ( empty ( $per_page) || $per_page < 1 ) {
			$per_page = $screen->get_option( 'per_page', 'default' );
		}

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

	private function _get_expire($post_type) {
		global $nginxchampuru;

		$expires = get_option(NginxChampuru::OPTION_NAME_CACHE_EXPIRES);

		if (isset($expires[$post_type]) && strlen($expires[$post_type])) {
			return $expires[$post_type];
		} else {
			return $nginxchampuru->get_default_expire();
		}
	}
}