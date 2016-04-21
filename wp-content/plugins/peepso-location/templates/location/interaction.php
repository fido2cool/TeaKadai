<div id="pslocation" class="hidden ps-postbox-dropdown">
	<div class="ps-postbox-location ps-postbox-location-compact">
		<div class="ps-postbox-loading" style="display: none;">
			<img src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>" alt="" />
			<div> </div>
		</div>
		<div class="ps-postbox-locmap">
			<div id="pslocation-map" class="ps-postbox-map"></div>
			<div class="ps-postbox-locct">
				<?php _e('Enter your location:', 'locso'); ?>
				<br/>
				<input type="text" id="postbox_loc_search" class="ps-input" name="postbox_loc_search" value="" disabled/>
				<ul class="ps-postbox-locations"></ul>
				<div class="ps-postbox-action ps-location-action" style="display: none;">
					<button class="ps-btn ps-btn-primary ps-add-location" style="display: inline-block;">
						<i class="ps-icon-map-marker"></i><?php _e('Select', 'locso'); ?>
					</button>
					<button class="ps-btn ps-btn-danger ps-remove-location" style="display: none;">
						<i class="ps-icon-remove"></i><?php _e('Remove', 'locso'); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
</div>
<div style="display: none;">
	<div id="pslocation-search-loading">
		<img src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>" alt="" />
	</div>
	<div id="pslocation-in-text"><?php _e(' at ', 'locso'); ?></div>
</div>
