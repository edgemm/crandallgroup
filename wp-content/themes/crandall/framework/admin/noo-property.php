<?php
if(!class_exists('NooPropertyFilterDropdown')):
	class NooPropertyFilterDropdown extends Walker {

		var $tree_type = 'category';
		var $db_fields = array ('parent' => 'parent', 'id' => 'term_id', 'slug' => 'slug' );

		public function start_el( &$output, $cat, $depth = 0, $args = array(), $current_object_id = 0 ) {

			if ( ! empty( $args['hierarchical'] ) )
				$pad = str_repeat('-', $depth * 2);
			else
				$pad = '';

			$cat_name = $cat->name;

			$value = isset( $args['value'] ) && $args['value'] == 'id' ? $cat->term_id : $cat->slug;

			$output .= "\t<option class=\"level-$depth\" value=\"" . $value . "\"";

			if ( $value == $args['selected'] || ( is_array( $args['selected'] ) && in_array( $value, $args['selected'] ) ) )
				$output .= ' selected="selected"';

			$output .= '>';

			$output .= $pad . $cat_name;

			if ( ! empty( $args['show_count'] ) )
				$output .= '&nbsp;(' . $cat->count . ')';

			$output .= "</option>\n";
		}

		public function display_element( $element, &$children_elements, $max_depth, $depth = 0, $args, &$output ) {
			if ( ! $element || 0 === $element->count ) {
				return;
			}
			parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
		}
	}
endif;

if(!class_exists('NooPropertySearchDropdown')):
	class NooPropertySearchDropdown extends Walker {

		var $tree_type = 'category';
		var $db_fields = array ('parent' => 'parent', 'id' => 'term_id', 'slug' => 'slug' );

		public function start_el( &$output, $term, $depth = 0, $args = array(), $current_object_id = 0 ) {

			if ( ! empty( $args['hierarchical'] ) ) {
				$pad = str_repeat('-', $depth * 2);
				$pad = !empty( $pad ) ? $pad . '&nbsp;' : '';
			} else {
				$pad = '';
			}

			$cat_name = $term->name;

			$value = isset( $args['value'] ) && $args['value'] == 'id' ? $term->term_id : $term->slug;
			$parent = '';
			if( $args['taxonomy'] == 'property_sub_location' ) {
				$parent_data = get_option( 'noo_sub_location_parent' );
				if( isset( $parent_data[$term->term_id] ) ) {
					$parent_location = get_term_by('id',$parent_data[$term->term_id],'property_location');
					$parent .= ' data-parent-location="' . $parent_location->slug . '"';
				}
			}

			$output .= "\t<li class=\"level-$depth\" $parent><a href=\"#\" data-value=\"" . $value . "\">";
			$output .= $pad . $cat_name;
			if ( ! empty( $args['show_count'] ) )
				$output .= '&nbsp;(' . $term->count . ')';
			$output .= "</a></li>\n";
		}

		public function display_element( $element, &$children_elements, $max_depth, $depth = 0, $args, &$output ) {
			if ( ! $element || 0 === $element->count ) {
				return;
			}
			parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
		}
	}
endif;

if(!function_exists('noo_dropdown_search')):
	function noo_dropdown_search($args = ''){
		$defaults = array(
			'show_option_all' => '', 'show_option_none' => '',
			'orderby' => 'id', 'order' => 'ASC',
			'show_count' => 1,
			'hide_empty' => 1, 'child_of' => 0,
			'exclude' => '', 'echo' => 1,
			'hierarchical' => 1,
			'depth' => 0,
			'taxonomy' => 'category',
			'hide_if_empty' => false,
			'option_none_value' => '',
			'meta' => '',
			'walker'=>new NooPropertySearchDropdown
		);
		$defaults['selected'] = ( is_category() ) ? get_query_var( 'cat' ) : 0;
		$r = wp_parse_args( $args, $defaults );
		$taxonomies = get_terms( $r['taxonomy'], $r );
		if ( ! $r['hide_if_empty'] || ! empty( $taxonomies ) ) {
			$output = "<ul class=\"dropdown-menu\">\n";
		} else {
			$output = '';
		}
		
		if ( empty( $taxonomies ) && ! $r['hide_if_empty'] && ! empty( $r['show_option_none'] ) ) {
			$show_option_none = $r['show_option_none'];
			$output .= "\t<li><a data-value=\"\" href=\"#\">$show_option_none</a></li>\n";
		}
		if ( $r['show_option_none'] ) {
			$show_option_none = $r['show_option_none'];
			$output .= "\t<li><a data-value=\"\" href=\"#\">$show_option_none</a></li>\n";
		}
		
		if ( $r['hierarchical'] ) {
			$depth = $r['depth'];  // Walk the full depth.
		} else {
			$depth = -1; // Flat.
		}
		$output .= walk_category_dropdown_tree( $taxonomies, $depth, $r );
		
		if ( ! $r['hide_if_empty'] || ! empty( $taxonomies ) ) {
			$output .= "</ul>\n";
		}
		if ( $r['echo'] ) {
			echo $output;
		}
		return $output;
	}
endif;

