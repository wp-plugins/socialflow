<?php
/*
Plugin Name: SocialFlow
Plugin URI: http://wordpress.org/extend/plugins/socialflow/
Description: 
Author: SocialFlow, Stresslimit, Pete Mall
Version: 0.1
Author URI: http://socialflow.com/
License: GPLv2 or later
Text Domain: socialflow
Domain Path: /i18n

*/

class SocialFlow_Plugin {

	public static $instance;
	const consumer_key = 'acbe74e2cc182d888412';
	const consumer_secret = '650108a50ea3cb2bd6f9';

	public function __construct() {
		global $pagenow;
		self::$instance = $this;

		$this->l10n = array(
			'true'       => __( 'Yes',         'socialflow' ),
			'false'      => __( 'No',          'socialflow' ),
			'optimize'   => __( 'Optimize',    'socialflow' ),
			'publishnow' => __( 'Publish Now', 'socialflow' ),
			'hold'       => __( 'Hold',        'socialflow' ),
			'max'        => 'index.php' == $pagenow ? 140 : 118
		);
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Load textdomain and hooks.
	 */
	public function init() {
		// Translations
		load_plugin_textdomain( 'socialflow', false, basename( dirname( __FILE__ ) ) . '/i18n' );

		add_action( 'post_row_actions',                array( $this, 'row_actions'               ), 10, 2 );
		add_action( 'page_row_actions',                array( $this, 'row_actions'               ), 10, 2 );
		add_action( 'admin_enqueue_scripts',           array( $this, 'enqueue'                   ), 10, 2 );
		add_action( 'transition_post_status',          array( $this, 'transition_post_status'    ), 10, 3 );
		add_action( 'add_meta_boxes',                  array( $this, 'add_meta_box'              ) );
		add_action( 'save_post',                       array( $this, 'save_post'                 ) );
		add_action( 'wp_dashboard_setup',              array( $this, 'register_dashboard_widget' ) );
		add_action( 'wp_ajax_sf-shorten-msg',          array( $this, 'shorten_message'           ) );
		add_action( 'admin_init',                      array( $this, 'admin_init'                ) );
		add_action( 'admin_notices',                   array( $this, 'admin_notices'             ) );
	}

	public function admin_init() {
		if ( isset( $_POST['sf'] ) ) {
			$o = get_option( 'socialflow' );
			$o['publish_option'] = sanitize_text_field( $_POST['sf']['message_option'] );
			$o['enable'] = sanitize_text_field( $_POST['sf']['enable'] );

			if ( !empty( $o['accounts'] ) ) {
				foreach ( $o['accounts'] as $id => $account ) {
					if ( in_array( $id, $_POST['sf']['accounts'] ) )
						$o['accounts'][$id]['status'] = 1;
					else
						$o['accounts'][$id]['status'] = 0;
				}
			}
			update_option( 'socialflow', $o );
		} elseif ( isset( $_GET['action'], $_GET['_wpnonce'], $_GET['post'] ) && 'sf-publish' == $_GET['action'] && wp_verify_nonce( $_GET['_wpnonce'], 'sf-publish_' . $_GET['post'] ) ) {
			$sent = false;
			$referer = remove_query_arg( array( 'action', '_wpnonce', 'post' ), wp_get_referer() );
			$message = get_post_meta( $_GET['post'], 'sf_text', true );

			if ( ! $message = get_post_meta( $_GET['post'], 'sf_text', true ) )
				$message = get_the_title( $_GET['post'] );

			$message .= ' ' . get_permalink( $_GET['post'] );

			if ( $this->send_message( sanitize_text_field( $message ) ) ) {
				update_post_meta( $_GET['post'], 'sf_timestamp', date_i18n( 'Y-m-d G:i:s', false, 'gmt' ) . ' UTC' );
				$sent = true;
			}
			wp_redirect( add_query_arg( 'sf_sent', $sent, $referer ) );
			exit;
		}
	}

	public function add_meta_box() {
		add_meta_box( 'socialflow', __( 'SocialFlow', 'socialflow' ), array( $this, 'display_compose_form' ), 'post', 'side', 'high', array( 'post_page' => true ) );
	}

	public function enqueue( $hook ) {
		if ( ! in_array( $hook, array( 'index.php', 'post.php', 'post-new.php', 'edit.php' ) ) )
			return;

		$color = 'fresh' == get_user_meta( get_current_user_id(), 'admin_color', true ) ? '#F1F1F1' : '#F5FAFD';
		?>
<style type="text/css">
	#socialflow div.inside { margin: 0; padding: 0 }
	#socialflow h3 { background: url(<?php echo plugins_url( 'images/socialflow.png', __FILE__ ); ?>) 7px <?php echo $color; ?> no-repeat; }
	#socialflow h3 span { margin-left: 25px; }
	#socialflow th { width: 100px; }
	#socialflow fieldset { line-height: 1.4em; padding: 10px 0px }
	#socialflow fieldset span { padding-right: 15px }
	#socialflow #shorten-explanation { text-align:left; }
	#shorten-links, #count { float: right; margin-left: 25px }
	#minor-publishing #compose { float: left; }
	#sf-text { margin-bottom: 2px; height: 6em; width: 100%; }
	.sf-error { color: #BC0B0B; }
	#sf-hidden { background: none; border: none; font-weight: 900; vertical-align: middle; }
	.misc-pub-section:first-child { border-top-width: 0; }
	#message-option-display, #sf-username-display, #sf-passcode-display, #enable-display { font-weight: bold; }
	#message-option-select, #post-format, #edit-accounts-div { line-height: 2.5em; margin-top: 3px; }
	#socialflow .submit { text-align: center; padding: 10px 10px 8px !important; clear: both; border-top: none; }
	#enable-select, #post-format { line-height: 2.5em; margin-top: 3px; }
	.socialflow-authorize { margin: 10px; }
	#shorten-links #ajax-loading { padding: 0 5px;}
	div.sf-updated { background-color: #ffffe0; border-color: #e6db55; }
	div.sf-error { background-color: #FFEBE8; border-color: #C00; }
	div.sf-updated, div.sf-error { -webkit-border-radius: 3px; border-radius: 3px; border-width: 1px; border-style: solid; padding: 5px; margin: 10px; }
	div.sf-updated p, div.sf-error p { margin: 0.5em 0; padding: 2px; }
	#sf-accounts { max-height: 250px; overflow: auto; }
</style>
		<?php
		wp_enqueue_script( 'textarea-counter', plugins_url( 'js/textarea-counter.js', __FILE__ ), array( 'jquery' ), '20111020' );
		wp_enqueue_script( 'socialflow', plugins_url( 'js/socialflow.js', __FILE__ ), array( 'jquery', 'textarea-counter' ), '20111020' );
		wp_localize_script( 'socialflow', 'sf_l10n', $this->l10n );
	}

	/**
	 * Add SocialFlow link to row actions for posts and pages.
	 */
	public function row_actions( $actions, $post ) {
		if ( 'publish' == $post->post_status ) {
			$url = add_query_arg( array( 'action' => 'sf-publish', 'post' => $post->ID ), admin_url() );
			$timestamp = get_post_meta( $post->ID, 'sf_timestamp', true );
			$title = $timestamp ? $timestamp : __( 'Send to SocialFlow', 'socialflow' );
			$actions['sf_publish'] = '<a href="' . wp_nonce_url( $url, "sf-publish_{$post->ID}" ) . '" style="color: #532F64;" title="' . esc_attr( $title ) . '">' . __( 'Send to SocialFlow', 'socialflow' ) . '</a>';
		}
		return $actions;
	}

	/**
	 * Register the SocialFlow dashboard widget.
	 */
	public function register_dashboard_widget() {
		if ( current_user_can( 'publish_posts' ) )
			wp_add_dashboard_widget( 'socialflow', __( 'SocialFlow', 'socialflow' ), array( $this, 'dashboard_widget' ), array( $this, 'dashboard_widget_settings' ) );
	}

	/**
	 * Display the SocialFlow dashboard widget.
	 */
	public function dashboard_widget() {
		$options = get_option( 'socialflow' );

		if ( isset( $_POST['socialflow'], $_POST['sf_textnonce'] ) && wp_verify_nonce( $_POST['sf_textnonce'], plugin_basename( __FILE__ ) ) ) {
			require_once( dirname( __FILE__ ) ) . '/includes/class-wp-socialflow.php';
			$sf = new WP_SocialFlow( self::consumer_key, self::consumer_secret, $options['access_token']['oauth_token'], $options['access_token']['oauth_token_secret'] );
			$message = sanitize_text_field( $_POST['socialflow']['text'] );
			$publish_option = sanitize_text_field( $_POST['message_option'] );
			$publish_option = 'publishnow' == $publish_option ? 'publish now' : $publish_option;

			if ( empty( $options['accounts'] ) ) {
				?><div class="sf-error"><p><?php _e( 'No social account selected.', 'socialflow' ); ?></p></div><?php
				return;
			}

			if ( $this->send_message( $message, $publish_option ) ) {
				?><div class="sf-updated"><p><?php _e( 'Your message has been sent.', 'socialflow' ); ?></p></div><?php
			} else {
				?><div class="sf-error"><p><?php printf( __( 'There was a problem communicating the SocialFlow API. If this error persists, please <a href="%s">submit a support request</a>.', 'socialflow' ), 'http://socialflow.com/contact' ); ?></p></div><?php
			}
		}

		if ( isset( $_REQUEST['sf_disconnect'] ) ) {
			unset( $options['access_token'] );
			unset( $options['accounts'] );
			update_option( 'socialflow', $options );
		}

		if ( isset( $_GET['oauth_token'], $options['oauth_token'] ) ) {
			if ( $options['oauth_token'] == $_GET['oauth_token'] ) {
				require_once( dirname( __FILE__ ) ) . '/includes/class-wp-socialflow.php';
				$sf = new WP_SocialFlow( self::consumer_key, self::consumer_secret, $options['oauth_token'], $options['oauth_token_secret'] );
				$options['access_token'] = $sf->get_access_token( $_GET['oauth_verifier'] );
				unset( $options['oauth_token'] );
				unset( $options['oauth_token_secret'] );
				$options['publish_option'] = empty( $options['publish_option'] ) ? 'publishnow' : $options['publish_option'];
				$options['enable'] = 'true';
				$options['accounts'] = $sf->get_account_list();
				foreach ( $options['accounts'] as &$account )
					$account['status'] = true;
				update_option( 'socialflow', $options );
			}
		}

		if ( 
			! $options ||
			empty( $options['access_token'] ) ||
			empty( $options['access_token']['oauth_token'] ) ||
			empty( $options['access_token']['oauth_token_secret'] ) ) {
			$this->display_authorize_form();
		} else {
			$this->display_compose_form();
		}
	}

	/**
	 * Handle the SocialFlow dashboard widget settings.
	 */
	public function dashboard_widget_settings() {
		$options = get_option( 'socialflow', array() );

		if ( empty( $options ) )
			$options['socialflow'] = array( 'access_token' => array(), 'option' => 'optimize', 'enable' => 'false' );

		if ( $_POST && isset( $_POST['socialflow'] ) ) {
			$options['socialflow']['option']   = isset( $_POST['socialflow']['option'] ) ? sanitize_text_field( $_POST['socialflow']['option'] ) : '';
			$options['socialflow']['enable']   = isset( $_POST['socialflow']['enable'] ) ? sanitize_text_field( $_POST['socialflow']['enable'] ) : '';

			update_option( 'socialflow', $options );
		}

		if ( ! isset( $_GET['edit'] ) )
			return;

		$this->display_settings_form( $options );
	}

	/**
	 * Display the authorize form for the dashboard widget.
	 */
	private function display_authorize_form() {
		require_once( dirname( __FILE__ ) ) . '/includes/class-wp-socialflow.php';
		$sf = new WP_SocialFlow( self::consumer_key, self::consumer_secret );
		if ( ! $request_token = $sf->get_request_token( admin_url() ) ) {
			?><div class="misc-pub-section"><p><span class="sf-error"><?php _e( 'There was a problem communicating the SocialFlow API.</span> If this error persists, please <a href="http://socialflow.com/contact">submit a support request</a>.', 'socialflow' ); ?></p></div><?php
			return;
		}

		$signup = 'http://socialflow.com/signup';
		if ( $links = $sf->get_account_links( self::consumer_key ) )
			$signup = $links->signup;

		$options = get_option( 'socialflow' );
		$options['oauth_token'] = $request_token['oauth_token'];
		$options['oauth_token_secret'] = $request_token['oauth_token_secret'];
		
		update_option( 'socialflow', $options );
		?>
		<div class="socialflow-authorize">
			<p><?php _e( 'Optimize publishing to Twitter and Facebook using <a href="http://socialflow.com/">SocialFlow</a>.', 'socialflow' ); ?></p>
			<p><?php printf( __( 'Don’t have a SocialFlow account? <a href="%1$s">Sign Up</a>', 'socialflow' ), esc_url( $signup ) ); ?></p>
			<p><a href="%2$s"><?php _e('Help/FAQ'); ?></a></p>

			<p><a class="button-primary" href="<?php echo esc_url( $sf->get_authorize_url( $request_token ) ); ?>"><?php _e( 'Connect to SocialFlow', 'socialflow' ); ?></a></p>
		</div>
		
		<?php
	}

	/**
	 * Display the settings form for the dashboard widget.
	 */
	private function display_settings_form( $options ) {
		$default = array( 'publish_option' => 'optimize', 'enable' => 'false', 'accounts' => array() );
		$options = array_merge( $default, $options );

		?><div class="submitbox"><?php

		if ( !empty( $options['access_token'] ) ) {
			require_once( dirname( __FILE__ ) ) . '/includes/class-wp-socialflow.php';
			$sf = new WP_SocialFlow( self::consumer_key, self::consumer_secret, $options['access_token']['oauth_token'], $options['access_token']['oauth_token_secret'] );

			$accounts = $sf->get_account_list();

			if ( !$accounts ) {
				?><div class="misc-pub-section"><p><span class="sf-error"><?php _e( 'There was a problem communicating the SocialFlow API.</span> If this error persists, please <a href="http://socialflow.com/contact">submit a support request</a>.', 'socialflow' ); ?></p></div><?php
			} else {
				foreach ( $accounts as $key => $account ) {
					if ( !isset( $account['service_type'] ) || 'publishing' != $account['service_type'] )
					 	unset( $accounts[ $key ] );
					else
						$accounts[ $key ]['status'] = isset( $options['accounts'][$key]['status'] ) ? $options['accounts'][$key]['status'] : true;
				}
			}
			$options['accounts'] = $accounts;
			update_option( 'socialflow', $options );
		}

		?>
			<div id="misc-publishing-actions">

				<div class="misc-pub-section">
					<label for="sf-username-display"><?php _e( 'SocialFlow:', 'socialflow' ); ?></label>
					<span id="sf-username-display" class="hide-if-no-js"><?php echo empty( $options['access_token'] ) ? __( 'Not Authorized', 'socialflow' ) : __( 'Authorized', 'socialflow' );  ?></span>
					<?php if ( ! empty( $options['access_token'] ) ) : ?>
						<a href="#" class="edit-sf-auth hide-if-no-js" tabindex='4'><?php _e( 'Edit', 'socialflow' ); ?></a>
						<div id="sf-auth-div" class="hide-if-js">
							<p><a href="<?php echo add_query_arg( 'sf_disconnect', true, admin_url() ); ?>" class="button-primary"><?php _e( 'Disconnect', 'socialflow' ); ?></a>
					 		<a href="#" class="cancel-sf-auth hide-if-no-js">Cancel</a></p>
						</div>
					<?php endif; ?>
				</div>

				<div class="misc-pub-section">
					<label for="sf_message_option"><?php _e( 'Default Message Option:', 'socialflow' ); ?></label>
					<span id="message-option-display" class="hide-if-no-js"><?php echo esc_html( $this->l10n[ $options['publish_option'] ] ); ?></span>
					<a href="#" class="edit-message-options hide-if-no-js" tabindex='4'><?php _e( 'Edit', 'socialflow' ); ?></a>						
					<div id="message-option-select" class="hide-if-js">
						<input type="radio" name="sf[message_option]" id="message-option-optimize" value="optimize" <?php checked( $options['publish_option'], 'optimize' ); ?> /> <label for="message-option-optimize" class="selectit"><?php _e( 'Optimize', 'socialflow' ); ?></label><br />
						<input type="radio" name="sf[message_option]" id="message-option-publishnow" value="publishnow" <?php checked( $options['publish_option'], 'publishnow' ); ?> /> <label for="message-option-publishnow" class="selectit"><?php _e( 'Publish Now', 'socialflow' ); ?></label><br />
						<input type="radio" name="sf[message_option]" id="message-option-hold" value="hold" <?php checked( $options['publish_option'], 'hold' ); ?> /> <label for="message-option-hold" class="selectit"><?php _e( 'Hold', 'socialflow' ); ?></label><br />
					 	<a href="#" class="save-message-options hide-if-no-js button">OK</a>
					 	<a href="#" class="cancel-message-options hide-if-no-js">Cancel</a>
					</div>
				</div>

				<div class="misc-pub-section" id="sf-accounts">
					<label for="sf_message_option"><?php _e( 'Account(s):', 'socialflow' ); ?></label>
					<div id="edit-accounts-div">
						<?php if ( !empty( $accounts ) ) {
							foreach ( $accounts as $account ) {
								$id = 'sf[accounts][' . esc_attr( $account['client_service_id'] ) . ']';
								echo '<label for="' . $id . '" class="selectit"><input type="checkbox" id="' . $id . '" name="sf[accounts][]" value="' . esc_attr( $account['client_service_id'] ) . '" ' . checked( $account['status'], true, false ) . ' /> ' . esc_html( $account['name'].' - '.ucfirst($account['account_type']) ) . '</label><br />';
								}
							} ?>
					</div>
				</div>

				<div class="misc-pub-section">
					<label for="sf_enable"><?php _e( 'Send new blog post messages to SocialFlow:', 'socialflow' ); ?></label>
					<span id="enable-display" class="hide-if-no-js"><?php echo esc_html( $this->l10n[ $options['enable'] ] ); ?></span>
					<a href="#" class="edit-enable hide-if-no-js" tabindex='4'><?php _e( 'Edit', 'socialflow' ); ?></a>						
					<div id="enable-select" class="hide-if-js">
						<input type="radio" name="sf[enable]" id="enable-yes" value="true" <?php checked( $options['enable'], 'true' ); ?> /> <label for="enable-yes" class="selectit"><?php _e( 'Yes', 'socialflow' ); ?></label><br />
						<input type="radio" name="sf[enable]" id="enable-no" value="false" <?php checked( $options['enable'], 'false' ); ?> /> <label for="enable-no" class="selectit"><?php _e( 'No', 'socialflow' ); ?></label><br />
					 	<a href="#" class="save-enable hide-if-no-js button">OK</a>
					 	<a href="#" class="cancel-enable hide-if-no-js">Cancel</a>
					</div>
				</div>

			</div>
		</div><?php
		
	}

	/**
	 * Display the compose message form for the dashboard widget.
	 */
	public function display_compose_form( $post = null, $metabox = array( 'args' => array( 'post_page' => false ) ) ) {
		$message_option = 'optimize';
		$options = get_option( 'socialflow' );

		?>
		<form name="sf-post" method="post" id="sf-post">
		<?php wp_nonce_field( plugin_basename( __FILE__ ), 'sf_textnonce' ); ?>
		<div class="submitbox">
			<div id="minor-publishing">
				<div id="minor-publishing-actions">
					<ul>
						<li id='compose'><?php _e( 'Compose', 'socialflow' ); ?></li>
						<li id="sf_char_count"><span>140</span></li>
<?php	if ( $metabox['args']['post_page'] ) : ?>
						<li id="shorten-explanation"><p class="description"><?php _e('The link to your post will be included when your message is sent.') ?></p></li>
<?php	else : ?>
						<li id="shorten-links">
							<img src="<?php echo admin_url( 'images/wpspin_light.gif' ); ?>" class="ajax-loading" id="ajax-loading" alt="">
							<a href="#" class="shorten-links"><?php _e( 'Shorten Links', 'socialflow' ); ?></a>
						</li>
<?php	endif; ?>
					</ul>
					<textarea rows="1" cols="40" name="socialflow[text]" tabindex="6" id="sf-text"><?php if ( $metabox['args']['post_page'] ) echo esc_textarea( get_post_meta( $post->ID, 'sf_text', true ) ); ?></textarea>
				</div>

			<?php if ( empty( $post ) ) : ?>
				<div id="misc-publishing-actions">
					<div class="misc-pub-section misc-pub-section-last">
						<label for="sf_message_option"><?php _e( 'Publish Option:', 'socialflow' ); ?></label>
						<span id="message-option-display" class="hide-if-no-js"><?php echo esc_html( $this->l10n[ $options['publish_option'] ] ); ?></span>
						<a href="#" class="edit-message-options hide-if-no-js" tabindex='4'><?php _e( 'Edit', 'socialflow' ); ?></a>						
						<div id="message-option-select" class="hide-if-js">
							<input type="radio" name="message_option" id="message-option-optimize" value="optimize" <?php checked( $options['publish_option'], 'optimize' ); ?> /> <label for="message-option-optimize" class="selectit"><?php _e( 'Optimize', 'socialflow' ); ?></label><br />
							<input type="radio" name="message_option" id="message-option-publishnow" value="publishnow" <?php checked( $options['publish_option'], 'publishnow' ); ?> /> <label for="message-option-publishnow" class="selectit"><?php _e( 'Publish Now', 'socialflow' ); ?></label><br />
							<input type="radio" name="message_option" id="message-option-hold" value="hold" <?php checked( $options['publish_option'], 'hold' ); ?> /> <label for="message-option-hold" class="selectit"><?php _e( 'Hold', 'socialflow' ); ?></label><br />
						 	<a href="#" class="save-message-options hide-if-no-js button">OK</a>
						 	<a href="#" class="cancel-message-options hide-if-no-js">Cancel</a>
						</div>
					</div>
				</div>
			</div>

			<div id="major-publishing-actions">
				<div id="publishing-action"><?php submit_button( __( 'Send to SocialFlow', 'socialflow' ), 'primary', 'authorize', false ); ?></div>
				<div class="clear"></div>
			</div>
			<?php else : ?>
		</div>
			<?php endif; ?>
		</div>
			</form>
		<?php
	}

	function save_post( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return;

		if ( ! isset( $_POST['sf_textnonce'] ) || ! wp_verify_nonce( $_POST['sf_textnonce'], plugin_basename( __FILE__ ) ) )
			return;

		if ( 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) )
				return;
		} else {
		    if ( ! current_user_can( 'edit_post', $post_id ) )
		        return;
		}

		update_post_meta( $post_id, 'sf_text', sanitize_text_field( $_POST['socialflow']['text'] ) );
	}

	function transition_post_status( $new_status, $old_status, $post ) {
		if ( 'publish' != $new_status || 'publish' == $old_status )
		 	return;

		$message = get_post_meta( $post->ID, 'sf_text', true );
		$message .= ' ' . get_permalink( $post->ID );

		if ( $this->send_message( $message ) )
			update_post_meta( $post->ID, 'sf_timestamp', date_i18n( 'Y-m-d G:i:s', false, 'gmt' ) . ' UTC' );
	}

	public function shorten_message( $message = '' ) {
		if ( !$message = $_REQUEST['sf_message'] )
			return;

		$options = get_option( 'socialflow' );
		require_once( dirname( __FILE__ ) ) . '/includes/class-wp-socialflow.php';
		$sf = new WP_SocialFlow( self::consumer_key, self::consumer_secret, $options['access_token']['oauth_token'], $options['access_token']['oauth_token_secret'] );

		$response = $sf->shorten_links( $message );

		echo $response;
	}

	public function send_message( $message = '', $message_option = 'publish now' ) {
		$options = get_option( 'socialflow' );
		require_once( dirname( __FILE__ ) ) . '/includes/class-wp-socialflow.php';
		$sf = new WP_SocialFlow( self::consumer_key, self::consumer_secret, $options['access_token']['oauth_token'], $options['access_token']['oauth_token_secret'] );

		if ( empty( $options['accounts'] ) )
			return false;

		$return = true;
		foreach( $options['accounts'] as $account )
			$return = $return && $sf->add_message( $message, $account['service_user_id'], $account['account_type'], $message_option );

		return $return;
	}

	public function admin_notices() {
		if ( !isset( $_GET['sf_sent'] ) ) 
			return;

		if ( $_GET['sf_sent'] ) {
			?><div class="updated"><p><?php _e( 'The post has been sent to SocialFlow.', 'socialflow' ); ?></p></div><?php
		} else {
			?><div class="error"><p><?php printf( __( 'There was a problem communicating the SocialFlow API. If this error persists, please <a href="%s">submit a support request</a>.', 'socialflow' ), 'http://socialflow.com/contact' ); ?></p></div><?php
		}
		$_SERVER['REQUEST_URI'] = remove_query_arg( 'sf_sent', $_SERVER['REQUEST_URI'] );
	}

}

new SocialFlow_Plugin;
