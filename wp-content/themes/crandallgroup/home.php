<?php
/*
Template Name: Home
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
				<?php the_content(); ?>
			<div class="row pageContent">
				<div class="large-6 columns">
					<h3>Portland's Leading New Homes and Resale Sales Team</h3>
					<p>From your initial inquiry all the way through closing, The Crandall Group sets the Standard of Excellence in home sales.</p>
					<p>Our Team consists of highly trained professionals specializing in new home sales, residential resale and investment properties. <a href="http://homes.crandallgroup.com/idx/14419/contact.php">Contact us now</a>, we do it all!</p>
				</div>
				<div class="large-6 columns">
						<a href="http://homes.crandallgroup.com/idx/14419/contact.php"><img src="<?php echo get_stylesheet_directory_uri() ; ?>/assets/img/images/CrandallGroup-CALL-RECTANGLE.jpg"></a>
						<a href="http://homes.crandallgroup.com/idx/14419/advancedSearch.php"><img src="<?php echo get_stylesheet_directory_uri() ; ?>/assets/img/images/Search-For-Homes-Front-Page.jpg"></a>
            <img src="<?php echo get_stylesheet_directory_uri() ; ?>/assets/img/images/home-tagline-cropped.jpg">
				</div>	
			</div>
			<footer>
				<?php wp_link_pages(array('before' => '<nav id="page-nav"><p>' . __('Pages:', 'FoundationPress'), 'after' => '</p></nav>' )); ?>
				<p><?php the_tags(); ?></p>
			</footer>
		</article>
	<?php endwhile; // End the loop ?>

	</div>
</div>

<?php get_footer(); ?>
