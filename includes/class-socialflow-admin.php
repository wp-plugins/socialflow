<?php
/**
 * Holds the SocialFlow Admin settings class
 *
 * @package SocialFlow
 */
class SocialFlow_Admin {

	/**
	 * PHP5 constructor
	 *
	 * @since 2.0
	 * @access public
	 */
	function __construct() {

		// Register setting and maybe perform authorization
		add_action( 'admin_init', array( &$this, 'admin_init' ) );

		// Load admin menu classes
		$this->admin_menu();

		// Add notices
		add_action( 'admin_notices', array( &$this, 'initial_nag' ) );

		// Include scripts
		add_action( 'admin_enqueue_scripts', array( &$this, 'load_settings_page' ) );

		// Increase http request timeout
		//add_filter( 'http_request_timeout', array( &$this, 'http_request_timeout' ) );

		register_activation_hook( SF_ABSPATH . '/socialflow.php', array( 'SocialFlow_Admin', 'install' ) );
		register_uninstall_hook( SF_ABSPATH . '/socialflow.php', array( 'SocialFlow_Admin', 'uninstall' ) );
	}

	/**
	 * Registers SocialFlow settings
	 *
	 * This is used because register_setting() isn't available until the "admin_init" hook.
	 *
	 * @since 2.0
	 * @access public
	 */
	function admin_init() {
		global $socialflow;

		// Maybe connect with SocialFlow
		$this->maybe_authorize();

		// Register our settings in the global "whitelist_settings"
		register_setting( 'socialflow', 'socialflow',  array( &$this, 'save_settings' ) );
	}

	/**
	 * Adds "Social Flow" to the WordPress "Settings" menu
	 *
	 * @since 2.0
	 * @access public
	 */
	function admin_menu() {
		global $socialflow;

		// Load abstract page class
		require_once( SF_ABSPATH . '/includes/settings/page.php' );
		// Load page classes
		require_once( SF_ABSPATH . '/includes/settings/general.php' );
		require_once( SF_ABSPATH . '/includes/settings/accounts.php' );
		require_once( SF_ABSPATH . '/includes/settings/messages.php' );
		require_once( SF_ABSPATH . '/includes/settings/categories.php' );
		require_once( SF_ABSPATH . '/includes/settings/debug.php' );

		// Init menu classes
		new SocialFlow_Admin_Settings_General;
		new SocialFlow_Admin_Settings_Accounts;
		new SocialFlow_Admin_Settings_Categories;
		new SocialFlow_Admin_Settings_Debug;
		//new SocialFlow_Admin_Settings_Messages;
	}

	/**
	 * Outputs message to admin to visit settings page after initial plugin activation
	 *
	 * @since 2.0
	 * @access public
	 */
	function initial_nag() {
		global $socialflow;

		if ( !$socialflow->is_authorized() AND current_user_can( 'manage_options' ) AND ! (isset($_GET['page']) AND 'socialflow' == $_GET['page'] ) ) {
			$socialflow->render_view( 'notice/not-authorized' );
		}
	}

	/**
	 * Loads admin styles and scripts
	 *
	 * @since 2.0
	 * @access public
	 */
	function load_settings_page() {
		global $pagenow;
		if ( in_array( $pagenow, array( 'post.php', 'post-new.php', 'admin.php', 'edit.php' ) ) ) {

			// Enqueue neccessary scripts 
			wp_enqueue_script( 'timepicker', plugins_url( '/socialflow/assets/js/jquery.timepicker.js' ), array( 'jquery', 'jquery-ui-slider', 'jquery-ui-datepicker'), true );
			wp_enqueue_script( 'jquery.maxlength', plugins_url( '/socialflow/assets/js/jquery.maxlength-min.js' ), array( 'jquery'), '1.0.5', true );
			wp_enqueue_script( 'socialflow-slider', plugins_url( '/socialflow/assets/js/thumb-slider.js' ), array( 'jquery'), '1.1.5', true );

			wp_register_script( 'socialflow-admin', plugins_url( '/socialflow/assets/js/socialflow.js' ), array( 'jquery'), '2.0', true );
			wp_enqueue_script( 'socialflow-admin' );
			wp_enqueue_script( 'socialflow-categories', plugins_url( '/socialflow/assets/js/sf-categories.js' ), array( 'jquery'), '2.0', true );

			// Enqeue styles
			wp_enqueue_style( 'socialflow-admin', plugins_url( '/socialflow/assets/css/socialflow.css' ) );
			wp_enqueue_style( 'jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.1/themes/smoothness/jquery-ui.css', false, '1.8.1', false);

			// Thickbox scripts for compose now post action
			wp_enqueue_script( 'thickbox' );
			wp_enqueue_style( 'thickbox' );
		}
	}

