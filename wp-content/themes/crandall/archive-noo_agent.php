<?php get_header(); ?>
<div class="container-wrap">
	<div class="main-content container-boxed max offset">
		<div class="row">
			<div class="<?php noo_main_class(); ?>" role="main">
				<!-- Begin The loop -->
				<?php
				if(have_posts()) :
					$agent_args = array(
						"post_type" 		=> "noo_agent",
						"posts_per_page"	=> -1,
						"nopaging"		=> true,
						"order"			=> "DESC"
					);
					$agent_query = new WP_Query( $agent_args );
					NooAgent::display_content( $agent_query, __('Meet the Team',NOO_TEXT_DOMAIN) );
				else:
				noo_get_layout( 'no-content' );
				endif;
				?>
				<!-- End The loop -->
				<?php noo_pagination(); ?>
			</div> <!-- /.main -->
			<?php get_sidebar(); ?>
		</div><!--/.row-->
	</div><!--/.container-boxed-->
</div><!--/.container-wrap-->
	
<?php get_footer(); ?>