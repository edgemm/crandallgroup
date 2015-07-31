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
			<?php //echo term_description(); ?>
				<!-- Begin The loop -->
				<?php if ( have_posts() ) : ?>
					<?php NooProperty::display_content( '', single_term_title( "", false ), true, '', false, false, false, false, term_description() ); ?>
				<?php else : ?>
				<div class="properties-header">
					<h1 class="page-title"><?php single_term_title(); ?></h1>
				</div>
				<div class="properties-category-desc no-properties">
					<?php echo term_description(); ?>
				</div>
				<div class="properties-content no-properties">
					<p class="no-properties-heading">No current <?php single_term_title(); ?> listings available. Check back again soon!</p>
				</div>
				<?php endif; ?>
				<!-- End The loop -->
				<?php noo_pagination(); ?>
			</div> <!-- /.main -->
			<?php get_sidebar(); ?>
		</div><!--/.row-->
	</div><!--/.container-boxed-->
</div><!--/.container-wrap-->
	
<?php get_footer(); ?>