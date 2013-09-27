<?php
/**
 * Template for displaying full message statistics
 *
 * @since 1.0
 *
 * @param $form_messages - array of successful form submission statuses
 * @param $last_sent     - string for last sent date in mysql date format
 */

global $socialflow;
$i = 0;
if ( !empty( $form_messages ) ) : ?>
<table cellspacing="0" class="wp-list-table widefat fixed sf-statistics">
	<tbody class="list:statistics">
		<tr>
			<th colspan="2">
				<a href="#" class="sf-js-update-multiple-messages clickable"><?php _e( 'Refresh Stats', 'socialflow' ) ?></a>
			</th>
			<th class="refresh-column"></th>
		</tr>
		<?php $i = 0; ?>
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
				<?php if ( $first ) : ?>
				<tr class="message <?php echo $alt ?>" >
					<th class="massage-date" colspan="3" >
						<?php echo mysql2date( 'd F, Y h:i', $date ); ?>
					</th>
				</tr>
				<?php endif; ?>
				<tr class="message <?php echo $alt ?>" data-id="<?php echo $message['content_item_id'] ?>" data-date="<?php echo $date ?>" data-account-id="<?php echo $user_id ?>" data-post_id="<?php echo $post_id; ?>" >
					<td class="account column-account">
						<?php echo $socialflow->accounts->get_display_name( $user_id, false ); ?>
					</td>
					<td class="column-status">
						<span class="status">
							<?php echo $message['status']; ?>
							<?php echo $queue_status; ?>
						</span>
					</td>
					<td class="refresh-column">
						<img class="sf-message-loader" style="display:none;" src="<?php echo plugins_url( '/socialflow/assets/images/wpspin.gif' ) ?>" alt="">
					</td>
				</tr>

			<?php $first = false; endforeach; ?>
		<?php endforeach ?>
	</tbody>
</table>
<?php endif; // we have statuses ! ?>