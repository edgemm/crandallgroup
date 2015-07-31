<?php
/*
Template Name: Builders
*/
?>
<?php get_header(); ?>
<div class="container-wrap">	
	<div class="main-content container-boxed max offset">
		<div class="row">
			<div class="<?php noo_main_class(); ?>" role="main">
				<?php if( ! noo_get_post_meta(get_the_ID(), '_noo_wp_page_hide_page_title', false) ) : ?>
				<h1 class="page-title builders-title"><?php the_title(); ?></h1>
				<?php endif; ?>
				<div class="builders list">
					<div class="builder-content">
				<?php // get all builder terms

				$builder_args = array(
					'hide_empty'		=> 0
				);
				
				$builders = get_terms( 'builder', $builder_args );
				
				if( !empty( $builders ) && !is_wp_error( $builders ) ) :

					// create array to be sorted by sort field
					$arr_builders = array();

					// add builders to array
					foreach( $builders as $b ) :

						$i = $b->term_id;
						$sort_order = get_tax_meta( $i, 'builders_meta_sort' );
						$arr_builders[ $sort_order ] = $b->term_id;

					endforeach;

					// sort builders array by key (sort order value)
					ksort( $arr_builders, SORT_NUMERIC );

					// output $arr_builders values
					foreach( $arr_builders as $sort_order => $i ) :

						$b = get_term( $i, 'builder' );

						$photo = get_tax_meta( $i, 'builders_meta_photo' );

						$desc = $b->description;
						$desc = ( strlen( $desc ) > 65 ) ? substr( $desc, 0, 65 ) . "..." : $desc;

				?>
				<article id="builder-<?php echo $i; ?>" class="builder <?php echo $b->slug; ?> hentry">
					<div class="builder-featured">
						<a class="content-thumb" href="/builder/<?php echo $b->slug; ?>">
							<?php
							if ( $photo ) :
								$src = $photo['url'];
							else :
								$src = '/wp-content/themes/crandall/assets/images/image-coming-soon.png';
							endif;
							?>
							<img class="builder-img" src="<?php echo $src; ?>" class="attachment-property-thumb wp-post-image" alt="<?php echo $b->name; ?>">
						</a>
					</div>
					<div class="builder-wrap">
						<h2 class="builder-title">
							<a href="/builder/<?php echo $b->slug; ?>" title="<?php echo $b->name; ?>"><?php echo $b->name; ?></a>
						</h2>
						<div class="builder-excerpt">
							<p class="builder-fullwidth-excerpt"><?php echo $desc; ?></p>
						</div>
						<div class="builder-summary">
							<div class="builder-info">
								<div class="builder-action">
									<a href="/builder/<?php echo $b->slug; ?>" title="<?php echo $b->name; ?>">More Details</a>
								</div>
							</div>
						</div>
					</div>
				</article>
				<?
					endforeach;
				
				endif;
				
				?>
					</div>
				</div>
			</div> <!-- /.main -->
		</div><!--/.row-->
	</div><!--/.container-full-->
</div><!--/.container-wrap-->
	
<?php get_footer(); ?>