<?php
/**
 * Holds the SocialFlow Admin Debug settings class
 *
 * @package SocialFlow
 */
class SocialFlow_Admin_Settings_Debug extends SocialFlow_Admin_Settings_Page {

	/**
	 * Add general menu item
	 */
	function __construct() {
		global $socialflow;

		// Initialize only when debug mode is on
		if ( !SF_DEBUG ) {
			return;
		}

		// current page slug
		$this->slug = 'debug';

		// Store current page object
		$socialflow->pages[ $this->slug ] = &$this;

		// Store developer mail
		$this->dev = 'evgeny.dmitriev@dizzain.com';

		// Add action to add menu page
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );

		add_filter( 'sf_save_settings', array( &$this, 'save_settings' ) );

		// Include scripts
		add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) );

		// Add update notice
		add_action( 'admin_notices', array( &$this, 'admin_notices' ) );
	}

	/**
	 * This is callback for admin_menu action fired in construct
	 *
	 * @since 2.1
	 * @access public
	 */
	function admin_menu() {

		add_submenu_page( 
			'socialflow',
			__( 'Debug', 'socialflow' ),
			__( 'Debug', 'socialflow' ),
			'manage_options',
			$this->slug,
			array( &$this, 'page' )
		);

	}

	/**
	 * Render admin page with all accounts
	 */
	function page() {
		global $socialflow; ?>
		<div class="wrap socialflow">
			<div class="icon32"><img src="<?php echo plugins_url( '/socialflow/assets/images/socialflow.png' ) ?>" alt=""></div>
			<h2><?php esc_html_e( 'Debug', 'socialflow' ); ?></h2>

			<form action="options.php" method="post">
				<?php $this->time_debug(); ?>
				<?php /* ?>
				<p><input type="submit" class="button-primary" value="<?php esc_attr_e( 'Send debug data to SF', 'socialflow' ) ?>" /></p>
				<?php */ ?>
				<input type="hidden" value="<?php echo $this->slug ?>" name="socialflow-page" />
				<?php settings_fields( 'socialflow' ); ?>
			</form>
			
		</div>
		<?php
	}

	/**
	 * Outputs HTML for time debug section
	 *
	 * @since 2.0
	 * @access public
	 */
	function time_debug() {
		global $socialflow; ?>

		<h3><?php _e( 'Date &amp; Time', 'socialflow' ); ?></h3>

		<table class="form-table">


			<tr valign="top">
				<th scope="row"><?php _e( 'Server GMT time:', 'socialflow' ); ?></th>
				<td><?php echo date( 'Y-m-d H:i' ); ?></td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php _e( 'JS GMT time:', 'socialflow' ); ?></th>
				<td id="js-debug-current-gmt-time">Loading...</td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php _e( 'Timezone:', 'socialflow' ); ?></th>
				<td><?php echo get_option( 'timezone_string' ); ?></td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php _e( 'GMT offset:', 'socialflow' ); ?></th>
				<td><?php echo get_option( 'gmt_offset' ); ?></td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php _e( 'Current wordpress time:', 'socialflow' ); ?></th>
				<td><?php echo date( 'Y-m-d H:i', current_time( 'timestamp' ) ); ?></td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php _e( 'JS gmt time with timezone offset:', 'socialflow' ); ?></th>
				<td id="js-debug-current-gmt-offset-time">Loading...</td>
			</tr>


			<?php /* ?>
			<tr valign="top">
				<th scope="row"><?php _e( 'Server GMT time:', 'socialflow' ); ?></th>
				<td><input type="text" class="disabled" value="<?php echo date( 'Y-m-d H:i' ); ?>" name="socialflow[debug][server_gmt]"></td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php _e( 'JS GMT time:', 'socialflow' ); ?></th>
				<td id="js-debug-current-gmt-time"><input type="text" class="disabled" value="undefined" name="socialflow[debug][user_gmt]"></td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php _e( 'Timezone:', 'socialflow' ); ?></th>
				<td><input type="text" class="disabled" value="<?php echo get_option( 'timezone_string' ); ?>" name="socialflow[debug][server_tz]"></td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php _e( 'GMT offset:', 'socialflow' ); ?></th>
				<td><input type="text" class="disabled" value="<?php echo get_option( 'gmt_offset' ); ?>" name="socialflow[debug][server_gmt_offset]"></td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php _e( 'Current wordpress time:', 'socialflow' ); ?></th>
				<td><input type="text" class="disabled" value="<?php echo date( 'Y-m-d H:i', current_time( 'timestamp' ) ); ?>" name="socialflow[debug][server_time]"></td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php _e( 'JS gmt time with timezone offset:', 'socialflow' ); ?></th>
				<td id="js-debug-current-gmt-offset-time"><input type="text" class="disabled" value="undefined" name="socialflow[debug][user_gmt_with_offset]"></td>
			</tr>
			<?php */ ?>

		</table>
		<?php
	}


	/**
	 * Sanitizes settings
	 *
	 * Callback for "sf_save_settings" hook in method SocialFlow_Admin::save_settings()
	 *
	 * @see SocialFlow_Admin::save_settings()
	 * @since 2.0
	 * @access public
	 *
	 * @param string|array $settings Settings passed in from filter
	 * @return string|array Sanitized settings
	 */
	function save_settings( $settings = array() ) {
		global $socialflow;

		if ( isset( $_POST['socialflow-page'] ) AND ( $this->slug == $_POST['socialflow-page'] ) ) {

			// Create message content
			$content = '';

			$content = '<table>';
			foreach ($settings['debug'] as $key => $value) {
				$content .= '<tr>
					<td>'.$key.'</td>
					<td>'.$value.'</td>
				</tr>';
			}
			$content .= '</table>';

			$headers = array();
			$headers[] = 'From: '.get_bloginfo( 'name' ).' <'.get_bloginfo( 'admin_email' ).'>';
			$headers[] = 'Content-type: text/html';

			// Send message to developer
			wp_mail( $this->dev, 'SocialFlow debug', $content, $headers );
		}

		return $settings;
	}

	/**
	 * Enqueue 
	 * @return [type] [description]
	 */
	function admin_enqueue_scripts() {
		global $socialflow;

		if ( $socialflow->is_page( 'debug' ) ) {
			wp_enqueue_script( 'socialflow-admin-debug-date', plugins_url( '/socialflow/assets/js/date.js' ), array( 'jquery'), '1.0' );
			wp_enqueue_script( 'socialflow-admin-debug', plugins_url( '/socialflow/assets/js/debug.js' ), array( 'jquery'), '1.0' );

			$debug_data = array(
				'tzOffset' => get_option( 'gmt_offset' ) * HOUR_IN_SECONDS
			);

			wp_localize_script( 'socialflow-admin-debug', 'debugData', $debug_data );
		}
	}

	/**
	 * Output success or failure admin notice when updating options page
	 */
	function admin_notices() {
		global $socialflow;

		if ( isset( $_GET['page'] ) AND $this->slug == $_GET['page'] AND isset( $_GET['settings-updated'] ) AND $_GET['settings-updated'] ) {
			$socialflow->render_view( 'notice/debug-send' );
		}
	}

}