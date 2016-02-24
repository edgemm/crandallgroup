<?php noo_get_layout( 'footer', 'widgetized' ); ?>
<?php
	$image_logo = noo_get_image_option( 'noo_bottom_bar_logo_image', '' );
	$noo_bottom_bar_content = noo_get_option( 'noo_bottom_bar_content', '' );
?>
<?php if ( $image_logo || $noo_bottom_bar_content ) : ?>
	<footer class="colophon site-info" role="contentinfo">
		<div class="container-full">
			<?php if ( $noo_bottom_bar_content != '' || $noo_bottom_bar_social ) : ?>
			<div class="footer-more">
				<div class="container-boxed max">
					<div class="row">
						<div class="col-md-6 footer-first">
						<?php if ( $image_logo ) : ?>
							<a href="/">
							<?php
								echo '<img src="' . $image_logo . '" alt="' . get_bloginfo( 'name' ) . '">';
							?>
							</a>
							<p class="footer-licensing">Licensed in the State of Oregon</p>
						<?php endif; ?>
						<?php if ( $noo_bottom_bar_content != '' ) : ?>
							<div class="noo-bottom-bar-content footer-copyright">
								<?php echo $noo_bottom_bar_content; ?>
							</div>
						<?php endif; ?>
							<ul class="footer-associations clearfix">
								<li><img src="/wp-content/themes/crandall/assets/images/jls-logo.png" alt="John L. Scott Real Estate" /></li>
								<li><img src="/wp-content/themes/crandall/assets/images/realtor-logo.png" alt="National Association of Realtors" /></li>
								<li><img src="/wp-content/themes/crandall/assets/images/eho-logo.png" alt="Fair Housing and Equal Opportunity" /></li>
							</ul>
						</div>
						<div class="col-md-6 footer-last">
							<div class="footer-contact-info">
								<div class="footer-contact-addr">
									<div class="footer-contact-icon">
										<i class="fa fa-map-marker"></i>
									</div>
									<div class="footer-contact-addr-content">
										<a href="https://goo.gl/maps/C96Yp" target="_blank&quot;">1800 NW 167th Pl., Suite 150<br>Beaverton, OR 97006</a>
									</div>
								</div>
								<div class="footer-contact-phone">
									<i class="fa fa-phone"></i>
									<a href="tel:503.936.0332">503.936.0332</a>
								</div>
								<div class="footer-contact-email">
									<i class="fa fa-envelope-o"></i>
									<a href="mailto:info@crandallgroup.com">info@crandallgroup.com</a>
								</div>
							</div>
						</div>
						
					</div>
				</div>
			</div>
			<?php endif; ?>
		</div> <!-- /.container-boxed -->
	</footer> <!-- /.colophon.site-info -->
<?php endif; ?>
</div> <!-- /#top.site -->
<?php wp_footer(); ?>

<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-48008407-1', 'auto');
  ga('send', 'pageview');

</script>

</body>
</html>
