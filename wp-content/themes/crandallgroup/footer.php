</section>
<footer class="row">
	<?php do_action('foundationPress_before_footer'); ?>
	<?php dynamic_sidebar("footer-widgets"); ?>
	<?php do_action('foundationPress_after_footer'); ?>
  <div class="copyright">
    <div class="large-6 columns">
      <ul class="inline-list">
        <li><a href="http://www.facebook.com/crandallgroup"><i class="fa fa-facebook-square"></i></a></li>
        <li><a href="http://www.twitter.com/crandallgrp"><i class="fa fa-twitter-square"></i></a></li>
        <li><a href="http://www.youtube.com/crandallgroup"><i class="fa fa-youtube-square"></i></a></li>
      </ul>
    </div>
    <div class="large-6 columns">
      <img src="<?php echo get_stylesheet_directory_uri() ; ?>/assets/img/images/johnlscottlogo.png" id="footerImg">
    </div>
  </div>
</footer>
<a class="exit-off-canvas"></a>


	<?php do_action('foundationPress_layout_end'); ?>
	</div>
</div>
<?php wp_footer(); ?>
<?php do_action('foundationPress_before_closing_body'); ?>
</body>
</html>
