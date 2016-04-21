<div class="peepso ps-page-profile">
    <section id="mainbody" class="ps-wrapper clearfix">
        <section id="component" role="article" class="clearfix">
            <?php peepso('load-template', 'general/navbar'); ?>
            <div id="cProfileWrapper" class="clearfix">
                <?php peepso('load-template', 'profile/focus'); ?>

                <div id="editLayout-stop" class="page-action" style="display: none;">
                    <a onclick="profile.editLayout.stop()" href="javascript:void(0)"><?php _e('Finished Editing Apps Layout', 'peepso'); ?></a>
                </div>

                <div class="ps-body">
                    <?php
                    // widgets top
                    $widgets_profile_sidebar_top = apply_filters('peepso_widget_prerender', 'profile_sidebar_top');

                    // widgets bottom
                    $widgets_profile_sidebar_bottom = apply_filters('peepso_widget_prerender', 'profile_sidebar_bottom');
                    ?>

                    <?php
                    if (peepso('profile', 'get.has-sidebar') || count($widgets_profile_sidebar_top) > 0 || count($widgets_profile_sidebar_bottom) > 0) { ?>
                        <?php peepso('load-template', 'sidebar/sidebar', array('profile_sidebar_top'=>$widgets_profile_sidebar_top, 'profile_sidebar_bottom'=>$widgets_profile_sidebar_bottom, )); ?>
                    <?php } ?>

                    <div class="ps-main <?php if (peepso('profile', 'get.has-sidebar') || count($widgets_profile_sidebar_top) > 0 || count($widgets_profile_sidebar_bottom) > 0) echo ''; else echo 'ps-main-full'; ?>">
                        <!-- js_profile_feed_top -->
                        <div class="activity-stream-front">
                            <?php
                            if(peepso('profile', 'can-post')) {
                                peepso('load-template', 'general/postbox', array('is_current_user' => peepso('profile', 'is_current_user')));
                            }
                            ?>

                            <div class="ps-latest-activities-container" data-actid="-1" style="display: none;">
                                <a id="activity-update-click" class="btn btn-block" href="javascript:void(0);"></a>
                            </div>


                            <div class="tab-pane active" id="stream">
                                <div id="ps-activitystream" class="ps-stream-container cstream-list creset-list" data-filter="all" data-filterid="0" data-groupid data-eventid data-profileid>
                                    <?php
                                    if (peepso('activity', 'has-posts')) {
                                        // display all posts
                                        while (peepso('activity', 'next-post')) {
                                            peepso('activity', 'show-post'); // display post and any comments
                                        }

                                        peepso('activity', 'show-more-posts-link');
                                    }
                                    ?>
                                </div>


                            </div>
                        </div><!-- end activity-stream-front -->

                        <?php peepso('load-template', 'activity/dialogs'); ?>
                        <div id="apps-sortable" class="connectedSortable"></div>
                    </div><!-- cMain -->
                </div><!-- end row -->
            </div><!-- end cProfileWrapper --><!-- js_bottom -->
            <div id="ps-dialogs" style="display:none">
                <?php peepso('profile', 'dialogs'); // give add-ons a chance to output some HTML ?>
            </div>
        </section><!--end component-->
    </section><!--end mainbody-->
</div><!--end row-->
