<?php
/**
 * Template for displaying full message statistics
 *
 * @since 1.0
 *
 * @param $form_messages - array of successful form submission statuses
 * @param $last_sent     - string for last sent date in mysql date format
 * @param $post_id       - int ID for current post
 */
global $socialflow;
$i = 0;
?>
<div class="full-stats-container">
<?php if ( !empty( $form_messages ) ) : ?>
<p>
	<?php printf( __( 'Last time message was successfully sent at %s', 'socialflow' ), mysql2date( 'd F, Y h:i a', $last_sent ) ); ?>
	<span id="js-sf-toggle-statistics" class="clickable"><?php _e( 'Expand Statistics.', 'socialflow' ); ?></span>
</p>
<table id="sf-statistics" cellspacing="0" class="wp-list-table widefat fixed sf-statistics" style="display:none">
	<thead><tr>
		<th style="width:150px" class="manage-column column-date" scope="col">
			<span><?php _e( 'Last Sent', 'socialflow' ) ?></span>
		</th>
		<th class="manage-column column-status" scope="col">
			<?php _e( 'Account', 'socialflow' ) ?>
		</th>
		<th class="manage-column column-status" scope="col">
			<?php _e( 'Status', 'socialflow' ) ?>
		</th>
		<th scope="col" width="20px">
			<img title="<?php _e( 'Refresh Message Stats', 'socialflow' ); ?>" alt="<?php _e( 'Refresh', 'socialflow' ); ?>" class="sf-js-update-multiple-messages" src="<?php echo plugins_url( '/socialflow/assets/images/reload.png' ) ?>" >
		</th>
	</tr></thead>

	<tbody class="list:statistics">
		<?php foreach ( $form_messages as $date => $success ) : 
			$first = true;
			$alt = ( $i%2 == 0 ) ? 'alternate' : '';
			$i++;
		?>
			<?php foreach ( $success as $user_id => $message ) : 
				$account = $socialflow->accounts->get( $user_id );

				// In queue status
				if ( isset( $message['is_published'] ) ) {
					$queue_status = ( 0 == $message['is_published'] ) ? __( 'In Queue', 'socialflow' ) : __( 'Published', 'socialflow' );
				} else {
					$queue_status = '';
				}
			?>
				<tr class="message <?php echo $alt ?>" data-id="<?php echo $message['content_item_id'] ?>" data-date="<?php echo $date ?>" data-account-id="<?php echo $user_id ?>" data-post_id="<?php echo $post_id; ?>" >
					<?php if ( $first ) : ?>
					<td class="username column-username" rowspan="<?php echo count( $success ); ?>"  >
						<?php echo mysql2date( 'd F, Y h:i', $date ); ?>
					</td>
					<?php endif; ?>
					<td class="account column-account">
						<?php echo $socialflow->accounts->get_display_name( $user_id ); ?>
					</td>
					<td class="status column-status" >
						<?php echo $message['status']; ?>
						<?php echo $queue_status; ?>
					</td>
					<td>
						<img class="sf-message-loader" style="display:none;" src="<?php echo plugins_url( '/socialflow/assets/images/wpspin.gif' ) ?>" alt="">
					</td>
				</tr>

			<?php $first = false; endforeach; ?>
		<?php endforeach ?>
	</tbody>
</table>
<?php endif; // we have statuses ! ?>
</div><!-- full stats container -->