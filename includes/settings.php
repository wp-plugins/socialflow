<?php

class SocialFlow_Settings {
	public function __construct() {
		add_action( 'admin_init', array( $this, 'settings_init' ) );
		add_action( 'admin_menu', array( $this, 'settings_menu' ) );
	}

	public function settings_page() {
		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2><?php _e( 'SocialFLow Settings' ); ?></h2>
			<form method="post" action="options.php">
				<?php
					settings_fields( 'sf_options' );
					do_settings_sections( 'sf-settings' );
					submit_button();
				?>
			</form>

		</div>
		<?php
	}

	public function settings_init() {
		register_setting( 'sf_options', 'socialflow' );
		add_settings_section( 'sf_general', '', '__return_false', 'sf-settings' );
		add_settings_field( 'sf_status', __( 'SocialFlow Plugin Status', 'socialflow' ), array( $this, 'status_field' ) , 'sf-settings', 'sf_general' );
		add_settings_field( 'sf_message_option', __( 'Default Message Option', 'socialflow' ), array( $this, 'message_option_field' ) , 'sf-settings', 'sf_general' );
		add_settings_field( 'sf_accounts', __( 'Message these accounts when blog posts are published', 'socialflow' ), array( $this, 'accounts_field' ) , 'sf-settings', 'sf_general' );
		add_settings_field( 'sf_enable', __( 'Automatically send blog post messages to SocialFlow', 'socialflow' ), array( $this, 'enable_field' ) , 'sf-settings', 'sf_general' );
	}

	function settings_menu() {
		add_options_page( __( 'SocialFlow Settings', 'socialflow' ), __( 'SocialFlow', 'socialflow' ), 'manage_options', 'sf-settings', array( $this, 'settings_page' ) );
	}

	function status_field() {
		$options = get_option( 'socialflow', array() );
		echo empty( $options['access_token'] ) ? __( 'Not Authorized', 'socialflow' ) : __( 'Authorized', 'socialflow' );
		if ( ! empty( $options['access_token'] ) ) : ?> - <a href="<?php echo esc_url( add_query_arg( 'sf_disconnect', true, admin_url( '/' ) ) ); ?>"><?php _e( 'Disconnect', 'socialflow' ); ?></a><?php endif;
	}

	function enable_field() {
		$options = get_option( 'socialflow', array() );
		$options['enable'] = isset( $options['enable'] ) ? $options['enable'] : 0; ?>
		<input type="radio" name="socialflow[enable]" id="enable-yes" value="1" <?php checked( $options['enable'], 1 ); ?> /> <label for="enable-yes" class="selectit"><?php _e( 'Yes', 'socialflow' ); ?> </label>
		<input type="radio" name="socialflow[enable]" id="enable-no" value="0" <?php checked( $options['enable'], 0 ); ?> /> <label for="enable-no" class="selectit"><?php _e( 'No', 'socialflow' ); ?></label>
		<?php
	}

	function message_option_field() {
		$options = get_option( 'socialflow', array() );
		$options['publish_option'] = empty( $options['publish_option'] ) ? 'optimize' : $options['publish_option'];
		if ( ! empty( $options['access_token'] ) ) {
			foreach( $options['access_token'] as $key => $val )
				echo '<input type="hidden" name="socialflow[access_token]['. esc_attr( $key ) . ']" value="'. esc_attr( $val ) .'" />';
		}
		?>
		<select id="socialflow" name="socialflow[publish_option]">
			<option value="optimize" <?php selected( $options['publish_option'], 'optimize' ); ?>><?php _e( 'Optimize', 'socialflow' ); ?></option>
			<option value="publishnow" <?php selected( $options['publish_option'], 'publishnow' ); ?>><?php _e( 'Publish Now', 'socialflow' ); ?></option>
			<option value="hold" <?php selected( $options['publish_option'], 'hold' ); ?>><?php _e( 'Hold', 'socialflow' ); ?></option>
		</select> <?php
	}

	function accounts_field() {
		$options = get_option( 'socialflow', array() );
		if ( !empty( $options['access_token'] ) ) {
			require_once( dirname( __FILE__ ) ) . '/class-wp-socialflow.php';
			$sf = new WP_SocialFlow( SocialFlow_Plugin::consumer_key, SocialFlow_Plugin::consumer_secret, $options['access_token']['oauth_token'], $options['access_token']['oauth_token_secret'] );

			$accounts = $sf->get_account_list();

			if ( false === $accounts ) {
				?><div class="error"><p><?php _e( 'There was a problem communicating with the SocialFlow API. Please Try again later. If this problem persists, please email support@socialflow.com', 'socialflow' ); ?></p></div><?php
			} elseif ( empty( $accounts ) ) {
				?><div class="error"><p><?php _e( 'You have not authorized SocialFlow to optimize any Twitter accounts or Facebook Pages. Please go to <a href="https://app.socialflow.com">SocialFlow</a> to set this up.', 'socialflow' ); ?></p></div><?php
			} else {
				foreach ( $accounts as $key => $account ) {
					if ( !isset( $account['service_type'] ) || 'publishing' != $account['service_type'] ) {
						unset( $accounts[ $key ] );
					} else {
						if ( isset( $options['accounts'][$key]['status'] ) )
							$accounts[ $key ]['status'] = $options['accounts'][$key]['status'];
						else
							$accounts[ $key ]['status'] = 'off';
					}
				}
			}
			$options['accounts'] = $accounts;
			update_option( 'socialflow', $options );
		}
		
		if ( !empty( $accounts ) ) {
			foreach ( $accounts as $key => $account ) {
				$id = 'sf[accounts][' . esc_attr( $account['client_service_id'] ) . ']';
				echo '<input type="hidden" id="socialflow[accounts][' . esc_attr( $key ) .'][service_type]" value="' . $account['service_type'] . '" />';
				echo '<input type="hidden" id="socialflow[accounts][' . esc_attr( $key ) .'][service_user_id]" value="' . $account['service_user_id'] . '" />';
				echo '<input type="hidden" id="socialflow[accounts][' . esc_attr( $key ) .'][create_date]" value="' . $account['create_date'] . '" />';
				echo '<input type="hidden" id="socialflow[accounts][' . esc_attr( $key ) .'][name]" value="' . $account['name'] . '" />';
				echo '<input type="hidden" id="socialflow[accounts][' . esc_attr( $key ) .'][client_service_id]" value="' . $account['client_service_id'] . '" />';
				echo '<input type="hidden" id="socialflow[accounts][' . esc_attr( $key ) .'][account_type]" value="' . $account['account_type'] . '" />';
				echo '<input type="hidden" id="socialflow[accounts][' . esc_attr( $key ) .'][screen_name]" value="' . $account['screen_name'] . '" />';

				echo '<label for="' . $id . '" class="selectit"><input type="checkbox" id="' . $id . '" name="socialflow[accounts][' . esc_attr( $key ) . '][status]" ' . checked( $account['status'], 'on', false ) . ' /> ' . esc_html( $account['name'].' - '.ucfirst($account['account_type']) ) . '</label><br />';
			}
		}
	}
}

new SocialFlow_Settings;
?>