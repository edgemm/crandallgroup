<?php get_header(); ?>
<div class="container-wrap">
	<div class="main-content container-boxed max offset">
		<div class="row">
			<div class="noo-main col-md-12" role="main">
				<?php
				$term = get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) );
				$i = $term->term_id;
				$builder_name = $term->name;
				$builder_desc = $term->description;
				$builder_contact = get_tax_meta( $i, 'builders_meta_contact_name' );
				$builder_phone = get_tax_meta( $i, 'builders_meta_contact_phone' );
				$builder_email = get_tax_meta( $i, 'builders_meta_contact_email' );
				$builder_logo = get_tax_meta( $i, 'builders_meta_photo' );
				?>
				<div class="builder clearfix">
					<h1 class="page-title"><?php echo $builder_name; ?></h1>
					<div class="builder-details">						
						<p class="builder-desc">
							<?php
							if ( $builder_logo ) :
								echo '<img class="builder-photo" src="' . $builder_logo['url'] . '" alt="' . $builder_name . ' Logo" title="' . $builder_name . '" />';
							else :
								echo '<div class="builder-photo photo-none">Image Coming Soon</div>';
							endif;
							?>
							<span class="builder-bio"><?php echo $builder_desc; ?></span>
						</p>
						<div class="builder-contact">
							<ul class="builder-contact-info">
								<li class="contact-name"><i class="fa fa-user"></i><?php echo $builder_contact; ?></li>
								<li class="contact-phone"><i class="fa fa-phone"></i><a href="tel:<?php echo $builder_phone; ?>"><?php echo $builder_phone; ?></a></li>
								<li class="contact-email"><i class="fa fa-envelope-o"></i><a href="mailto:<?php echo $builder_email; ?>"><?php echo $builder_email; ?></a></li>
							</ul>
						</div>
					</div>
					
					
				</div>
				<!-- Begin The loop -->
				<?php if ( have_posts() ) : ?>
					<?php NooProperty::display_content('',single_term_title( "", false ))?>
				<?php else : ?>
					<?php noo_get_layout( 'no-content' ); ?>
				<?php endif; ?>
				<!-- End The loop -->
				<?php noo_pagination(); ?>
			</div> <!-- /.main -->
		</div> <!-- /.row -->
	
	</div> <!-- /.container-boxed.max.offset -->
</div>
<?php get_footer(); ?>