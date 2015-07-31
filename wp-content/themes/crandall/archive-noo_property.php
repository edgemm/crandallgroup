<?php 
$noo_property_listing_map = noo_get_option('noo_property_listing_map',1);
?>
<?php get_header(); ?>
<div class="container-wrap">
	<?php if(!empty($noo_property_listing_map)):?>
	<?php echo do_shortcode('[noo_advanced_search_property style="'.noo_get_option('noo_property_listing_map_layout','horizontal').'"]');?>
	<?php endif;?>
	<div class="main-content container-boxed max offset">
		<div class="row">
			<div class="<?php noo_main_class(); ?>" role="main">
				<!-- Begin The loop -->
				<?php if ( have_posts() ) : ?>
					<?php NooProperty::display_content('',__('Properties',NOO_TEXT_DOMAIN))?>
				<?php else : ?>
					<?php noo_get_layout( 'no-content' ); ?>
				<?php endif; ?>
				<!-- End The loop -->
				<?php noo_pagination(); ?>
			</div> <!-- /.main -->
			<?php get_sidebar(); ?>
		</div><!--/.row-->
	</div><!--/.container-boxed-->
</div><!--/.container-wrap-->
	
<?php get_footer(); ?>