	/**
	 * Maybe connect to sociaflow
	 *
	 * @since 2.0
	 * @access public
	 */
	function maybe_authorize() {
		global $socialflow;
		// Validate token 
		if ( isset( $_GET['oauth_token'] ) AND $_GET['oauth_token'] == $socialflow->options->get( 'oauth_token' ) ) {

			$api = $socialflow->get_api( $socialflow->options->get( 'oauth_token' ), $socialflow->options->get( 'oauth_token_secret' ) );

			// Store access tokens
			$socialflow->options->set( 'access_token', $api->get_access_token( $_GET['oauth_verifier'] ));

			// Unset temporary token and secret
			$socialflow->options->delete( 'oauth_token' );
			$socialflow->options->delete( 'oauth_token_secret' );

			// Get list of all user account and enable sf by default for each account
			$accounts = $api->get_account_list();

			if ( is_wp_error( $accounts ) ) {
				
			}

			// Enable all publishing accounts by default
			$enabled = array();
			foreach ( $accounts as $key => $account ) {
				if ( 'publishing' == $account['service_type'] ) {
					$enabled[] = $key;
				}
			}

			// Store all user accounts
			$socialflow->options->set('accounts', $accounts);

			// Remove initial nag
			$socialflow->options->set( 'initial_nag', 0 );

			// Set send to and visible accounts
			$socialflow->options->set( 'show', $enabled );
			$socialflow->options->set( 'send', $enabled );

			// Save update options
			$socialflow->options->save();

			wp_redirect( admin_url( 'options-general.php?page=socialflow' ) );
			exit;

		} elseif ( isset($_GET['sf_unauthorize']) AND current_user_can( 'manage_options' ) ) {
			// To-Do Check temporary token
			// Remove all options
			delete_option('socialflow');

			wp_redirect( admin_url( 'options-general.php?page=socialflow' ) );
		}
	}

	/**
	 * Sanitizes SocialFlow settings
	 *
	 * This is the callback for register_setting()
	 *
	 * @since 2.0
	 * @access public
	 *
	 * @param string|array $settings Settings passed in from filter
	 * @return string|array Sanitized settings
	 */
	function save_settings( $settings = array() ) {
		global $socialflow;
		$settings = empty( $settings ) ? array() : $settings;

		// Merge current settings (we need to store account information which is not stored in the fields)
		$settings = $socialflow->array_merge_recursive( $socialflow->options->options, $settings );

		// Allow plugins/modules to add/modify settings when having post request
		$settings = isset( $_POST['socialflow'] ) ? apply_filters( 'sf_save_settings', $settings ) : $settings;

		return $settings;
	}

	/**
	 * Increase http request timeout 
	 * Callback for http_request_timeout filter
	 *
	 * @since 2.0
	 * @param int - number of seconds before connection will be terminated
	 */
	function http_request_timeout( $time ) {

		// Maybe some other plugin has already increased this parametr
		if ( $time < 10 )
			$time = 10;

		return $time;
	}

	/**
	 * Installs SocialFlow
	 *
	 * @since 2.0
	 * @access private
	 */
	function install() {
		global $socialflow;

	}

	/**
	 * Uninstalls SocialFlow
	 *
	 * @since 2.0
	 * @access private
	 */
	function uninstall() {
		global $socialflow;

		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		// Delete options
		delete_option( 'socialflow' );
	}

	/**
	 * PHP4 constructor
	 *
	 * @since 2.0
	 * @access public
	 */
	function SocialFlow_Admin() {
		$this->__construct();
	}
}