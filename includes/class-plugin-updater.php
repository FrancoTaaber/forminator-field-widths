<?php
/**
 * Plugin Update Checker.
 *
 * Handles self-hosted plugin updates from GitHub releases.
 *
 * @package Forminator_Field_Widths
 * @since   1.0.0
 */

namespace Forminator_Field_Widths;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Plugin_Updater
 *
 * Checks for plugin updates from GitHub and enables updates through the WordPress dashboard.
 *
 * @since 1.0.0
 */
class Plugin_Updater {

	/**
	 * Plugin slug.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $plugin_slug;

	/**
	 * Plugin file path.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $plugin_file;

	/**
	 * Current version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $version;

	/**
	 * GitHub repository (owner/repo format).
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $github_repo;

	/**
	 * Cache key for update data.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $cache_key = 'ffw_update_check';

	/**
	 * Cache duration in seconds.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private $cache_duration = 43200; // 12 hours.

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param string $plugin_file Path to main plugin file.
	 * @param string $version     Current plugin version.
	 * @param array  $args        Optional configuration arguments.
	 */
	public function __construct( $plugin_file, $version, $args = array() ) {
		$this->plugin_file = $plugin_file;
		$this->plugin_slug = plugin_basename( $plugin_file );
		$this->version     = $version;
		$this->github_repo = isset( $args['github_repo'] ) ? $args['github_repo'] : '';

		$this->init();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function init() {
		// Hook into WordPress update system.
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
		add_action( 'upgrader_process_complete', array( $this, 'purge_cache' ), 10, 2 );

		// Add "Check for updates" link on plugins page.
		add_filter( 'plugin_row_meta', array( $this, 'add_check_update_link' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'handle_manual_check' ) );

		// Show update notice.
		add_action( 'in_plugin_update_message-' . $this->plugin_slug, array( $this, 'show_update_message' ), 10, 2 );
	}

	/**
	 * Check for plugin updates.
	 *
	 * @since 1.0.0
	 * @param object $transient Update transient data.
	 * @return object Modified transient.
	 */
	public function check_for_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$remote_data = $this->get_remote_data();

		if ( $remote_data && version_compare( $this->version, $remote_data['version'], '<' ) ) {
			$plugin_data = array(
				'id'            => $this->plugin_slug,
				'slug'          => dirname( $this->plugin_slug ),
				'plugin'        => $this->plugin_slug,
				'new_version'   => $remote_data['version'],
				'url'           => $remote_data['homepage'],
				'package'       => $remote_data['download_url'],
				'icons'         => array(),
				'banners'       => array(),
				'banners_rtl'   => array(),
				'tested'        => $remote_data['tested'] ?? '',
				'requires_php'  => $remote_data['requires_php'] ?? '7.4',
				'compatibility' => new \stdClass(),
			);

			$transient->response[ $this->plugin_slug ] = (object) $plugin_data;
		} else {
			// No update available.
			$transient->no_update[ $this->plugin_slug ] = (object) array(
				'id'          => $this->plugin_slug,
				'slug'        => dirname( $this->plugin_slug ),
				'plugin'      => $this->plugin_slug,
				'new_version' => $this->version,
				'url'         => '',
				'package'     => '',
			);
		}

		return $transient;
	}

