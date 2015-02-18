<div class="main">

	<table style="border: none;" width="100%" id="wc_title_test_list" class="wp-list-table widefat fixed posts">

		<thead>
		<tr>

			<th width="30" scope="col" align="left"><?php echo __('ID', 'wc_title_test'); ?></th>
			<th width="100" scope="col" align="left"><?php echo __('Status', 'wc_title_test'); ?></th>
			<th scope="col" align="left"><?php echo __('Title', 'wc_title_test'); ?></th>
			<th width="70" scope="col" align="left"><?php echo __('Type', 'wc_title_test'); ?></th>
			<th width="100" scope="col" align="left"><?php echo __('Requests', 'wc_title_test'); ?></th>
			<th width="100" scope="col" align="left" title="Order count / Requests * 100"><?php echo __('Order count', 'wc_title_test'); ?></th>
			<th width="50" scope="col" align="left"><?php echo __('Rate', 'wc_title_test'); ?></th>
			<th width="150" scope="col" align="left"><?php echo __('Action', 'wc_title_test'); ?></th>

		</tr>
		</thead>

		<tbody>

		<?php foreach ($tests as $key => $value) { ?>

			<?php $original = isset($value->original) ? $value->original : false; ?>

			<tr class="<?php echo $key % 2 == 0 ? 'alternate' : ''; ?>">

				<td class="column-id"><?php echo $value->ID ?></td>
				<td class="column-status">
					<?php echo $value->post_status == "publish" ? '<span class="published">Published</span>' : '<span class="draft">Draft</span>' ?>
				</td>
				<td><?php echo $value->post_title ?></td>
				<td><?php echo $original ? 'Original' : 'Test'; ?></td>
				<td><?php echo $value->display_count ? $value->display_count : 0 ; ?> ( <?php echo $total_display_count > 0 ? round($value->display_count * 100 / $total_display_count, 2) : 0; ?>% )</td>
				<td>
					<a href="edit.php?post_type=shop_order&filter_by_title_test=<?php echo $value->ID; ?>">
						<?php echo $value->order_count ?>
					</a>
				</td>
				<td title="<?php echo $value->order_count ?> / <?php echo $value->display_count ?> * 100">
					<?php echo $value->display_count > 0 ? round($value->order_count / $value->display_count * 100, 2) : 0; ?>%
				</td>
				<td>
					<?php if (!$original) {  ?>
						<a target="_blank" href="<?php echo get_post_permalink($parent_post->ID) . (get_option('permalink_structure') ? '?' : '&') ?>wc_title_test=<?php echo $value->ID; ?>">Preview</a><span class="sep"> | </span>
						<a href="<?php echo get_edit_post_link($value->ID); ?>">Edit</a><span class="sep"> | </span>
						<a class="delete-title-test submitdelete" data-id="<?php echo $value->ID; ?>" href="<?php echo get_delete_post_link($value->ID); ?>">Delete</a>
					<?php } else { ?>
						<a target="_blank" href="<?php echo get_post_permalink($parent_post->ID); ?>?wc_title_test=original">Preview</a>
					<?php } ?>
				</td>

			</tr>

		<?php } ?>

		</tbody>


	</table>

</div>