<?php
/**
 * Holds the SocialFlow Admin Message settings class
 *
 * @package SocialFlow
 */
class SocialFlow_Admin_Settings_General extends SocialFlow_Admin_Settings_Page {

	/**
	 * Add general menu item
	 */
	function __construct() {
		global $socialflow;

		$this->slug = 'socialflow';

		// Store current page object
		$socialflow->pages[ $this->slug ] = &$this;

		// Add action to add menu page
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );

		add_filter( 'sf_save_settings', array( &$this, 'save_settings' ) );

		// Add update notice
		add_action( 'admin_notices', array( &$this, 'admin_notices' ) );

		// Include scripts
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
	}

	/**
	 * This is callback for admin_menu action fired in construct
	 *
	 * @since 2.1
	 * @access public
	 */
	function admin_menu() {

		add_menu_page(
			__( 'SocialFlow', 'socialflow' ),
			__( 'SocialFlow', 'socialflow' ),
			'manage_options',
			$this->slug,
			array( &$this, 'page' ),
			plugin_dir_url( SF_FILE ) . 'assets/images/menu-icon.png'
		);
		
		add_submenu_page(
			'socialflow',
			__( 'Default Settings', 'socialflow' ),
			__( 'Default Settings', 'socialflow' ),
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
			<h2><?php esc_html_e( 'Default Settings', 'socialflow' ); ?></h2>

			<form action="options.php" method="post">
				<?php if ( ! $socialflow->options->get( 'access_token' ) ) {
					$this->authorize_settings();
				} else {
					$this->basic_settings(); ?>
					<p><input type="submit" name="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'socialflow' ) ?>" /></p>
				<?php } ?>

				<?php // Render the hidden input fields and handle the security aspects.  ?>
				<?php settings_fields( 'socialflow' ); ?>
				<input type="hidden" value="socialflow" name="socialflow-page" />
			</form>

			<?php if ( $socialflow->options->get( 'access_token' ) ) : ?>
			<br />
			<p>
				<?php // Add temp token to check with ?>
				<a id="toggle-disconnect" class="clickable"><?php _e( 'Disconnect from SocialFlow.', 'socialflow' ) ?></a> &nbsp;&nbsp;&nbsp;&nbsp;
				<a id="disconnect-link" style="display:none" href="<?php echo admin_url( 'options-general.php?page=socialflow&sf_unauthorize=1' ) ?>"><?php _e( 'All plugin options will be removed.', 'socialflow' ) ?></a>
			</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Outputs HTML for authorize Settings
	 *
	 * @since 2.0
	 * @access public
	 */
	function authorize_settings() {
		global $socialflow;

		$api = $socialflow->get_api();

		if ( ! $request_token = $api->get_request_token( add_query_arg( 'sf_oauth', true, admin_url( 'options-general.php?page=socialflow' ) ) ) ) {
			?><div class="misc-pub-section"><p><span class="sf-error"><?php _e( 'There was a problem communicating with the SocialFlow API. Please Try again later. If this problem persists, please email support@socialflow.com', 'sfp' ); ?></p></div><?php
			return;
		}

		$signup = 'http://socialflow.com/signup';
		if ( $links = $api->get_account_links( SF_KEY ) )
			$signup = $links->signup;

		// Store Oauth token and secret
		$socialflow->options->set( 'oauth_token', $request_token['oauth_token'] );
		$socialflow->options->set( 'oauth_token_secret', $request_token['oauth_token_secret'] );
		$socialflow->options->save();
		?>
		<div class="socialflow-authorize">
			<p><?php _e( 'Optimize publishing to Twitter and Facebook using <a href="http://socialflow.com/">SocialFlow</a>.', 'socialflow' ); ?></p>
			<p><?php printf( __( 'Don’t have a SocialFlow account? <a href="%s">Sign Up</a>', 'socialflow' ), esc_url( $signup ) ); ?></p>
			<p><a href="http://support.socialflow.com/entries/20573086-wordpress-plugin-faq-help"><?php _e( 'Help/FAQ', 'socialflow' ); ?></a></p>

			<p><a class="button-primary" href="<?php echo esc_url( $api->get_authorize_url( $request_token ) ); ?>"><?php _e( 'Connect to SocialFlow', 'socialflow' ); ?></a></p>
		</div>

		<?php
	}

	/**
	 * Outputs HTML for "Basic" settings tab
	 *
	 * @since 2.0
	 * @access public
	 */
	function basic_settings() {
		global $socialflow; ?>

		<table class="form-table">

			<tr valign="top">
				<th scope="row"><label for="sf_publish_option"><?php _e( 'Default Publishing Option:', 'socialflow' ); ?></label></th>
				<td>
					<select name="socialflow[publish_option]" id="js-publish-options">
						<option value="optimize" <?php selected( $socialflow->options->get( 'publish_option' ), 'optimize' ); ?>><?php _e( 'Optimize', 'socialflow' ); ?></option>
						<option value="publish now" <?php selected( $socialflow->options->get( 'publish_option' ), 'publish now' ); ?>><?php _e( 'Publish Now', 'socialflow' ); ?></option>
						<option value="hold" <?php selected( $socialflow->options->get( 'publish_option' ), 'hold' ); ?>><?php _e( 'Hold', 'socialflow' ); ?></option>
						<option value="schedule" <?php selected( $socialflow->options->get( 'publish_option' ), 'schedule' ); ?>><?php _e( 'Schedule', 'socialflow' ); ?></option>
					</select>
					<div class="optimize" <?php if ( 'optimize' != $socialflow->options->get( 'publish_option' ) ) echo 'style="display:none;"' ?> id="js-optimize-options">
						<br />

						<input id="sf_must_send" type="checkbox" value="1" name="socialflow[must_send]" <?php checked( 1, $socialflow->options->get( 'must_send' ) ) ?> />
						<label for="sf_must_send"><?php _e( 'Must Send', 'socialflow' ); ?></label>

						<select name="socialflow[optimize_period]" id="js-optimize-period">
							<option <?php selected( $socialflow->options->get( 'optimize_period' ), '10 minutes' ); ?> value="10 minutes" >10 minutes</option>
							<option <?php selected( $socialflow->options->get( 'optimize_period' ), '1 hour' ); ?> value="1 hour">1 hour</option>
							<option <?php selected( $socialflow->options->get( 'optimize_period' ), '1 day' ); ?> value="1 day">1 day</option>
							<option <?php selected( $socialflow->options->get( 'optimize_period' ), '1 week' ); ?> value="1 week">1 week</option>
							<option <?php selected( $socialflow->options->get( 'optimize_period' ), 'anytime' ); ?> value="anytime">Anytime</option>
							<option <?php selected( $socialflow->options->get( 'optimize_period' ), 'range' ); ?> value="range">Pick a range</option>
						</select>

						<span class="range" <?php if ( 'range' != $socialflow->options->get( 'optimize_period' ) ) echo 'style="display:none;"' ?> id="js-optimize-range">
							<label for="optimize-from">from</label> <input type="text" value="<?php echo $socialflow->options->get( 'optimize_start_date' ) ?>" class="datetimepicker" name="socialflow[optimize_start_date]" data-tz-offset="<?php echo ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ?>" />
							<label for="optimize-from">to</label> <input type="text" value="<?php echo $socialflow->options->get( 'optimize_end_date' ) ?>" class="datetimepicker" name="socialflow[optimize_end_date]" data-tz-offset="<?php echo ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ?>" />
						</span>

					</div>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="sf_compose_now"><?php _e( 'Send to SocialFlow when the post is published:', 'socialflow' ); ?></label></th>
				<td><input id="sf_compose_now" type="checkbox" value="1" name="socialflow[compose_now]" <?php checked( 1, $socialflow->options->get( 'compose_now' ) ) ?> /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="sf_shorten_links"><?php _e( 'Shorten Links:', 'socialflow' ); ?></label></th>
				<td><input id="sf_shorten_links" type="checkbox" value="1" name="socialflow[shorten_links]" <?php checked( 1, $socialflow->options->get( 'shorten_links' ) ) ?> /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="post_types"><?php _e( 'Enable plugin for this post types:', 'socialflow' ); ?></label></th>
				<td>
					<?php 
					$types = get_post_types( 
						array(
							'public'  => true,
							'show_ui' => true
						),
						'objects'
					);

					if ( isset( $types['attachment'] ) ) {
						unset( $types['attachment'] );
					}
					$checked = $socialflow->options->get( 'post_type', array() );
					foreach ( $types as $type => $post_type ) : ?>
						<input type="checkbox" value="<?php echo $type ?>" name="socialflow[post_type][]" <?php checked( true, in_array( $type, $checked ) ) ?> id="sf_post_types-<?php echo $type ?>" />
						<label for="sf_post_types-<?php echo $type ?>"><?php echo $post_type->labels->name ?></label>
						<br>
					<?php endforeach; ?>
				</td>
			</tr>
		
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

		if ( !isset( $_POST['socialflow'] ) )
			return;

		$data = $_POST['socialflow'];

		if ( isset( $_POST['socialflow-page'] ) AND ( $this->slug == $_POST['socialflow-page'] ) ) {

			// Sanitize new settings
			$settings['publish_option'] = esc_attr( $data['publish_option'] );

			$settings['optimize_period'] = isset( $data['optimize_period'] ) ? esc_attr( $data['optimize_period'] ) : null;
			$settings['optimize_range_from'] = isset( $data['optimize_range_from'] ) ? esc_attr( $data['optimize_range_from'] ) : null;
			$settings['optimize_range_to'] = isset( $data['optimize_range_to'] ) ? esc_attr( $data['optimize_range_to'] ) : null;

			$settings['post_type'] = isset( $data['post_type'] ) ? array_map( 'esc_attr', $data['post_type'] ) : array();
			$settings['shorten_links'] = isset( $data['shorten_links'] ) ? absint( $data['shorten_links']) : 0;
			$settings['must_send'] = isset( $data['must_send'] ) ? absint( $data['must_send'] ) : 0;
			$settings['compose_now'] = isset( $data['compose_now'] ) ? absint( $data['compose_now'] ) : 0;
		}

		return $settings;
	}

	/**
	 * Enqueue general settngs scripts
	 * @param  string $hook current page hook
	 * @return void       
	 */
	function enqueue_scripts( $hook ) {
		if ( 'toplevel_page_socialflow' == $hook ) {
			
		}
	}

}