<?php
/**
 * Nginx Cache Controller.
 */
class Nginx_Cache_Controller_Commands extends WP_CLI_Command {

	/**
	 * Flush proxy caches.
	 *
	 * ## OPTIONS
	 *
	 * [--url=<url>]
	 * : The name of the person to greet.
	 *
	 * ## EXAMPLES
	 *
	 *     wp nginx flush
	 *
	 * @subcommand flush
	 */
	function flush($args, $assoc_args) {
		global $nginxchampuru;

		list($subcommand) = $args;

		switch ($subcommand) {
			case "flush":
				$nginxchampuru->transientExec("flush_cache", 'all', 0);
				WP_CLI::success( "All proxy caches are flushed." );
				break;
		}
	}
}

WP_CLI::add_command('nginx', 'Nginx_Cache_Controller_Commands');