if(!class_exists('NooProperty')):
	class NooProperty{
		public function __construct(){
			add_action('init', array(&$this,'init'));
			add_action('init', array(&$this,'register_post_type'));
			
			add_filter( 'template_include', array( $this, 'template_loader' ) );
		
			if(!is_admin())
				add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
			
			add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );
			
			add_shortcode('noo_recent_properties', array(&$this,'recent_properties_shortcode'));
			add_shortcode('noo_single_property', array(&$this,'single_property_shortcode'));
			add_shortcode('noo_advanced_search_property', array(&$this,'advanced_search_property_shortcode'));
			add_shortcode('property_slider', array(&$this,'property_slider_shortcode'));  
			add_shortcode('property_slide', array(&$this,'property_slide_shortcode'));
			
			//Ajax Contact Agent
			add_action('wp_ajax_noo_contact_agent', array(&$this,'ajax_contact_agent'));
			add_action('wp_ajax_nopriv_noo_contact_agent', array(&$this,'ajax_contact_agent'));
			add_action('wp_ajax_noo_contact_agent_property', array(&$this,'ajax_contact_agent_property'));
			add_action('wp_ajax_nopriv_noo_contact_agent_property', array(&$this,'ajax_contact_agent_property'));
			
			//Ajax Contact Agent
			add_action('wp_ajax_noo_agent_ajax_property', array(&$this,'ajax_agent_property'));
			add_action('wp_ajax_nopriv_noo_agent_ajax_property', array(&$this,'ajax_agent_property'));
				
			
			if(is_admin()):
				add_action('admin_init', array(&$this,'admin_init'));
				
				add_action ( 'add_meta_boxes', array (&$this, 'add_meta_boxes' ), 30 );
				
				add_action('admin_menu',array(&$this,'admin_menu'));
				//Property
				add_filter( 'manage_edit-noo_property_columns', array($this,'property_columns') );
				add_filter( 'manage_noo_property_posts_custom_column',  array($this,'property_column'), 2 );
				
				//Label
				add_action('property_label_add_form_fields',array(&$this,'add_property_label_color'));
				add_action('property_label_edit_form_fields',array(&$this,'edit_property_label_color'),10,3);
				add_action( 'created_term', array($this,'save_label_color'), 10,3 );
				add_action( 'edit_term', array($this,'save_label_color'), 10,3 );
				
				//Map Marker
				add_action('property_category_add_form_fields',array(&$this,'add_category_map_marker'));
				add_action('property_category_edit_form_fields',array(&$this,'edit_category_map_marker'),10,3);
				add_action( 'created_term', array($this,'save_category_map_marker'), 10,3 );
				add_action( 'edit_term', array($this,'save_category_map_marker'), 10,3 );
				
				//Location
				add_action('property_location_add_form_fields',array(&$this,'add_location'));
				add_action('property_location_edit_form_fields',array(&$this,'edit_location'),10,3);
				
				//Status
				add_action('property_status_add_form_fields',array(&$this,'add_status'));
				add_action('property_status_edit_form_fields',array(&$this,'edit_status'),10,3);
				
				//Sub location 
				add_action('property_sub_location_add_form_fields',array(&$this,'add_sub_location'));
				add_action('property_sub_location_edit_form_fields',array(&$this,'edit_sub_location'),10,3);
				add_action( 'created_term', array($this,'save_sub_location_callback'), 10,3 );
				add_action( 'edit_term', array($this,'save_sub_location_callback'), 10,3 );
				add_filter( 'manage_edit-property_sub_location_columns', array($this,'sub_location_columns') );
				add_filter( 'manage_property_sub_location_custom_column',  array($this,'sub_location_column'), 10, 3 );
				
				add_action( 'admin_print_scripts-post.php', array( &$this, 'enqueue_map_scripts' ) );
				add_action( 'admin_print_scripts-post-new.php', array( &$this, 'enqueue_map_scripts' ) );
				
				add_action( 'admin_enqueue_scripts', array(&$this,'enqueue_scripts'));
			endif;
		}
		
		public function init(){
			
		}

		public static function enqueue_gmap_js( $load_map_data = false ) {
			static $has_map_data = false;

			if( wp_script_is( 'noo-property-map', 'enqueued' ) ) {
				// return if loaded and no need for reload
				if( $has_map_data || !$load_map_data ) {
					return;
				} else {
					wp_dequeue_script( 'noo-property-map');
				}
			}

			if( !$has_map_data ) {
				$has_map_data = $load_map_data;
			}

			$latitude = self::get_google_map_option('latitude','40.714398');
			$longitude = self::get_google_map_option('longitude','-74.005279');
			$nooGmapL10n = array(
				'ajax_url'        => admin_url( 'admin-ajax.php', 'relative' ),
				'home_url'		  => get_site_url(),
				'theme_dir'		  => get_template_directory(),
				'theme_uri'		  => get_template_directory_uri(),
				'latitude'=>$latitude,
				'longitude'=>$longitude,
				'maxZoom_MarkerClusterer'=>5,
				'zoom'=>self::get_google_map_option('zoom',12),
				'fitbounds'=>self::get_google_map_option('fitbounds','1') ? true : false,
				'draggable'=>self::get_google_map_option('draggable','1') ? true : false,
				'area_unit' => self::get_general_option('area_unit'),
				'thousands_sep' => wp_specialchars_decode( stripslashes(self::get_general_option('price_thousand_sep')),ENT_QUOTES),
				'decimal_sep' => wp_specialchars_decode( stripslashes(self::get_general_option('price_decimal_sep')),ENT_QUOTES),
				'num_decimals' => self::get_general_option('price_num_decimals'),
				'currency'=>self::get_currency_symbol(self::get_general_option('currency')),
				'currency_position'=>self::get_general_option('currency_position','left'),
				'default_label'=>'',
				'fullscreen_label'=>'',
				'no_geolocation_pos'=>__("The browser couldn't detect your position!",NOO_TEXT_DOMAIN),
				'no_geolocation_msg'=>__("Geolocation is not supported by this browser.",NOO_TEXT_DOMAIN),
				'markers'=> ( $has_map_data ? self::get_properties_markers() : json_encode(array()) ),
				'ajax_finishedMsg'=>__('All posts displayed',NOO_TEXT_DOMAIN),
			);
			wp_localize_script('noo-property-map', 'nooGmapL10n', $nooGmapL10n);
			wp_enqueue_script( 'noo-property-map' );
		}
		
		public function restrict_manage_posts(){
			global $typenow, $wp_query;
			switch ( $typenow ) {
				case 'noo_property' :
					$this->property_filters();
				break;
			}
		}
		
		public function property_filters(){
			global $wp_query;
			$current_property_category = isset( $wp_query->query['property_category'] ) ? $wp_query->query['property_category'] : '';
			wp_dropdown_categories(array(
				'taxonomy'=>'property_category',
				'name'=>'property_category',
				'echo'=>true,
				'show_count'=>true,
				'show_option_none'=>__('--Show All--',NOO_TEXT_DOMAIN),
				'option_none_value'=>0,
				'selected'=>$current_property_category,
				'walker'=>new NooPropertyFilterDropdown
			));
			
			
			$current_property_location = isset( $wp_query->query['property_location'] ) ? $wp_query->query['property_location'] : '';
			wp_dropdown_categories(array(
				'taxonomy'=>'property_location',
				'name'=>'property_location',
				'echo'=>true,
				'show_count'=>true,
				'show_option_none'=>__('--Show All--',NOO_TEXT_DOMAIN),
				'option_none_value'=>0,
				'selected'=>$current_property_location,
				'walker'=>new NooPropertyFilterDropdown
			));
			
			$current_property_sub_location = isset( $wp_query->query['property_sub_location'] ) ? $wp_query->query['property_sub_location'] : '';
			wp_dropdown_categories(array(
				'taxonomy'=>'property_sub_location',
				'name'=>'property_sub_location',
				'echo'=>true,
				'show_count'=>true,
				'show_option_none'=>__('--Show All--',NOO_TEXT_DOMAIN),
				'option_none_value'=>0,
				'hierarchical'=>true,
				'selected'=>$current_property_sub_location,
				'walker'=>new NooPropertyFilterDropdown
			));
			
			$current_property_status = isset( $wp_query->query['property_status'] ) ? $wp_query->query['property_status'] : '';
			wp_dropdown_categories(array(
				'taxonomy'=>'property_status',
				'name'=>'property_status',
				'echo'=>true,
				'show_count'=>true,
				'show_option_none'=>__('--Show All--',NOO_TEXT_DOMAIN),
				'option_none_value'=>0,
				'selected'=>$current_property_status,
				'walker'=>new NooPropertyFilterDropdown
			));
				
		}
		
		/**
		 * Hook into pre_get_posts
		 *
		 * @param WP_Query $q query object
		 * @return void
		 */
		public function pre_get_posts($q){
			global $wpdb,$noo_show_sold;

			if( $q->is_main_query() && $q->is_singular ) {
				return;
			}

			if( self::is_noo_property_query( $q ) ){
					if(empty($noo_show_sold)){
						$sold = get_option('default_property_status');
						$tax_query = array(
								'taxonomy' => 'property_status',
								'terms'    => array( $sold ),
								'operator' => 'NOT IN',
						);
						$q->tax_query->queries[] = $tax_query;
						$q->query_vars['tax_query'] = $q->tax_query->queries;
					}
					if(isset($_GET['orderby'])){
						$orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'menu_order title';
						$orderby = strtolower( $orderby );
						$order   = 'DESC';
						$args    = array();
						$args['orderby']  = 'menu_order title';
						$args['order']    = $order == 'DESC' ? 'DESC' : 'ASC';
						$args['meta_key'] = '';
						
						switch ( $orderby ) {
							case 'rand' :
								$args['orderby']  = 'rand';
								break;
							case 'date' :
								$args['orderby']  = 'date';
								$args['order']    = $order == 'ASC' ? 'ASC' : 'DESC';
								break;
							case 'bath' :
								$args['orderby']  = "meta_value_num {$wpdb->posts}.ID";
								$args['order']    = $order == 'DESC' ? 'DESC' : 'ASC';
								$args['meta_key'] = '_bathrooms';
								break;
							case 'bed' :
								$args['orderby']  = "meta_value_num {$wpdb->posts}.ID";
								$args['order']    = $order == 'DESC' ? 'DESC' : 'ASC';
								$args['meta_key'] = '_bedrooms';
								break;
							case 'area' :
								$args['orderby']  = "meta_value_num {$wpdb->posts}.ID";
								$args['order']    = $order == 'DESC' ? 'DESC' : 'ASC';
								$args['meta_key'] = '_area';
								break;
							case 'price' :
								$args['orderby']  = "meta_value_num {$wpdb->posts}.ID";
								$args['order']    = $order == 'DESC' ? 'DESC' : 'ASC';
								$args['meta_key'] = '_price';
								break;
							case 'name' :
								$args['orderby']  = 'title';
								$args['order']    = $order == 'DESC' ? 'DESC' : 'ASC';
								break;
						}
						$q->set( 'orderby', $args['orderby'] );
						$q->set( 'order', $args['order'] );
						if ( isset( $args['meta_key'] ) )
							$q->set( 'meta_key', $args['meta_key'] );
					}
			}
		}

		public static function is_noo_property_query( $query = null ) {
			if( empty( $query ) ) return false;

			if( isset($query->query_vars['post_type']) && $query->query_vars['post_type'] === 'noo_property' )
				return true;

			if( $query->is_tax ) {
				if( ( isset( $query->query_vars['property_category'] ) && !empty( $query->query_vars['property_category'] ) )
					|| ( isset( $query->query_vars['property_status'] ) && !empty( $query->query_vars['property_status'] ) )
					|| ( isset( $query->query_vars['property_location'] ) && !empty( $query->query_vars['property_location'] ) )
					|| ( isset( $query->query_vars['property_sub_location'] ) && !empty( $query->query_vars['property_sub_location'] ) ) ) {
					return true;
				}
			}

			return false;
		}
		
		public function template_loader($template){
			if(is_tax('property_category') || is_tax('property_status') || is_tax('property_location') || is_tax('property_sub_location')){
				$template       = locate_template( 'taxonomy-property_category.php' );
			}
			return $template;
		}
		
		public static function get_general_option($id,$default = null){
			$options = get_option('noo_property_general');
			if (isset($options[$id])) {
				return $options[$id];
			}
			return $default;
		}
		
		public static function get_custom_field_option($id,$default = null){
			$options = get_option('noo_property_custom_filed');
			if (isset($options[$id])) {
				if (function_exists('icl_translate') ){
					if( is_array($options[$id]) ) {
						foreach ($options[$id] as $index => $custom_field) {
							if( !is_array($custom_field) ) continue;
							$options[$id][$index]['label_translated'] = icl_translate(NOO_TEXT_DOMAIN,'noo_property_custom_fields_'. sanitize_title(@$custom_field['name']), @$custom_field['label'] );
						}
					}
                }
				return $options[$id];
			}
			return $default;
		}
		
		public static function get_feature_option($id,$default = null){
			$options = get_option('noo_property_feature');
			if (isset($options[$id])) {
				return $options[$id];
			}
			return $default;
		}
		
		public static function get_advanced_search_option($id,$default = null){
			$options = get_option('noo_property_advanced_search');
			if (isset($options[$id])) {
				return $options[$id];
			}
			return $default;
		}
		
		
		public static function get_google_map_option($id,$default = null){
			$options = get_option('noo_property_google_map');
			if (isset($options[$id])) {
				return $options[$id];
			}
			return $default;
		}
		
		
		public function admin_init(){
			register_setting('noo_property_general','noo_property_general');
			register_setting('noo_property_custom_filed','noo_property_custom_filed');
			register_setting('noo_property_feature','noo_property_feature');
			register_setting('noo_property_advanced_search','noo_property_advanced_search');
			register_setting('noo_property_google_map','noo_property_google_map');
			
			add_action('noo_property_settings_general', array(&$this,'settings_general'));
			add_action('noo_property_settings_custom_field', array(&$this,'settings_custom_field'));
			add_action('noo_property_settings_feature', array(&$this,'settings_feature'));
			add_action('noo_property_settings_advanced_search', array(&$this,'settings_advanced_search'));
			add_action('noo_property_settings_google_map', array(&$this,'settings_google_map'));
			
			$this->feature_property();
			
		}
		
		
		public function add_meta_boxes(){
			$property_labels = array();
			$property_labels[] = array('value'=>'','label'=>__('Select a label',NOO_TEXT_DOMAIN));
			$property_labe_terms = (array) get_terms('property_label',array('hide_empty'=>0));

			foreach ($property_labe_terms as $label){
				$property_labels[] = array('value'=>$label->term_id,'label'=>$label->name);
			}
			$meta_box = array(
					'id' => "property_detail",
					'title' => __('Property Details', NOO_TEXT_DOMAIN) ,
					'page' => 'noo_property',
					'context' => 'normal',
					'priority' => 'high',
					'fields' => array(
							array(
								'id'=>'_label',
								'label'=>__('Property Label',NOO_TEXT_DOMAIN),
								'type'=>'select',
								'options'=>$property_labels
							),
							array(
									'id' => '_address',
									'label' => __('Address',NOO_TEXT_DOMAIN),
									'type' => 'text',
							),
							array(
									'id' => '_price',
									'label' => __('Price',NOO_TEXT_DOMAIN) . ' (' . NooProperty::get_currency_symbol(NooProperty::get_general_option('currency')) . ')',
									'type' => 'text',
							),
							array(
									'id' => '_price_label',
									'label' => __('After Price Label',NOO_TEXT_DOMAIN),
									'type' => 'text',
							),
							array(
									'id' => '_area',
									'label' => __('Area',NOO_TEXT_DOMAIN) . ' (' . NooProperty::get_general_option('area_unit') . ')',
									'type' => 'text',
							),
							array(
									'id' => '_bedrooms',
									'label' => __('Bedrooms',NOO_TEXT_DOMAIN),
									'type' => 'text',
							),
							array(
									'id' => '_bathrooms',
									'label' => __('Bathrooms',NOO_TEXT_DOMAIN),
									'type' => 'text',
							)
					)
			);
			
			// Create a callback function
			$callback = create_function( '$post,$meta_box', 'noo_create_meta_box( $post, $meta_box["args"] );' );
			add_meta_box( $meta_box['id'], $meta_box['title'], $callback, $meta_box['page'], $meta_box['context'], $meta_box['priority'], $meta_box );
				
				
			
			$custom_fields = self::get_custom_field_option('custom_field');
			$property_detail_fields = array();
			if($custom_fields){
				foreach ($custom_fields as $custom_field){
					$id = '_noo_property_field_'.sanitize_title(@$custom_field['name']);
					$property_detail_fields[] = array(
						'label' => isset( $custom_field['label_translated'] ) ? $custom_field['label_translated'] : @$custom_field['label'] ,
						'id' => $id,
						'type' => 'text',
					);
				}
				

				$meta_box = array(
						'id' => "property_custom",
						'title' => __('Property Custom', NOO_TEXT_DOMAIN) ,
						'page' => 'noo_property',
						'context' => 'normal',
						'priority' => 'high',
						'fields' => $property_detail_fields
				);
					
				// Create a callback function
				$callback = create_function( '$post,$meta_box', 'noo_create_meta_box( $post, $meta_box["args"] );' );
				add_meta_box( $meta_box['id'], $meta_box['title'], $callback, $meta_box['page'], $meta_box['context'], $meta_box['priority'], $meta_box );
					
			}
			
			$features = self::get_feature_option('features');
			$property_feature_fields = array();
			if($features){
				foreach ($features as $feature){
						
					$property_feature_fields[] = array(
							'label' => function_exists('icl_translate') ? icl_translate(NOO_TEXT_DOMAIN,'noo_property_features_'. sanitize_title( $feature ), $feature ) : $feature,
							'id' => '_noo_property_feature_'.sanitize_title($feature),
							'type' => 'checkbox',
					);
				}
			}
			if( !empty( $property_feature_fields ) ) {
				$meta_box = array(
						'id' => "property_feature",
						'title' => __('Property Features', NOO_TEXT_DOMAIN) ,
						'page' => 'noo_property',
						'context' => 'normal',
						'priority' => 'high',
						'fields' => $property_feature_fields
				);

				// Create a callback function
				$callback = create_function( '$post,$meta_box', 'noo_create_meta_box( $post, $meta_box["args"] );' );
				add_meta_box( $meta_box['id'], $meta_box['title'], $callback, $meta_box['page'], $meta_box['context'], $meta_box['priority'], $meta_box );
			}

			$meta_box = array(
					'id' => "property_map",
					'title' => __('Place in Map', NOO_TEXT_DOMAIN) ,
					'page' => 'noo_property',
					'context' => 'normal',
					'priority' => 'high',
					'fields' => array(
							array(
									'id' => '_noo_property_gmap',
									'type' => 'gmap',
									'callback'=>array(&$this,'meta_box_google_map')
							),
							array(
									'label' =>__('Latitude',NOO_TEXT_DOMAIN),
									'id' => '_noo_property_gmap_latitude',
									'type' => 'text',
									'std'=> self::get_google_map_option('latitude','40.714398')
							),
							array(
									'label' =>__('Longitude',NOO_TEXT_DOMAIN),
									'id' => '_noo_property_gmap_longitude',
									'type' => 'text',
									'std' => self::get_google_map_option('longitude','-74.005279')
							),
							array(
								'label' =>__('Map Zoom Level',NOO_TEXT_DOMAIN),
								'id' => '_noo_property_gmap_zoom',
								'type' => 'text',
								'std' => '16'
							),
					)
			);
			$callback = create_function( '$post,$meta_box', 'noo_create_meta_box( $post, $meta_box["args"] );' );
			add_meta_box( $meta_box['id'], $meta_box['title'], $callback, $meta_box['page'], $meta_box['context'], $meta_box['priority'], $meta_box );
				
			$meta_box = array(
					'id' => "property_video",
					'title' => __('Property Video', NOO_TEXT_DOMAIN) ,
					'page' => 'noo_property',
					'context' => 'normal',
					'priority' => 'high',
					'fields' => array(
							array(
									'label' => __('Video Embedded', NOO_TEXT_DOMAIN),
									'desc' => __('Enter a Youtube, Vimeo, Soundcloud, etc... URL. See supported services at <a href="http://codex.wordpress.org/Embeds">http://codex.wordpress.org/Embeds</a>.', NOO_TEXT_DOMAIN),
									'id' => '_video_embedded',
									'type' => 'text',
							),
					),
			);
			// Create a callback function
			$callback = create_function( '$post,$meta_box', 'noo_create_meta_box( $post, $meta_box["args"] );' );
			add_meta_box( $meta_box['id'], $meta_box['title'], $callback, $meta_box['page'], $meta_box['context'], $meta_box['priority'], $meta_box );
			
				
			
			$meta_box = array(
					'id' => "property_gallery",
					'title' => __('Gallery', NOO_TEXT_DOMAIN) ,
					'page' => 'noo_property',
					'context' => 'normal',
					'priority' => 'high',
					'fields' => array(
							array(
									'label' =>__('Gallery',NOO_TEXT_DOMAIN),
									'id' => '_gallery',
									'type' => 'gallery',
							),
					),
			);
			// Create a callback function
			$callback = create_function( '$post,$meta_box', 'noo_create_meta_box( $post, $meta_box["args"] );' );
			add_meta_box( $meta_box['id'], $meta_box['title'], $callback, $meta_box['page'], $meta_box['context'], $meta_box['priority'], $meta_box );
			
			
			
			$meta_box = array(
				'id' => 'agent_responsible',
				'title' => __('Agent Responsible', NOO_TEXT_DOMAIN),
				'page' => 'noo_property',
				'context' => 'side',
				'priority' => 'default',
				'fields' => array(
					array(
						'label' => __('Agent Responsible', NOO_TEXT_DOMAIN),
						'id'    => '_agent_responsible',
						'type'  => 'agents',
						'callback' => 'NooAgent::render_metabox_fields'
					)
				)
			);
			// Create a callback function
			$callback = create_function( '$post,$meta_box', 'noo_create_meta_box( $post, $meta_box["args"] );' );
			add_meta_box( $meta_box['id'], $meta_box['title'], $callback, $meta_box['page'], $meta_box['context'], $meta_box['priority'], $meta_box );
		}
		
		public function meta_box_google_map($post,$meta_box){
			?>
			<style>
			<!--
			.noo-form-group._gallery > label{
				display: none;
			}
			.noo-form-group._gallery .noo-thumb-wrapper img{
				max-width: 112px;
				max-height: 112px;
				width: 112px;
				height: 112px;
			}
			._noo_property_gmap .noo-control{float: none;width: 100%;}
			-->
			</style>
			<div class="noo_property_google_map">
				<div id="noo_property_google_map" class="noo_property_google_map" style="height: 380px; margin-bottom: 30px; overflow: hidden;position: relative;width: 100%;">
				</div>
				<div class="noo_property_google_map_search">
					<input placeholder="<?php echo __('Search your map',NOO_TEXT_DOMAIN)?>" type="text" autocomplete="off" id="noo_property_google_map_search_input">
				</div>
			</div>
			<?php
		}
		
		public function admin_menu(){
			add_submenu_page('edit.php?post_type=noo_property',  __('Settings',NOO_TEXT_DOMAIN),   __('Settings',NOO_TEXT_DOMAIN), 'edit_posts', 'noo-property-setting',array(&$this,'settings_page'));			
		}
		
		public function settings_page(){
			$current_tab     = empty( $_GET['tab'] ) ? 'general' : sanitize_title( $_GET['tab'] );
			$tabs = apply_filters( 'noo_property_settings_tabs_array', array(
				'general'=>__('General',NOO_TEXT_DOMAIN),
				'custom_field'=>__('Custom Fields',NOO_TEXT_DOMAIN),
				'feature'	=>__('Listings Features & Amenities',NOO_TEXT_DOMAIN),
				'advanced_search'	=>__('Advanced Search',NOO_TEXT_DOMAIN),
				'google_map'	=>__('Google Map',NOO_TEXT_DOMAIN)
			));
			
			?>
			<div class="wrap">
				<form action="options.php" method="post">
					<h2 class="nav-tab-wrapper">
						<?php
							foreach ( $tabs as $name => $label )
								echo '<a href="' . admin_url( 'edit.php?post_type=noo_property&page=noo-property-setting&tab=' . $name ) . '" class="nav-tab ' . ( $current_tab == $name ? 'nav-tab-active' : '' ) . '">' . $label . '</a>';
						?>
					</h2>
					<?php 
					do_action( 'noo_property_settings_' . $current_tab );
					?>
					<p class="submit">
						<input type="submit" value="<?php echo __('Save Changes',NOO_TEXT_DOMAIN) ?>" class="button button-primary" id="submit" name="submit">
					</p>
				</form>
			</div>			
			<?php
		}
		
		public function settings_general(){
			$currency_code_options = self::get_currencies();
			$archive_slug = self::get_general_option('archive_slug','properties');
			$area_unit = self::get_general_option('area_unit');
			$currency = self::get_general_option('currency');
			$currency_position = self::get_general_option('currency_position');
			$price_thousand_sep = self::get_general_option('price_thousand_sep');
			$price_decimal_sep = self::get_general_option('price_decimal_sep');
			$price_num_decimals = self::get_general_option('price_num_decimals');
			foreach ( $currency_code_options as $code => $name ) {
				$currency_code_options[ $code ] = $name . ' (' . self::get_currency_symbol( $code ) . ')';
			}
			?>
			<?php settings_fields('noo_property_general'); ?>
			<h3><?php echo __('General Options',NOO_TEXT_DOMAIN)?></h3>
			<table class="form-table" cellspacing="0">
				<tbody>
					<tr>
						<th>
							<?php esc_html_e('Property Archive base (slug)',NOO_TEXT_DOMAIN)?>
						</th>
						<td>
							<input type="text" name="noo_property_general[archive_slug]" value="<?php echo ($archive_slug ? $archive_slug :'properties') ?>">
							<p><small><?php echo sprintf( __( 'If you made change on this opiton, you will have to go to <a href="%s" target="_blank">Permalink Settings</a><br/> and click "Save Changes" button for reseting WordPress link structure.', NOO_TEXT_DOMAIN ), admin_url( '/options-permalink.php' ) ); ?></small></p>
						</td>
					</tr>
					<tr>
						<th>
							<?php esc_html_e('Area Unit',NOO_TEXT_DOMAIN)?>
						</th>
						<td>
							<input type="text" name="noo_property_general[area_unit]" value="<?php echo ($area_unit ? $area_unit :'m') ?>">
						</td>
					</tr>
					<tr>
						<th>
							<?php esc_html_e('Currency',NOO_TEXT_DOMAIN)?>
						</th>
						<td>
							<select name="noo_property_general[currency]">
								<?php foreach ($currency_code_options as $key=>$label):?>
								<option value="<?php echo esc_attr($key)?>" <?php selected($currency,$key)?>><?php echo esc_html($label)?></option>
								<?php endforeach;?>
							</select>
						</td>
					</tr>
					<tr>
						<th>
							<?php esc_html_e('Currency Position',NOO_TEXT_DOMAIN)?>
						</th>
						<td>
							<?php 
							$position = array(
									'left'        => __( 'Left', NOO_TEXT_DOMAIN ) . ' (' . self::get_currency_symbol() . '99.99)',
									'right'       => __( 'Right', NOO_TEXT_DOMAIN ) . ' (99.99' . self::get_currency_symbol() . ')',
									'left_space'  => __( 'Left with space', NOO_TEXT_DOMAIN ) . ' (' . self::get_currency_symbol() . ' 99.99)',
									'right_space' => __( 'Right with space', NOO_TEXT_DOMAIN ) . ' (99.99 ' . self::get_currency_symbol() . ')'
							)
							?>
							<select name="noo_property_general[currency_position]">
								<?php foreach ($position as $key=>$label):?>
								<option value="<?php echo esc_attr($key)?>" <?php selected($currency_position,$key)?>><?php echo esc_html($label)?></option>
								<?php endforeach;?>
							</select>
						</td>
					</tr>
					<tr>
						<th>
							<?php esc_html_e('Thousand Separator',NOO_TEXT_DOMAIN)?>
						</th>
						<td>
							<input type="text" name="noo_property_general[price_thousand_sep]" value="<?php echo ($price_thousand_sep ? $price_thousand_sep :',') ?>">
						</td>
					</tr>
					<tr>
						<th>
							<?php esc_html_e('Decimal Separator',NOO_TEXT_DOMAIN)?>
						</th>
						<td>
							<input type="text" name="noo_property_general[price_decimal_sep]" value="<?php echo ($price_decimal_sep ? $price_decimal_sep :'.') ?>">
						</td>
					</tr>
					<tr>
						<th>
							<?php esc_html_e('Number of Decimals',NOO_TEXT_DOMAIN)?>
						</th>
						<td>
							<input type="number" step="1" min="0" name="noo_property_general[price_num_decimals]" value="<?php echo ($price_num_decimals !=='' && $price_num_decimals !== null && $price_num_decimals !== array() ? $price_num_decimals :'2') ?>">
						</td>
					</tr>
				</tbody>
			</table>
			<?php
			}
		
		public function settings_custom_field(){
		
			$fields = self::get_custom_field_option('custom_field');
			?>
			<?php settings_fields('noo_property_custom_filed'); ?>
			<h3><?php echo __('Custom Fields',NOO_TEXT_DOMAIN)?></h3>
			<table class="form-table" cellspacing="0">
				<tbody>
					<tr>
						<th>
							<?php esc_html_e('Fields',NOO_TEXT_DOMAIN)?>
						</th>
						<td>
							<?php 
								$num_arr = count($fields) ? array_map( 'absint', array_keys($fields) ) : array();
								$num = !empty($num_arr) ? end($num_arr) : 1;
							?>
							<table class="widefat noo_property_custom_field_table" data-num="<?php echo $num ?>" cellspacing="0" >
								<thead>
									<tr>
										<th style="padding: 9px 7px">
											<?php esc_html_e('Field Name',NOO_TEXT_DOMAIN)?>
										</th>
										<th style="padding: 9px 7px">
											<?php esc_html_e('Field Label',NOO_TEXT_DOMAIN)?>
										</th>
										<th style="padding: 9px 7px">
											<?php esc_html_e('Action',NOO_TEXT_DOMAIN)?>
										</th>
									</tr>
								</thead>
								<tbody>
									<?php  if(!empty($fields)): ?>
									<?php foreach ($fields as $key=>$field):?>
									<tr data-stt = "<?php echo esc_attr($key)?>">
										<td>
											<input type="text" value="<?php echo esc_attr($field['name'])?>" placeholder="<?php esc_attr_e('Field Name',NOO_TEXT_DOMAIN)?>" name="noo_property_custom_filed[custom_field][<?php echo $key?>][name]">
										</td>
										<td>
											<input type="text" value="<?php echo esc_attr($field['label'])?>" placeholder="<?php esc_attr_e('Field Label',NOO_TEXT_DOMAIN)?>" name="noo_property_custom_filed[custom_field][<?php echo $key?>][label]">
										</td>
										<td>
											<input class="button button-primary" onclick="return delete_noo_property_custom_field(this);" type="button" value="<?php esc_attr_e('Delete',NOO_TEXT_DOMAIN)?>">
										</td>
									</tr>
									<?php endforeach;?>
									<?php endif;?>
								</tbody>
								<tfoot>
									<tr>
										<td colspan="4">
											<input class="button button-primary" id="add_noo_property_custom_field" type="button" value="<?php esc_attr_e('Add',NOO_TEXT_DOMAIN)?>">
										</td>
									</tr>
								</tfoot>
							</table>
						</td>
					</tr>
				</tbody>
			</table>
			<?php
		}
		public function settings_feature(){
		
			$features = self::get_feature_option('features');
			?>
			<?php settings_fields('noo_property_feature'); ?>
			<h3><?php echo __('Listings Features & Amenities',NOO_TEXT_DOMAIN)?></h3>
			<table class="form-table" cellspacing="0">
				<tbody>
					<tr>
						<th>
							<?php esc_html_e('Add New Element in Features and Amenities ',NOO_TEXT_DOMAIN)?>
						</th>
						<td>
							<table class="widefat noo_property_feature_table" cellspacing="0" >
								<thead>
									<tr>
										<th style="padding: 9px 7px">
											<?php esc_html_e('Feature Name',NOO_TEXT_DOMAIN)?>
										</th>
										<th style="padding: 9px 7px">
											<?php esc_html_e('Action',NOO_TEXT_DOMAIN)?>
										</th>
									</tr>
								</thead>
								<tbody>
									<?php  if(!empty($features)): ?>
									<?php foreach ($features as $k=>$feature):?>
									<tr>
										<td>
											<input type="text" value="<?php echo esc_attr($feature)?>" placeholder="<?php esc_attr_e('Feature Name',NOO_TEXT_DOMAIN)?>" name="noo_property_feature[features][]">
										</td>
										<td>
											<input class="button button-primary" onclick="return delete_noo_property_feature(this);" type="button" value="<?php esc_attr_e('Delete',NOO_TEXT_DOMAIN)?>">
										</td>
									</tr>
									<?php endforeach;?>
									<?php endif;?>
								</tbody>
								<tfoot>
									<tr>
										<td colspan="2">
											<input class="button button-primary" id="add_noo_property_feature" type="button" value="<?php esc_attr_e('Add',NOO_TEXT_DOMAIN)?>">
										</td>
									</tr>
								</tfoot>
							</table>
						</td>
					</tr>
					<tr>
						<th>
							<?php esc_html_e('Show the Features and Amenities that are not available',NOO_TEXT_DOMAIN)?>
						</th>
						<td>
							<?php $show_no_feature = self::get_feature_option('show_no_feature')?>
							<select name="noo_property_feature[show_no_feature]">
								<option <?php selected($show_no_feature,'yes')?> value="yes"><?php esc_html_e("Yes",NOO_TEXT_DOMAIN)?></option>
								<option <?php selected($show_no_feature,'no')?> value="no"><?php esc_html_e("No",NOO_TEXT_DOMAIN)?></option>
							</select>
						</td>
					</tr>
				</tbody>
			</table>
			<?php
		}
		
		public function settings_advanced_search(){
			$fields = array(
				''=>__('None', NOO_TEXT_DOMAIN),
				'property_location'=>__('Property Location',NOO_TEXT_DOMAIN),
				'property_sub_location'=>__('Property Sub Location',NOO_TEXT_DOMAIN),
				'property_status'=>__('Property Status',NOO_TEXT_DOMAIN),
				'property_category'=>__('Property Types',NOO_TEXT_DOMAIN),
				'_bedrooms'=>__('Bedrooms Meta',NOO_TEXT_DOMAIN),
				'_bathrooms'=>__('Bathrooms Meta',NOO_TEXT_DOMAIN),
				'_price'=>__('Price Meta',NOO_TEXT_DOMAIN),
				'_area'=>__('Area Meta',NOO_TEXT_DOMAIN),
			);
			$custom_fields = self::get_custom_field_option('custom_field');
			if($custom_fields){
				foreach ($custom_fields as $k=>$custom_field){
					$label = __('Custom Field: ',NOO_TEXT_DOMAIN).( isset( $custom_field['label_translated'] ) ? $custom_field['label_translated'] : (isset($custom_field['label']) ? $custom_field['label'] : $k));
					$id = '_noo_property_field_'.sanitize_title(@$custom_field['name']).'|'.(isset($custom_field['label']) ? $custom_field['label'] : $k);
					$fields[$id] = $label;
				}
			}
			$pos1 = self::get_advanced_search_option('pos1','property_location');
			$pos2 = self::get_advanced_search_option('pos2','property_sub_location');
			$pos3 = self::get_advanced_search_option('pos3','property_status');
			$pos4 = self::get_advanced_search_option('pos4','property_category');
			$pos5 = self::get_advanced_search_option('pos5','_bedrooms');
			$pos6 = self::get_advanced_search_option('pos6','_bathrooms');
			$pos7 = self::get_advanced_search_option('pos7','_price');
			$pos8 = self::get_advanced_search_option('pos8','_area');
			
			wp_enqueue_style('vendor-chosen-css');
			wp_enqueue_script('vendor-chosen-js');
			
		?>
			<?php settings_fields('noo_property_advanced_search'); ?>
			<h3><?php echo __('Search Field Position',NOO_TEXT_DOMAIN)?></h3>
			<table class="form-table" cellspacing="0">
				<tbody>
					<tr>
						<th>
							<?php _e('Position #1',NOO_TEXT_DOMAIN)?>
						</th>
						<td>
							<select name="noo_property_advanced_search[pos1]">
							<?php foreach ($fields as $key=>$field):?>
								<option value="<?php echo esc_attr($key)?>" <?php selected($pos1,esc_attr($key))?>><?php echo $field?></option>
							<?php endforeach;?>
							</select>
						</td>
					</tr>
					<tr>
						<th>
							<?php _e('Position #2',NOO_TEXT_DOMAIN)?>
						</th>
						<td>
							<select name="noo_property_advanced_search[pos2]">
							<?php foreach ($fields as $key=>$field):?>
								<option value="<?php echo esc_attr($key)?>" <?php selected($pos2,esc_attr($key))?>><?php echo $field?></option>
							<?php endforeach;?>
							</select>
						</td>
					</tr>
					<tr>
						<th>
							<?php _e('Position #3',NOO_TEXT_DOMAIN)?>
						</th>
						<td>
							<select name="noo_property_advanced_search[pos3]">
							<?php foreach ($fields as $key=>$field):?>
								<option value="<?php echo esc_attr($key)?>" <?php selected($pos3,esc_attr($key))?>><?php echo $field?></option>
							<?php endforeach;?>
							</select>
						</td>
					</tr>
					<tr>
						<th>
							<?php _e('Position #4',NOO_TEXT_DOMAIN)?>
						</th>
						<td>
							<select name="noo_property_advanced_search[pos4]">
							<?php foreach ($fields as $key=>$field):?>
								<option value="<?php echo esc_attr($key)?>" <?php selected($pos4,esc_attr($key))?>><?php echo $field?></option>
							<?php endforeach;?>
							</select>
						</td>
					</tr>
					<tr>
						<th>
							<?php _e('Position #5',NOO_TEXT_DOMAIN)?>
						</th>
						<td>
							<select name="noo_property_advanced_search[pos5]">
							<?php foreach ($fields as $key=>$field):?>
								<option value="<?php echo esc_attr($key)?>" <?php selected($pos5,esc_attr($key))?>><?php echo $field?></option>
							<?php endforeach;?>
							</select>
						</td>
					</tr>
					<tr>
						<th>
							<?php _e('Position #6',NOO_TEXT_DOMAIN)?>
						</th>
						<td>
							<select name="noo_property_advanced_search[pos6]">
							<?php foreach ($fields as $key=>$field):?>
								<option value="<?php echo esc_attr($key)?>" <?php selected($pos6,esc_attr($key))?>><?php echo $field?></option>
							<?php endforeach;?>
							</select>
						</td>
					</tr>
					<tr>
						<th>
							<?php _e('Position #7',NOO_TEXT_DOMAIN)?>
						</th>
						<td>
							<select name="noo_property_advanced_search[pos7]">
							<?php foreach ($fields as $key=>$field):?>
								<option value="<?php echo esc_attr($key)?>" <?php selected($pos7,esc_attr($key))?>><?php echo $field?></option>
							<?php endforeach;?>
							</select>
						</td>
					</tr>
					<tr>
						<th>
							<?php _e('Position #8',NOO_TEXT_DOMAIN)?>
						</th>
						<td>
							<select name="noo_property_advanced_search[pos8]">
							<?php foreach ($fields as $key=>$field):?>
								<option value="<?php echo esc_attr($key)?>" <?php selected($pos8,esc_attr($key))?>><?php echo $field?></option>
							<?php endforeach;?>
							</select>
						</td>
					</tr>
				</tbody>
			</table>
			<h3><?php echo __('Advanced Search Field',NOO_TEXT_DOMAIN)?></h3>
			<?php 
			$features = self::get_feature_option('features');
			$feature_selected = self::get_advanced_search_option('advanced_search_field',array());
			?>
			<table class="form-table" cellspacing="0">
				<tbody>
					<tr>
						<th>
							<?php _e('Select Advanced Search Field',NOO_TEXT_DOMAIN)?>
						</th>
						<td>
							<select class="advanced_search_field" name="noo_property_advanced_search[advanced_search_field][]" multiple="multiple">
							<?php if($features):?>
								<?php foreach ((array)$features as $key=>$feature):?>
									<?php 
									$field_id = sanitize_title($feature);
									$feature = function_exists('icl_translate') ? icl_translate(NOO_TEXT_DOMAIN,'noo_property_features_'. $field_id, $feature ) : $feature;
									?>
									<option value="<?php echo esc_attr($field_id)?>" <?php if(in_array($field_id, $feature_selected)):?> selected<?php endif;?>><?php echo ucfirst($feature)?></option>
								<?php endforeach;?>
							<?php endif;?>
							</select>
							<script type="text/javascript">
								jQuery(document).ready(function(){
									jQuery("select.advanced_search_field").chosen({
										"disable_search_threshold":10
									});
								});
							</script>
							<style type="text/css">
							.chosen-container input[type="text"]{
								height: auto !important;
							}
							</style>
						</td>
					</tr>
				</tbody>
			</table>
		<?php
		}
		public function settings_google_map(){
		?>
			<?php settings_fields('noo_property_google_map'); ?>
			<h3><?php echo __('Google Map',NOO_TEXT_DOMAIN)?></h3>
			<table class="form-table" cellspacing="0">
				<tbody>
					<tr>
						<th>
							<?php esc_html_e('Starting Point Latitude',NOO_TEXT_DOMAIN)?>
						</th>
						<td>
							<input type="text" class="regular-text" value="<?php echo self::get_google_map_option('latitude','40.714398')?>" name="noo_property_google_map[latitude]">
						</td>
					</tr>
					<tr>
						<th>
							<?php esc_html_e('Starting Point Longitude',NOO_TEXT_DOMAIN)?>
						</th>
						<td>
							<input type="text" class="regular-text"  value="<?php echo self::get_google_map_option('longitude','-74.005279')?>" name="noo_property_google_map[longitude]">
						</td>
					</tr>
					<tr>
						<th>
							<?php esc_html_e('Default Zoom Level',NOO_TEXT_DOMAIN)?>
						</th>
						<td>
							<input type="text" class="regular-text"  value="<?php echo self::get_google_map_option('zoom','12')?>" name="noo_property_google_map[zoom]">
						</td>
					</tr>
					<tr>
						<th>
							<?php esc_html_e('Automatically Fit all Properties',NOO_TEXT_DOMAIN)?>
						</th>
						<td>
							<input type="hidden" value="0" name="noo_property_google_map[fitbounds]">
							<input type="checkbox" value="1" <?php checked(self::get_google_map_option('fitbounds','1'), '1'); ?> name="noo_property_google_map[fitbounds]">
							<small><?php _e('Enable this option and all your listings will fit into your map automatically. Sometimes, the above options will be disregarded.', NOO_TEXT_DOMAIN); ?></small>
						</td>
					</tr>
					<tr>
						<th>
							<?php esc_html_e('Default Map Height (px)',NOO_TEXT_DOMAIN)?>
						</th>
						<td>
							<input type="text" class="regular-text"  value="<?php echo self::get_google_map_option('height','700')?>" name="noo_property_google_map[height]">
						</td>
					</tr>
					<tr>
						<th>
							<?php esc_html_e('Draggable',NOO_TEXT_DOMAIN)?>
						</th>
						<td>
							<input type="hidden" value="0" name="noo_property_google_map[draggable]">
							<input type="checkbox" value="1" <?php checked(self::get_google_map_option('draggable','1'), '1'); ?> name="noo_property_google_map[draggable]">
							<small><?php _e('Enable this option to make the map draggable on mobile. Be carefull with it because draggable map may prevent scrolling through map on small mobile screen.', NOO_TEXT_DOMAIN); ?></small>
						</td>
					</tr>
				</tbody>
			</table>
			<?php
		}
		
		public function property_slider_shortcode ( $atts, $content = null ) {
			self::enqueue_gmap_js();
			wp_enqueue_script('noo-property');
			extract( shortcode_atts( array(
			'visibility'         => '',
			'class'              => '',
			'id'                 => '',
			'custom_style'       => '',
			'animation'          => 'slide',
			'visible_items'      => '1',
			'slider_time'        => '3000',
			'slider_speed'       => '600',
			'slider_height'      => '700',
			'auto_play'          => '',
			'indicator'          => '',
			'prev_next_control'  => '',
			'show_search_form'   => '',
			'advanced_search'    => '',
			'show_search_info'   => 'true',
			'search_info_title'  => null,
			'search_info_content'=> null,
			), $atts ) );
		
			wp_enqueue_script( 'vendor-carouFredSel' );
		
			$show_search_form = ( $show_search_form == 'true' );
			if( !$show_search_form ) {
				$search_info_title = '';
				$search_info_content = '';
			}
			$show_search_info = $show_search_form ? ( $show_search_info == 'true' ) : false;
			$class            = ( $class              != '' ) ? esc_attr( $class ) : '' ;
			$visibility       = ( $visibility         != '' ) && ( $visibility != 'all' ) ? esc_attr( $visibility ) : '';
			switch ($visibility) {
				case 'hidden-phone':
					$class .= ' hidden-xs';
					break;
				case 'hidden-tablet':
					$class .= ' hidden-sm hidden-md';
					break;
				case 'hidden-pc':
					$class .= ' hidden-lg';
					break;
				case 'visible-phone':
					$class .= ' visible-xs-block visible-xs-inline visible-xs-inline-block';
					break;
				case 'visible-tablet':
					$class .= ' visible-sm-block visible-sm-inline visible-sm-inline-block visible-md-block visible-md-inline visible-md-inline-block';
					break;
				case 'visible-phone':
					$class .= ' visible-lg-block visible-lg-inline visible-lg-inline-block';
					break;
			}
		
		
			$html  = array();
		
			$id    = ( $id    != '' ) ? esc_attr( $id ) : 'noo-slider-' . noo_vc_elements_id_increment();
			
			$class .=' property-slider';
			
			$class = ( $class != '' ) ? 'class="' . $class . '"' : '';
			$custom_style   = ( $custom_style  != '' ) ? 'style="' . $custom_style . '"' : '';
		
			$indicator_html = array();
			$indicator_js   = array();
			if( $indicator == 'true') {
				$indicator_js[] = '    pagination: {';
				$indicator_js[] = '      container: "#' . $id . '-pagination"';
				$indicator_js[] = '    },';
		
				$indicator_html[] = '  <div id="' . $id . '-pagination" class="slider-indicators"></div>';
			}
		
			$prev_next_control_html = array();
			$prev_next_control_js   = array();
			if( $prev_next_control == 'true') {
				$prev_next_control_js[]   = '    prev: {';
				$prev_next_control_js[]   = '      button: "#' . $id . '-prev"';
				$prev_next_control_js[]   = '    },';
				$prev_next_control_js[]   = '    next: {';
				$prev_next_control_js[]   = '      button: "#' . $id . '-next"';
				$prev_next_control_js[]   = '    },';
		
				$prev_next_control_html[] = '  <a id="' . $id . '-prev" class="slider-control prev-btn" role="button" href="#"><span class="slider-icon-prev"></span></a>';
				$prev_next_control_html[] = '  <a id="' . $id . '-next" class="slider-control next-btn" role="button" href="#"><span class="slider-icon-next"></span></a>';
			}
		
			$swipe  = $pause_on_hover = 'true';
			$animation = ( $animation == 'slide' ) ? 'scroll' : $animation; // Not allow fading with carousel
		
		
			$html[] = '<div '.$class.' '.$custom_style.'>';
			$html[] = "<div id=\"{$id}\" class=\"noo-slider noo-property-slide-wrap\">";
			$html[] = '  <ul class="sliders">';
			$html[] = do_shortcode( $content );
			$html[] = '  </ul>';
			$html[] = '  <div class="clearfix"></div>';
			$html[] = implode( "\n", $indicator_html );
			$html[] = implode( "\n", $prev_next_control_html );
			$html[] = '</div>';
			if( $show_search_form ) {
				ob_start();
				self::advanced_map(false,'',false,'',$show_search_info,false,'property',false,!!$advanced_search,'',$search_info_title,$search_info_content);
				$html[] = ob_get_clean();
			}
			$html[] = '</div>';
		
			// slider script
			$html[] = '<script>';
			$html[] = "jQuery('document').ready(function ($) {";
			$html[] = "  $('#{$id} .sliders').carouFredSel({";
			$html[] = "    infinite: true,";
			$html[] = "    circular: true,";
			$html[] = "    responsive: true,";
			$html[] = "    debug : false,";
			$html[] = '    scroll: {';
			$html[] = '      items: 1,';
			$html[] = ( $slider_speed   != ''         ) ? '      duration: ' . $slider_speed . ',' : '';
			$html[] = ( $pause_on_hover == 'true'     ) ? '      pauseOnHover: "resume",' : '';
			$html[] = '      fx: "' . $animation . '"';
			$html[] = '    },';
			$html[] = '    auto: {';
			$html[] = ( $slider_time    != ''     ) ? '      timeoutDuration: ' . $slider_time . ',' : '';
			$html[] = ( $auto_play      == 'true' ) ? '      play: true' : '      play: false';
			$html[] = '    },';
			$html[] = implode( "\n", $prev_next_control_js );
			$html[] = implode( "\n", $indicator_js );
			$html[] = '    swipe: {';
			$html[] = "      onTouch: {$swipe},";
			$html[] = "      onMouse: {$swipe}";
			$html[] = '    }';
			$html[] = '  });';
			$html[] = '});';
			$html[] = '</script>';
			if( !empty( $slider_height ) ) {
				$html[] = '<style>';
				$html[] = "  #{$id}.noo-slider .caroufredsel_wrapper .sliders .slide-item.noo-property-slide { max-height: {$slider_height}px; }";
				$html[] = '</style>';
			}
		
			return implode( "\n", $html );
		}
		
		public function property_slide_shortcode($atts, $content = null){
			extract( shortcode_atts( array(
				'property_id'=>'',
				'background_type'=>'thumbnail',
				'image'=>'',
				
			), $atts ) );
			if(empty($property_id))
				return '';
				
			
			$property = get_post($property_id);
			if(empty($property))
				return '';
			
			$output = '';
			$output .='<li class="slide-item noo-property-slide">';
			if($background_type == 'thumbnail'){
				//$thumbnail = wp_get_attachment_url(get_post_thumbnail_id($property->ID));
				$output .= get_the_post_thumbnail($property->ID,'property-slider');
				//$output .='<img class="slide-image" src="' . $thumbnail . '">';
			}elseif ($background_type == 'image' && !empty($image)){
				$thumbnail = wp_get_attachment_url($image);
				$output .='<img class="slide-image" src="' . $thumbnail . '">';
			}
			$output .='<div class="slide-caption">';
			$output .='<div class="slide-caption-info">';
			$output .='<h3><a href="'.esc_url(get_permalink($property->ID)).'">'.get_the_title($property->ID).'</a>';
			if($address=noo_get_post_meta($property->ID,'_address')){
				$output .='<small>'.$address.'</small>';
			}
			$output .='</h3>';
			$output .='<div class="info-summary">';
			$output .='<div class="size"><span>'.self::get_area_html($property->ID).'</span></div>';
			$output .='<div class="bathrooms"><span>'.noo_get_post_meta($property->ID,'_bathrooms').'</span></div>'; 
			$output .='<div class="bedrooms"><span>'.noo_get_post_meta($property->ID,'_bedrooms').'</span></div>';
			$output .='<div class="property-price">';
			$output .='<span>'.NooProperty::get_price_html($property->ID).'</span>';
			$output .='</div>';
			$output .='</div>';
			$output .='</div>';
			$output .='<div class="slide-caption-action">';
			$output .='<a href="'.esc_url(get_permalink($property->ID)).'">'.__('More Details',NOO_TEXT_DOMAIN).'</a>';
			$output .='</div>';
			$output .='</div>';
			$output .='</li>';
			return $output;
		}
		public function advanced_search_property_shortcode($atts, $content = null){
			extract( shortcode_atts( array(
				'title'                     => '',
				'source'					=> 'property',
				'map_height'                => '',
				'style'                     => 'horizontal',
				'disable_map'               => '',
				'disable_search_form'		=> '',
				'advanced_search'           => '',
				'no_search_container'       => '',
				'visibility'                => '',
				'class'                     => '',
				'custom_style'              => ''
			), $atts ) );
			$style = !!$disable_search_form ? '' : $style;
			$show_advanced_search_field = ($style == 'horizontal') ? !!$advanced_search : false;
			$disable_map          = ( $disable_map == 'true' );
			self::enqueue_gmap_js( !$disable_map && $source == 'property' );
			
			$no_search_container  = $disable_map ? ( $no_search_container == 'true' ) : false;
			if( $source == 'IDX' ) {
				$disable_search_form = true;
				$advanced_search = false;
			}
			$visibility           = ( $visibility  != ''     ) && ( $visibility != 'all' ) ? esc_attr( $visibility ) : '';
			$class                = ( $class       != ''     ) ? esc_attr( $class ) : '';
			switch ($visibility) {
				case 'hidden-phone':
					$class .= ' hidden-xs';
					break;
				case 'hidden-tablet':
					$class .= ' hidden-sm hidden-md';
					break;
				case 'hidden-pc':
					$class .= ' hidden-lg';
					break;
				case 'visible-phone':
					$class .= ' visible-xs-block visible-xs-inline visible-xs-inline-block';
					break;
				case 'visible-tablet':
					$class .= ' visible-sm-block visible-sm-inline visible-sm-inline-block visible-md-block visible-md-inline visible-md-inline-block';
					break;
				case 'visible-phone':
					$class .= ' visible-lg-block visible-lg-inline visible-lg-inline-block';
					break;
			}
			$custom_style = ( $custom_style != '' ) ? ' style="' . $custom_style . '"' : '';
			ob_start();
			?>
			<div class="noo_advanced_search_property <?php echo $style.' '.$class?>" <?php echo $custom_style?>>
				<?php self::advanced_map( !$disable_map, '', false, '', false, $no_search_container, $source,!!$disable_search_form,$show_advanced_search_field, $map_height)?>
			</div>
			<?php
			return ob_get_clean();
		}
		
		public function recent_properties_shortcode($atts, $content = null){
			wp_enqueue_script('noo-property');
			extract( shortcode_atts( array(
				'title'             => '',
				'type'				=> 'list',
				'property_id'		=> '',
				'property_category'	=> '',
				'property_staus'	=> '',
				'property_label'	=> '',
				'property_location'	=> '',
				'property_sub_location'	=>'',
				'number'            => '6',
				'show'				=> '',
				'style'				=> 'grid',
				'show_control'		=> 'no',
				'show_pagination'	=> 'no',
				'visibility'        => '',
				'class'             => '',
				'custom_style'      => ''
			), $atts ) );
			
			$visibility       = ( $visibility      != ''     ) && ( $visibility != 'all' ) ? esc_attr( $visibility ) : '';
			$class            = ( $class           != ''     ) ? 'recent-properties ' . esc_attr( $class ) : 'recent-properties';
			switch ($visibility) {
				case 'hidden-phone':
					$class .= ' hidden-xs';
					break;
				case 'hidden-tablet':
					$class .= ' hidden-sm hidden-md';
					break;
				case 'hidden-pc':
					$class .= ' hidden-lg';
					break;
				case 'visible-phone':
					$class .= ' visible-xs-block visible-xs-inline visible-xs-inline-block';
					break;
				case 'visible-tablet':
					$class .= ' visible-sm-block visible-sm-inline visible-sm-inline-block visible-md-block visible-md-inline visible-md-inline-block';
					break;
				case 'visible-phone':
					$class .= ' visible-lg-block visible-lg-inline visible-lg-inline-block';
					break;
			}
			
			$class = ( $class != '' ) ? ' class="' . esc_attr( $class ) . '"' : '';
			$custom_style = ( $custom_style != '' ) ? ' style="' . $custom_style . '"' : '';
			if( is_front_page() || is_home()) {
				$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : ( ( get_query_var( 'page' ) ) ? get_query_var( 'page' ) : 1 );
			} else {
				$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
			}
			$args = array(
				'paged'			  	  => $paged,
				'orderby'         	  => "date",
				'order'           	  => "DESC",
				'posts_per_page'      => $number,
				'post_status'         => 'publish',
				'post_type'			  =>'noo_property',
			);
			if($type == 'list'){
				$property_label = absint($property_label);
				$sub_location = $category = $location = '';

				$args['tax_query'] = array('relation' => 'AND');
				if(!empty($property_category)){
					$args['tax_query'][] = array(
							'taxonomy'     => 'property_category',
							'field'        => 'slug',
							'terms'        => $property_category
						);
				}
				if(!empty($property_status)){
					$args['tax_query'][] = array(
							'taxonomy'     => 'property_status',
							'field'        => 'slug',
							'terms'        => $property_status
						);
				}
				if( !empty($property_location)){
					$args['tax_query'][] = array(
							'taxonomy'     => 'property_location',
							'field'        => 'slug',
							'terms'        => $property_location
						);
				}
				if( !empty($property_sub_location)){
					$args['tax_query'][] = array(
							'taxonomy'     => 'property_sub_location',
							'field'        => 'slug',
							'terms'        => $property_sub_location
						);
				}
				// $args['tax_query'] = array(
				// 	'relation' => 'AND',
				// 	$location,
				// 	$sub_location,
				// 	$category
				// );
				if(!empty($property_label)){
					$args['meta_query'][] = array(
						'key'   => '_label',
						'value' => $property_label
					);
				}
				if($show === 'featured'){
					$args['meta_query'][] = array(
							'key'   => '_featured',
							'value' => 'yes'
					);
				}
			
				$show_pagination  = $show_pagination ==='yes' ? true : false;
				$show_control = $show_control==='yes' ? true: false;
			
			}elseif ($type == 'single'){
				$args['p'] = absint($property_id);
			
				$show_pagination  = false;
				$show_control = false;
			}
			$q = new WP_Query($args);
			if($style==='grid'):
				 ob_start();
				  echo '<div class="recent-properties">';
				  self::display_content($q,$title,$show_control,'grid',$show_pagination);
				  echo '</div>';
				 return ob_get_clean();
			elseif ($style === 'list'):
				ob_start();
				echo '<div class="recent-properties">';
				self::display_content($q,$title,$show_control,'list',$show_pagination);
				echo '</div>';
				return ob_get_clean();
			elseif($style === 'slider'):
				ob_start();
				?>
				<?php if($q->have_posts()):?>
					<div class="recent-properties recent-properties-slider">
						<?php if(!empty($title)):?>
						<div class="recent-properties-title"><h3><?php echo $title?></h3></div>
						<?php endif;?>
						<?php 
						$i = 0;
						$visible = 4;
						$r=0;
						?>
						<div class="recent-properties-content">
							<div class="caroufredsel-wrap">
							<ul>
							<?php while ($q->have_posts()): $q->the_post();global $post;?>
								<?php if ($r++ % $visible == 0):?>
								<li>
								<?php endif;?>
								<?php if ($i++ % 2 == 0):?>
								<div class="property-row">
								<?php endif;?>
								<article <?php post_class(); ?>>
									<div class="property-featured">
								        <a class="content-thumb" href="<?php the_permalink() ?>">
											<?php echo get_the_post_thumbnail(get_the_ID(),'property-thumb') ?>
										</a>
										<span class="property-category"><?php echo get_the_term_list(get_the_ID(), 'property_category', '', ', ')?></span>
										<div class="property-detail">
											<div class="size"><span><?php echo self::get_area_html(get_the_ID());?></span></div>
											<div class="bathrooms"><span><?php echo noo_get_post_meta(get_the_ID(),'_bathrooms')?></span></div>
											<div class="bedrooms"><span><?php echo noo_get_post_meta(get_the_ID(),'_bedrooms')?></span></div>
										</div>
								    </div>
								    <div class="property-wrap">
										<h2 class="property-title">
											<a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php the_title(); ?></a>
										</h2>
										<div class="property-excerpt">
											<?php if($excerpt = $post->post_content):?>
												<?php 
												$num_word = 15;
												$excerpt = strip_shortcodes($excerpt);
												echo '<p>' . wp_trim_words($excerpt,$num_word,'...') . '</p>';
												?>
											<?php endif;?>
										</div>
									</div>
									<div class="property-summary">
										<div class="property-info">
											<div class="property-price">
												<span><?php echo self::get_price_html(get_the_ID(),true)?></span>
											</div>
											<div class="property-action">
												<a href="<?php the_permalink()?>"><?php echo __('More Details',NOO_TEXT_DOMAIN)?></a>
											</div>
										</div>
									</div>
								</article>
								<?php if ($i % 2 == 0 || $i == $q->post_count):?>
								</div>
								<?php endif;?>
								<?php if ($r % $visible == 0 || $r == $q->post_count):?>
								</li>
								<?php endif;?>
							<?php endwhile;?>
							</ul>
							</div>
							<a class="caroufredsel-prev" href="#"></a>
					    	<a class="caroufredsel-next" href="#"></a>
						</div>
					</div>
				<?php endif;?>
				<?php
				wp_reset_query();
				return ob_get_clean();
			elseif ($style==='featured'): 
					ob_start();
					?>
					<?php if($q->have_posts()):?>
						<div class="recent-properties recent-properties-featured">
						<?php if(!empty($title)):?>
						<div class="recent-properties-title"><h3><?php echo $title?></h3></div>
						<?php endif;?>
						<div class="recent-properties-content">
							<div class="caroufredsel-wrap">
							<ul>
							<?php while ($q->have_posts()): $q->the_post();global $post;?>
								<li>
								<article <?php post_class(); ?>>
									<div class="property-featured">
								        <a class="content-thumb" href="<?php the_permalink() ?>">
											<?php echo get_the_post_thumbnail(get_the_ID(),'property-image') ?>
										</a>
										<span class="property-category"><?php echo get_the_term_list(get_the_ID(), 'property_category', '', ', ')?></span>
								    </div>
								    <div class="property-wrap">
										<h2 class="property-title">
											<a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php the_title(); ?></a>
										</h2>
										<div class="property-excerpt">
											<?php if($excerpt = $post->post_content):?>
												<?php 
												$num_word = 30;
												$excerpt = strip_shortcodes($excerpt);
												echo '<p>' . wp_trim_words($excerpt,$num_word,'...') . '</p>';
												?>
											<?php endif;?>
										</div>
										<div class="property-summary">
											<div class="property-detail">
												<div class="size"><span><?php echo self::get_area_html(get_the_ID());?></span></div>
												<div class="bathrooms"><span><?php echo noo_get_post_meta(get_the_ID(),'_bathrooms')?></span></div>
												<div class="bedrooms"><span><?php echo noo_get_post_meta(get_the_ID(),'_bedrooms')?></span></div>
											</div>
											<div class="property-info">
												<div class="property-price">
													<span><?php echo self::get_price_html(get_the_ID(),true)?></span>
												</div>
												<div class="property-action">
													<a href="<?php the_permalink()?>"><?php echo __('More Details',NOO_TEXT_DOMAIN)?> <i class="fa fa-arrow-circle-o-right"></i></a>
												</div>
											</div>
										</div>
									</div>
								</article>
								</li>
							<?php endwhile;?>
							</ul>
							</div>
							<a class="caroufredsel-prev" href="#"></a>
					    	<a class="caroufredsel-next" href="#"></a>
						</div>
					</div>
				<?php endif;?>
				<?php
				wp_reset_query();
				return ob_get_clean();
			endif;
		}
		
		public function single_property_shortcode($atts, $content = null){
			$atts = wp_parse_args( $atts, array(
				'title'             => '',
				'type'				=> 'single',
				'property_id'		=> '',
				'style'				=> 'featured',
				'visibility'        => '',
				'class'             => '',
				'custom_style'      => ''
			) );

			return $this->recent_properties_shortcode( $atts, $content );
		}
		
		public static function get_similar_property(){
			global $post;
			
			$status = get_the_terms($post->ID, 'property_status');
			$categories =get_the_terms( $post->ID, 'property_category' );
			$num = noo_get_option( 'noo_property_similar_num', 2 );
			
			$args = array(
					'posts_per_page' => absint( $num ),
					'post__not_in' => array($post->ID),
					'orderby' => 'rand',
					'post_type'=>'noo_property',
					'tax_query' => array(
						'relation' => 'AND',
						array(
							'taxonomy' 	=> 'property_category',
							'terms' 	=> wp_get_object_terms($post->ID, 'property_category', array('fields' => 'ids')),
							'field' 	=> 'id'
						),
						array(
							'taxonomy' 	=> 'property_status',
							'terms' 	=> wp_get_object_terms($post->ID, 'property_status', array('fields' => 'ids')),
							'field' 	=> 'id'
						),
					)
			);
			
			$similar = new WP_Query($args);
			if($similar->have_posts()):
			?>
			<div class="similar-property">
				<div class="similar-property-title">
					<h3><?php echo __('Similar Properties',NOO_TEXT_DOMAIN)?></h3>
				</div>
				<div class="similar-property-content">
					<div class="properties grid">
					<?php while ($similar->have_posts()): $similar->the_post(); global $post;?>
						<article id="property-<?php the_ID(); ?>" <?php post_class(); ?>>
							<div class="property-featured">
						        <a class="content-thumb" href="<?php the_permalink() ?>">
									<?php echo get_the_post_thumbnail(get_the_ID(),'property-thumb') ?>
								</a>
						    </div>
						    <div class="property-wrap">
								<h2 class="property-title">
									<a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php the_title(); ?></a>
								</h2>
								<div class="property-summary">
									<div class="property-detail">
										<div class="size"><span><?php echo self::get_area_html(get_the_ID());?></span></div>
										<div class="bathrooms"><span><?php echo noo_get_post_meta(get_the_ID(),'_bathrooms')?></span></div>
										<div class="bedrooms"><span><?php echo noo_get_post_meta(get_the_ID(),'_bedrooms')?></span></div>
									</div>
									<div class="property-info">
										<div class="property-price">
											<span><?php echo self::get_price_html(get_the_ID())?></span>
										</div>
										<div class="property-action">
											<a href="<?php the_permalink()?>"><?php echo __('More Details',NOO_TEXT_DOMAIN)?></a>
										</div>
									</div>
								</div>
							</div>
						</article>
					<?php endwhile;?>
					</div>
				</div>
			</div>
			<?php
			endif;
			wp_reset_query();
			wp_reset_postdata();
		}
		
		public static function get_price_html($post_id,$label = true){
			$price			= trim( noo_get_post_meta($post_id,'_price') );
			$price			= (preg_match ("/^([0-9]+)$/", $price)) ? self::format_price($price) : esc_html( $price );
			$price_label    = esc_html(noo_get_post_meta($post_id,'_price_label'));
			if($label)
				return $price.' '.$price_label;
			else 
				return $price;
		}
		
		public static function get_area_html($post_id){
			$area = noo_get_post_meta($post_id,'_area');
			$area_unit = self::get_general_option('area_unit');
			return $area.' '.$area_unit;
		}
		
		/**
		 * Format the price with a currency symbol.
		 * @param float $price
		 * @return string
		 */
		public static function format_price($price,$html = true){
			$return          = '';
			$currency_code   = self::get_general_option('currency');
			$currency_symbol = self::get_currency_symbol($currency_code);
			$currency_position = self::get_general_option('currency_position');
			switch ( $currency_position ) {
				case 'left' :
					$format = '%1$s%2$s';
					break;
				case 'right' :
					$format = '%2$s%1$s';
					break;
				case 'left_space' :
					$format = '%1$s&nbsp;%2$s';
					break;
				case 'right_space' :
					$format = '%2$s&nbsp;%1$s';
					break;
				default:
					$format = '%1$s%2$s';
			}
			
			$thousands_sep = wp_specialchars_decode( stripslashes(self::get_general_option('price_thousand_sep')),ENT_QUOTES);
			$decimal_sep = wp_specialchars_decode( stripslashes(self::get_general_option('price_decimal_sep')),ENT_QUOTES);
			$num_decimals = self::get_general_option('price_num_decimals');
			
			$price  = floatval( $price );
			
			if(!$html) {
				return self::number_format( $price, $num_decimals, '.', '', $currency_code );
			}
			
			$price 	= self::number_format( $price, $num_decimals, $decimal_sep, $thousands_sep, $currency_code );
			if('text' === $html) {
				return sprintf( $format, $currency_symbol, $price );
			}

			//$price = preg_replace( '/' . preg_quote( self::get_general_option('price_decimal_sep'), '/' ) . '0++$/', '', $price );
			$return = '<span class="amount">' . sprintf( $format, $currency_symbol, $price ) . '</span>';
			
			return $return;
		}

		//
		private static function inr_comma($input, $thousands_sep = ',') {
		    // This function is written by some anonymous person – I got it from Google
			if(strlen($input)<=2)
				{ return $input; }
			$length=substr($input,0,strlen($input)-2);
			$formatted_input = self::inr_comma($length, $thousands_sep).$thousands_sep.substr($input,-2);
			return $formatted_input;
		}

		// Create custom function because some currency need special treat
		private static function number_format($num, $num_decimals = 2, $decimal_sep = '.', $thousands_sep = ',', $currency_code = '' ) {
			if( empty( $currency_code ) || $currency_code != 'INR' ) {
				return number_format( $num, $num_decimals, $decimal_sep, $thousands_sep );
			}

		    // Special format for Indian Rupee
			$pos = strpos((string)$num, '.');
			if ($pos === false) {
				$decimalpart = str_repeat("0", $num_decimals);
			}
			else {
				$decimalpart = substr($num, $pos+1, $num_decimals);
				$num = substr($num, 0, $pos);
			}

			$decimalpart = !empty($decimalpart) ? $decimal_sep . $decimalpart : '';

			if(strlen($num) > 3 & strlen($num) <= 12) {
				$last3digits = substr($num, -3 );
				$numexceptlastdigits = substr($num, 0, -3 );
				$formatted = self::inr_comma($numexceptlastdigits, $thousands_sep);
				$stringtoreturn = $formatted.$thousands_sep.$last3digits.$decimalpart ;
			} elseif(strlen($num)<=3) {
				$stringtoreturn = $num.$decimalpart ;
			} elseif(strlen($num)>12) {
				$stringtoreturn = number_format( $num, $num_decimals, $decimal_sep, $thousands_sep );
			}

			if(substr($stringtoreturn,0,2) == ( '-' . $decimal_sep ) ) {
				$stringtoreturn = '-'.substr( $stringtoreturn, 2 );
			}

			return $stringtoreturn;
		}
		
		
		public static function get_properties_markers($args = array()){
			$defaults = array(
					'post_type'     =>  'noo_property',
					'post_status'   =>  'publish',
					'nopaging'      =>  'true'
			);
			$markers = array();
			$args = wp_parse_args($args,$defaults);
			$properties = new WP_Query($args);
			if($properties->have_posts()){
				while ($properties->have_posts()): $properties->the_post();
					$post_id =  get_the_ID();
					$lat     =  esc_html(get_post_meta($post_id, '_noo_property_gmap_latitude', true));
					$long    =  esc_html(get_post_meta($post_id, '_noo_property_gmap_longitude', true));
					$title   =  wp_trim_words(get_the_title($post_id),7);
					$image   = get_template_directory_uri().'/assets/images/no-image.png';
					if(has_post_thumbnail($post_id))
						$image   =  get_the_post_thumbnail($post_id,'property-infobox');
					
					//$area    		= noo_get_post_meta(get_the_ID(),'_area');
					$bedrooms	 	= noo_get_post_meta(get_the_ID(),'_bedrooms');
					$bathrooms		= noo_get_post_meta(get_the_ID(),'_bathrooms');
					$price			= noo_get_post_meta($post_id,'_price');
					
					$property_location = '';
					$property_sub_location = '';
					$property_status = '';
					$property_category = '';
					$property_location_terms   		=   get_the_terms($post_id,'property_location' );
					if($property_location_terms && !is_wp_error($property_location_terms)){
						foreach($property_location_terms as $location_term){
							if(empty($location_term->slug))
								continue;
							$property_location = $location_term->slug;
							break;
						}
					}
					$property_sub_location_terms   	=   get_the_terms($post_id,'property_sub_location' );
					if($property_sub_location_terms && !is_wp_error($property_sub_location_terms)){
						foreach($property_sub_location_terms as $sub_location_term){
							if(empty($sub_location_term->slug))
								continue;
							$property_sub_location = $sub_location_term->slug;
							break;
						}
					}
					
					$property_status_terms   		=   get_the_terms($post_id,'property_status' );
					if($property_status_terms && !is_wp_error($property_status_terms)){
						foreach($property_status_terms as $status_term){
							if(empty($status_term->slug))
								continue;
							$property_status = $status_term->slug;
							break;
						}
					}
					$property_category_terms          =   get_the_terms($post_id,'property_category' );
					$property_category_marker = '';
					if($property_category_terms && !is_wp_error($property_category_terms)){
						$map_markers = get_option( 'noo_category_map_markers' );
						foreach($property_category_terms as $category_term){
							if(empty($category_term->slug))
								continue;
							$property_category = $category_term->slug;
							if(isset($map_markers[$category_term->term_id]) && !empty($map_markers[$category_term->term_id])){
								$property_category_marker = wp_get_attachment_url($map_markers[$category_term->term_id]);
							}
							break;
						}
					}
					
					$marker = array(
						'latitude'=>$lat,
						'longitude'=>$long,
						'image'=>$image,
						'title'=>$title,
						'area'=>self::get_area_html($post_id),
						'bedrooms'=>absint($bedrooms),
						'bathrooms'=>absint($bathrooms),
						'price'=>self::format_price($price,false),
						'price_html'=>self::get_price_html($post_id),
						'url'=> get_permalink($post_id), 
						'location'=>$property_location,
						'sub_location'=>$property_sub_location,
						'status'=>$property_status,
						'category'=>$property_category,
						'icon'=>$property_category_marker,
					);
					$markers[] = $marker;
				endwhile;
			}
			wp_reset_query();
			wp_reset_postdata();
			return json_encode($markers);
		}
		
		public static function advanced_map_search_field($field='',$show_status=false){
			if(empty($field) )
				return '';
			
			global $wpdb;
			switch ($field){
				case 'property_location':
					
					$g_location = isset( $_GET['location'] ) ? esc_attr( $_GET['location'] ) : '';
					$g_location = ( empty($g_location) && is_tax('property_location') ) ? get_query_var( 'term' ) : $g_location;
					if( empty( $g_location ) && is_tax('property_sub_location') ) {
						$sub_location = get_query_var( 'term' );
						$sub_location_term = get_term_by('slug',$sub_location,'property_sub_location');
						$parent_data = get_option( 'noo_sub_location_parent' );
						if( isset( $parent_data[$sub_location_term->term_id] ) ) {
							$parent_location = get_term_by('id',$parent_data[$sub_location_term->term_id],'property_location');
							$g_location = $parent_location->slug;
						}
					}
					?>
					<div class="form-group glocation">
			   			<div class="dropdown">
	   						<?php 
	   						if($show_status && !empty($g_location) && ($g_location_term = get_term_by('slug',$g_location,'property_location'))):
	   						?>
	   						<span class="glocation-label" data-toggle="dropdown"><?php echo esc_html($g_location_term->name)?></span>
	   						<?php
	   						else:
	   						?>
	   						<span class="glocation-label" data-toggle="dropdown"><?php _e('All Locations',NOO_TEXT_DOMAIN)?></span>
	   						<?php
	   						endif;
	   						?>
	   						<?php 
	   						noo_dropdown_search(array(
		   						'taxonomy'=>'property_location',
		   						'show_option_none'=>__('All Locations',NOO_TEXT_DOMAIN),
		   					));
	   						?>
			   				<input type="hidden" class="glocation_input" name="location" value="<?php echo $g_location?>">
			   			</div>
			   		</div>
					<?php 
					return ;
				break;
				case 'property_sub_location':
					$g_sub_location = isset( $_GET['sub_location'] ) ? esc_attr( $_GET['sub_location'] ) : '';
					$g_sub_location = ( empty($g_sub_location) && is_tax('property_sub_location') ) ? get_query_var( 'term' ) : $g_sub_location;
					?>
					<div class="form-group gsub-location">
			   			<div class="dropdown">
			   				<?php 
	   						if($show_status && !empty($g_sub_location) && ($g_sub_location_term = get_term_by('slug',$g_sub_location,'property_sub_location'))):
	   						?>
	   						<span class="glocation-label" data-toggle="dropdown"><?php echo esc_html($g_sub_location_term->name)?></span>
	   						<?php
	   						else:
	   						?>
	   						<span class="glocation-label" data-toggle="dropdown"><?php _e('All Sub-locations',NOO_TEXT_DOMAIN)?></span>
	   						<?php
	   						endif;
	   						?>
	   						<?php 
	   						noo_dropdown_search(array(
		   						'taxonomy'=>'property_sub_location',
		   						'show_option_none'=>__('All Sub-locations',NOO_TEXT_DOMAIN),
		   					));
	   						?>
			   				<input type="hidden" class="gsub_location_input" name="sub_location" value="<?php echo $g_sub_location?>">
			   			</div>
			   		</div>
					<?php
					return;
				break;
				case 'property_status':
					$g_status = isset( $_GET['status'] ) ? esc_attr( $_GET['status'] ) : '';
					$g_status = ( empty($g_status) && is_tax('property_status') ) ? get_query_var( 'term' ) : $g_status;
					?>
					<div class="form-group gstatus">
			   			<div class="dropdown">
			   				<?php 
	   						if($show_status && !empty($g_status) && ($g_status_term = get_term_by('slug',$g_status,'property_status'))):
	   						?>
	   						<span class="gstatus-label" data-toggle="dropdown"><?php echo esc_html($g_status_term->name)?></span>
	   						<?php
	   						else:
	   						?>
	   						<span class="gstatus-label" data-toggle="dropdown"><?php _e('All Status',NOO_TEXT_DOMAIN)?></span>
	   						<?php
	   						endif;
	   						?>
	   						<?php 
	   						noo_dropdown_search(array(
		   						'taxonomy'=>'property_status',
		   						'show_option_none'=>__('All Status',NOO_TEXT_DOMAIN),
		   					));
	   						?>
			   				<input type="hidden" class="gstatus_input" name="status" value="<?php echo $g_status?>">
			   			</div>
			   		</div>
					<?php
					return;
				break;
				case 'property_category':
					$g_category = isset( $_GET['category'] ) ? esc_attr( $_GET['category'] ) : '';
					$g_category = ( empty($g_category) && is_tax('property_category') ) ? get_query_var( 'term' ) : $g_category;
					?>
					<div class="form-group gtype">
			   			<div class="dropdown">
			   				<?php 
	   						if($show_status && !empty($g_category) && ($g_category_term = get_term_by('slug',$g_category,'property_category'))):
	   						?>
	   						<span class="gtype-label" data-toggle="dropdown"><?php echo esc_html($g_category_term->name)?></span>
	   						<?php
	   						else:
	   						?>
	   						<span class="gtype-label" data-toggle="dropdown"><?php _e('All Types',NOO_TEXT_DOMAIN)?></span>
	   						<?php
	   						endif;
	   						?>
	   						<?php 
	   						noo_dropdown_search(array(
		   						'taxonomy'=>'property_category',
		   						'show_option_none'=>__('All Types',NOO_TEXT_DOMAIN),
		   					));
	   						?>
			   				<input type="hidden" class="gcategory_input" name="category" value="<?php echo $g_category?>">
			   			</div>
			   		</div>
					<?php
					return;
				break;
				case '_bedrooms':
					$g_bedroom = isset( $_GET['bedroom'] ) ? esc_attr( $_GET['bedroom'] ) : '';
					$min_bedroom = $max_bedroom = 0;
					$min_bedroom = ceil( $wpdb->get_var(
							$wpdb->prepare('
						SELECT min(meta_value + 0)
						FROM %1$s
						LEFT JOIN %2$s ON %1$s.ID = %2$s.post_id
						WHERE meta_key = \'%3$s\' AND post_type = \'%4$s\' AND post_status = \'%5$s\'
						', $wpdb->posts, $wpdb->postmeta, '_bedrooms', 'noo_property', 'publish')
					) );
					$max_bedroom = ceil( $wpdb->get_var(
							$wpdb->prepare('
						SELECT max(meta_value + 0)
						FROM %1$s
						LEFT JOIN %2$s ON %1$s.ID = %2$s.post_id
						WHERE meta_key = \'%3$s\' AND post_type = \'%4$s\' AND post_status = \'%5$s\'
						', $wpdb->posts, $wpdb->postmeta, '_bedrooms', 'noo_property', 'publish')
					) );
							
					?>
					<div class="form-group gbed">
			   			<div class="dropdown">
			   				<?php 
	   						if($show_status && !empty($g_bedroom)):
	   						?>
	   						<span class="gbed-label" data-toggle="dropdown"><?php echo $g_bedroom?></span>
	   						<?php
	   						else:
	   						?>
	   						<span class="gbed-label" data-toggle="dropdown"><?php _e('No. of Bedrooms',NOO_TEXT_DOMAIN)?></span>
	   						<?php
	   						endif;
			   				?>
			   				<ul class="dropdown-menu">
			   					<li>
			   						<a href="#" data-value="" ><?php _e('No. of Bedrooms',NOO_TEXT_DOMAIN)?></a>
			   					</li>
			   					<?php foreach (range(absint($min_bedroom),absint($max_bedroom)) as $step):?>
			   					<li>
			   						<a href="#" data-value="<?php echo $step?>" ><?php echo $step ?></a>
			   					</li>
			   					<?php endforeach;?>
			   				</ul>
			   				<input type="hidden" class="gbedroom_input" name="bedroom" value="<?php echo $g_bedroom?>">
			   			</div>
			   		</div>
					<?php
					return;
				break;
				case '_bathrooms':
					$g_bathroom = isset( $_GET['bathroom'] ) ? esc_attr( $_GET['bathroom'] ) : '';
					$min_bathroom = $max_bathroom = 0;
					$min_bathroom = ceil( $wpdb->get_var(
						$wpdb->prepare('
						SELECT min(meta_value + 0)
						FROM %1$s
						LEFT JOIN %2$s ON %1$s.ID = %2$s.post_id
						WHERE meta_key = \'%3$s\' AND post_type = \'%4$s\' AND post_status = \'%5$s\'
					', $wpdb->posts, $wpdb->postmeta, '_bathrooms', 'noo_property', 'publish')
					) );
					$max_bathroom = ceil( $wpdb->get_var(
						$wpdb->prepare('
								SELECT max(meta_value + 0)
								FROM %1$s
								LEFT JOIN %2$s ON %1$s.ID = %2$s.post_id
								WHERE meta_key = \'%3$s\' AND post_type = \'%4$s\' AND post_status = \'%5$s\'
							', $wpdb->posts, $wpdb->postmeta, '_bathrooms', 'noo_property', 'publish')
					) );
					?>
					<div class="form-group gbath">
			   			<div class="dropdown">
			   				<?php 
	   						if($show_status && !empty($g_bathroom)):
	   						?>
	   						<span class="gbath-label" data-toggle="dropdown"><?php echo $g_bathroom?></span>
	   						<?php
	   						else:
	   						?>
	   						<span class="gbath-label" data-toggle="dropdown"><?php _e('No. of Bathrooms',NOO_TEXT_DOMAIN)?></span>
			   				<?php
	   						endif;
			   				?>
			   				<ul class="dropdown-menu">
			   					<li>
			   						<a href="#" data-value=""><?php _e('No. of Bathrooms',NOO_TEXT_DOMAIN)?></a>
			   					</li>
			   					<?php foreach (range(absint($min_bathroom),absint($max_bathroom)) as $step):?>
			   					<li>
			   						<a href="#" data-value="<?php echo $step?>"><?php echo $step ?></a>
			   					</li>
			   					<?php endforeach;?>
			   				</ul>
			   				<input type="hidden" class="gbathroom_input" name="bathroom" value="<?php echo $g_bathroom?>">
			   			</div>
			   		</div>
					<?php
					return;
				break;
				case '_price':
					$min_price = $max_price = 0;
					$min_price = ceil( $wpdb->get_var(
						$wpdb->prepare('
						SELECT min(meta_value + 0)
						FROM %1$s
						LEFT JOIN %2$s ON %1$s.ID = %2$s.post_id
						WHERE meta_key = \'%3$s\' AND post_type = \'%4$s\' AND post_status = \'%5$s\'
						', $wpdb->posts, $wpdb->postmeta, '_price', 'noo_property', 'publish')
					) );
					$max_price = ceil( $wpdb->get_var(
							$wpdb->prepare('
						SELECT max(meta_value + 0)
						FROM %1$s
						LEFT JOIN %2$s ON %1$s.ID = %2$s.post_id
						WHERE meta_key = \'%3$s\' AND post_type = \'%4$s\' AND post_status = \'%5$s\'
						', $wpdb->posts, $wpdb->postmeta, '_price', 'noo_property', 'publish')
					) );
					$g_min_price = isset( $_GET['min_price'] ) ? esc_attr( $_GET['min_price'] ) : $min_price;
					$g_max_price = isset( $_GET['max_price'] ) ? esc_attr( $_GET['max_price'] ) : $max_price;
							
					?>
					<div class="form-group gprice">
			   			<span class="gprice-label"><?php _e('Price',NOO_TEXT_DOMAIN)?></span>
			   			<div class="gprice-slider-range"></div>
			   			<input type="hidden" name="min_price" class="gprice_min" data-min="<?php echo $min_price ?>" value="<?php echo $g_min_price ?>">
			   			<input type="hidden" name="max_price" class="gprice_max" data-max="<?php echo $max_price ?>" value="<?php echo $g_max_price ?>">
			   		</div>
					<?php
					return;
				break;
				case '_area':
					$min_area = $max_area = 0;
					$min_area = ceil( $wpdb->get_var(
					$wpdb->prepare('
						SELECT min(meta_value + 0)
						FROM %1$s
						LEFT JOIN %2$s ON %1$s.ID = %2$s.post_id AND post_status = \'%5$s\'
						WHERE meta_key = \'%3$s\' AND post_type = \'%4$s\'
					', $wpdb->posts, $wpdb->postmeta, '_area', 'noo_property', 'publish')
						) );
					$max_area = ceil( $wpdb->get_var(
							$wpdb->prepare('
						SELECT max(meta_value + 0)
						FROM %1$s
						LEFT JOIN %2$s ON %1$s.ID = %2$s.post_id AND post_status = \'%5$s\'
						WHERE meta_key = \'%3$s\' AND post_type = \'%4$s\'
					', $wpdb->posts, $wpdb->postmeta, '_area', 'noo_property', 'publish')
						) );
							
					$g_min_area = isset( $_GET['min_area'] ) ? esc_attr( $_GET['min_area'] ) : $min_area;
					$g_max_area = isset( $_GET['max_area'] ) ? esc_attr( $_GET['max_area'] ) : $max_area;
						
							
					?>
						<div class="form-group garea">
				   			<span class="garea-label"><?php _e('Area',NOO_TEXT_DOMAIN)?></span>
				   			<div class="garea-slider-range"></div>
				   			<input type="hidden" class="garea_min" name="min_area" data-min="<?php echo $min_area ?>" value="<?php echo $g_min_area ?>">
				   			<input type="hidden" class="garea_max" name="max_area" data-max="<?php echo $max_area ?>" value="<?php echo $g_max_area ?>">
				   		</div>
					<?php
					return;
				break;
				default:
				break;
			}
			if(strstr( $field, '_noo_property_field_' )){
				$field_arr = explode('|', $field);
				$field_id = @$field_arr[0];
				$get_field = isset($_GET[$field_id]) ? $_GET[$field_id] : '';
				$field_label = @$field_arr[1];
				$field_values = $wpdb->get_results(
					$wpdb->prepare('
						SELECT meta_value
						FROM %1$s
						LEFT JOIN %2$s ON %1$s.post_id = %2$s.ID
						WHERE meta_key = \'%3$s\' AND post_type = \'%4$s\' AND post_status = \'%5$s\'
						', $wpdb->postmeta, $wpdb->posts, $field_id, 'noo_property', 'publish'),OBJECT_K);
				if ( $field_values ) {
					?>
					<div class="form-group <?php echo $field_id?>">
			   			<div class="dropdown">
			   				<?php 
	   						if($show_status && !empty($get_field)):
	   						?>
	   							<span data-toggle="dropdown" class="<?php echo $field_id?>-label"><?php echo $get_field ?></span>
	   						<?php
	   						else:
	   						?>
	   							<span data-toggle="dropdown" class="<?php echo $field_id?>-label"><?php echo __('All',NOO_TEXT_DOMAIN).' '.$field_label ?></span>
	   						<?php
	   						endif;
	   						?>
			   				
			   				<ul class="dropdown-menu">
			   					<li>
			   						<a data-value="" href="#"><?php echo __('All',NOO_TEXT_DOMAIN).' '.$field_label ?></a>
			   					</li>
			   					<?php foreach ($field_values as $key=>$field_value):?>
			   					<?php 
			   					if(empty($key))
			   						continue;
			   					?>
			   					<li>
			   						<a data-value="<?php echo esc_attr($key)?>" href="#"><?php echo esc_html($key)?></a>
			   					</li>
			   					<?php endforeach;?>
			   				</ul>
			   				<input type="hidden" value="<?php echo $get_field ?>" name="<?php echo $field_id?>" class="<?php echo $field_id ?>_input">
			   			</div>
			   		</div>
					<?php
				}
				return;
			}
			return;
		}
		
		public static function advanced_map($gmap=true, $btn_label='', $show_status=false, $map_class='', $search_info=false, $no_search_container=false,$source='property',$disable_search_form = false,$show_advanced_search_field=false, $map_height='',$search_info_title=null,$search_info_content=null){
			global $wpdb;
			$result_pages = get_pages(
				array(
						'meta_key' => '_wp_page_template',
						'meta_value' => 'search-property-result.php'
				)
			);
			if($result_pages){
				$result_page_url = get_permalink($result_pages[0]->ID);
				if(is_page($result_pages[0]->ID)){
					$show_status = true;
				}
			}else{
				$result_page_url = '';
			}
			
			if(empty($btn_label))
				$btn_label=__('Search Property',NOO_TEXT_DOMAIN);

			$map_class = !$gmap ? 'no-map ' . $map_class : $map_class;
			$map_class = $no_search_container ? 'no-container ' . $map_class : $map_class;
			$map_height = empty( $map_height ) ? self::get_google_map_option('height', 700) : $map_height;
		?>
		<div class="noo-map <?php echo esc_attr($map_class)?>">
			<?php if($gmap):?>
				<div id="gmap" data-source="<?php echo $source?>" style="height: <?php echo $map_height; ?>px;" ></div>
				<div class="gmap-search">
					<input placeholder="<?php echo __('Search your map',NOO_TEXT_DOMAIN)?>" type="text" autocomplete="off" id="gmap_search_input">
				</div>
				<div class="gmap-control">
					<a class="gmap-mylocation" href="#"><i class="fa fa-map-marker"></i><?php echo __('My Location',NOO_TEXT_DOMAIN)?></a>
					<a class="gmap-full" href="#"><i class="fa fa-expand"></i></a>
					<a class="gmap-prev" href="#"><i class="fa fa-angle-left"></i></a>
					<a class="gmap-next" href="#"><i class="fa fa-angle-right"></i></a>
				</div>
				<div class="gmap-zoom">
					<a href="#" class="zoom-in"><i class="fa fa-plus"></i></a>
					<a href="#" class="zoom-out"><i class="fa fa-minus"></i></a>
				</div>
				<div class="gmap-loading"><?php _e('Loading Maps',NOO_TEXT_DOMAIN);?>
			         <div class="gmap-loader">
			            <div class="rect1"></div>
			            <div class="rect2"></div>
			            <div class="rect3"></div>
			            <div class="rect4"></div>
			            <div class="rect5"></div>
			        </div>
			   </div>
			<?php endif;?>
			<?php if(!$disable_search_form):?>
			<div class="gsearch">
				<?php if( !$no_search_container ) : ?>
				<div class="container-boxed">
				<?php endif; ?>
		   			<?php if($search_info):
			   			$search_info_title = is_null($search_info_title) ? __('Find Your Place',NOO_TEXT_DOMAIN) : $search_info_title;
			   			$search_info_content = is_null($search_info_content) ? __('Instantly find your desired place with your expected location, price and other criteria just by starting your search now',NOO_TEXT_DOMAIN) : $search_info_content;
			   			if( !empty( $search_info_title ) || !empty( $search_info_content ) ) :
		   			?>
		   			<div class="gsearch-info">
		   				<?php if( !empty( $search_info_title ) ) : ?>
						<h4 class="gsearch-info-title"><?php echo $search_info_title?></h4>
						<?php endif; ?>
		   				<?php if( !empty( $search_info_content ) ) : ?>
						<div class="gsearch-info-content"><?php echo $search_info_content?></div>
						<?php endif; ?>
					</div>
						<?php endif;?>
					<?php endif;?>
				   	<div class="gsearch-wrap">
				   		<?php if($gmap):?>
					   	<h3 class="gsearch-title"><i class="fa fa-search"></i><span><?php echo __('SEARCH FOR PROPERTY',NOO_TEXT_DOMAIN)?></span></h3>
					   	<?php endif;?>
					   	<form action="<?php echo $result_page_url ?>" class="gsearchform" method="get" role="search">
					   		<?php if( get_option('permalink_structure') == '' && !empty($result_page_url) ) : ?>
					   		<input type="hidden" name="page_id" value="<?php echo $result_pages[0]->ID; ?>" >
					   		<?php endif; ?>
					   		<div class="gsearch-content">
					   			<div class="gsearch-field">
							   		<?php 
							   		// count number of search fields
							   		$field_count = 0;
							   		if( self::get_advanced_search_option('pos1','property_location') ) {
							   			self::advanced_map_search_field(self::get_advanced_search_option('pos1','property_location'),$show_status);
							   			$field_count++;
							   		}
							   		
							   		if( self::get_advanced_search_option('pos2','property_sub_location') ) {
							   			self::advanced_map_search_field(self::get_advanced_search_option('pos2','property_sub_location'),$show_status);
							   			$field_count++;
							   		}
							   		if( self::get_advanced_search_option('pos3','property_status') ) {
							   			self::advanced_map_search_field(self::get_advanced_search_option('pos3','property_status'),$show_status);
							   			$field_count++;
							   		}
							   		if( self::get_advanced_search_option('pos4','property_category') ) {
							   			self::advanced_map_search_field(self::get_advanced_search_option('pos4','property_category'),$show_status);
							   			$field_count++;
							   		}
							   		if( self::get_advanced_search_option('pos5','_bedrooms') ) {
							   			self::advanced_map_search_field(self::get_advanced_search_option('pos5','_bedrooms'),$show_status);
							   			$field_count++;
							   		}
							   		if( self::get_advanced_search_option('pos6','_bathrooms') ) {
							   			self::advanced_map_search_field(self::get_advanced_search_option('pos6','_bathrooms'),$show_status);
							   			$field_count++;
							   		}
							   		if( self::get_advanced_search_option('pos7','_price') ) {
							   			self::advanced_map_search_field(self::get_advanced_search_option('pos7','_price'),$show_status);
							   			$field_count++;
							   		}
							   		if( self::get_advanced_search_option('pos8','_area') ) {
							   			self::advanced_map_search_field(self::get_advanced_search_option('pos8','_area'),$show_status);
							   			$field_count++;
							   		}
							   		?>
							   		<?php 
							   		if($show_advanced_search_field){
								   		$advanced_search_field = self::get_advanced_search_option('advanced_search_field',array());
								   		if(!empty($advanced_search_field) && is_array($advanced_search_field) && ($features = self::get_feature_option('features'))){
								   			echo '<div class="gsearch-feature">';
								   			echo '<a href="#gsearch-feature" class="gsearch-feature-control" data-parent="#gsearch-feature" data-toggle="collapse">'.__('Advanced Search',NOO_TEXT_DOMAIN).'</a>';
								   			echo '<div id="gsearch-feature" class="panel-collapse collapse row">';
								   			foreach ($features as $feature){
								   				$feature_id = sanitize_title($feature);
								   				$feature = function_exists('icl_translate') ? icl_translate(NOO_TEXT_DOMAIN,'noo_property_features_'. $field_id, $feature ) : $feature;
								   				if(in_array($feature_id, $advanced_search_field)){
									   				$id = '_noo_property_feature_'.$feature_id;
									   				$cheked = isset($_GET[$id]) ? true : false;
									   				echo '<div class="col-sm-3">';
									   				echo '<label class="checkbox-label" for="'.$id.'"><input '.($cheked && $show_status ? ' checked="checked"':'').' type="checkbox" value="1" class="" name="'.$id.'" id="'.$id.'">&nbsp;'.ucfirst($feature).'</label>';
									   				echo '</div>';
								   				}
								   			}
								   			echo '</div>';
								   			echo '</div>';
								   		}
							   		}
							   		?>
							   	</div>
							   	<div class="gsearch-action">
							   		<div  class="gsubmit <?php if( $field_count <= 4 ) echo 'one-line'; ?>">
							   			<button type="submit"><?php echo $btn_label ?></button>
							   		</div>
							   	</div>
					   		</div>
					   	</form>
					</div>
				<?php if( !$no_search_container ) : ?>
				</div>
				<?php endif; ?>
		   </div>
		   <?php endif;?>
		</div>
		<?php
		}
		
		public function register_post_type(){
			if(post_type_exists('noo_property'))
				return ;
			
			$noo_icon = NOO_FRAMEWORK_ADMIN_URI . '/assets/images/noo20x20.png';
			if ( floatval( get_bloginfo( 'version' ) ) >= 3.8 ) {
				$noo_icon = 'dashicons-location';
			}

			register_post_type('noo_property',array(
				'labels' => array(
					'name'                  => __('Properties',NOO_TEXT_DOMAIN),
					'singular_name'         => __('Property',NOO_TEXT_DOMAIN),
					'add_new'               => __('Add New Property',NOO_TEXT_DOMAIN),
					'add_new_item'          => __('Add Property',NOO_TEXT_DOMAIN),
					'edit'                  => __('Edit',NOO_TEXT_DOMAIN),
					'edit_item'             => __('Edit Property',NOO_TEXT_DOMAIN),
					'new_item'              => __('New Property',NOO_TEXT_DOMAIN),
					'view'                  => __('View',NOO_TEXT_DOMAIN),
					'view_item'             => __('View Property',NOO_TEXT_DOMAIN),
					'search_items'          => __('Search Property',NOO_TEXT_DOMAIN),
					'not_found'             => __('No Properties found',NOO_TEXT_DOMAIN),
					'not_found_in_trash'    => __('No Properties found in Trash',NOO_TEXT_DOMAIN),
					'parent'                => __('Parent Property',NOO_TEXT_DOMAIN)
				),
				'public' => true,
				'has_archive' => self::get_general_option('archive_slug','properties'),
				'menu_icon'=>$noo_icon,
				'rewrite' => array('slug' => self::get_general_option('archive_slug','properties'),'with_front' => false),
				'supports' => array('title', 'editor', 'thumbnail', 'comments'),
				'can_export' => true,
				)
			);
			
			register_taxonomy ( 'property_category', 'noo_property', array (
					'labels' => array (
							'name' => __ ( 'Property Type', NOO_TEXT_DOMAIN ),
							'add_new_item' => __ ( 'Add New Property Type', NOO_TEXT_DOMAIN ),
							'new_item_name' => __ ( 'New Property Type', NOO_TEXT_DOMAIN ) 
					),
					'hierarchical' => true,
					'query_var' => true,
					'rewrite' => array ('slug' => 'listings' ) 
			) );
			
			
			register_taxonomy ( 'property_label', 'noo_property', array (
				'labels' => array (
					'name' => __ ( 'Property Label', NOO_TEXT_DOMAIN ),
					'add_new_item' => __ ( 'Add New Property Label', NOO_TEXT_DOMAIN ),
					'new_item_name' => __ ( 'New Property Label', NOO_TEXT_DOMAIN )
				),
				'show_ui'               => true,
				'query_var'             => true,
				'show_in_nav_menus'     => false,
				'meta_box_cb'			=>false,
			) );
			
			
			register_taxonomy ( 'property_location', 'noo_property', array (
					'labels' => array (
							'name' => __ ( 'Property Location', NOO_TEXT_DOMAIN ),
							'add_new_item' => __ ( 'Add New Property Location', NOO_TEXT_DOMAIN ),
							'new_item_name' => __ ( 'New Property Location', NOO_TEXT_DOMAIN ) 
					),
					'hierarchical' => true,
					'query_var' => true,
					'rewrite' => array ('slug' => 'property-location') 
			) );
			
			register_taxonomy ( 'property_sub_location', 'noo_property', array (
				'labels' => array (
					'name' => __ ( 'Property Sub-location', NOO_TEXT_DOMAIN ),
					'add_new_item' => __ ( 'Add New Property Sub-location', NOO_TEXT_DOMAIN ),
					'new_item_name' => __ ( 'New Property Sub-location', NOO_TEXT_DOMAIN )
				),
				'hierarchical' => true,
				'query_var' => true,
				'show_ui'               => true,
				'rewrite' => array ('slug' => 'property-sub-location')
			) );
				
			register_taxonomy ( 'property_status', 'noo_property', array (
				'labels' => array (
					'name' => __ ( 'Property Status', NOO_TEXT_DOMAIN ),
					'add_new_item' => __ ( 'Add New Property Status', NOO_TEXT_DOMAIN ),
					'new_item_name' => __ ( 'New Property Status', NOO_TEXT_DOMAIN )
				),
				'hierarchical' => true,
				'query_var' => true,
				'rewrite' => array ('slug' => 'status' )
			) );
			//delete_option('default_property_status');
			$default_property_status = get_option('default_property_status');
			if(empty($default_property_status)){
				$slug = sanitize_title(__('sold',NOO_TEXT_DOMAIN));
				$ret = wp_insert_term(esc_html(__('Sold',NOO_TEXT_DOMAIN)),'property_status',array('slug' => $slug));
				if ( $ret && !is_wp_error( $ret ) && ($term = get_term_by('slug', $slug, 'property_status')) ){
					$r  = update_option('default_property_status', $term->term_id);
				}
			}
				
			
		}

		public function property_columns( $columns ) {
			$new_columns = array();
			$new_columns['cb'] = $columns['cb'];
			$new_columns['property_id'] = __( 'ID', NOO_TEXT_DOMAIN );
			$new_columns['featured'] = __( 'Featured', NOO_TEXT_DOMAIN );
			unset( $columns['cb'] );
		
			return array_merge( $new_columns, $columns );
		}
		
		public function property_column( $column) {
			global $post;
			$featured = noo_get_post_meta($post->ID,'_featured');
		
			if ( $column == 'featured' ) {
				$url = wp_nonce_url( admin_url( 'admin-ajax.php?action=noo_property_feature&property_id=' . $post->ID ), 'noo-property-feature' );
				echo '<a href="' . esc_url( $url ) . '" title="'. __( 'Toggle featured', NOO_TEXT_DOMAIN ) . '">';
				if ( 'yes' === $featured ) {
					echo '<span class="noo-property-feature" title="'.esc_attr('Yes',NOO_TEXT_DOMAIN).'"><i class="dashicons dashicons-star-filled "></i></span>';
				} else {
					echo '<span class="noo-property-feature not-featured"  title="'.esc_attr('No',NOO_TEXT_DOMAIN).'"><i class="dashicons dashicons-star-empty"></i></span>';
				}
				echo '</a>';
			}elseif ($column == 'property_id'){
					echo $post->ID;
			}
			return $column;
		}
		
		public function feature_property(){
			if(isset($_GET['action']) && $_GET['action'] == 'noo_property_feature'){
				if ( ! current_user_can( 'edit_posts' ) ) {
					wp_die( __( 'You do not have sufficient permissions to access this page.', NOO_TEXT_DOMAIN ), '', array( 'response' => 403 ) );
				}
				
				if ( ! check_admin_referer( 'noo-property-feature' ) ) {
					wp_die( __( 'You have taken too long. Please go back and retry.', NOO_TEXT_DOMAIN ), '', array( 'response' => 403 ) );
				}
				
				$post_id = ! empty( $_GET['property_id'] ) ? (int) $_GET['property_id'] : '';
				
				if ( ! $post_id || get_post_type( $post_id ) !== 'noo_property' ) {
					die;
				}
				
				$featured = noo_get_post_meta( $post_id, '_featured', true );
				
				if ( 'yes' === $featured ) {
					update_post_meta( $post_id, '_featured', 'no' );
				} else {
					update_post_meta( $post_id, '_featured', 'yes' );
				}
				
				
				wp_safe_redirect( remove_query_arg( array( 'trashed', 'untrashed', 'deleted', 'ids' ), wp_get_referer() ) );
				die();
			}
		}
		
		public function sub_location_columns( $columns ) {
			$new_columns = array();
			$new_columns['cb'] = $columns['cb'];
			$new_columns['location_id'] = __( 'Location', NOO_TEXT_DOMAIN );
		
			unset( $columns['cb'] );
		
			return array_merge( $new_columns, $columns );
		}
		
		public function sub_location_column( $columns, $column, $id ) {
			if ( $column == 'location_id' ) {
				$sub_location_parent_options = get_option('noo_sub_location_parent');
				$selected = isset($sub_location_parent_options[$id]) ? $sub_location_parent_options[$id] : '';
				if($selected && $location = get_term($selected, 'property_location')){
					echo $location->name;
				}
			}
			return $columns;
		}
		
		public function add_location(){
			?>
		<script type="text/javascript">
		<!--
		jQuery(document).ready(function($){
		$('#parent').closest('.form-field').hide();
		});
		//-->
		</script>
		<?php
		}
		public function edit_location($term, $taxonomy){
		?>
		<script type="text/javascript">
		<!--
		jQuery(document).ready(function($){
		$('#parent').closest('.form-field').hide();
		});
		//-->
		</script>
		<?php
		}
		
		public function add_status(){
		?>
		<script type="text/javascript">
		<!--
		jQuery(document).ready(function($){
		$('#parent').closest('.form-field').hide();
		});
		//-->
		</script>
		<?php
		}
		public function edit_status($term, $taxonomy){
		?>
		<script type="text/javascript">
		<!--
		jQuery(document).ready(function($){
		$('#parent').closest('.form-field').hide();
		});
		//-->
		</script>
		<?php
		}
		
		public function add_sub_location(){
			$locations = get_terms('property_location', array( 'hide_empty' => false ));
			?>
			<div class="form-field">
				<label><?php _e('Location',NOO_TEXT_DOMAIN)?></label>
				<select name="noo_location_parent">
				<?php foreach ((array)$locations as $location):?>
				<option value="<?php echo $location->term_id ?>"><?php echo $location->name?></option>
				<?php endforeach;?>
				</select>
			</div>
			<?php
		}
		
		public function edit_sub_location($term, $taxonomy){
			$locations = get_terms('property_location');
			$sub_location_parent_options = get_option('noo_sub_location_parent');
			$selected = isset($sub_location_parent_options[$term->term_id]) ? $sub_location_parent_options[$term->term_id] : 0;
		?>
		<tr class="form-field">
			<th scope="row" valign="top"><label><?php _e('Location', NOO_TEXT_DOMAIN); ?></label></th>
			<td>
				<select name="noo_location_parent">
					<?php foreach ((array)$locations as $location):?>
					<option value="<?php echo $location->term_id ?>" <?php selected($selected,$location->term_id)?>><?php echo $location->name?></option>
					<?php endforeach;?>
				</select>
			</td>
		</tr>
		<?php
		}
		
		public function save_sub_location_callback($term_id, $tt_id, $taxonomy){
			if ( isset( $_POST['noo_location_parent'] ) ){
				$parents = get_option( 'noo_sub_location_parent' );
				if ( ! $parents )
					$parents = array();
				$parents[$term_id] = absint($_POST['noo_location_parent']);
				update_option('noo_sub_location_parent', $parents);
			}
		}
		
		public function add_property_label_color(){
			wp_enqueue_style( 'wp-color-picker');
			wp_enqueue_script( 'wp-color-picker');
			?>
			<div class="form-field">
				<label><?php _e( 'Color', NOO_TEXT_DOMAIN ); ?></label>
				<input id="noo_property_label_color" type="text" size="40" value="" name="noo_property_label_color">
				<script type="text/javascript">
					jQuery(document).ready(function($){
					    $("#noo_property_label_color").wpColorPicker();
					});
				 </script>
			</div>
			<?php
		}
		
		public function edit_property_label_color($term, $taxonomy){
			wp_enqueue_style( 'wp-color-picker');
			wp_enqueue_script( 'wp-color-picker');
			$noo_property_label_colors = get_option('noo_property_label_colors');
			$color 	= isset($noo_property_label_colors[$term->term_id]) ? $noo_property_label_colors[$term->term_id] : '';
			?>
			<tr class="form-field">
				<th scope="row" valign="top"><label><?php _e('Color', NOO_TEXT_DOMAIN); ?></label></th>
				<td>
					<input id="noo_property_label_color" type="text" size="40" value="<?php echo $color?>" name="noo_property_label_color">
					<script type="text/javascript">
						jQuery(document).ready(function($){
						    $("#noo_property_label_color").wpColorPicker();
						});
					 </script>
				</td>
			</tr>
			<?php
		}
		
		public function save_label_color($term_id, $tt_id, $taxonomy){
			if ( isset( $_POST['noo_property_label_color'] ) ){
				$noo_property_label_colors = get_option( 'noo_property_label_colors' );
				if ( ! $noo_property_label_colors )
					$noo_property_label_colors = array();
				$noo_property_label_colors[$term_id] = $_POST['noo_property_label_color'];
				update_option('noo_property_label_colors', $noo_property_label_colors);
			}
		}
		
		public function add_category_map_marker(){
			if(function_exists( 'wp_enqueue_media' )){
				wp_enqueue_media();
			}else{
				wp_enqueue_style('thickbox');
				wp_enqueue_script('media-upload');
				wp_enqueue_script('thickbox');
			}
			?>
			<div class="form-field">
				<label><?php _e( 'Map Marker Icon', NOO_TEXT_DOMAIN ); ?></label>
				<div id="category_map_marker_icon" style="float:left;margin-right:10px;">
					<img src="<?php echo NOO_FRAMEWORK_ADMIN_URI . '/assets/images/placeholder.png'; ?>" width="60px" height="60px" />
				</div>
				<div style="line-height:60px;">
					<input type="hidden" id="category_map_marker_icon_id" name="category_map_marker_icon_id" />
					<button type="button" class="upload_image_button button"><?php _e('Upload/Add image', NOO_TEXT_DOMAIN); ?></button>
					<button type="button" class="remove_image_button button"><?php _e('Remove image', NOO_TEXT_DOMAIN); ?></button>
				</div>
				<script type="text/javascript">
					
					 // Only show the "remove image" button when needed
					 if ( ! jQuery('#category_map_marker_icon_id').val() )
						 jQuery('.remove_image_button').hide();
			
					// Uploading files
					var file_frame;
			
					jQuery(document).on( 'click', '.upload_image_button', function( event ){
			
						event.preventDefault();
			
						// If the media frame already exists, reopen it.
						if ( file_frame ) {
							file_frame.open();
							return;
						}
			
						// Create the media frame.
						file_frame = wp.media.frames.downloadable_file = wp.media({
							title: '<?php _e( 'Choose an image', NOO_TEXT_DOMAIN ); ?>',
							button: {
								text: '<?php _e( 'Use image', NOO_TEXT_DOMAIN ); ?>',
							},
							multiple: false
						});
			
						// When an image is selected, run a callback.
						file_frame.on( 'select', function() {
							attachment = file_frame.state().get('selection').first().toJSON();
			
							jQuery('#category_map_marker_icon_id').val( attachment.id );
							jQuery('#category_map_marker_icon img').attr('src', attachment.url );
							jQuery('.remove_image_button').show();
						});
			
						// Finally, open the modal.
						file_frame.open();
					});
			
					jQuery(document).on( 'click', '.remove_image_button', function( event ){
						jQuery('#category_map_marker_icon img').attr('src', '<?php echo NOO_FRAMEWORK_ADMIN_URI . '/assets/images/placeholder.png'; ?>');
						jQuery('#category_map_marker_icon_id').val('');
						jQuery('.remove_image_button').hide();
						return false;
					});
			
				</script>
				<div class="clear"></div>
			</div>
			<?php
		}
		
		public function edit_category_map_marker($term, $taxonomy){
			if(function_exists( 'wp_enqueue_media' )){
				wp_enqueue_media();
			}else{
				wp_enqueue_style('thickbox');
				wp_enqueue_script('media-upload');
				wp_enqueue_script('thickbox');
			}
			$map_markers = get_option( 'noo_category_map_markers' );
			$image 			= '';
			$category_map_marker_icon_id 	= isset($map_markers[$term->term_id]) ? $map_markers[$term->term_id] : '';
			if ($category_map_marker_icon_id) :
				$image = wp_get_attachment_url( $category_map_marker_icon_id );
			else :
				$image = NOO_FRAMEWORK_ADMIN_URI . '/assets/images/placeholder.png';
			endif;
		?>
		<tr class="form-field">
			<th scope="row" valign="top"><label><?php _e('Map Marker Icon', NOO_TEXT_DOMAIN); ?></label></th>
			<td>
				<div id="category_map_marker_icon" style="float:left;margin-right:10px;"><img src="<?php echo $image; ?>" width="60px" height="60px" /></div>
				<div style="line-height:60px;">
					<input type="hidden" id="category_map_marker_icon_id" name="category_map_marker_icon_id" value="<?php echo $category_map_marker_icon_id; ?>" />
					<button type="button" class="upload_image_button button"><?php _e('Upload/Add image', NOO_TEXT_DOMAIN); ?></button>
					<button type="button" class="remove_image_button button"><?php _e('Remove image', NOO_TEXT_DOMAIN); ?></button>
				</div>
				<script type="text/javascript">
	
					jQuery(function(){
	
						 // Only show the "remove image" button when needed
						 if ( ! jQuery('#category_map_marker_icon_id').val() )
							 jQuery('.remove_image_button').hide();
	
						// Uploading files
						var file_frame;
	
						jQuery(document).on( 'click', '.upload_image_button', function( event ){
	
							event.preventDefault();
	
							// If the media frame already exists, reopen it.
							if ( file_frame ) {
								file_frame.open();
								return;
							}
	
							// Create the media frame.
							file_frame = wp.media.frames.downloadable_file = wp.media({
								title: '<?php _e( 'Choose an image', NOO_TEXT_DOMAIN ); ?>',
								button: {
									text: '<?php _e( 'Use image', NOO_TEXT_DOMAIN ); ?>',
								},
								multiple: false
							});
	
							// When an image is selected, run a callback.
							file_frame.on( 'select', function() {
								attachment = file_frame.state().get('selection').first().toJSON();
	
								jQuery('#category_map_marker_icon_id').val( attachment.id );
								jQuery('#category_map_marker_icon img').attr('src', attachment.url );
								jQuery('.remove_image_button').show();
							});
	
							// Finally, open the modal.
							file_frame.open();
						});
	
						jQuery(document).on( 'click', '.remove_image_button', function( event ){
							jQuery('#category_map_marker_icon img').attr('src', '<?php echo NOO_FRAMEWORK_ADMIN_URI . '/assets/images/placeholder.png'; ?>');
							jQuery('#category_map_marker_icon_id').val('');
							jQuery('.remove_image_button').hide();
							return false;
						});
					});
	
				</script>
				<div class="clear"></div>
			</td>
		</tr>
		<?php
		}
		
		public function save_category_map_marker($term_id, $tt_id, $taxonomy ){
			if ( isset( $_POST['category_map_marker_icon_id'] ) ){
				$map_markers = get_option( 'noo_category_map_markers' );
				if ( ! $map_markers )
					$map_markers = array();
				$map_markers[$term_id] = absint($_POST['category_map_marker_icon_id']);
				update_option('noo_category_map_markers', $map_markers);
			}	
		}
		
		public function enqueue_map_scripts(){
			global $post;
			if(get_post_type() === 'noo_property'){
				$latitude = self::get_google_map_option('latitude','40.714398');
				if($lat = noo_get_post_meta($post->ID,'_noo_property_gmap_latitude'))
					$latitude = $lat;
				
				$longitude = self::get_google_map_option('longitude','-74.005279');
				if($long = noo_get_post_meta($post->ID,'_noo_property_gmap_longitude'))
					$longitude = $long;
				
				$nooGoogleMap = array(
					'latitude'=>$latitude,
					'longitude'=>$longitude,
				);
				wp_enqueue_script('google-map','http'.(is_ssl() ? 's':'').'://maps.googleapis.com/maps/api/js?v=3.exp&sensor=true&libraries=places',array('jquery'), '1.0', false);
				wp_register_script( 'noo-property-google-map', NOO_ASSETS_URI . '/js/map-picker.js', array( 'google-map'), null, true );
				
				wp_localize_script('noo-property-google-map', 'nooGoogleMap', $nooGoogleMap);
				wp_enqueue_script('noo-property-google-map');
			}
		}
		
		public function enqueue_scripts(){

			$custom_field_type= apply_filters('noo_property_custom_field_type', array(
				'text'=>__('Short text',NOO_TEXT_DOMAIN),
				'textarea'	=>__('Long text',NOO_TEXT_DOMAIN),
				'date'		=>__('Date',NOO_TEXT_DOMAIN)
			
			));
			/*
			ob_start();
			?>
			<select name="noo_property_custom_filed[custom_field][__i__][type]">
				<?php foreach ($custom_field_type as $value=>$type):?>
					<option value="<?php echo esc_attr($value)?>"><?php esc_html($type)?></option>
				<?php endforeach;?>
			</select>
			<?php
			$type_html = ob_get_clean();
			*/
			$feature_tmpl='';
			$feature_tmpl .= '<tr>';
			$feature_tmpl .= '<td>';
			$feature_tmpl .= '<input type="text" value="" placeholder="'.esc_attr__('Feature Name',NOO_TEXT_DOMAIN).'" name="noo_property_feature[features][]">';
			$feature_tmpl .= '</td>';
			$feature_tmpl .= '<td>';
			$feature_tmpl .= '<input class="button button-primary" onclick="return delete_noo_property_feature(this);" type="button" value="'.esc_attr__('Delete',NOO_TEXT_DOMAIN).'">';
			$feature_tmpl .= '</td>';
			$feature_tmpl .= '</tr>';
			
			$custom_field_tmpl = '';
			$custom_field_tmpl.= '<tr>';
			$custom_field_tmpl.= '<td>';
			$custom_field_tmpl.= '<input type="text" value="" placeholder="'.esc_attr__('Field Name',NOO_TEXT_DOMAIN).'" name="noo_property_custom_filed[custom_field][__i__][name]">';
			$custom_field_tmpl.= '</td>';
			$custom_field_tmpl.= '<td>';
			$custom_field_tmpl.= '<input type="text" value="" placeholder="'.esc_attr__('Field Label',NOO_TEXT_DOMAIN).'" name="noo_property_custom_filed[custom_field][__i__][label]">';
			$custom_field_tmpl.= '</td>';
// 			$custom_field_tmpl.= '<td>';
// 			$custom_field_tmpl.= ''.$type_html;
// 			$custom_field_tmpl.= '</td>';
			$custom_field_tmpl.= '<td>';
			$custom_field_tmpl.= '<input class="button button-primary" onclick="return delete_noo_property_custom_field(this);" type="button" value="'.esc_attr__('Delete',NOO_TEXT_DOMAIN).'">';
			$custom_field_tmpl.= '</td>';
			$custom_field_tmpl.= '</tr>';
			
			$noopropertyL10n = array(
				'feature_tmpl'=>$feature_tmpl,
				'custom_field_tmpl'=>$custom_field_tmpl,
			);
			wp_enqueue_style( 'noo-property', NOO_FRAMEWORK_ADMIN_URI . '/assets/css/noo-property-admin.css');
			wp_register_script( 'noo-property', NOO_FRAMEWORK_ADMIN_URI . '/assets/js/noo-property-admin.js', array( 'jquery','jquery-ui-sortable'), null, true );
			wp_localize_script('noo-property', 'noopropertyL10n', $noopropertyL10n);
			wp_enqueue_script('noo-property');
		}
		
		public static function get_currencies() {
			return array_unique(
					apply_filters( 'noo_property_currencies',
							array(
									'AED' => __( 'United Arab Emirates Dirham', NOO_TEXT_DOMAIN ),
									'AUD' => __( 'Australian Dollars', NOO_TEXT_DOMAIN ),
									'BDT' => __( 'Bangladeshi Taka', NOO_TEXT_DOMAIN ),
									'BRL' => __( 'Brazilian Real', NOO_TEXT_DOMAIN ),
									'BGN' => __( 'Bulgarian Lev', NOO_TEXT_DOMAIN ),
									'CAD' => __( 'Canadian Dollars', NOO_TEXT_DOMAIN ),
									'CLP' => __( 'Chilean Peso', NOO_TEXT_DOMAIN ),
									'CNY' => __( 'Chinese Yuan', NOO_TEXT_DOMAIN ),
									'COP' => __( 'Colombian Peso', NOO_TEXT_DOMAIN ),
									'CZK' => __( 'Czech Koruna', NOO_TEXT_DOMAIN ),
									'DKK' => __( 'Danish Krone', NOO_TEXT_DOMAIN ),
									'EUR' => __( 'Euros', NOO_TEXT_DOMAIN ),
									'HKD' => __( 'Hong Kong Dollar', NOO_TEXT_DOMAIN ),
									'HRK' => __( 'Croatia kuna', NOO_TEXT_DOMAIN ),
									'HUF' => __( 'Hungarian Forint', NOO_TEXT_DOMAIN ),
									'ISK' => __( 'Icelandic krona', NOO_TEXT_DOMAIN ),
									'IDR' => __( 'Indonesia Rupiah', NOO_TEXT_DOMAIN ),
									'INR' => __( 'Indian Rupee', NOO_TEXT_DOMAIN ),
									'ILS' => __( 'Israeli Shekel', NOO_TEXT_DOMAIN ),
									'JPY' => __( 'Japanese Yen', NOO_TEXT_DOMAIN ),
									'KRW' => __( 'South Korean Won', NOO_TEXT_DOMAIN ),
									'MYR' => __( 'Malaysian Ringgits', NOO_TEXT_DOMAIN ),
									'MXN' => __( 'Mexican Peso', NOO_TEXT_DOMAIN ),
									'NGN' => __( 'Nigerian Naira', NOO_TEXT_DOMAIN ),
									'NOK' => __( 'Norwegian Krone', NOO_TEXT_DOMAIN ),
									'NZD' => __( 'New Zealand Dollar', NOO_TEXT_DOMAIN ),
									'PHP' => __( 'Philippine Pesos', NOO_TEXT_DOMAIN ),
									'PKR' => __( 'Pakistani Rupees', NOO_TEXT_DOMAIN ),
									'PLN' => __( 'Polish Zloty', NOO_TEXT_DOMAIN ),
									'GBP' => __( 'Pounds Sterling', NOO_TEXT_DOMAIN ),
									'RON' => __( 'Romanian Leu', NOO_TEXT_DOMAIN ),
									'RUB' => __( 'Russian Ruble', NOO_TEXT_DOMAIN ),
									'SGD' => __( 'Singapore Dollar', NOO_TEXT_DOMAIN ),
									'ZAR' => __( 'South African rand', NOO_TEXT_DOMAIN ),
									'SEK' => __( 'Swedish Krona', NOO_TEXT_DOMAIN ),
									'CHF' => __( 'Swiss Franc', NOO_TEXT_DOMAIN ),
									'TWD' => __( 'Taiwan New Dollars', NOO_TEXT_DOMAIN ),
									'THB' => __( 'Thai Baht', NOO_TEXT_DOMAIN ),
									'TRY' => __( 'Turkish Lira', NOO_TEXT_DOMAIN ),
									'USD' => __( 'US Dollars', NOO_TEXT_DOMAIN ),
									'VND' => __( 'Vietnamese Dong', NOO_TEXT_DOMAIN ),
							)
					)
			);
		}
		
		public static function get_currency_symbol( $currency = '' ) {
			if ( ! $currency ) {
				$currency = self::get_general_option('currency');
			}
		
			switch ( $currency ) {
				case 'AED' :
					$currency_symbol = 'د.إ';
					break;
				case 'BDT':
					$currency_symbol = '&#2547;&nbsp;';
					break;
				case 'BRL' :
					$currency_symbol = '&#82;&#36;';
					break;
				case 'BGN' :
					$currency_symbol = '&#1083;&#1074;.';
					break;
				case 'AUD' :
				case 'CAD' :
				case 'CLP' :
				case 'MXN' :
				case 'NZD' :
				case 'HKD' :
				case 'SGD' :
				case 'USD' :
					$currency_symbol = '&#36;';
					break;
				case 'EUR' :
					$currency_symbol = '&euro;';
					break;
				case 'CNY' :
				case 'RMB' :
				case 'JPY' :
					$currency_symbol = '&yen;';
					break;
				case 'RUB' :
					$currency_symbol = '&#1088;&#1091;&#1073;.';
					break;
				case 'KRW' : $currency_symbol = '&#8361;'; break;
				case 'TRY' : $currency_symbol = '&#84;&#76;'; break;
				case 'NOK' : $currency_symbol = '&#107;&#114;'; break;
				case 'ZAR' : $currency_symbol = '&#82;'; break;
				case 'CZK' : $currency_symbol = '&#75;&#269;'; break;
				case 'MYR' : $currency_symbol = '&#82;&#77;'; break;
				case 'DKK' : $currency_symbol = 'kr.'; break;
				case 'HUF' : $currency_symbol = '&#70;&#116;'; break;
				case 'IDR' : $currency_symbol = 'Rp'; break;
				case 'INR' : $currency_symbol = '&#8377;'; break;
				case 'ISK' : $currency_symbol = 'Kr.'; break;
				case 'ILS' : $currency_symbol = '&#8362;'; break;
				case 'PHP' : $currency_symbol = '&#8369;'; break;
				case 'PKR' : $currency_symbol = 'Rs'; break;
				case 'PLN' : $currency_symbol = '&#122;&#322;'; break;
				case 'SEK' : $currency_symbol = '&#107;&#114;'; break;
				case 'CHF' : $currency_symbol = '&#67;&#72;&#70;'; break;
				case 'TWD' : $currency_symbol = '&#78;&#84;&#36;'; break;
				case 'THB' : $currency_symbol = '&#3647;'; break;
				case 'GBP' : $currency_symbol = '&pound;'; break;
				case 'RON' : $currency_symbol = 'lei'; break;
				case 'VND' : $currency_symbol = '&#8363;'; break;
				case 'NGN' : $currency_symbol = '&#8358;'; break;
				case 'HRK' : $currency_symbol = 'Kn'; break;
				default    : $currency_symbol = ''; break;
			}
		
			return apply_filters( 'noo_property_currency_symbol', $currency_symbol, $currency );
		}
		
		public function ajax_agent_property(){
			global $noo_show_sold;
			$noo_show_sold = true;
			$agent_id = $_POST['agent_id'];
			$page = $_POST['page'];
			$args = array(
					'paged'=>$page,
					'posts_per_page' =>4,
					'post_type'=>'noo_property',
					'meta_query' => array(
							array(
									'key' => '_agent_responsible',
									'value' => $agent_id,
							),
					),
			);
			$r = new WP_Query($args);
			ob_start();
			self::display_content($r,__('My Properties',NOO_TEXT_DOMAIN),true,'',true,true,false,true);
			$ouput = ob_get_clean();
			wp_reset_query();
			$noo_show_sold = false;
			wp_send_json(array('content'=>trim($ouput)));
		}
		
		public function ajax_contact_agent( $is_property_contact = false ){
			$response = '';
			$_POST = stripslashes_deep($_POST);
			$no_html	= array();

			$nonce = $_POST['security'];
			$agent_id = isset( $_POST['agent_id'] ) ? wp_kses( $_POST['agent_id'], $no_html ) : '';
			$property_id = isset( $_POST['property_id'] ) ? wp_kses( $_POST['property_id'], $no_html ) : '';
			$verify = wp_verify_nonce( $nonce, 'noo-contact-agent-'.$agent_id );
			if( $is_property_contact && ( empty( $property_id ) || !is_numeric( $property_id ) ) ) {
				$verify = false;
			}

			if(false != $verify){
				$error = array();
				$name = isset( $_POST['name'] ) ? wp_kses( $_POST['name'], $no_html ) : '';
				$email = isset( $_POST['email'] ) ? wp_kses( $_POST['email'], $no_html ) : '';
				$message = isset( $_POST['message'] ) ? wp_kses( $_POST['message'], $no_html ) : '';
				if($name===null || $name===array() || $name==='' || empty($name) && is_scalar($name) && trim($name)===''){
					$error[] = array(
						'field'=>'name',
						'message'=>__("Please fill the required field.",NOO_TEXT_DOMAIN)
					);
				}
				if($email===null || $email===array() || $email==='' || empty($email) && is_scalar($email) && trim($email)===''){
					$error[] = array(
							'field'=>'email',
							'message'=>__("Please fill the required field.",NOO_TEXT_DOMAIN)
					);
				}else{
					$pattern='/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/';
					$valid=is_string($email) && strlen($email)<=254 && (preg_match($pattern,$email));
					if(!$valid){
						$error[] = array(
							'field'=>'email',
							'message'=>__("Email address seems invalid.",NOO_TEXT_DOMAIN)
						);
					}
				}
				if($message===null || $message===array() || $message==='' || empty($message) && is_scalar($message) && trim($message)===''){
					$error[] = array(
						'field'=>'message',
						'message'=>__("Please fill the required field.",NOO_TEXT_DOMAIN)
					);
				}
				$response = array('error'=>$error,'msg'=>'');
				if(!empty($error)){
					wp_send_json($response);
				}
				if($agent = get_post($agent_id)){
					$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
					$agent_email = get_post_meta($agent_id,'_noo_agent_email');

					$headers = 'From: ' . $name . ' <' . $email . '>' . "\r\n";
					$email_content = '';
						
					if( $is_property_contact ) {
						$property_title = get_the_title( $property_id );
						$property_link = get_permalink( $property_id );

						$email_content = sprintf( __("%s just sent you a message via %s's page", NOO_TEXT_DOMAIN), $name, $property_title) . "\r\n\r\n";
						$email_content .= __("----------------------------------------------", NOO_TEXT_DOMAIN) . "\r\n\r\n";
						$email_content .= $message . "\r\n\r\n";
						$email_content .= __("----------------------------------------------", NOO_TEXT_DOMAIN) . "\r\n\r\n";
						$email_content .= sprintf( __("You can reply to this email to respond or send email to %s", NOO_TEXT_DOMAIN), $email) . "\r\n\r\n";
						$email_content .= sprintf( __("Check %s's details at %s", NOO_TEXT_DOMAIN), $property_title, $property_link) . "\r\n\r\n";
					} else {
						$agent_link = get_permalink( $agent_id );

						$email_content = sprintf( __("%s just sent you a message via your profile", NOO_TEXT_DOMAIN), $name) . "\r\n\r\n";
						$email_content .= __("----------------------------------------------", NOO_TEXT_DOMAIN) . "\r\n\r\n";
						$email_content .= $message . "\r\n\r\n";
						$email_content .= __("----------------------------------------------", NOO_TEXT_DOMAIN) . "\r\n\r\n";
						$email_content .= sprintf( __("You can reply to this email to respond or send email to %s", NOO_TEXT_DOMAIN), $email) . "\r\n\r\n";
						$email_content .= sprintf( __("Check your details at %s", NOO_TEXT_DOMAIN), $agent_link) . "\r\n\r\n";
					}

					$email_content = apply_filters('noo_agent_contact_message', $email_content, $agent_id, $name, $email, $message);
						
					do_action('before_noo_agent_contact_send_mail', $agent_id, $name, $email, $message);

					wp_mail($agent_email,
						sprintf( __("[%s] New message from [%s]", NOO_TEXT_DOMAIN), $blogname, $name),
						$email_content,
						$headers);

					do_action('after_noo_agent_contact_send_mail', $agent_id, $name, $email, $message);
				}

				$response['msg'] = __('Your message was sent successfully. Thanks.',NOO_TEXT_DOMAIN);
				wp_send_json($response);
			}
			die;
		}
		
		public function ajax_contact_agent_property(){
			$this->ajax_contact_agent( true );
		}
		
		public static function contact_agent(){
			$property_id = get_the_ID();
			$agent_id = noo_get_post_meta($property_id,'_agent_responsible');
			if(empty($agent_id))
				return '';
			if($agent = get_post($agent_id)){
				?>
				<?php 
					// Variables
					$prefix = '_noo_agent';

					$avatar_src = wp_get_attachment_image_src( get_post_thumbnail_id( $agent->ID ), 'full' );
					if( empty($avatar_src) ) {
						$avatar_src		= NOO_ASSETS_URI . '/images/default-avatar.png';
					} else {
						$avatar_src		= $avatar_src[0];
					}

					// Agent's info
					$phone			= noo_get_post_meta( $agent->ID, "{$prefix}_phone", '' );
					$mobile			= noo_get_post_meta( $agent->ID, "{$prefix}_mobile", '' );
					$email			= noo_get_post_meta( $agent->ID, "{$prefix}_email", '' );
					$skype			= noo_get_post_meta( $agent->ID, "{$prefix}_skype", '' );
					$facebook		= noo_get_post_meta( $agent->ID, "{$prefix}_facebook", '' );
					$twitter		= noo_get_post_meta( $agent->ID, "{$prefix}_twitter", '' );
					$google_plus	= noo_get_post_meta( $agent->ID, "{$prefix}_google_plus", '' );
					$linkedin		= noo_get_post_meta( $agent->ID, "{$prefix}_linkedin", '' );
					$pinterest		= noo_get_post_meta( $agent->ID, "{$prefix}_pinterest", '' );
				?>
				<div class="agent-property">
					<div class="agent-property-title">
						<h3><?php echo __('Contact Agent',NOO_TEXT_DOMAIN)?></h3>
					</div>
					<div class="agents grid">
						<div <?php post_class('',$agent->ID); ?>>
						    <div class="agent-featured">
						        <a class="content-thumb" href="<?php echo get_permalink($agent->ID) ?>">
									<img src="<?php echo $avatar_src; ?>" alt="<?php the_title(); ?>"/>
								</a>
						    </div>
							<div class="agent-wrap">
								<div class="agent-summary">
									<div class="agent-info">
										<?php if( !empty( $phone ) ) : ?>
											<div class="agent-phone"><i class="fa fa-phone"></i>&nbsp;<?php echo $phone; ?></div>
										<?php endif; ?>
										<?php if( !empty( $mobile ) ) : ?>
											<div class="agent-mobile"><i class="fa fa-tablet"></i>&nbsp;<?php echo $mobile; ?></div>
										<?php endif; ?>
										<?php if( !empty( $email ) ) : ?>
											<div class="agent-email"><i class="fa fa-envelope-square"></i>&nbsp;<?php echo $email; ?></div>
										<?php endif; ?>
										<?php if( !empty( $skype ) ) : ?>
											<div class="agent-skype"><i class="fa fa-skype"></i>&nbsp;<?php echo $skype; ?></div>
										<?php endif; ?>
									</div>
									<div class="agent-desc">
										<div class="agent-social">
											<?php echo ( !empty($facebook) ? '<a class="noo-social-facebook" href="' . $facebook . '"></a>' : '' ); ?>
											<?php echo ( !empty($twitter) ? '<a class="noo-social-twitter" href="' . $twitter . '"></a>' : '' ); ?>
											<?php echo ( !empty($google_plus) ? '<a class="noo-social-googleplus" href="' . $google_plus . '"></a>' : '' ); ?>
											<?php echo ( !empty($linkedin) ? '<a class="noo-social-linkedin" href="' . $linkedin . '"></a>' : '' ); ?>
											<?php echo ( !empty($pinterest) ? '<a class="noo-social-pinterest" href="' . $pinterest . '"></a>' : '' ); ?>
										</div>
										<div class="agent-action">
											<a href="<?php echo get_permalink($agent->ID)?>">
												<?php echo get_the_title($agent->ID); ?>
											</a>
										</div>
									</div>
									
								</div>
							</div>
						</div>
						<div class="conact-agent">
							<form role="form" id="conactagentform" method="post">
								<div style="display: none;">
									<input type="hidden" name="action" value="noo_contact_agent_property">
									<input type="hidden" name="agent_id" value="<?php echo $agent->ID?>">
									<input type="hidden" name="property_id" value="<?php echo $property_id?>">
									<input type="hidden" name="security" value="<?php echo wp_create_nonce('noo-contact-agent-'.$agent->ID)?>">
								</div>
								<?php do_action('before_noo_agent_contact_form')?>
								<?php do_action( 'noo_agent_contact_form_before_fields' ); ?>
								<?php 
								$fields = array(
									'name'=>'<div class="form-group"><input type="text" name="name" class="form-control" placeholder="'.__('Your Name *',NOO_TEXT_DOMAIN).'"></div>',
									'email'=>'<div class="form-group"><input type="email" name="email" class="form-control" placeholder="'.__('Your Email *',NOO_TEXT_DOMAIN).'"></div>',
									'message'=>'<div class="form-group"><textarea name="message" class="form-control" rows="5" placeholder="'.__('Message *',NOO_TEXT_DOMAIN).'"></textarea></div>',
								);
								$fields = apply_filters( 'noo_property_agent_contact_form_default_fields', $fields );
								foreach ($fields as $field):
									echo $field;
								endforeach;
								do_action( 'noo_agent_contact_form_after_fields' );
								?>
								<div class="form-action col-md-12 col-sm-12">
									<img class="ajax-loader" src="<?php echo NOO_ASSETS_URI ?>/images/ajax-loader.gif" alt="<?php _e('Sending ...',NOO_TEXT_DOMAIN)?>" style="visibility: hidden;">
									<button type="submit" class="btn btn-default"><?php _e('Send a Message',NOO_TEXT_DOMAIN)?></button>
								</div>
								<?php do_action('before_noo_agent_contact_form')?>
							</form>
						</div>
					</div>
				</div>
				<?php
			}
		}
		public static function get_single_category($post_id){
			$terms = get_the_terms( $post_id, 'property_category' );
			if ( is_wp_error( $terms ) )
				return false;
			
			if ( empty( $terms ) )
				return false;
			
			foreach ( $terms as $term ) {
				return $term;
				break;
			}
			
		}

		public static function social_share( $post_id = null ) {
			$post_id = (null === $post_id) ? get_the_id() : $post_id;
			$post_type =  get_post_type($post_id);

			if( $post_type != 'noo_property' ) {
				echo '';
				return false;
			}

			$prefix        = 'noo_property';

			$share_url     = urlencode( get_permalink() );
			$share_title   = urlencode( get_the_title() );
			$share_source  = urlencode( get_bloginfo( 'name' ) );
			$share_content = urlencode( get_the_content() );
			$share_media   = wp_get_attachment_thumb_url( get_post_thumbnail_id() );
			$popup_attr    = 'resizable=0, toolbar=0, menubar=0, status=0, location=0, scrollbars=0';

			$facebook     = noo_get_option( "{$prefix}_social_facebook", true );
			$twitter      = noo_get_option( "{$prefix}_social_twitter", true );
			$google		  = noo_get_option( "{$prefix}_social_google", true );
			$pinterest    = noo_get_option( "{$prefix}_social_pinterest", false );
			$linkedin     = noo_get_option( "{$prefix}_social_linkedin", false );


			$html = array();

			if ( $facebook || $twitter || $google || $pinterest || $linkedin ) {
				$html[] = '<div class="property-share clearfix">';

				if($facebook) {
					$html[] = '<a href="#share" data-toggle="tooltip" data-placement="bottom" data-trigger="hover" class="noo-social-facebook"'
								. ' title="' . __( 'Share on Facebook', NOO_TEXT_DOMAIN ) . '"'
								. ' onclick="window.open(' 
									. "'http://www.facebook.com/sharer.php?u={$share_url}&amp;t={$share_title}','popupFacebook','width=650,height=270,{$popup_attr}');"
									. ' return false;">';
					$html[] = '</a>';
				}

				if($twitter) {
					$html[] = '<a href="#share" class="noo-social-twitter"'
								. ' title="' . __( 'Share on Twitter', NOO_TEXT_DOMAIN ) . '"'
								. ' onclick="window.open('
									. "'https://twitter.com/intent/tweet?text={$share_title}&amp;url={$share_url}','popupTwitter','width=500,height=370,{$popup_attr}');"
									. ' return false;">';
					$html[] = '</a>';
				}

				if($google) {
					$html[] = '<a href="#share" class="noo-social-googleplus"'
								. ' title="' . __( 'Share on Google+', NOO_TEXT_DOMAIN ) . '"'
								. ' onclick="window.open('
									. "'https://plus.google.com/share?url={$share_url}','popupGooglePlus','width=650,height=226,{$popup_attr}');"
									. ' return false;">';
					$html[] = '</a>';
				}

				if($pinterest) {
					$html[] = '<a href="#share" class="noo-social-pinterest"'
								. ' title="' . __( 'Share on Pinterest', NOO_TEXT_DOMAIN ) . '"'
								. ' onclick="window.open('
									. "'http://pinterest.com/pin/create/button/?url={$share_url}&amp;media={$share_media}&amp;description={$share_title}','popupPinterest','width=750,height=265,{$popup_attr}');"
									. ' return false;">';
					$html[] = '</a>';
				}

				if($linkedin) {
					$html[] = '<a href="#share" class="noo-social-linkedin"'
								. ' title="' . __( 'Share on LinkedIn', NOO_TEXT_DOMAIN ) . '"'
								. ' onclick="window.open('
									. "'http://www.linkedin.com/shareArticle?mini=true&amp;url={$share_url}&amp;title={$share_title}&amp;summary={$share_content}&amp;source={$share_source}','popupLinkedIn','width=610,height=480,{$popup_attr}');"
									. ' return false;">';
					$html[] = '</a>';
				}

				$html[] = '</div>'; // .agent-social
			}

			echo implode("\n", $html);
		}
		
		public static function display_detail($query=null){
			self::enqueue_gmap_js();
			wp_enqueue_script('noo-property');
			wp_enqueue_script( 'vendor-nivo-lightbox-js' );
			wp_enqueue_style( 'vendor-nivo-lightbox-default-css' );

			if(empty($query)){
				global $wp_query;
				$query = $wp_query;
			}
			while ($query->have_posts()): $query->the_post(); global $post;
			?>
			<article id="post-<?php the_ID(); ?>" class="property">
				<h1 class="property-title">
					<?php the_title(); ?>
					<small><?php echo noo_get_post_meta(null,'_address')?></small>
				</h1>
				<?php self::social_share( get_the_id() ); ?>
				<?php if (has_post_thumbnail()) { ?>
				<?php 
				$gallery = noo_get_post_meta(get_the_ID(),'_gallery','');
				$gallery_ids = explode(',',$gallery);
				$gallery_ids = array_filter($gallery_ids);

				$property_category	= get_the_term_list(get_the_ID(), 'property_category', '', ', <span class="property-category-separator"></span>');
				$property_status	= get_the_term_list(get_the_ID(), 'property_status');
				$property_location	= get_the_term_list(get_the_ID(), 'property_location');
				$property_sub_location	= get_the_term_list(get_the_ID(), 'property_sub_location');
				$property_price		= self::get_price_html(get_the_ID());
				$property_area		= trim(self::get_area_html(get_the_ID()));
				$property_bedrooms	= noo_get_post_meta(get_the_ID(),'_bedrooms');
				$property_bathrooms	= noo_get_post_meta(get_the_ID(),'_bathrooms');
				?>
			    <div class="property-abovefold clearfix">
					<div class="property-featured col-md-6 clearfix">
					    <div class="images">
						    <div class="caroufredsel-wrap">
							    <ul>
							    <?php
							    $image = wp_get_attachment_image_src(get_post_thumbnail_id(),'property-full');
							    ?>
								    <li>
									    <a class="noo-lightbox-item" data-lightbox-gallery="gallert_<?php the_ID()?>" href="<?php echo $image[0]?>"><?php echo get_the_post_thumbnail(get_the_ID(), 'cg-property-medium' ) ?></a>
								    </li>
								    <?php if(!empty($gallery_ids)): ?>
								    <?php foreach ($gallery_ids as $gallery_id): $gallery_image = wp_get_attachment_image_src($gallery_id,'property-full')?>
								    <li>
									    <a class="noo-lightbox-item" data-lightbox-gallery="gallert_<?php the_ID()?>" href="<?php echo $gallery_image[0]?>"><?php echo wp_get_attachment_image( $gallery_id, 'cg-property-medium' ); ?></a>
								    </li>
								    <?php endforeach;?>
								    <?php endif;?>
							    </ul>
						    </div>
					    </div>
					    <?php 
					    
					    if(!empty($gallery_ids)):
					    ?>
					    <div class="thumbnails">
						    <div class="thumbnails-wrap">
							    <ul>
							    <li>
								    <a data-rel="0" href="<?php echo $image[0]?>"><?php echo get_the_post_thumbnail(get_the_ID(), 'cg-property-thumb') ?></a>
							    </li>
							    <?php $i = 1;?>
							    <?php foreach ($gallery_ids as $gallery_id): $gallery_image = wp_get_attachment_image_src($gallery_id,'property-full')?>
							    <li>
								    <a data-rel="<?php echo $i ?>" href="<?php echo $gallery_image[0]?>"><?php echo wp_get_attachment_image( $gallery_id, 'cg-property-thumb'); ?></a>
							    </li>
							    <?php $i++;?>
							    <?php endforeach;?>
							    </ul>
						    </div>
						    <a class="caroufredsel-prev" href="#"></a>
						    <a class="caroufredsel-next" href="#"></a>
					    </div>
					    <?php endif;?>
					</div>
					<div class="property-contact col-md-6">
						<h2 class="property-contact-title">Contact an Agent</h2>
						<!--<p class="property-contact-content">Interested in or have questions about this property? Our agents are ready to help you!</p>-->
						<?php echo do_shortcode( '[contact-form-7 id="139" title="Contact - Property Listing"]' ); ?>
					</div>
				</div>
			    <?php } ?>
				<div class="property-summary clearfix">
					<div class="row">
						<div class="property-detail col-md-4 col-sm-4">
							<h4 class="property-detail-title"><?php _e('Property Detail',NOO_TEXT_DOMAIN)?></h4>
							<div class="property-detail-content">
								<div class="detail-field row">
									<?php if( !empty($property_category) ) : ?>
										<span class="col-sm-5 detail-field-label type-label"><?php echo __('Type',NOO_TEXT_DOMAIN)?></span>
										<span class="col-sm-7 detail-field-value type-value"><?php echo $property_category?></span>
									<?php endif; ?>
									<?php if( !empty($property_status) ) : ?>
										<span class="col-sm-5 detail-field-label status-label"><?php echo __('Status',NOO_TEXT_DOMAIN)?></span>
										<span class="col-sm-7 detail-field-value status-value"><?php echo $property_status?></span>
									<?php endif; ?>
									<?php if( !empty($property_location) ) : ?>
										<span class="col-sm-5 detail-field-label location-label"><?php echo __('Location',NOO_TEXT_DOMAIN)?></span>
										<span class="col-sm-7 detail-field-value location-value"><?php echo $property_location?></span>
									<?php endif; ?>
									<?php if( !empty($property_sub_location) ) : ?>
										<span class="col-sm-5 detail-field-label sub_location-label"><?php echo __('Sub Location',NOO_TEXT_DOMAIN)?></span>
										<span class="col-sm-7 detail-field-value sub_location-value"><?php echo $property_sub_location?></span>
									<?php endif; ?>
									<?php if( !empty($property_price) ) : ?>
										<span class="col-sm-5 detail-field-label price-label"><?php echo __('Price',NOO_TEXT_DOMAIN)?></span>
										<span class="col-sm-7 detail-field-value price-value"><?php echo $property_price?></span>
									<?php endif; ?>
									<?php if( !empty($property_area) ) : ?>
										<span class="col-sm-5 detail-field-label area-label"><?php echo __('Area',NOO_TEXT_DOMAIN)?></span>
										<span class="col-sm-7 detail-field-value area-value"><?php echo $property_area?></span>
									<?php endif; ?>
									<?php if( !empty($property_bedrooms) ) : ?>
										<span class="col-sm-5 detail-field-label bedrooms-label"><?php echo __('Bedrooms',NOO_TEXT_DOMAIN)?></span>
										<span class="col-sm-7 detail-field-value bedrooms-value"><?php echo $property_bedrooms?></span>
									<?php endif; ?>
									<?php if( !empty($property_bathrooms) ) : ?>
										<span class="col-sm-5 detail-field-label bathrooms-label"><?php echo __('Bathrooms',NOO_TEXT_DOMAIN)?></span>
										<span class="col-sm-7 detail-field-value bathrooms-value"><?php echo $property_bathrooms?></span>
									<?php endif; ?>
								<?php $custom_fields = self::get_custom_field_option('custom_field');?>
								<?php foreach ((array)$custom_fields  as $field):?> 
									<?php  $custom_field_value = trim(noo_get_post_meta(get_the_ID(),'_noo_property_field_'.sanitize_title(@$field['name']),null)); ?>
									<?php if(!empty($custom_field_value)):?>
									<span class="col-sm-5 detail-field-label <?php echo sanitize_title(@$field['name'])?>"><?php echo isset( $field['label_translated'] ) ? $field['label_translated'] : @$field['label']?></span>
									<span class="col-sm-7 detail-field-value <?php echo sanitize_title(@$field['name'])?>"><?php echo $custom_field_value ?></span>
									<?php endif;?>
								<?php endforeach;?>
								</div>
							</div>
						</div>
						<div class="property-desc col-md-8 col-sm-8">
							<!--<h4 class="property-detail-title"><?php _e('Property Description',NOO_TEXT_DOMAIN)?></h4>-->
							<?php the_content();?>
						</div>
						<!--<div class="property-content col-md-8 col-sm-8">
						</div>-->
					</div>
				</div>
				<?php $features = (array) self::get_feature_option('features');
				if( !empty( $features ) && is_array( $features ) ) : ?>
				<div class="property-feature">
					<h4 class="property-feature-title"><?php _e('Property Features',NOO_TEXT_DOMAIN)?></h4>
					<div class="property-feature-content">
						<?php $show_no_feature = ( self::get_feature_option('show_no_feature') == 'yes' );
						?>
						<?php foreach ($features as $feature):?>
							<?php if(noo_get_post_meta(get_the_ID(),'_noo_property_feature_'.sanitize_title($feature))):
							$feature = function_exists('icl_translate') ? icl_translate(NOO_TEXT_DOMAIN,'noo_property_features_'. sanitize_title($feature), $feature ) : $feature;
							?>
							<div class="has">
								<i class="fa fa-check-circle"></i> <?php echo $feature?>
							</div>
							<?php elseif( $show_no_feature ) :
							$feature = function_exists('icl_translate') ? icl_translate(NOO_TEXT_DOMAIN,'noo_property_features_'. sanitize_title($feature), $feature ) : $feature;
							?>
							<div class="no-has">
								<i class="fa fa-times-circle"></i> <?php echo $feature?>
							</div>
							<?php endif;?>
						
						<?php endforeach;?>
					</div>
				</div>
				<?php endif; ?>
				<?php if($_video_embedded = noo_get_post_meta(get_the_ID(),'_video_embedded','')):?>
				<?php 
				$video_w = ( isset( $content_width ) ) ? $content_width : 1200;
				$video_h = $video_w / 1.61; //1.61 golden ratio
				global $wp_embed;
				$embed = $wp_embed->run_shortcode( '[embed]' . $_video_embedded . '[/embed]' );
				?>
				<div class="property-video">
					<h4 class="property-video-title"><?php _e('Property Video',NOO_TEXT_DOMAIN)?></h4>
					<div class="property-video-content">
						<?php echo $embed; ?>
					</div>
				</div>
				<?php endif;?>
				<div class="property-map">
					<h4 class="property-map-title"><?php _e('Find this property on map',NOO_TEXT_DOMAIN)?></h4>
					<div class="property-map-content">
						<div class="property-map-search">
							<input placeholder="<?php echo __('Search your map',NOO_TEXT_DOMAIN)?>" type="text" autocomplete="off" id="property_map_search_input">
						</div>
						<?php 
						$property_category_terms          =   get_the_terms(get_the_ID(),'property_category' );
						$property_category_marker = '';
						if($property_category_terms && !is_wp_error($property_category_terms)){
							$map_markers = get_option( 'noo_category_map_markers' );
							foreach($property_category_terms as $category_term){
								if(empty($category_term->slug))
									continue;
								$property_category = $category_term->slug;
								if(isset($map_markers[$category_term->term_id]) && !empty($map_markers[$category_term->term_id])){
									$property_category_marker = wp_get_attachment_url($map_markers[$category_term->term_id]);
								}
								break;
							}
						}
						?>
						<div id="property-map-<?php echo get_the_ID()?>" class="property-map-box" data-marker="<?php echo esc_attr($property_category_marker)?>" data-zoom="<?php echo esc_attr(noo_get_post_meta(get_the_ID(), '_noo_property_gmap_zoom', '16'))?>" data-latitude="<?php echo esc_attr(noo_get_post_meta(get_the_ID(),'_noo_property_gmap_latitude'))?>" data-longitude="<?php echo esc_attr(noo_get_post_meta(get_the_ID(),'_noo_property_gmap_longitude'))?>"></div>
					</div>
				</div>
			</article> <!-- /#post- -->
			<?php self::contact_agent()?>
			<?php self::get_similar_property();?>
			<?php
			endwhile;
			wp_reset_query();
		}
		/**
		 * 
		 * @param string $query
		 * @param string $title
		 * @param string $display_mode
		 * @param string $default_mode
		 * @param string $show_pagination
		 * @param string $ajax_pagination
		 * @param string $show_orderby
		 */
		public static function display_content($query='',$title='', $display_mode  = true,$default_mode = '',$show_pagination = false,$ajax_pagination=false,$show_orderby=false,$ajax_content=false,$category_desc=''){
			self::enqueue_gmap_js();
			wp_enqueue_script('noo-property');
			global $wp_query,$wp_rewrite;
			if(!empty($query)){
				$wp_query = $query;
			}
			if ($wp_query->is_main_query())
				$show_orderby = noo_get_option('noo_property_listing_orderby', 1);
			if(empty($default_mode)){
				$default_mode = noo_get_option('noo_property_listing_layout','grid');
			}
			$mode = (isset($_GET['mode']) ? $_GET['mode'] : $default_mode);
			$is_fullwidth = false;
			if(is_post_type_archive('noo_property')
					|| is_tax('property_status')
					|| is_tax('property_sub_location')
					|| is_tax('property_location')
					|| is_tax('property_category')){
				$noo_property_layout =  noo_get_option('noo_property_layout','fullwidth');
				if($noo_property_layout == 'fullwidth'){
					$is_fullwidth = true;
				}
			}
			
			if($wp_query->have_posts()):
			if(!$ajax_content){
			?>
			<div class="properties <?php echo $mode ?>" <?php echo $ajax_pagination ? 'data-paginate="loadmore"':''?>>
				<div class="properties-header">
					<h1 class="page-title"><?php echo $title ?></h1>
					<?php if($display_mode):?>
					<div class="properties-toolbar">
						<a class="<?php echo $mode == 'grid' ?'selected':'' ?>" data-mode="grid" href="<?php echo esc_url(add_query_arg( 'mode','grid'))?>" title="<?php echo esc_attr('Grid',NOO_TEXT_DOMAIN)?>"><i class="fa fa-th-large"></i></a>
						<a class="<?php echo $mode == 'list' ?'selected':'' ?>" data-mode="list" href="<?php echo esc_url(add_query_arg( 'mode','list'))?>" title="<?php echo esc_attr('List',NOO_TEXT_DOMAIN)?>"><i class="fa fa-list"></i></a>
						<?php if($show_orderby):?>
						<form class="properties-ordering" method="get">
							<div class="properties-ordering-label"><?php _e('Sorted by',NOO_TEXT_DOMAIN)?></div>
							<div class="form-group properties-ordering-select">
								<div class="dropdown">
									<?php 
									$order_arr = array(
										'date'=>__('Date',NOO_TEXT_DOMAIN),
										'price'=>__('Price',NOO_TEXT_DOMAIN),
										'name'=>__('Name',NOO_TEXT_DOMAIN),
										'area'=>__('Area',NOO_TEXT_DOMAIN),
										'bath'=>__('Bath',NOO_TEXT_DOMAIN),
										'bed'=>__('Bed',NOO_TEXT_DOMAIN),
									);
									$ordered = isset($_GET['orderby']) && array_key_exists($_GET['orderby'], $order_arr) ? $order_arr[$_GET['orderby']] : __('Date',NOO_TEXT_DOMAIN);
									?>
									<span data-toggle="dropdown"><?php echo $ordered ?></span>
									<ul class="dropdown-menu">
									<?php foreach ($order_arr as $k=>$v):?>
										<li><a  data-value="<?php echo esc_attr($k)?>"><?php echo $v ?></a></li>
									<?php endforeach;?>
									</ul>
								</div>
							</div>
							<input type="hidden" name="orderby" value="">
							<?php
								foreach ( $_GET as $key => $val ) {
									if ( 'orderby' === $key || 'submit' === $key )
										continue;
									
									if ( is_array( $val ) ) {
										foreach( $val as $innerVal ) {
											echo '<input type="hidden" name="' . esc_attr( $key ) . '[]" value="' . esc_attr( $innerVal ) . '" />';
										}
									
									} else {
										echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $val ) . '" />';
									}
								}
							?>
						</form>
						<?php endif;?>
					</div>
					<?php endif;?>
				</div>
				<div class="properties-content<?php echo $ajax_pagination ? ' loadmore-wrap':''?>">
					<?php if( $category_desc ) : // add property category description if present ?>
					<div class="properties-category-desc"><?php echo $category_desc; ?></div>
					<?php endif; ?>
				<?php }?>
				<?php while ( $wp_query->have_posts() ) : $wp_query->the_post(); global $post; ?>
					<article id="property-<?php the_ID(); ?>" <?php post_class(); ?>>
					    <div class="property-featured">
					    	<?php if('yes' === noo_get_post_meta($post->ID,'_featured')):?>
					    	<span class="featured"><i class="fa fa-star"></i></span>
					    	<?php endif;?>
					        <a class="content-thumb" href="<?php the_permalink() ?>">
								<?php echo get_the_post_thumbnail(get_the_ID(),'property-thumb') ?>
							</a>
							<?php 
							$sold = get_option('default_property_status');
							if(!empty($sold) && (has_term($sold,'property_status'))):
							$sold_term = get_term($sold, 'property_status');
							?>
							<span class="property-label sold"><?php echo $sold_term->name?></span>
							<?php
							endif;
							?>
							<?php 
							$_label = noo_get_post_meta(null,'_label');
							if(!empty($_label) && ($property_label = get_term($_label, 'property_label'))):
							$noo_property_label_colors = get_option('noo_property_label_colors');
							$color 	= isset($noo_property_label_colors[$property_label->term_id]) ? $noo_property_label_colors[$property_label->term_id] : '';
							?>
							<span class="property-label" <?php echo (!empty($color) ? ' style="background-color:'.$color.'"':'')?>><?php echo $property_label->name?></span>
							<?php endif;?>
							<span class="property-category"><?php echo get_the_term_list(get_the_ID(), 'property_category', '', ', ')?></span>
					    </div>
						<div class="property-wrap">
							<h2 class="property-title">
								<a href="<?php the_permalink(); ?>" title="<?php echo esc_attr( sprintf( __( 'Permanent link to: "%s"',NOO_TEXT_DOMAIN ), the_title_attribute( 'echo=0' ) ) ); ?>"><?php the_title(); ?></a>
								<?php if($is_fullwidth):?>
								<small><?php echo noo_get_post_meta(null,'_address')?></small>
								<?php endif;?>
							</h2>
							<div class="property-excerpt">
								<?php if($excerpt = $post->post_content):?>
									<?php 
									$num_word = 15;
									$excerpt = strip_shortcodes($excerpt);
									echo '<p>' . wp_trim_words($excerpt,$num_word,'...') . '</p>';
									echo '<p class="property-fullwidth-excerpt">' . wp_trim_words($excerpt,25,'...') . '</p>';
									?>
								<?php endif;?>
							</div>
							<div class="property-summary">
								<div class="property-detail">
									<div class="size"><span><?php echo self::get_area_html(get_the_ID());?></span></div>
									<div class="bathrooms"><span><?php echo noo_get_post_meta(get_the_ID(),'_bathrooms')?></span></div>
									<div class="bedrooms"><span><?php echo noo_get_post_meta(get_the_ID(),'_bedrooms')?></span></div>
								</div>
								<div class="property-info">
									<div class="property-price">
										<span><?php echo self::get_price_html(get_the_ID())?></span>
									</div>
									<div class="property-action">
										<a href="<?php the_permalink()?>"><?php echo __('More Details',NOO_TEXT_DOMAIN)?></a>
									</div>
								</div>
								<div class="property-info property-fullwidth-info">
									<div class="property-price">
										<span><?php echo self::get_price_html(get_the_ID())?></span>
									</div>
									<div class="size"><span><?php echo self::get_area_html(get_the_ID());?></span></div>
									<div class="bathrooms"><span><?php echo noo_get_post_meta(get_the_ID(),'_bathrooms')?></span></div>
									<div class="bedrooms"><span><?php echo noo_get_post_meta(get_the_ID(),'_bedrooms')?></span></div>
									
								</div>
							</div>
						</div>
						<div class="property-action property-fullwidth-action">
							<a href="<?php the_permalink()?>"><?php echo __('More Details',NOO_TEXT_DOMAIN)?></a>
						</div>
					</article> <!-- /#post- -->
				<?php endwhile; ?>
					<?php if (!$ajax_content){?>
					</div>
					<?php if($ajax_pagination && (1 < $wp_query->max_num_pages)):?>
					<div class="loadmore-action">
						<div class="noo-loader loadmore-loading">
				            <div class="rect1"></div>
				            <div class="rect2"></div>
				            <div class="rect3"></div>
				            <div class="rect4"></div>
				            <div class="rect5"></div>
				        </div>
						<button type="button" class="btn btn-default btn-block btn-loadmore"><?php _e('Load More',NOO_TEXT_DOMAIN)?></button>
					</div>
					<?php endif;?>
					<?php
					if($show_pagination || $ajax_pagination){
						noo_pagination(array(),$wp_query);
					}
				}
			if(!$ajax_content){
			?>
			</div>
			<?php
			}
			endif;
			wp_reset_query();
		}
	}
new NooProperty();	
endif;