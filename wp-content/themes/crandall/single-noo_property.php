<?php 
$noo_property_detail_map = noo_get_option('noo_property_detail_map',0);
?>
<?php get_header(); ?>
<div class="container-wrap">
	<?php if(!empty($noo_property_detail_map)):?>
	<?php echo do_shortcode('[noo_advanced_search_property style="'.noo_get_option('noo_property_detail_map_layout','horizontal').'"]');?>
	<?php endif;?>
	<div class="main-content container-boxed max offset">
		<div class="row">
			<div class="<?php noo_main_class(); ?>" role="main">
				<?php NooProperty::display_detail()?>
			</div>
			<?php get_sidebar(); ?>
		</div> <!-- /.row -->
	
	</div> <!-- /.container-boxed.max.offset -->
	
<!--	<div class="noo-vc-row row bg-primary-overlay bg-image cta-lower" style="padding-top: 10px; padding-bottom: 10px;">
		<div class="noo-vc-col col-md-12 ">
			<div class="container-boxed max">
				<div class="noo-vc-row row  noo-call-to-action" style=" padding-top: 20px; padding-bottom: 20px;">
					<div class="noo-vc-col col-md-6 col-sm-12 ">
						<div class="noo-text-block cta-text-block">
							<h3 class="cta-headline color-overlay">FIND YOUR HOME</h3>
							<p>Get in touch with the Crandall Group</p>
						</div>
					</div>
					<div class="noo-vc-col col-md-6 col-sm-12 " style="text-align: right;padding-top: 20px;">
						<a href="#" id="noo-button-6" class="btn btn-thirdary rounded metro btn-default" style="font-size: 24px;font-weight: bold; padding-top:  7px; padding-bottom:  7px; padding-left:  70px; padding-right:  70px;" role="button">CONTACT AN AGENT</a>
					</div>
				</div>
			</div>
		</div>
		<div class=" parallax" data-parallax="1" data-parallax_no_mobile="1" data-velocity="0.05" style="background-image: url('http://crandallgroup.staging.wpengine.com/wp-content/uploads/2015/03/DSC_5606.jpg'); background-position: 50% 10px;"></div>
	</div>-->
</div>
<?php get_footer(); ?>