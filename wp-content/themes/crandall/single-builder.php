<?php get_header(); ?>
<div class="container-wrap">
	<div class="main-content container-boxed max offset">
		<div class="row">
			<div class="<?php noo_main_class(); ?>" role="main">
				<?php NooProperty::display_detail()?>
			</div>
			<?php get_sidebar(); ?>
		</div> <!-- /.row -->
	
	</div> <!-- /.container-boxed.max.offset -->
</div>
<?php get_footer(); ?>