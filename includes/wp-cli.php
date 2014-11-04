<?php
/**
 * Nginx Cache Controller
 *
 * @package nginx_champuru
 * @subpackage commands/community
 * @maintainer DigitalCube Co.,Ltd
 */
class Nginx_Cache_Controller_Commands extends WP_CLI_Command {

    /**
     * Flush proxy caches.
     *
     * ## OPTIONS
     *
     * [--cache=<url>]
     * : The name of the person to greet.
     *
     * [--format=<format>]
     * : Accepted values: csv, json. Default: csv
     *
     * ## EXAMPLES
     *
     *     wp nginx list --format=json
     *     wp nginx flush
     *     wp nginx flush --cache=http://example.com/archives/10
     *
     * @synopsis [--cache=<url>]
     * @subcommand flush
     */
    function flush($args, $assoc_args) {
        global $nginxchampuru;

		if (isset($assoc_args['cache']) && $assoc_args['cache']) {
			$id = url_to_postid($assoc_args['cache']);
			if ($id) {
        		NginxChampuru_FlushCache::flush_by_post($id);
                WP_CLI::success( "Proxy caches are flushed on ".$assoc_args['cache'] );
                exit;
			} else {
				WP_CLI::error('Cache url is not found.');
                exit;
			}
		} else {
        	$nginxchampuru->transientExec("flush_cache", 'all', 0);
            WP_CLI::success( "All proxy caches are flushed." );
            exit;
		}
    }

    /**
     * Show list of all proxy caches.
     *
     * ## EXAMPLES
     *
     *     wp nginx list
     *
     * @subcommand list
     */
    function _list($args, $assoc_args) {
        global $nginxchampuru;
        $format = strtolower(isset($assoc_args['format']) ? $assoc_args['format'] : 'csv');
        $items = (array)$nginxchampuru->get_cached_objects();
        $fields = array( "cache_id", "post_type", "cache_url", "cache_saved");

        switch ($format) {
            case 'csv':
            case 'json':
                \WP_CLI\Utils\format_items( $format, $items, $fields );
                break;
            default:
                WP_CLI::error(sprintf('Invalid format "%s".', $format));
        }
        exit;
	}
}

WP_CLI::add_command('nginx', 'Nginx_Cache_Controller_Commands');