	/**
	 * Plugin information for the WordPress plugin details popup.
	 *
	 * @since 1.0.0
	 * @param false|object|array $result The result object or array.
	 * @param string             $action The type of information being requested.
	 * @param object             $args   Plugin API arguments.
	 * @return false|object
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( dirname( $this->plugin_slug ) !== $args->slug ) {
			return $result;
		}

		$remote_data = $this->get_remote_data();

		if ( ! $remote_data ) {
			return $result;
		}

		$plugin_info = array(
			'name'           => $remote_data['name'] ?? 'Forminator Field Widths',
			'slug'           => dirname( $this->plugin_slug ),
			'version'        => $remote_data['version'],
			'author'         => $remote_data['author'] ?? 'Franco Taaber',
			'author_profile' => $remote_data['author_profile'] ?? '',
			'requires'       => $remote_data['requires'] ?? '5.8',
			'tested'         => $remote_data['tested'] ?? '',
			'requires_php'   => $remote_data['requires_php'] ?? '7.4',
			'sections'       => array(
				'description' => $remote_data['description'] ?? '',
				'changelog'   => $remote_data['changelog'] ?? '',
			),
			'download_link'  => $remote_data['download_url'],
			'homepage'       => $remote_data['homepage'] ?? '',
			'last_updated'   => $remote_data['last_updated'] ?? '',
		);

		return (object) $plugin_info;
	}

	/**
	 * Get remote update data.
	 *
	 * @since 1.0.0
	 * @param bool $force_check Force a fresh check, ignoring cache.
	 * @return array|false Update data or false on failure.
	 */
	private function get_remote_data( $force_check = false ) {
		// Check cache first.
		if ( ! $force_check ) {
			$cached = get_transient( $this->cache_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$remote_data = $this->get_github_release_data();

		// Cache the result.
		if ( $remote_data ) {
			set_transient( $this->cache_key, $remote_data, $this->cache_duration );
		}

		return $remote_data;
	}

	/**
	 * Get update data from GitHub releases.
	 *
	 * @since 1.0.0
	 * @return array|false
	 */
	private function get_github_release_data() {
		if ( empty( $this->github_repo ) ) {
			return false;
		}

		$api_url = sprintf(
			'https://api.github.com/repos/%s/releases/latest',
			$this->github_repo
		);

		$response = wp_remote_get(
			$api_url,
			array(
				'headers' => array(
					'Accept'     => 'application/vnd.github.v3+json',
					'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ),
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_code ) {
			return false;
		}

		$release = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $release['tag_name'] ) ) {
			return false;
		}

		// Find the ZIP asset.
		$download_url = '';
		if ( ! empty( $release['assets'] ) ) {
			foreach ( $release['assets'] as $asset ) {
				if (
					'application/zip' === $asset['content_type'] ||
					( isset( $asset['name'] ) && str_ends_with( $asset['name'], '.zip' ) )
				) {
					$download_url = $asset['browser_download_url'];
					break;
				}
			}
		}

		// Fallback to zipball URL.
		if ( empty( $download_url ) && ! empty( $release['zipball_url'] ) ) {
			$download_url = $release['zipball_url'];
		}

		if ( empty( $download_url ) ) {
			return false;
		}

		// Parse version from tag (remove 'v' prefix if present).
		$version = ltrim( $release['tag_name'], 'v' );

		return array(
			'name'         => 'Forminator Field Widths',
			'version'      => $version,
			'download_url' => $download_url,
			'homepage'     => $release['html_url'] ?? '',
			'changelog'    => $this->parse_changelog( $release['body'] ?? '' ),
			'description'  => __( 'Add visual field width controls to Forminator forms. Easily customize field widths without writing CSS code.', 'forminator-field-widths' ),
			'last_updated' => $release['published_at'] ?? '',
			'requires'     => '5.8',
			'tested'       => '6.9',
			'requires_php' => '7.4',
			'author'       => $release['author']['login'] ?? 'FrancoTaaber',
		);
	}

	/**
	 * Parse changelog from GitHub release body.
	 *
	 * @since 1.0.0
	 * @param string $body Release body content.
	 * @return string HTML formatted changelog.
	 */
	private function parse_changelog( $body ) {
		if ( empty( $body ) ) {
			return '';
		}

		// Convert markdown-ish content to HTML.
		$changelog = esc_html( $body );
		$changelog = nl2br( $changelog );
		$changelog = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $changelog );
		$changelog = preg_replace( '/\*(.+?)\*/', '<em>$1</em>', $changelog );
		$changelog = preg_replace( '/^- /m', '&bull; ', $changelog );

		return '<div class="changelog">' . $changelog . '</div>';
	}

	/**
	 * Purge update cache after plugin update.
	 *
	 * @since 1.0.0
	 * @param \WP_Upgrader $upgrader Upgrader instance.
	 * @param array        $options  Update options.
	 * @return void
	 */
	public function purge_cache( $upgrader, $options ) {
		if ( 'update' === $options['action'] && 'plugin' === $options['type'] ) {
			delete_transient( $this->cache_key );
		}
	}

	/**
	 * Add "Check for updates" link on plugins page.
	 *
	 * @since 1.0.0
	 * @param array  $links Plugin row meta links.
	 * @param string $file  Plugin file.
	 * @return array Modified links.
	 */
	public function add_check_update_link( $links, $file ) {
		if ( $this->plugin_slug !== $file ) {
			return $links;
		}

		$check_url = wp_nonce_url(
			add_query_arg(
				array(
					'ffw_check_update' => '1',
				),
				admin_url( 'plugins.php' )
			),
			'ffw_check_update'
		);

		$links[] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $check_url ),
			esc_html__( 'Check for updates', 'forminator-field-widths' )
		);

		return $links;
	}

	/**
	 * Handle manual update check.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_manual_check() {
		if ( ! isset( $_GET['ffw_check_update'] ) ) {
			return;
		}

		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'ffw_check_update' ) ) {
			return;
		}

		// Force a fresh check.
		delete_transient( $this->cache_key );
		delete_site_transient( 'update_plugins' );

		// Redirect back to plugins page with a message.
		wp_safe_redirect(
			add_query_arg(
				array(
					'ffw_update_checked' => '1',
				),
				admin_url( 'plugins.php' )
			)
		);
		exit;
	}

	/**
	 * Show update message on plugins page.
	 *
	 * @since 1.0.0
	 * @param array  $plugin_data Plugin data.
	 * @param object $response    Update response.
	 * @return void
	 */
	public function show_update_message( $plugin_data, $response ) {
		if ( ! empty( $response->upgrade_notice ) ) {
			printf(
				'<br /><span style="display: inline-block; padding: 4px 0;"><strong>%s</strong> %s</span>',
				esc_html__( 'Upgrade Notice:', 'forminator-field-widths' ),
				esc_html( $response->upgrade_notice )
			);
		}
	}
}
