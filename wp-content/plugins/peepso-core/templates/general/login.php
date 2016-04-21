<div id="registration" class="ps-landing-action <?php if( 1 === PeepSo::get_option('wsl_enable',0)) { ?>ps-landing-social<?php } ?>">
  <div class="login-area">
    <form class="ps-form" action="" onsubmit="return false;" method="post" name="login" id="form-login">
      <div class="ps-landing-form">
        <div class="ps-form-input ps-form-input-icon">
          <span class="ps-icon"><i class="ps-icon-user"></i></span>
          <input class="ps-input" type="text" name="username" id="username" placeholder="<?php _e('Username', 'peepso'); ?>" mouseev="true"
            autocomplete="off" keyev="true" clickev="true" />
        </div>
        <div class="ps-form-input ps-form-input-icon">
          <span class="ps-icon"><i class="ps-icon-lock"></i></span>
          <input class="ps-input" type="password" name="password" id="password" placeholder="<?php _e('Password', 'peepso'); ?>" mouseev="true"
                autocomplete="off" keyev="true" clickev="true" />
        </div>
        <div class="ps-form-input ps-form-input--button">
          <button type="submit" id="login-submit" class="ps-btn ps-btn-login">
            <span><?php _e('Login', 'peepso'); ?></span>
            <img style="display:none" src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>">
          </button>
        </div>

        <?php if( 1 === PeepSo::get_option('wsl_enable',0)) { ?>
          <div class="ps-widget--wsl ps-form-input ps-js--wsl">
            <?php
            add_filter( 'wsl_render_auth_widget_alter_provider_icon_markup', 'wsl_use_peepso_icons', 10, 3 );
            do_action( 'wordpress_social_login' );
            remove_filter( 'wsl_render_auth_widget_alter_provider_icon_markup', 'wsl_use_peepso_icons', 10 );
            ?>
            <a href="javascript:" class="ps-btn hidden">
              <i class="ps-icon-angle-down"></i>
            </a>
            <div class="ps-widget--wsl-dropdown hidden">
              <ul class="hidden-links hidden"></ul>
            </div>
          </div>
        <?php } ?>
      </div>

      <div class="ps-checkbox">
        <input type="checkbox" alt="<?php _e('Remember Me', 'peepso'); ?>" value="yes" id="remember" name="remember" />
        <label for="remember"><?php _e('Remember Me', 'peepso'); ?></label>
      </div>
      <a class="ps-link" href="<?php peepso('page-link', 'recover'); ?>"><?php _e('Recover Password', 'peepso'); ?></a>
      <a class="ps-link" href="<?php peepso('page-link', 'register'); ?>?resend"><?php _e('Resend activation code', 'peepso'); ?></a>
      <!-- Alert -->
      <div class="errlogin calert clear alert-error" style="display:none"></div>

      <input type="hidden" name="option" value="ps_users" />
      <input type="hidden" name="task" value="-user-login" />
    </form>

    <div style="display:none">
      <form name="loginform" id="loginform" action="<?php peepso('page-link', 'home'); ?>wp-login.php" method="post">
        <input type="text" name="log" id="user_login" />
        <input type="password" name="pwd" id="user_pass" />
        <input type="checkbox" name="rememberme" id="rememberme" value="forever" />
        <input type="submit" name="wp-submit" id="wp-submit" value="Log In" />
        <input type="hidden" name="redirect_to" value="<?php peepso('redirect-login'); ?>" />
        <input type="hidden" name="testcookie" value="1" />
        <?php wp_nonce_field('ajax-login-nonce', 'security'); ?>
      </form>
    </div>
  </div>
</div>

<script>
jQuery(function( $ ) {
  $('#form-login').on('submit', function( e ) {
    e.preventDefault();
    e.stopPropagation();
    ps_login.form_submit( e );
  });

  $(function() {

    var $nav = $('.wp-social-login-widget');
    var $wrap = $('.ps-js--wsl');
    var $btn = $('.ps-js--wsl .ps-btn');
    var $vlinks = $('.ps-js--wsl .wp-social-login-provider-list');
    var $hlinks = $('.ps-js--wsl .hidden-links');
    var $hdrop = $('.ps-js--wsl .ps-widget--wsl-dropdown');

    var numOfItems = 0;
    var totalSpace = 0;
    var breakWidths = [];

    // Get initial state
    $vlinks.children().outerWidth(function(i, w) {
    totalSpace += w;
    numOfItems += 1;
    breakWidths.push(totalSpace);
    });

    var availableSpace, numOfVisibleItems, requiredSpace;

    function check() {

    // Get instant state
    availableSpace = $vlinks.width() - 40;
    numOfVisibleItems = $vlinks.children().length;
    requiredSpace = breakWidths[numOfVisibleItems - 1];

    // There is not enought space
    if (requiredSpace > availableSpace) {
      $vlinks.children().last().prependTo($hlinks);
      numOfVisibleItems -= 1;
      check();
      // There is more than enough space
    } else if (availableSpace > breakWidths[numOfVisibleItems]) {
      $hlinks.children().first().appendTo($vlinks);
      numOfVisibleItems += 1;
    }
    // Update the button accordingly
    $btn.attr("count", numOfItems - numOfVisibleItems);
    if (numOfVisibleItems === numOfItems) {
      $btn.addClass('hidden');
      $wrap.removeClass('has-more');
    } else $btn.removeClass('hidden'), $wrap.addClass('has-more');
    }

    // Window listeners
    $(window).resize(function() {
    check();
    });

    $btn.on('click', function() {
    $hlinks.toggleClass('hidden');
    $hdrop.toggleClass('hidden');
    });

    check();

  });
});
</script>
