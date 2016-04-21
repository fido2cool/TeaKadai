<ul class="ps-submenu clearfix">
	<li class=" ">
		<?php $class = '';
				if (isset($_GET['edit']))
					$class = ' class="active" ';
		?>
		<a href="<?php echo PeepSo::get_page('profile'); ?>?edit" <?php echo $class; ?> ><?php _e('Edit Profile', 'peepso'); ?></a>
	</li>
	<li class=" ">
		<?php $class = '';
				if (isset($_GET['pref']))
					$class = ' class="active" ';
		?>
		<a href="<?php echo PeepSo::get_page('profile'); ?>?pref" <?php echo $class; ?> ><?php _e('Preferences', 'peepso'); ?></a>
	</li>
	<li class=" ">
		<?php $class = '';
				if (isset($_GET['notifications']))
					$class = ' class="active" ';
		?>
		<a href="<?php echo PeepSo::get_page('profile'); ?>?notifications" <?php echo $class; ?> ><?php _e('Notifications', 'peepso'); ?></a>
	</li>
	<li class=" ">
		<?php $class = '';
				if (isset($_GET['blocked']))
					$class = ' class="active" ';
		?>
		<a href="<?php echo PeepSo::get_page('profile'); ?>?blocked" <?php echo $class; ?> ><?php _e('Block List', 'peepso'); ?></a>
	</li>
	<li class=" ">
		<?php $class = '';
				if (isset($_GET['alerts']))
					$class = ' class="active" ';
		?>
		<a href="<?php echo PeepSo::get_page('profile'); ?>?alerts" <?php echo $class; ?> ><?php _e('Emails and Notifications', 'peepso'); ?></a>
	</li>
	<?php if (PeepSo::get_option('site_registration_allowdelete', FALSE) && ! PeepSo::is_admin()) { ?>
	<li class="action">
		<?php $class = '';
				if (isset($_GET['delete']))
					$class = ' class="active" ';
		?>
		<a href="#" onclick="profile.delete_profile(); return false;" <?php echo $class; ?> ><?php _e('Delete Profile', 'peepso'); ?></a>
	</li>
	<?php } ?>
</ul>
<?php peepso('load-template', 'activity/dialogs'); ?>
