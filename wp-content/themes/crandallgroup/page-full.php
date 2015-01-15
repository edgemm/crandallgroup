<?php
/*
Template Name: Full Width
*/
get_header(); ?>
<div class="row">
	<div class="small-12 large-12 columns" role="main">

	<?php /* Start loop */ ?>
	<?php while (have_posts()) : the_post(); ?>
		<article <?php post_class() ?> id="post-<?php the_ID(); ?>">
			<header>
			</header>
			<div class="entry-content">
  			<div class="pageContent">
				<?php the_content(); ?>
  			</div>
			</div>
			<footer>
				<?php wp_link_pages(array('before' => '<nav id="page-nav"><p>' . __('Pages:', 'FoundationPress'), 'after' => '</p></nav>' )); ?>
			</footer>
		</article>
	<?php endwhile; // End the loop ?>

	</div>
</div>

<?php get_footer(); ?>
