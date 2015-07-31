<?php
/*
Template Name: Search Property Results
*/
?>
<?php 
$location = $sub_location = $status = $category = '';
if( isset( $_GET['location'] ) && $_GET['location'] !=''){
	$location = array(
		'taxonomy'     => 'property_location',
		'field'        => 'slug',
		'terms'        => $_GET['location']
	);
}
if( isset( $_GET['sub_location'] ) && $_GET['sub_location'] !=''){
	$sub_location = array(
			'taxonomy'     => 'property_sub_location',
			'field'        => 'slug',
			'terms'        => $_GET['sub_location']
	);
}
if( isset( $_GET['status'] ) && $_GET['status'] !=''){
	$status = array(
			'taxonomy'     => 'property_status',
			'field'        => 'slug',
			'terms'        => $_GET['status']
	);
}
if( isset( $_GET['category'] ) && $_GET['category'] !=''){
	$category = array(
			'taxonomy'     => 'property_category',
			'field'        => 'slug',
			'terms'        => $_GET['category']
	);
}
$meta_query = array('relation' => 'AND');
if(isset( $_GET['bedroom'] ) && is_numeric($_GET['bedroom'])){
	$bedroom['key'] = '_bedrooms';
	$bedroom['value'] = $_GET['bedroom'];
	$meta_query[] = $bedroom;
}
if(isset( $_GET['bathroom'] ) && is_numeric($_GET['bathroom'])){
	$bathroom['key'] = '_bathrooms';
	$bathroom['value'] = $_GET['bathroom'];
	$meta_query[] = $bathroom;
}
if(isset( $_GET['min_area'] )){
	$min_area['key']      = '_area';
	$min_area['value']    = intval($_GET['min_area']);
	$min_area['type']     = 'NUMERIC';
	$min_area['compare']  = '>=';
	$meta_query[]     = $min_area;
}
if(isset( $_GET['max_area'] )){
	$max_area['key']      = '_area';
	$max_area['value']    = intval($_GET['max_area']);
	$max_area['type']     = 'NUMERIC';
	$max_area['compare']  = '<=';
	$meta_query[]     = $max_area;
}
if(isset( $_GET['min_price'] )){
	$min_price['key']      = '_price';
	$min_price['value']    = floatval($_GET['min_price']);
	$min_price['type']     = 'NUMERIC';
	$min_price['compare']  = '>=';
	$meta_query[]     = $min_price;
}
if(isset( $_GET['max_price'] )){
	$max_price['key']      = '_price';
	$max_price['value']    = floatval($_GET['max_price']);
	$max_price['type']     = 'NUMERIC';
	$max_price['compare']  = '<=';
	$meta_query[]     	   = $max_price;
}
$get_keys = array_keys($_GET);
foreach ($get_keys as $get_key){
	if(strstr( $get_key, '_noo_property_field_' )){
		$value = $_GET[$get_key];
		if(!empty($value)){
			$meta_query[]	= array(
				'key'=>$get_key,
				'value'=>$value,
				'compare'=>'LIKE'
			);
		}
	}elseif (strstr($get_key, '_noo_property_feature_')){
		$meta_query[]	= array(
			'key'=>$get_key,
			'value'=>'1',
		);
	}
}
$args = array(
		'post_type'     => 'noo_property',
		'post_status'   => 'publish',
		'posts_per_page' => -1,
		'nopaging'      => true,
		'meta_query'    => $meta_query,
		'tax_query'     => array(
				'relation' => 'AND',
				$location,
				$sub_location,
				$status,
				$category
		)
);
$r = new WP_Query($args);
?>
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
				<?php if ( $r->have_posts() ) : ?>
					<?php NooProperty::display_content($r,get_the_title())?>
				<?php else : ?>
					<?php noo_get_layout( 'no-content' ); ?>
				<?php endif; ?>
				<?php 
					wp_reset_query();
					wp_reset_postdata();
				?>
			</div> <!-- /.main -->
			<?php get_sidebar(); ?>
		</div><!--/.row-->
	</div><!--/.container-boxed-->
</div><!--/.container-wrap-->
<?php get_footer(); ?>