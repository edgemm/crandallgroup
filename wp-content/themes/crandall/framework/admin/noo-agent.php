<?php
/**
 * Register NOO Agent.
 * This file register Item and Category for NOO Agent.
 *
 * @package    NOO CitiLights
 * @subpackage NOO Agent
 * @version    1.0.0
 * @author     Kan Nguyen <khanhnq@nootheme.com>
 * @copyright  Copyright (c) 2014, NooTheme
 * @license    http://opensource.org/licenses/gpl-2.0.php GPL v2 or later
 * @link       http://nootheme.com
 */

if(!class_exists('NooAgent')) :
	class NooAgent {

		const AGENT_SLUG = 'agents';
		const AGENT_POST_TYPE = 'noo_agent';
		const AGENT_META_PREFIX = '_noo_agent';

		public function __construct() {
			add_action('init', array(&$this,'register_post_type'));
			add_shortcode('noo_recent_agents', array(&$this,'recent_agents_shortcode'));
			add_shortcode('noo_login_register', array(&$this,'login_register_shortcode'));
			add_shortcode('noo_membership_packages', array(&$this,'noo_membership_packages_shortcode'));
			if( is_admin() ) {
				add_action( 'add_meta_boxes', array (&$this, 'remove_meta_boxes' ), 20 );
				add_action( 'add_meta_boxes', array (&$this, 'add_meta_boxes' ), 30 );
				add_filter( 'enter_title_here', array (&$this,'custom_enter_title') );
				add_action( 'admin_enqueue_scripts', array (&$this,'enqueue_style_script') );
				add_action( 'admin_menu', array(&$this, 'settings_sub_menu') );

				// Hide IDX page template when the plugin is not installed
				add_action( 'admin_footer', array(&$this, 'hide_IDX_page_template'), 10 );
			}

			if( get_option('noo_membership_type', 'membership') != 'none' ) {
				add_action('save_post', array(&$this, 'save_post'), 1, 2);
				// add_action('user_register', array(&$this, 'agent_user_register'));
				add_action('user_register', array(&$this, 'hide_admin_bar_front'));
				add_action('show_user_profile', array(&$this, 'edit_user_profile'));
				add_action('edit_user_profile_update', array(&$this, 'edit_user_profile_update'));
				add_action('personal_options_update', array(&$this, 'edit_user_profile_update'));

				add_action('transition_post_status', array(&$this, 'transition_post_status'), 10, 3);

				// Remove admin bar and redirect profile page to site interface 
				if( !current_user_can('activate_plugins') ) {
					add_action( 'wp_before_admin_bar_render', array(&$this, 'stop_admin_bar_render') );
					add_action( 'admin_init', array(&$this, 'stop_admin_profile') );
				}
			}



			// Ajax for frontend functions
			add_action( 'wp_ajax_nopriv_noo_ajax_update_profile', array(&$this, 'ajax_update_profile') );
			add_action( 'wp_ajax_noo_ajax_update_profile', array(&$this, 'ajax_update_profile') );

			add_action( 'wp_ajax_nopriv_noo_ajax_change_password', array(&$this, 'ajax_change_password') );
			add_action( 'wp_ajax_noo_ajax_change_password', array(&$this, 'ajax_change_password') );

			add_action( 'wp_ajax_nopriv_noo_ajax_status_property', array(&$this, 'ajax_status_property') );
			add_action( 'wp_ajax_noo_ajax_status_property', array(&$this, 'ajax_status_property') );

			add_action( 'wp_ajax_nopriv_noo_ajax_featured_property', array(&$this, 'ajax_featured_property') );
			add_action( 'wp_ajax_noo_ajax_featured_property', array(&$this, 'ajax_featured_property') );

			add_action( 'wp_ajax_nopriv_noo_ajax_delete_property', array(&$this, 'ajax_delete_property') );
			add_action( 'wp_ajax_noo_ajax_delete_property', array(&$this, 'ajax_delete_property') );

			add_action( 'wp_ajax_nopriv_noo_ajax_login', array(&$this, 'ajax_login') );
			add_action( 'wp_ajax_noo_ajax_login', array(&$this, 'ajax_login') );
			add_action( 'wp_ajax_nopriv_noo_ajax_register', array(&$this, 'ajax_register') );
			add_action( 'wp_ajax_noo_ajax_register', array(&$this, 'ajax_register') );

			if( get_option('noo_membership_type', 'membership') == 'membership' ) {
				add_action( 'wp_ajax_nopriv_noo_ajax_membership_payment', array(&$this, 'ajax_membership_payment') );
				add_action( 'wp_ajax_noo_ajax_membership_payment', array(&$this, 'ajax_membership_payment') );
			}

			if( get_option('noo_membership_type', 'membership') == 'submission' ) {
				add_action( 'wp_ajax_nopriv_noo_ajax_listing_payment', array(&$this, 'ajax_listing_payment') );
				add_action( 'wp_ajax_noo_ajax_listing_payment', array(&$this, 'ajax_listing_payment') );
			}
		}

		public function register_post_type() {

			// Text for NOO Agent.
			$noo_agent_labels = array(
				'name' => __('Agents', NOO_TEXT_DOMAIN),
				'singular_name' => __('Agent', NOO_TEXT_DOMAIN),
				'menu_name' => __('Agents &amp; Membership', NOO_TEXT_DOMAIN),
				'all_items' => __('All Agents', NOO_TEXT_DOMAIN),
				'add_new' => __('Add Agent', NOO_TEXT_DOMAIN),
				'add_new_item' => __('Add Agent', NOO_TEXT_DOMAIN),
				'edit_item' => __('Edit Agent', NOO_TEXT_DOMAIN),
				'new_item' => __('New Agent', NOO_TEXT_DOMAIN),
				'view_item' => __('View Agent', NOO_TEXT_DOMAIN),
				'search_items' => __('Search Agent', NOO_TEXT_DOMAIN),
				'not_found' => __('No agents found', NOO_TEXT_DOMAIN),
				'not_found_in_trash' => __('No agents found in trash', NOO_TEXT_DOMAIN),
				'parent_item_colon' => ''
			);
			
			$noo_agent_icon = NOO_FRAMEWORK_ADMIN_URI . '/assets/images/noo20x20.png';
			if ( floatval( get_bloginfo( 'version' ) ) >= 3.8 ) {
				$noo_agent_icon = 'dashicons-businessman';
			}

			$noo_agent_slug = get_option( 'noo_agent_archive_slug', '' );
			$noo_agent_slug = !empty($noo_agent_slug) ? $noo_agent_slug : self::AGENT_SLUG;

			// Options
			$noo_agent_args = array(
				'labels' => $noo_agent_labels,
				'public' => true,
				'publicly_queryable' => true,
				'show_ui' => true,
				'show_in_menu' => true,
				// 'menu_position' => 5,
				'menu_icon' => $noo_agent_icon,
				'capability_type' => 'post',
				'hierarchical' => false,
				'supports' => array(
					'title',
					'editor',
					// 'excerpt',
					'thumbnail',
					'comments',
					// 'custom-fields',
					'revisions'
				),
				'has_archive' => true,
				'rewrite' => array(
					'slug' => $noo_agent_slug,
					'with_front' => false
				)
			);
			
			register_post_type(self::AGENT_POST_TYPE, $noo_agent_args);
		}

		public function remove_meta_boxes() {
			remove_meta_box( 'mymetabox_revslider_0', self::AGENT_POST_TYPE, 'normal' );
		}

		public function add_meta_boxes() {

			// Declare helper object
			$prefix = self::AGENT_META_PREFIX;
			$helper = new NOO_Meta_Boxes_Helper( $prefix, array( 'page' => self::AGENT_POST_TYPE ) );

			// Agent Detail
			$meta_box = array(
				'id'           => "{$prefix}_meta_box_agent",
				'title'        => __( 'Agent Details', NOO_TEXT_DOMAIN ),
				'context'      => 'normal',
				'priority'     => 'core',
				'description'  => '',
				'fields'       => array(
					array(
						'id' => "{$prefix}_position",
						'label' => __( 'Job Position', NOO_TEXT_DOMAIN ),
						'desc' => '',
						'type' => 'text',
						'std' => __( 'Real Estate agent', NOO_TEXT_DOMAIN )
					),
					array(
						'id' => "{$prefix}_email",
						'label' => __( 'Email', NOO_TEXT_DOMAIN ),
						'desc' => '',
						'type' => 'text',
						'std' => 'yourname@email.com'
					),
					array(
						'id' => "{$prefix}_phone",
						'label' => __( 'Phone', NOO_TEXT_DOMAIN ),
						'desc' => '',
						'type' => 'text',
						'std' => '(123) 456789'
					),
					array(
						'id' => "{$prefix}_mobile",
						'label' => __( 'Mobile', NOO_TEXT_DOMAIN ),
						'desc' => '',
						'type' => 'text',
						'std' => '(123) 456789'
					),
					array(
						'id' => "{$prefix}_skype",
						'label' => __( 'Skype Account', NOO_TEXT_DOMAIN ),
						'desc' => '',
						'type' => 'text',
						'std' => 'account_name'
					),
					array(
						'type' => 'divider',
					),
					array(
						'id' => "{$prefix}_facebook",
						'label' => __( 'Facebook Profile URL', NOO_TEXT_DOMAIN ),
						'desc' => '',
						'type' => 'text',
						'std' => ''
					),
					array(
						'id' => "{$prefix}_twitter",
						'label' => __( 'Twitter Profile URL', NOO_TEXT_DOMAIN ),
						'desc' => '',
						'type' => 'text',
						'std' => ''
					),
					array(
						'id' => "{$prefix}_google_plus",
						'label' => __( 'Google+ Profile URL', NOO_TEXT_DOMAIN ),
						'desc' => '',
						'type' => 'text',
						'std' => ''
					),
					array(
						'id' => "{$prefix}_linkedin",
						'label' => __( 'LinkedIn Profile URL', NOO_TEXT_DOMAIN ),
						'desc' => '',
						'type' => 'text',
						'std' => ''
					),
					array(
						'id' => "{$prefix}_pinterest",
						'label' => __( 'Pinterest Profile URL', NOO_TEXT_DOMAIN ),
						'desc' => '',
						'type' => 'text',
						'std' => ''
					)
				),
			);

			$helper->add_meta_box($meta_box);

			if( NooMembership::is_membership() ) {
				$meta_box = array(
					'id'           => "{$prefix}_meta_box_membership_package",
					'title'        => __( 'Membership Package', NOO_TEXT_DOMAIN ),
					'context'      => 'side',
					'priority'     => 'default',
					'description'  => '',
					'fields'       => array(
						array(
							'id' => "_membership_package",
							'label' => __( 'Select Membership Package', NOO_TEXT_DOMAIN ),
							'desc' => '',
							'type' => 'membership_packages',
							'std' => '',
							'callback' => 'NooAgent::render_metabox_fields'
						)
					)
				);

				$helper->add_meta_box($meta_box);
			}

			// User metabox
			$meta_box = array(
				'id'           => "{$prefix}_meta_box_user",
				'title'        => __( 'Login Information', NOO_TEXT_DOMAIN ),
				'context'      => 'side',
				'priority'     => 'default',
				'description'  => __( 'Manage Login Information of this agent', NOO_TEXT_DOMAIN ),
				'fields'       => array(
					array(
						'id' => '_user_edit',
						'label' => ( self::has_associated_user( get_the_ID() ) ? __( 'Edit Login Info', NOO_TEXT_DOMAIN ) : __( 'Create a Login Account', NOO_TEXT_DOMAIN) ),
						'type' => 'checkbox',
						'std'  => 'off',
						'child-fields' => array(
							'on'   => '_user_username,_user_password'
						)
					),
					array(
						'id' => '_user_username',
						'label' => __( 'User Name', NOO_TEXT_DOMAIN ),
						'type' => 'username',
						'std' => '',
						'callback' => 'NooAgent::render_metabox_fields'
					),
					array(
						'id' => '_user_password',
						'label' => __( 'Password', NOO_TEXT_DOMAIN ),
						'type' => 'user_password',
						'std' => '',
						'callback' => 'NooAgent::render_metabox_fields'
					)
				),
			);

			$helper->add_meta_box($meta_box);
		}

		public function custom_enter_title( $input ) {
			global $post_type;

			if ( self::AGENT_POST_TYPE == $post_type )
				return __( 'Agent Name', NOO_TEXT_DOMAIN );

			return $input;
		}

		public function save_post( $post_id, $post ) {
			if(!is_object($post) || !isset($post->post_type)) {
				return;
			}

			// Check if it's noo_agent
			if($post->post_type != self::AGENT_POST_TYPE){
				return;    
			}
			
			if( !isset( $_POST['noo_meta_boxes'] ) ) {
				return;
			}

			$noo_meta_boxes = $_POST['noo_meta_boxes'];

			$prefix = self::AGENT_META_PREFIX;

			// Email is required
			if( !isset($noo_meta_boxes["{$prefix}_email"]) ){
				return;
			}

			$email = $noo_meta_boxes["{$prefix}_email"];
			$user_edit = isset($noo_meta_boxes['_user_edit'] ) && $noo_meta_boxes['_user_edit'] == 'on';

			if( self::has_associated_user( $post_id ) ) {

				$associated_user_id = noo_get_post_meta( $post_id, '_associated_user_id' );

				// Update user
				$userdata = array();
				$user = new WP_User( $associated_user_id );
				if( $user->user_email != $email && !email_exists( $email ) ) {
					$userdata['user_email'] = $email;
				}

				if( $user_edit ) {
					if( isset($_POST['_user_password']) && !empty($_POST['_user_password']) ) {
						$userdata['user_pass'] = $_POST['_user_password'];
					}
				}

				if( !empty($userdata) ) {
					$userdata['ID'] = $associated_user_id;
					wp_update_user( $userdata );
				}
			} elseif( $user_edit ) {

				// Insert new user
				if(!isset($_POST['_user_username']) || empty($_POST['_user_username'])) {
					return;
				}

				$pass = isset($_POST['_user_password']) && !empty($_POST['_user_password']) ? $_POST['_user_password'] : null;
				$arr = explode(' ',trim($post->post_title));
				$first_name = array_shift($arr);
				$last_name = implode($arr);

				$userdata = array(
					'user_login' => $_POST['_user_username'],
					'user_email' => $email,
					'user_pass' => $pass,
					'first_name' => $first_name,
					'last_name' => $last_name
				);

				wp_insert_user( $userdata );
			}

			// Membership
			if( isset($noo_meta_boxes['_membership_package']) ) {
				self::set_agent_membership( $post_id, intval( $noo_meta_boxes['_membership_package']));
			}
		}

		public function agent_user_register( $user_id ) {
			$user = new WP_User( $user_id );
			$agent = array(
				'post_title'	=> $user->display_name,
				'post_status'	=> 'publish', 
				'post_type'     => self::AGENT_POST_TYPE
			);

			$prefix = '_noo_agent';

			$agent_id =  wp_insert_post( $agent );  
			update_post_meta( $agent_id, '_associated_user_id', $user_id);
			update_post_meta( $agent_id, "{$prefix}_email", $user->user_email);

			// Membership
			if( NooMembership::is_membership() ) {
				$freemium_enabled = (bool) ( esc_attr( get_option( 'noo_membership_freemium' ) ) );
				$freemium_listing_num = $freemium_enabled ? intval( get_option( 'noo_membership_freemium_listing_num' ) ) : 0;
				$freemium_listing_unlimited = $freemium_enabled ? (bool) get_option( 'noo_membership_freemium_listing_unlimited' ) : false;
				$freemium_featured_num = $freemium_enabled ? intval( get_option( 'noo_membership_freemium_featured_num' ) ) : 0;

				$listing_remain = $freemium_listing_unlimited ? -1 : $freemium_featured_num;
				update_post_meta( $agent_id, '_listing_remain', $listing_remain );
				update_post_meta( $agent_id, '_featured_remain', $freemium_featured_num );
			}
		}

		public function edit_user_profile( $user ) {
			?>
			<!-- <input type="hidden" id="_associated_agent_id" name="_associated_agent_id" value="<?php echo get_user_meta( $user->ID, '_associated_agent_id', true ); ?>"/> -->
			<?php
		}

		public function edit_user_profile_update( $user_id ) {
			$user = new WP_User( $user_id );
			$agent_id = get_user_meta( $user_id, '_associated_agent_id', true );

			// Update email so that user and agent always have the same email
			$agent_email = noo_get_post_meta( $agent_id, '_noo_agent_email', '' );
			$user_email = isset($_POST['email']) ? wp_kses( $_POST['email'], array() ) : $user->user_email;
			if( $agent_email != $user_email ) {
				update_post_meta( $agent_id, '_noo_agent_email', $user_email );
			}
		}

		public function transition_post_status( $new_status, $old_status, $post ) {
			if( $post->post_type !== 'noo_property' )
				return;

			if( get_option('noo_membership_type', 'membership') == 'none' )
				return;

			if( $new_status == 'publish' && $old_status != 'publish' ) {
				$agent_id = noo_get_post_meta( $post->ID, '_agent_responsible', '' );
				if( empty( $agent_id ) )
					return;

				$user_id = noo_get_post_meta( $agent_id, '_associated_user_id', '' );
				$user = get_user_by('id', $user_id);
				if( empty( $user ) )
					return;

				$user_email = $user->user_email;
				$site_name = get_option('blogname');
				$property_title = $post->post_title;
				$property_link = get_permalink( $post->ID );
				if( $user->roles[0] == 'subscriber'){
					$headers = 'From: No Reply <noreply@'.$_SERVER['HTTP_HOST'].'>' . "\r\n";
					$message .= sprintf( __("Congrats! You submitted %s on %s and it has been approved. You can check it here: %s", NOO_TEXT_DOMAIN), $property_title, $site_name, $property_link) . "\r\n\r\n";
					wp_mail($user_email,
						sprintf(__('[%s] Property submission approved',NOO_TEXT_DOMAIN), $site_name),
						$message,
						$headers);
				}
			}
		}

		public function hide_IDX_page_template() {
			global $pagenow;
			if ( !in_array( $pagenow, array( 'post-new.php', 'post.php') ) || get_post_type() != 'page' ) {
				return false;
			}

			?>
			<script type="text/javascript">
				(function($){
					$(document).ready(function(){
						$('#page_template option[value="page-dsIDX.php"]').remove();
					})
				})(jQuery)
			</script>
			<?php 
		}

		public function hide_admin_bar_front($user_ID) {
			update_user_meta( $user_ID, 'show_admin_bar_front', 'false' );
		}

		public function stop_admin_bar_render() {
			global $wp_admin_bar;
			$wp_admin_bar->remove_menu('edit-profile');
			$wp_admin_bar->remove_menu('user-actions');
		}

		public function stop_admin_profile() {
			global $pagenow;

			if( defined('IS_PROFILE_PAGE') && IS_PROFILE_PAGE === true ) {
				$dashboard_link = noo_get_page_link_by_template( 'agent_dashboard_profile.php' );
				wp_die( sprintf( __('Please access your profile from <a href="%s">Site interface</a>.', NOO_TEXT_DOMAIN ), $dashboard_link ), '', array( 'response' => 403 ) );
			}

			if($pagenow=='user-edit.php'){
				$dashboard_link = noo_get_page_link_by_template( 'agent_dashboard_profile.php' );
				wp_die( sprintf( __('Please access your profile from <a href="%s">Site interface</a>.', NOO_TEXT_DOMAIN ), $dashboard_link ), '', array( 'response' => 403 ) );
			}
		}

		public function enqueue_style_script( $hook ) {
			global $post;

			if ( $hook == 'post-new.php' || $hook == 'post.php' ) {
				if ( self::AGENT_POST_TYPE === $post->post_type || NooMembership::MEMBERSHIP_POST_TYPE === $post->post_type ) {
					wp_register_script( 'noo-agent-admin', NOO_FRAMEWORK_ADMIN_URI . '/assets/js/noo-agent-admin.js', null, null, true );
					wp_enqueue_script( 'noo-agent-admin' );
				}
			}

			// wp_register_style( 'noo-agent-admin', NOO_FRAMEWORK_ADMIN_URI . '/assets/css/noo-agent-admin.css' );
			// wp_enqueue_style( 'noo-agent-admin' );
		}

		public function ajax_update_profile() {
			if ( !is_user_logged_in() ) {
				$this->_ajax_exit( __( 'You are not logged in yet', NOO_TEXT_DOMAIN ) );
			}

			if( !check_ajax_referer('submit_profile', '_noo_profile_nonce', false) ) {
				$this->_ajax_exit( __( 'Your session is expired or you submitted an invalid form.', NOO_TEXT_DOMAIN ) );
			}

			global $current_user;
			get_currentuserinfo();

			$user_id		= $current_user->ID;
			$submit_user_id	= intval( $_POST['user_id'] );
			if( $user_id != $submit_user_id ) {
				$this->_ajax_exit( __('There\'s an unknown error. Please retry or contact Administrator.', NOO_TEXT_DOMAIN) );
			}

			$agent_id		= get_user_meta($user_id, '_associated_agent_id', true );
			$submit_agent_id	= intval( $_POST['agent_id'] );
			if( empty( $agent_id ) && empty( $submit_agent_id ) ) {
				$agent_id = self::create_agent_from_user( $user_id );
				if(empty($agent_id))
					$this->_ajax_exit( __('There\'s an unknown error when creating an agent profile for your account. Please resubmit your property or contact Administrator.', NOO_TEXT_DOMAIN) );
			} elseif( $agent_id != $submit_agent_id ) {
				$this->_ajax_exit( __('There\'s an unknown error. Please resubmit your property or contact Administrator.', NOO_TEXT_DOMAIN) );
			}

			$no_html	= array();

			$title			= wp_kses( $_POST['title'], $no_html );
			$position		= wp_kses( $_POST['position'], $no_html );
			$email			= wp_kses( $_POST['email'], $no_html );
			$phone			= wp_kses( $_POST['phone'], $no_html );
			$mobile			= wp_kses( $_POST['mobile'], $no_html );
			$skype			= wp_kses( $_POST['skype'], $no_html );
			$desc			= wp_kses( $_POST['desc'], $no_html );
			$avatar			= wp_kses( $_POST['avatar'], $no_html );

			$facebook		= wp_kses( $_POST['facebook'], $no_html );
			$twitter		= wp_kses( $_POST['twitter'], $no_html );
			$google_plus	= wp_kses( $_POST['google_plus'], $no_html );
			$linkedin		= wp_kses( $_POST['linkedin'], $no_html );
			$pinterest		= wp_kses( $_POST['pinterest'], $no_html );

			// Error data checking
			if( empty($title) ) {
				$this->_ajax_exit( __('Agent need a name', NOO_TEXT_DOMAIN) );
			}
			if( empty($email) ) {
				$this->_ajax_exit( __('Agent need a valid email', NOO_TEXT_DOMAIN) );
			}

			$agent = array(
				'ID'			=> $agent_id,
				'post_title'	=> $title,
				'post_content'	=> $desc
			);

			if( 0 === wp_update_post( $agent ) ) {
				$this->_ajax_exit( __('There\'s an unknown error when updating your profile. Please retry or contact Administrator.', NOO_TEXT_DOMAIN) );
			}

			set_post_thumbnail( $agent_id, $avatar );

			$prefix = self::AGENT_META_PREFIX;
			update_post_meta( $agent_id, "{$prefix}_position", $position );
			update_post_meta( $agent_id, "{$prefix}_email", $email );
			update_post_meta( $agent_id, "{$prefix}_phone", $phone );
			update_post_meta( $agent_id, "{$prefix}_email", $email );
			update_post_meta( $agent_id, "{$prefix}_mobile", $mobile );
			update_post_meta( $agent_id, "{$prefix}_skype", $skype );

			update_post_meta( $agent_id, "{$prefix}_avatar", $avatar );

			update_post_meta( $agent_id, "{$prefix}_facebook", $facebook );
			update_post_meta( $agent_id, "{$prefix}_twitter", $twitter );
			update_post_meta( $agent_id, "{$prefix}_pinterest", $pinterest );

			$this->_ajax_exit( __( 'Your profile has been updated successfully', NOO_TEXT_DOMAIN ), true );
		}

		public function ajax_change_password() {
			if ( !is_user_logged_in() ) {
				$this->_ajax_exit( __( 'You are not logged in yet', NOO_TEXT_DOMAIN ) );
			}

			if( !check_ajax_referer('submit_profile_password', '_noo_profile_password_nonce', false) ) {
				$this->_ajax_exit( __( 'Your session is expired or you submitted an invalid form.', NOO_TEXT_DOMAIN ) );
			}

			global $current_user;
			get_currentuserinfo();

			$user_id		= $current_user->ID;
			$submit_user_id	= intval( $_POST['user_id'] );
			if( $user_id != $submit_user_id ) {
				$this->_ajax_exit( __('There\'s an unknown error. Please retry or contact Administrator.', NOO_TEXT_DOMAIN) );
			}

			$no_html			= array();
			$old_pass			= wp_kses( $_POST['old_pass'] ,$no_html) ;
			$new_pass			= wp_kses( $_POST['new_pass'] ,$no_html) ;
			$new_pass_confirm	= wp_kses( $_POST['new_pass_confirm'] ,$no_html) ;

			if( empty( $new_pass ) || empty( $new_pass_confirm ) ){
				$this->_ajax_exit( __('The new password is blank.', NOO_TEXT_DOMAIN) );
			}

			if($new_pass != $new_pass_confirm){
				$this->_ajax_exit( __('Passwords do not match.', NOO_TEXT_DOMAIN) );
			}

			$user = get_user_by( 'id', $user_id );
			if ( $user && wp_check_password( $old_pass, $user->data->user_pass, $user->ID) ){
				wp_set_password( $new_pass, $user->ID );
				$response['success'] = true;
				$this->_ajax_exit( __('Password updated successfully', NOO_TEXT_DOMAIN), true );
			} else {
				$this->_ajax_exit( __('Old Password is not correct', NOO_TEXT_DOMAIN) );
			}

			$this->_ajax_exit();
		}

		public function ajax_status_property() {
			if ( !is_user_logged_in() ) {
				$this->_ajax_exit( __( 'You are not logged in yet', NOO_TEXT_DOMAIN ) );
			}

			if( !check_ajax_referer('noo_status_property', 'nonce', false) ) {
				$this->_ajax_exit( __( 'Your session is expired or your action is invalid.', NOO_TEXT_DOMAIN ) );
			}

			$user_id = get_current_user_id();
			$agent_id			= get_user_meta($user_id, '_associated_agent_id',true );

			// Agent checking
			$submit_agent_id	= intval( $_POST['agent_id'] );
			if( $agent_id != $submit_agent_id ) {
				$this->_ajax_exit( __('There\'s an unknown error. Please retry or contact Administrator.', NOO_TEXT_DOMAIN) );
			}

			$prop_id	= intval( $_POST['prop_id'] );

			if( empty( $agent_id ) || empty( $prop_id ) ) {
				$this->_ajax_exit( __('There\'s an unknown error. Please retry or contact Administrator.', NOO_TEXT_DOMAIN) );
			}

			if( !NooAgent::can_edit( $agent_id ) || !NooAgent::is_owner( $agent_id, $prop_id ) ) {
				$this->_ajax_exit( __('You don\'t have the rights to update this property', NOO_TEXT_DOMAIN) );
			}

			// update status
			$default_sold_status = get_option('default_property_status');
			if( !wp_set_post_terms( $prop_id, $default_sold_status, 'property_status' ) ) {
				$this->_ajax_exit( __('There\'s an unknown error. Please retry or contact Administrator.', NOO_TEXT_DOMAIN) );
			}

			$this->_ajax_exit( __('Your property has been updated successfully', NOO_TEXT_DOMAIN), true );
		}

		public function ajax_featured_property() {
			if ( !is_user_logged_in() ) {
				$this->_ajax_exit( __( 'You are not logged in yet', NOO_TEXT_DOMAIN ) );
			}

			if( !check_ajax_referer('noo_featured_property', 'nonce', false) ) {
				$this->_ajax_exit( __( 'Your session is expired or your action is invalid.', NOO_TEXT_DOMAIN ) );
			}

			$user_id = get_current_user_id();
			$agent_id			= get_user_meta($user_id, '_associated_agent_id',true );

			// Agent checking
			$submit_agent_id	= intval( $_POST['agent_id'] );
			if( $agent_id != $submit_agent_id ) {
				$this->_ajax_exit( __('There\'s an unknown error. Please retry or contact Administrator.', NOO_TEXT_DOMAIN) );
			}

			$prop_id	= intval( $_POST['prop_id'] );

			if( empty( $agent_id ) || empty( $prop_id ) ) {
				$this->_ajax_exit( __('There\'s an unknown error. Please retry or contact Administrator.', NOO_TEXT_DOMAIN) );
			}

			if( !NooAgent::can_set_featured( $agent_id ) || ( get_post_status( $prop_id ) != 'publish' ) ) {
				$this->_ajax_exit( __('You don\'t have the rights to update this property', NOO_TEXT_DOMAIN) );
			}

			$featured = noo_get_post_meta( $prop_id, '_featured', '' ) == 'yes';
			if( $featured ) {
				$this->_ajax_exit( __('This item is already a featured Property.', NOO_TEXT_DOMAIN) );
			}

			if( !update_post_meta( $prop_id, '_featured', 'yes' ) ) {
				$this->_ajax_exit( __('There\'s an unknown error. Please retry or contact Administrator.', NOO_TEXT_DOMAIN) );
			}

			NooAgent::decrease_featured_remain( $agent_id );
			$this->_ajax_exit( __('Your property has been updated successfully', NOO_TEXT_DOMAIN), true );
		}

		public function ajax_delete_property() {
			if ( !is_user_logged_in() ) {
				$this->_ajax_exit( __( 'You are not logged in yet', NOO_TEXT_DOMAIN ) );
			}

			if( !check_ajax_referer('noo_delete_property', 'nonce', false) ) {
				$this->_ajax_exit( __( 'Your session is expired or your action is invalid.', NOO_TEXT_DOMAIN ) );
			}

			$user_id = get_current_user_id();
			$agent_id			= get_user_meta($user_id, '_associated_agent_id',true );

			// Agent checking
			$submit_agent_id	= intval( $_POST['agent_id'] );
			if( $agent_id != $submit_agent_id ) {
				$this->_ajax_exit( __('There\'s an unknown error. Please retry or contact Administrator.', NOO_TEXT_DOMAIN) );
			}
			
			$prop_id	= intval( $_POST['prop_id'] );

			if( empty( $agent_id ) || empty( $prop_id ) ) {
				$this->_ajax_exit( __('There\'s an unknown error. Please retry or contact Administrator.', NOO_TEXT_DOMAIN) );
			}


			if( !NooAgent::can_edit( $agent_id ) || !NooAgent::is_owner( $agent_id, $prop_id ) ) {
				$this->_ajax_exit( __('You don\'t have the rights to delete this property', NOO_TEXT_DOMAIN) );
			}

			// delete attachments
			$arguments = array(
				'numberposts' => -1,
				'post_type' => 'attachment',
				'post_parent' => $prop_id,
				'post_status' => null,
				'exclude' => get_post_thumbnail_id(),
				'orderby' => 'menu_order',
				'order' => 'ASC'
			);
			$post_attachments = get_posts($arguments);

			foreach ($post_attachments as $attachment) {
				wp_delete_post($attachment->ID);
			}

			if( !wp_delete_post( $prop_id ) ) {
				$this->_ajax_exit( __('There\'s an unknown error. Please retry or contact Administrator.', NOO_TEXT_DOMAIN) );
			}

			$this->_ajax_exit( __('Your property has been deleted successfully', NOO_TEXT_DOMAIN), true );
		}

		public function ajax_membership_payment() {
			if ( !is_user_logged_in() ) {
				$this->_ajax_exit( __( 'You are not logged in yet', NOO_TEXT_DOMAIN ) );
			}

			// Check nonce
			if ( !isset($_POST['_noo_membership_nonce']) || !wp_verify_nonce($_POST['_noo_membership_nonce'],'noo_subscription') ){
				$this->_ajax_exit( __('Sorry, your session is expired or you submitted an invalid form.', NOO_TEXT_DOMAIN) );
			}

			$user_id	= get_current_user_id();
			$agent_id	= get_user_meta($user_id, '_associated_agent_id',true );

			// Agent checking
			$submit_agent_id	= isset( $_POST['agent_id'] ) ? intval( $_POST['agent_id'] ) : '';
			if( empty( $agent_id ) && empty( $submit_agent_id ) ) {
				$agent_id = NooAgent::create_agent_from_user( $user_id );
				if( !$agent_id ) {
					$this->_ajax_exit( __('There\'s an unknown error. Please retry or contact Administrator.', NOO_TEXT_DOMAIN) );
				}
			} elseif( !empty( $submit_agent_id ) && $agent_id != $submit_agent_id ) {
				$this->_ajax_exit( __('There\'s an unknown error. Please retry or contact Administrator.', NOO_TEXT_DOMAIN) );
			}

			$package_id			= intval( $_POST['package_id'] );
			if( empty( $agent_id ) || empty( $package_id ) ) {
				$this->_ajax_exit( __('Please select a Membership Package', NOO_TEXT_DOMAIN) );
			}

			$is_recurring		= isset( $_POST['recurring_payment'] ) ? (bool)( $_POST['recurring_payment'] ) : false;

			$recurring_time		= isset( $_POST['recurring_time'] ) ? intval( $_POST['recurring_time'] ) : 0;
			$paypal_url = NooAgent::getMembershipPaymentURL( $agent_id, $package_id, $is_recurring, $recurring_time );
			if( !$paypal_url ) {
				$this->_ajax_exit( __('There\'s an unknown error. Please retry or contact Administrator.', NOO_TEXT_DOMAIN) );
			}

			$this->_ajax_exit( $paypal_url, true );
		}

		public function ajax_listing_payment() {
			if ( !is_user_logged_in() ) {
				$this->_ajax_exit( __( 'You are not logged in yet', NOO_TEXT_DOMAIN ) );
			}

			if( !check_ajax_referer('noo_listing_payment', '_noo_listing_nonce', false) ) {
				$this->_ajax_exit( __( 'Your session is expired or your action is invalid.', NOO_TEXT_DOMAIN ) );
			}

			$user_id	= get_current_user_id();
			$agent_id	= get_user_meta($user_id, '_associated_agent_id',true );

			// Agent checking
			$submit_agent_id	= intval( $_POST['agent_id'] );
			if( $agent_id != $submit_agent_id ) {
				$this->_ajax_exit( __('There\'s an unknown error. Please retry or contact Administrator.', NOO_TEXT_DOMAIN) );
			}

			$prop_id	= intval( $_POST['prop_id'] );

			if( empty( $agent_id ) || empty( $prop_id ) ) {
				$this->_ajax_exit( __('There\'s an unknown error. Please retry or contact Administrator.', NOO_TEXT_DOMAIN) );
			}

			if( !NooAgent::is_owner( $agent_id, $prop_id ) ) {
				$this->_ajax_exit( __('This is not your property', NOO_TEXT_DOMAIN) );
			}

			$paid_listing	= (bool) esc_attr( noo_get_post_meta( $prop_id, '_paid_listing', false ) );
			$featured		= esc_attr( noo_get_post_meta( $prop_id, '_featured', '' ) ) == 'yes';

			if( !NooMembership::is_submission() || ( $paid_listing && $featured ) ) {
				$this->_ajax_exit( __('There\'s an unknown error. Please retry or contact Administrator.', NOO_TEXT_DOMAIN) );
			}

			$listing_price = floatval( esc_attr( get_option('noo_submission_listing_price') ) );
			$featured_price = floatval( esc_attr( get_option('noo_submission_featured_price') ) );

			$submit_featured = isset( $_POST['submission_featured'] ) ? (bool) $_POST['submission_featured'] : false;
			if( $paid_listing && !$submit_featured ) {
				$this->_ajax_exit( __('There\'s an unknown error. Please retry or contact Administrator.', NOO_TEXT_DOMAIN) );
			}

			$total_price = 0;
			if( !$paid_listing ) $total_price += $listing_price;
			if( $submit_featured && !$featured ) $total_price += $featured_price;

			$paypal_url = self::getListingPaymentURL( $agent_id, $prop_id,  $total_price, !$paid_listing, $submit_featured && !$featured );

			if( !$paypal_url ) {
				$this->_ajax_exit( __('There\'s an unknown error. Please retry or contact Administrator.', NOO_TEXT_DOMAIN) );
			} else {
				$this->_ajax_exit( $paypal_url, true );
			}
		}

		public function ajax_login() {
			if( !check_ajax_referer('noo_ajax_login', '_noo_login_nonce', false) ) {
				$this->_ajax_exit( __( 'Invalid form.', NOO_TEXT_DOMAIN ) );
			}

			$no_html     = array();
			$redirect_to = wp_kses ( $_POST['redirect_to'], $no_html);

			if ( is_user_logged_in() ) {
				$this->_ajax_exit( __( 'You\'ve already logged in. Redirecting ...', NOO_TEXT_DOMAIN ), true, $redirect_to );
			}

			$login_user  = wp_kses ( $_POST['log'], $no_html );
			$login_pwd   = wp_kses ( $_POST['pwd'], $no_html);
			$remember    = (bool) wp_kses ( $_POST['rememberme'], $no_html);

			if ( empty($login_user) ){
				$this->_ajax_exit( __( 'Username is empty!.', NOO_TEXT_DOMAIN ) );
			}
			if ( empty($login_pwd) ){
				$this->_ajax_exit( __( 'Password is empty!.', NOO_TEXT_DOMAIN ) );
			}

			wp_clear_auth_cookie();
			$info                   = array();
			$info['user_login']     = $login_user;
			$info['user_password']  = $login_pwd;
			$info['remember']       = $remember;
			$user_signon            = wp_signon( $info, true );

			if ( is_wp_error($user_signon) ){
				$this->_ajax_exit( __('Wrong username or password!', NOO_TEXT_DOMAIN) );       
			} else {
				global $current_user;
				wp_set_current_user($user_signon->ID);
				do_action('set_current_user');
				$current_user = wp_get_current_user();
			}

			$this->_ajax_exit(__( 'Logged in successfully. Redirecting ...', NOO_TEXT_DOMAIN ), true, $redirect_to );
		}

		public function ajax_register() {
			if( !check_ajax_referer('noo_ajax_register', '_noo_register_nonce', false) ) {
				$this->_ajax_exit( __( 'Invalid form.', NOO_TEXT_DOMAIN ) );
			}

			if ( is_user_logged_in() ) {
				$this->_ajax_exit( __( 'You\'ve already logged in.', NOO_TEXT_DOMAIN ) );
			}

			$no_html     = array();
			$user_login	= wp_kses ( $_POST['user_login'], $no_html );
			$user_email	= wp_kses ( $_POST['user_email'], $no_html);

			$sanitized_user_login = sanitize_user( $user_login );

			// Check the username
			if ( $sanitized_user_login == '' ) {
				$this->_ajax_exit( __( 'Please enter a username.', NOO_TEXT_DOMAIN ) );
			}

			if ( ! validate_username( $user_login ) ) {
				$this->_ajax_exit(  __( 'This username is invalid because it uses illegal characters. Please enter a valid username.', NOO_TEXT_DOMAIN ) );
			}

			if ( username_exists( $sanitized_user_login ) ) {
				$this->_ajax_exit( __( 'This username is already registered. Please choose another one.', NOO_TEXT_DOMAIN ) );
			}

			if ( empty($user_email) ) {
				$this->_ajax_exit( __( 'Please type your e-mail address.', NOO_TEXT_DOMAIN ) );
			}

			if ( !is_email( $user_email ) ) {
				$this->_ajax_exit( __( 'The email address isn\'t correct.', NOO_TEXT_DOMAIN ) );
			}

			if ( email_exists( $user_email ) ) {
				$this->_ajax_exit( __( 'This email is already registered, please choose another one.', NOO_TEXT_DOMAIN ) );
			}

			$user_pass = wp_generate_password( 12, false );
			$user_id = wp_create_user( $sanitized_user_login, $user_pass, $user_email );
			if ( ! $user_id || is_wp_error( $user_id ) ) {
				$this->_ajax_exit( __( 'Couldn\'t register you... please contact Administrator!', NOO_TEXT_DOMAIN ) );
			}

			update_user_option( $user_id, 'default_password_nag', true, true ); //Set up the Password change nag.

			// Mimic the default email of Wordpress
			$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

			$message  = sprintf(__('Username: %s', NOO_TEXT_DOMAIN), $sanitized_user_login) . "\r\n";
			$message .= sprintf(__('Password: %s', NOO_TEXT_DOMAIN), $user_pass) . "\r\n";
			$message .= self::get_login_url() . "\r\n";

			wp_mail($user_email, sprintf(__('[%s] Your username and password', NOO_TEXT_DOMAIN), $blogname), $message);

			$this->_ajax_exit(__( 'An email with the generated password was sent!', NOO_TEXT_DOMAIN ), true );
		}

		private function _ajax_exit( $message = '', $success = false, $redirect = '' ) {
			$response = array(
				'success' => $success,
				'message' => $message,
			);

			if( !empty( $redirect ) ) {
				$response['redirect'] = $redirect;
			}

			echo json_encode($response);
			exit();
		}

		public function settings_sub_menu() {
			//create new setting menu
			add_submenu_page('edit.php?post_type=noo_agent', __('Agents &amp; Membership Settings', NOO_TEXT_DOMAIN), __('Settings', NOO_TEXT_DOMAIN), 'administrator', 'noo-agent-settings', array (&$this, 'agent_settings'));

			//call register settings function
			add_action( 'admin_init', array(&$this,'register_settings') );
		}

		public function register_settings() {
			//register our settings
			register_setting( 'noo-agent-settings', 'noo_agent_archive_slug' );
			register_setting( 'noo-agent-settings', 'noo_agent_must_has_property' );
			register_setting( 'noo-agent-settings', 'noo_membership_type' );
			register_setting( 'noo-agent-settings', 'noo_membership_freemium' );
			register_setting( 'noo-agent-settings', 'noo_membership_freemium_listing_num' );
			register_setting( 'noo-agent-settings', 'noo_membership_freemium_listing_unlimited' );
			register_setting( 'noo-agent-settings', 'noo_membership_freemium_featured_num' );
			register_setting( 'noo-agent-settings', 'noo_membership_page' );
			register_setting( 'noo-agent-settings', 'noo_submission_listing_price' );
			register_setting( 'noo-agent-settings', 'noo_submission_featured_price' );
			register_setting( 'noo-agent-settings', 'noo_onetime_price' );
			register_setting( 'noo-agent-settings', 'noo_admin_approve' );
			register_setting( 'noo-agent-settings', 'noo_login_page' );
		}

		public function agent_settings() {
			?>
			<div class="wrap">
			<h2><?php _e('Agents &amp; Membership Settings', NOO_TEXT_DOMAIN); ?></h2>

				<form method="post" action="options.php">
					<?php settings_fields( 'noo-agent-settings' ); ?>
					<?php do_settings_sections( 'noo-agent-settings' ); ?>
					<?php
						$noo_membership_type = NooMembership::get_membership_type();
					?>
					<table class="form-table">
						<tr valign="top" class="noo_agent_archive_slug">
							<th scope="row"><label for="noo_agent_archive_slug"><?php _e( 'Agent Archive base (slug)', NOO_TEXT_DOMAIN ); ?></label></th>
							<td>
								<input name="noo_agent_archive_slug" type="text" class="regular-text code" value="<?php echo esc_attr( get_option('noo_agent_archive_slug', '') ); ?>" placeholder="<?php echo _x('agents', 'slug', NOO_TEXT_DOMAIN) ?>" />
								<p><small><?php echo sprintf( __( 'If you made change on this opiton, you will have to go to <a href="%s" target="_blank">Permalink Settings</a><br/> and click "Save Changes" button for reseting WordPress link structure.', NOO_TEXT_DOMAIN ), admin_url( '/options-permalink.php' ) ); ?></small></p>
							</td>
						</tr>
						<tr valign="top" class="noo_agent_must_has_property">
							<th scope="row"><label for="noo_agent_must_has_property"><?php _e( 'Only Show Agent with Property', NOO_TEXT_DOMAIN ); ?></label></th>
							<td>
								<input name="noo_agent_must_has_property" type="hidden" value="" />
								<input id="noo_agent_must_has_property" name="noo_agent_must_has_property" type="checkbox" <?php checked( get_option('noo_agent_must_has_property'), '1' ); ?> value="1" />
								<p><small><?php _e( 'If selected, only agent with at least one property can be show on Agent listing.', NOO_TEXT_DOMAIN ); ?></small></p>
							</td>
						</tr>
					</table>
					<hr/>
					<table class="form-table">
						<tr valign="top" class="noo_membership_type">
							<th scope="row"><?php _e( 'Membership Type', NOO_TEXT_DOMAIN ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><?php _e( 'Membership Type', NOO_TEXT_DOMAIN ); ?></legend>
									<label title="none">
										<input type="radio" name="noo_membership_type" value="none" <?php checked( $noo_membership_type, 'none'); ?>>
										<span><?php _e( 'No Membership (Agents created by Admin can still submit Property)', NOO_TEXT_DOMAIN); ?></span>
									</label>
									<br/>
									<label title="free">
										<input type="radio" name="noo_membership_type" value="free" <?php checked( $noo_membership_type, 'free'); ?>>
										<span><?php _e( 'Free for all Users', NOO_TEXT_DOMAIN); ?></span>
									</label>
									<br/>
									<label title="membership">
										<input type="radio" name="noo_membership_type" value="membership" <?php checked( $noo_membership_type, 'membership'); ?>>
										<span><?php _e( 'Membership Packages', NOO_TEXT_DOMAIN); ?></span>
									</label>
									<br/>
									<label title="submission">
										<input type="radio" name="noo_membership_type" value="submission" <?php checked( $noo_membership_type, 'submission'); ?>>
										<span><?php _e( 'Pay per Submission', NOO_TEXT_DOMAIN); ?></span>
									</label>
									<br/>
								</fieldset>
							</td>
						</tr>
						<tr valign="top" class="noo_membership_type-child noo_membership_type-membership-child noo_membership_freemium">
							<th scope="row"><label for="noo_membership_freemium"><?php _e( 'Enable Freemium Membership', NOO_TEXT_DOMAIN ); ?></label></th>
							<td><input type="checkbox" name="noo_membership_freemium" <?php checked( get_option('noo_membership_freemium', false) );?> value="1" /></td>
						</tr>
						<tr valign="top" class="noo_membership_freemium-child">
							<th scope="row"><label for="noo_membership_freemium_listing_num"><?php _e( 'Number of Free Listing', NOO_TEXT_DOMAIN ); ?></label></th>
							<td>
								<input type="text" name="noo_membership_freemium_listing_num" value="<?php echo esc_attr( get_option('noo_membership_freemium_listing_num', '1') ); ?>" <?php disabled( get_option('noo_membership_freemium_listing_unlimited', false) ); ?> />
								<input type="checkbox" name="noo_membership_freemium_listing_unlimited" <?php checked( get_option('noo_membership_freemium_listing_unlimited', false) ); ?> value="1" />
								<label for="noo_membership_freemium_listing_unlimited"><?php _e( 'Unlimited Listing?', NOO_TEXT_DOMAIN ); ?></label>
							</td>
						</tr>
						<tr valign="top" class="noo_membership_freemium-child">
							<th scope="row"><label for="noo_membership_freemium_featured_num"><?php _e( 'Number of Free Featured Properties', NOO_TEXT_DOMAIN ); ?></label></th>
							<td>
								<input type="text" name="noo_membership_freemium_featured_num" value="<?php echo esc_attr( get_option('noo_membership_freemium_featured_num', '0') ); ?>" />
							</td>
						</tr>
						<tr valign="top" class="noo_membership_type-child noo_membership_type-membership-child">
							<th scope="row"><label for="noo_membership_page"><?php _e( 'Membership listing page (Page with pricing table)', NOO_TEXT_DOMAIN ); ?></label></th>
							<td>
								<?php wp_dropdown_pages(
									array(
									'name'              => 'noo_membership_page',
									'echo'              => 1,
									'show_option_none'  => ' ',
									'option_none_value' => '',
									'selected'          => get_option('noo_membership_page', ''),
									)
								);
								?>
							</td>
						</tr>
						<tr valign="top" class="noo_membership_type-child noo_membership_type-submission-child">
							<th scope="row"><label for="noo_submission_listing_price"><?php _e( 'Price per Submission', NOO_TEXT_DOMAIN ); ?></label></th>
							<td>
								<input type="text" name="noo_submission_listing_price" value="<?php echo esc_attr( get_option('noo_submission_listing_price', '20.00') ); ?>" />
							</td>
						</tr>
						<tr valign="top" class="noo_membership_type-child noo_membership_type-submission-child">
							<th scope="row"><label for="noo_submission_featured_price"><?php _e( 'Price for Featured Property', NOO_TEXT_DOMAIN ); ?></label></th>
							<td>
								<input type="text" name="noo_submission_featured_price" value="<?php echo esc_attr( get_option('noo_submission_featured_price', '20.00') ); ?>" />
							</td>
						</tr>
						<tr valign="top" class="noo_membership_type-child noo_membership_type-onetime-child">
							<th scope="row"><label for="noo_onetime_price"><?php _e( 'Membership Price (onetime)', NOO_TEXT_DOMAIN ); ?></label></th>
							<td>
								<input type="text" name="noo_onetime_price" value="<?php echo esc_attr( get_option('noo_onetime_price', '20.00') ); ?>" />
							</td>
						</tr>
						<tr valign="top" class="noo_membership_type-child noo_membership_type-free-child noo_membership_type-membership-child noo_membership_type-submission-child noo_membership_type-onetime-child">
							<?php $noo_admin_approve = get_option('noo_admin_approve', 'add'); ?>
							<th scope="row"><label for="noo_admin_approve"><?php _e( 'Submitted Properties need approve from admin?', NOO_TEXT_DOMAIN ); ?></label></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><?php _e( 'Submitted Properties need approve from admin?', NOO_TEXT_DOMAIN ); ?></legend>
									<label title="all">
										<input type="radio" name="noo_admin_approve" value="all" <?php checked( $noo_admin_approve, 'all'); ?>>
										<span><?php _e( 'Yes, all newly added and edited properties', NOO_TEXT_DOMAIN); ?></span>
									</label>
									<br/>
									<label title="add">
										<input type="radio" name="noo_admin_approve" value="add" <?php checked( $noo_admin_approve, 'add'); ?>>
										<span><?php _e( 'Yes, but only newly submitted properties', NOO_TEXT_DOMAIN); ?></span>
									</label>
									<br/>
									<label title="none">
										<input type="radio" name="noo_admin_approve" value="none" <?php checked( $noo_admin_approve, 'none'); ?>>
										<span><?php _e( 'Don\'t need Admin approval', NOO_TEXT_DOMAIN); ?></span>
									</label>
								</fieldset>
							</td>
						</tr>
					</table>
					<?php if( get_option('users_can_register', true) ) : ?>
					<hr/>
					<table class="form-table noo_membership_type-child noo_membership_type-free-child noo_membership_type-membership-child noo_membership_type-submission-child noo_membership_type-onetime-child">
						<tr valign="top">
							<th scope="row"><label for="noo_login_page"><?php _e( 'Custom Login Page', NOO_TEXT_DOMAIN ); ?></label></th>
							<td>
								<?php wp_dropdown_pages(
									array(
									'name'              => 'noo_login_page',
									'echo'              => 1,
									'show_option_none'  => ' ',
									'option_none_value' => '',
									'selected'          => get_option('noo_login_page', ''),
									)
								);
								?>
							</td>
						</tr>
					</table>
					<?php else : ?>
					<h3 class="noo_membership_type-child noo_membership_type-free-child noo_membership_type-membership-child noo_membership_type-submission-child noo_membership_type-onetime-child"><?php echo sprintf( __( 'Registration is not enable on this site. Go to %s to Enable it.', NOO_TEXT_DOMAIN ), '<a href="' . admin_url('options-general.php') . '">' . __( 'General Setting', NOO_TEXT_DOMAIN ) . '</a>' ); ?></h3>
					<?php endif; ?>
					<?php submit_button(); ?>
				</form>
			</div>
			<script>
				jQuery( document ).ready( function ( $ ) {
					var $membership_type = $( '.noo_membership_type' );
					$membership_type.bind('toggle_children', function() {
						$this = $(this);
						if(!$this.is(':visible')) {
							$('.noo_membership_type-child').hide().trigger("toggle_children");

							return;
						}

						var value = $this.find( 'input[name=noo_membership_type]:checked' ).val();
						$('.noo_membership_type-child').hide().trigger('toggle_children');
						$('.noo_membership_type-' + value + '-child').show().trigger('toggle_children');
					});

					$membership_type.trigger('toggle_children');
					$membership_type.find('input').click( function() {
						$membership_type.trigger("toggle_children");
					});

					var $membership_freemium = $( '.noo_membership_freemium' );
					$membership_freemium.bind('toggle_children', function(){
						$this = $(this);
						if(!$this.is(':visible')) {
							$('.noo_membership_freemium-child').hide().trigger("toggle_children");

							return;
						}

						var value = $this.find( 'input[type=checkbox]' ).is(':checked');
						if( value ) {
							$('.noo_membership_freemium-child').show().trigger('toggle_children');
						} else {
							$('.noo_membership_freemium-child').hide().trigger('toggle_children');
						}
					});

					$membership_freemium.trigger('toggle_children');
					$membership_freemium.find('input').click( function() {
						$membership_freemium.trigger("toggle_children");
					});

					$('input[name=noo_membership_freemium_listing_unlimited]').click( function() {
						if( $(this).is(':checked') ) {
							$('input[name=noo_membership_freemium_listing_num]').prop('disabled', true);
						} else {
							$('input[name=noo_membership_freemium_listing_num]').prop('disabled', false);
						}
					});

				} );
			</script>
			<?php
		}

		public static function render_metabox_fields( $post, $id, $type, $meta, $std = null, $field = null ) {
			switch( $type ) {
				case 'agents':

					$value = $meta ? $meta : $std;
					$html = array();
					$html[] = '<select name="noo_meta_boxes[' . $id . ']" class="noo_agents_select" >';
					$html[] = '<option value=""' . selected( $value, '', false ) . '>'.__('- No Agent -', NOO_TEXT_DOMAIN).'</option>';

					$args = array(
						'post_type'     => self::AGENT_POST_TYPE,
						'posts_per_page' => -1,
						'post_status' => 'publish'
					);
					
					$agents = get_posts($args); //new WP_Query($args);
					if(!empty($agents)){
						foreach ($agents as $agent){
							$html[] ='<option value="'.$agent->ID.'"' . selected( $value, $agent->ID, false ) . '>'.$agent->post_title.'</option>';
						}
					}
					$html[] = '</select>';
					echo implode( "\n", $html);
					break;

				case 'packages':

					$value = $meta ? $meta : $std;
					$html = array();
					$html[] = '<select name="noo_meta_boxes[' . $id . ']" class="noo_packages_select" >';
					$html[] = '<option value=""' . selected( $value, '', false ) . '></option>';

					$args = array(
						'post_type'     => NooMembership::MEMBERSHIP_POST_TYPE,
						'posts_per_page' => -1,
						'post_status' => 'publish'
					);
					
					$packages = get_posts($args); //new WP_Query($args);
					if(!empty($packages)){
						foreach ($packages as $package){
							$html[] ='<option value="'.$package->ID.'"' . selected( $value, $package->ID, false ) . '>'.$package->post_title.'</option>';
						}
					}
					$html[] = '</select>';
					echo implode( "\n", $html);
					break;

				case 'username':
					$value = '';
					$disabled = '';
					
					if( self::has_associated_user( get_the_ID() ) ) {
						$associated_user_id = noo_get_post_meta( get_the_ID(), '_associated_user_id');
						$user = new WP_User( $associated_user_id );
						$value = ' value="' . $user->user_login . '"';
						$disabled = ' disabled="true"';
					}

					$value = empty( $value ) && ( $std != null && $std != '' ) ? ' placeholder="' . $std . '"' : $value;
					echo '<input id='.$id.' type="text" name="' . $id . '" ' . $value . $disabled . ' />';
					break;

				case 'user_password':
					$placeholder = self::has_associated_user( get_the_ID() ) ? __( 'Unchanged', NOO_TEXT_DOMAIN ) : '';
					echo '<input id='.$id.' type="password" name="' . $id . '" placeholder="' . $placeholder . '" />';
					break;

				case 'membership_packages':
					if( !NooMembership::is_membership() ) {
						return;
					}

					$value = $meta ? $meta : $std;
					$html = array();
					if( $value != '' ) {
						$html[] = '<p>' . __('If you change agent\'s package, all the package information will be reset.',NOO_TEXT_DOMAIN) . '</p>';
					}
					$html[] = '<select name="noo_meta_boxes[' . $id . ']" class="noo_package_select" >';
					if( get_option('noo_membership_freemium', true) ) {
						$html[] = '<option value=""' . selected( $value, '', false ) . '>'.__('Free Membership', NOO_TEXT_DOMAIN).'</option>';
					}

					$args = array(
						'post_type'     => NooMembership::MEMBERSHIP_POST_TYPE,
						'posts_per_page' => -1,
						'post_status' => 'publish'
					);
					$packages = get_posts($args);
					if(!empty($packages)){
						foreach ($packages as $package){
							$html[] ='<option value="'.$package->ID.'"' . selected( $value, $package->ID, false ) . '>'.$package->post_title.'</option>';
						}
					}

					$html[] = '</select>';

					$html[] = '<div id="noo-membership-packages-adder" class="noo-add-parent wp-hidden-children">';
					$html[] = '<h4> <a href="#noo-membership-packages-add" class="noo-add-toggle hide-if-no-js">';
					$html[] = __( '+ Add new Membership Package', NOO_TEXT_DOMAIN );
					$html[] = '</a></h4>';
					$html[] = '<p id="noo-membership-packages-add" class="category-add wp-hidden-child">';

					$html[] = '<label class="screen-reader-text" for="noo-membership-packages-title">' . __( 'Package Title', NOO_TEXT_DOMAIN ) . '</label>';
					$html[] = '<input type="text" name="noo-membership-packages-title" id="noo-membership-packages-title" class="form-required form-input-tip" placeholder="'.__( 'Package Title', NOO_TEXT_DOMAIN ) .'" aria-required="true"/>';
					$html[] = '<label class="screen-reader-text" for="noo-membership-packages-interval">' . __( 'Package Interval', NOO_TEXT_DOMAIN ) . '</label>';
					$html[] = '<input type="text" name="noo-membership-packages-interval" id="noo-membership-packages-interval" placeholder="'.__( 'Package Interval', NOO_TEXT_DOMAIN ) .'" style="width:64%;display: inline-block;float: left;margin-left: 0; margin-right: 0;height:28px;"/>';
					$html[] = '<select name="noo-membership-packages-interval_unit" id="noo-membership-packages-interval_unit" style="width:36%;display: inline-block;float: left; margin-left: 0; margin-right: 0;box-shadow: none;background-color:#ddd;">';
					$html[] = '<option value="day" selected="selected">' . __( 'Days', NOO_TEXT_DOMAIN) . '</option>';
					$html[] = '<option value="week">' . __( 'Weeks', NOO_TEXT_DOMAIN) . '</option>';
					$html[] = '<option value="month">' . __( 'Months', NOO_TEXT_DOMAIN) . '</option>';
					$html[] = '<option value="year">' . __( 'Years', NOO_TEXT_DOMAIN) . '</option>';
					$html[] = '</select>';
					$html[] = '<label class="screen-reader-text" for="noo-membership-packages-price">' . __( 'Package Price', NOO_TEXT_DOMAIN ) . '</label>';
					$html[] = '<input type="text" name="noo-membership-packages-price" id="noo-membership-packages-price" class="form-input-tip" placeholder="'.__( 'Package Price', NOO_TEXT_DOMAIN ) .'" aria-required="true"/>';
					$html[] = '<label class="screen-reader-text" for="noo-membership-packages-listing_num">' . __( 'Number of Listing', NOO_TEXT_DOMAIN ) . '</label>';
					$html[] = '<input type="text" name="noo-membership-packages-listing_num" id="noo-membership-packages-listing_num" class="form-input-tip" placeholder="'.__( 'Number of Listing', NOO_TEXT_DOMAIN ) .'" aria-required="true" style="width:64%;display: inline-block;" />';
					$html[] = '<label style="width:34%;display: inline-block;" for="noo-membership-packages-listing_num_unlimited"><input type="checkbox" name="noo-membership-packages-listing_num_unlimited" id="noo-membership-packages-listing_num_unlimited"/>' . __( 'Unlimited?', NOO_TEXT_DOMAIN ) . '</label>';
					$html[] = '<label class="screen-reader-text" for="noo-membership-packages-featured_num">' . __( 'Number of Featured', NOO_TEXT_DOMAIN ) . '</label>';
					$html[] = '<input type="text" name="noo-membership-packages-featured_num" id="noo-membership-packages-featured_num" class="form-input-tip" placeholder="'.__( 'Number of Featured', NOO_TEXT_DOMAIN ) .'" aria-required="true"/>';
					$html[] = '<input type="button" id="noo-membership-packages-add-submit" class="button" value="' . __( 'Add Membership Package', NOO_TEXT_DOMAIN ) . '" />';
					// $html[] = wp_nonce_field( 'noo-membership-packages_ajax_nonce', false );
					$html[] = '<span id="noo-membership-packages-ajax-response"></span>';
					$html[] = '</p>';

					$html[] = '</div>';

					echo implode( "\n", $html);
					break;
			}
		}

		public static function create_agent_from_user( $user_id = null ) {
			if( empty( $user_id ) ) {
				return 0;
			}

			// Insert new agent
			$user = new WP_User( $user_id );
			$agent = array(
				'post_title'	=> $user->display_name,
				'post_status'	=> 'publish', 
				'post_type'     => self::AGENT_POST_TYPE
				);

			$prefix = self::AGENT_META_PREFIX;

			$agent_id =  wp_insert_post( $agent );
			if( $agent_id ) {
				update_user_meta( $user_id, '_associated_agent_id', $agent_id);
				update_post_meta( $agent_id, '_associated_user_id', $user_id);
				update_post_meta( $agent_id, "{$prefix}_email", $user->user_email);

				$freemium_enabled = (bool) ( esc_attr( get_option( 'noo_membership_freemium' ) ) );
				$freemium_listing_num = $freemium_enabled ? intval( get_option( 'noo_membership_freemium_listing_num' ) ) : 0;
				$freemium_listing_unlimited = $freemium_enabled ? (bool) get_option( 'noo_membership_freemium_listing_unlimited' ) : false;

				$listing_remain = $freemium_listing_unlimited ? -1 : $freemium_listing_num;
				$featured_remain = $freemium_enabled ? intval( get_option( 'noo_membership_freemium_featured_num' ) ) : 0;

				$interval = -1;
				$interval_unit = 'day';

				$time = time();
				$activation_date = date('Y-m-d H:i:s', $time); // Date down to second

				update_post_meta( $agent_id, '_membership_package', '' );
				update_post_meta( $agent_id, '_listing_remain', $listing_remain );
				update_post_meta( $agent_id, '_featured_remain', $featured_remain );
				update_post_meta( $agent_id, '_activation_date', $activation_date );
				update_post_meta( $agent_id, '_membership_interval', $interval );
				update_post_meta( $agent_id, '_membership_interval_unit', $interval_unit );
			}

			return $agent_id;
		}

		public static function get_login_url() {
			$current_url = noo_current_url();
			$login_url = get_option( 'noo_login_page' );
			$login_url = ( !empty($login_url) ) ? get_permalink( $login_url ) : '';
			$login_url = ( !empty($login_url) ) ? add_query_arg('redirect_to', urlencode($current_url), $login_url) :  wp_login_url( $current_url );
			
			return $login_url;
		}

		public static function check_logged_in_user() {
			if ( !is_user_logged_in() ) {
				wp_redirect( self::get_login_url() );
			}
		}

		public static function display_content($query='',$title=''){
			global $wp_query;
			if(!empty($query)){
				$wp_query = $query;
			}
			if(empty($title) && is_tax())
				$title = single_term_title( "", false );
			if($wp_query->have_posts()):
			

			
			?>
			<div class="agents <?php echo (isset($_GET['mode']) ? $_GET['mode'] : 'grid') ?>">
				<div class="agents-header">
					<h1 class="page-title"><?php echo $title ?></h1>
				</div>
				<?php while ( $wp_query->have_posts() ) : $wp_query->the_post(); global $post; ?>
					<?php 
						// // Check if agent has properties
						// if( !self::has_my_properties( $post->ID ) ) {
						// 	continue;
						// }

						// Variables
						$prefix = self::AGENT_META_PREFIX;

						$avatar_src = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_ID() ), 'cg-agent' );
						if( empty($avatar_src) ) {
							$avatar_src		= NOO_ASSETS_URI . '/images/default-avatar.png';
						} else {
							$avatar_src		= $avatar_src[0];
						}

						// Agent's info
						$phone			= noo_get_post_meta( get_the_ID(), "{$prefix}_phone", '' );
						$mobile			= noo_get_post_meta( get_the_ID(), "{$prefix}_mobile", '' );
						$email			= noo_get_post_meta( get_the_ID(), "{$prefix}_email", '' );
						$skype			= noo_get_post_meta( get_the_ID(), "{$prefix}_skype", '' );
						$facebook		= noo_get_post_meta( get_the_ID(), "{$prefix}_facebook", '' );
						$twitter		= noo_get_post_meta( get_the_ID(), "{$prefix}_twitter", '' );
						$google_plus	= noo_get_post_meta( get_the_ID(), "{$prefix}_google_plus", '' );
						$linkedin		= noo_get_post_meta( get_the_ID(), "{$prefix}_linkedin", '' );
						$pinterest		= noo_get_post_meta( get_the_ID(), "{$prefix}_pinterest", '' );
					?>
					<article id="agent-<?php the_ID(); ?>" <?php post_class(); ?>>
					    <div class="agent-featured">
					        <a class="content-thumb" href="<?php the_permalink() ?>">
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
									<?php if( !empty($facebook) || !empty($twitter) || !empty($google_plus) || !empty($linkedin) || !empty($pinterest) ) : ?>
									<div class="agent-social">
										<?php echo ( !empty($facebook) ? '<a class="noo-social-facebook" href="' . $facebook . '"></a>' : '' ); ?>
										<?php echo ( !empty($twitter) ? '<a class="noo-social-twitter" href="' . $twitter . '"></a>' : '' ); ?>
										<?php echo ( !empty($google_plus) ? '<a class="noo-social-googleplus" href="' . $google_plus . '"></a>' : '' ); ?>
										<?php echo ( !empty($linkedin) ? '<a class="noo-social-linkedin" href="' . $linkedin . '"></a>' : '' ); ?>
										<?php echo ( !empty($pinterest) ? '<a class="noo-social-pinterest" href="' . $pinterest . '"></a>' : '' ); ?>
									</div>
									<?php endif; ?>
									<div class="agent-action">
										<a href="<?php the_permalink()?>"><?php the_title(); ?></a>
									</div>
								</div>
								
							</div>
						</div>
					</article> <!-- /#post- -->
				<?php endwhile; ?>
			</div>
			<?php
			endif;
		}
		
		public function noo_membership_packages_shortcode($atts, $content = null){
			extract( shortcode_atts( array(
				'style'				=> 'ascending',
				'featured_item'		=> '2',
				'btn_text'   		=> __('Sign Up', NOO_TEXT_DOMAIN),
				'visibility'        => '',
				'class'             => '',
				'custom_style'      => ''
			), $atts ) );

			wp_enqueue_script('noo-dashboard');

			$btn_text         = ( $btn_text    != '' ) ? $btn_text : __('Sign Up', NOO_TEXT_DOMAIN);
			$visibility       = ( $visibility  != '' ) && ( $visibility != 'all' ) ? esc_attr( $visibility ) : '';
			$class            = ( $class       != '' ) ? 'membership-packages ' . esc_attr( $class ) : 'membership-packages';
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
			
			$class = ( $class != '' ) ? '' . esc_attr( $class ) . ' noo-pricing-table ascending' : ' noo-pricing-table ascending';
			$custom_style = ( $custom_style != '' ) ? ' style="' . $custom_style . '"' : '';
			
			$packages = new WP_Query(array(
				'post_type'=>'noo_membership',
				'posts_per_page'=> -1,
				'ignore_sticky_posts' => 1,
				'order' => 'ASC',
			));
			$is_logged_in = is_user_logged_in();
			$html = array();
			if($packages->have_posts()) :
				switch( count( $packages->posts) ) {
					case 1:
						$class .= ' one-col';
						break;
					case 2:
						$class .= ' two-col';
						break;
					case 3:
						$class .= ' three-col';
						break;
					case 4:
						$class .= ' four-col';
						break;
					case 5:
						$class .= ' five-col';
						break;
					case 6:
						$class .= ' six-col';
						break;
					default:
						$class .= ' three-col';
						break;
				}

				$html[] = "<div class=\"{$class}\" {$custom_style}>";
				$count = 0;
				$nonce = wp_nonce_field('noo_subscription','_noo_membership_nonce', false);
				while ($packages->have_posts()): $packages->the_post();
					$count++;
					$title = get_the_title();
					$price = noo_get_post_meta(null,'_noo_membership_price');
					$interval = noo_get_post_meta(null,'_noo_membership_interval');
					if( (int)$interval > 1 ) {
						$after_price = noo_get_post_meta(null,'_noo_membership_interval').' '.noo_get_post_meta(null,'_noo_membership_interval_unit');	
					} else {
						$after_price = noo_get_post_meta(null,'_noo_membership_interval_unit');
					}

					$price = NooProperty::format_price( $price, 'text' );
					$price = '<span class="noo-price">' . $price  . '</span>';
					$price = ( trim($after_price)  != '' ) ? $price . ' /' . esc_attr( $after_price ) : $price;

					$package_listing = noo_get_post_meta(null,'_noo_membership_listing_num');
					$package_listing_unlimited = noo_get_post_meta(null,'_noo_membership_listing_num_unlimited', false);
					if( $package_listing_unlimited ) {
						$package_listing = __('Unlimited Property Submit', NOO_TEXT_DOMAIN);
					} else {
						$package_listing = __('Property Submit Limit: ', NOO_TEXT_DOMAIN) . $package_listing;
					}
					$package_featured = __('Featured Property: ', NOO_TEXT_DOMAIN) . noo_get_post_meta(null,'_noo_membership_featured_num');

					$html[] = '<div class="noo-pricing-column ' . ($count == (int)$featured_item ? 'featured' : '') .'">';
					$html[] = '  <div class="pricing-content" >';
					$html[] = '    <div class="pricing-header" >';
					$html[] = "      <h2 class=\"pricing-title\">{$title}</h2>";
					$html[] = "      <h3 class=\"pricing-value\">{$price}</h3>";
					$html[] = '    </div>';
					$html[] = '    <div class="pricing-info">';
					$html[] = '        <p class="text-center">'.$package_listing.'</p>';
					$html[] = '        <p class="text-center">'.$package_featured.'</p>';
					for( $i = 1; $i < 5; $i++ ) {
						$additional = noo_get_post_meta(null,'_noo_membership_additional_info_' . $i);
						if( !empty( $additional ) ) {
							$html[] = '        <p class="text-center">'.$additional.'</p>';
						}
					}
					$html[] = '    </div>';
					$html[] = '    <div class="pricing-footer" >';
					$html[] = '      <form class="subscription_post">';
					$html[] = '        <div class="form-message"></div>';
					$html[] = '        <input type="hidden" id="package_id" name="package_id" value="' . get_the_ID() . '">';
					$html[] = '        <input type="hidden" name="action"  value="noo_ajax_membership_payment" />';
					$html[] = '        <input type="submit" class="btn btn-lg btn-primary" role="button" value="' . $btn_text . '" />';
					$html[] = '      ' . $nonce;
					$html[] = '      </form>';
					$html[] = "    </div>";
					$html[] = "  </div>";
					$html[] = '</div>';
				endwhile;
				$html[] = "</div>";
			endif;
			wp_reset_query();
			if( !$is_logged_in ) {
				$current_url = get_permalink();
				$html[] = '<div class="noo-login-form" style="display:none;">';
				$html[] = '  <div class="form-message text-center"><h4>' . __('Sorry, you will have to login first!', NOO_TEXT_DOMAIN) . '</h4></div>';
				$html[] = do_shortcode( '[noo_login_register mode="both" redirect_to="' .$current_url . '" hide_for_login="true"]' );
				$html[] = '</div>';
			}

			return implode( "\n", $html );
		}
		
		public function recent_agents_shortcode($atts, $content = null){
			extract( shortcode_atts( array(
				'title'             => __('Recent Agents',NOO_TEXT_DOMAIN),
				'number'            => '6',
				'visibility'        => '',
				'class'             => '',
				'custom_style'      => ''
			), $atts ) );

			wp_enqueue_script( 'noo-property' );
			
			$visibility       = ( $visibility      != ''     ) && ( $visibility != 'all' ) ? esc_attr( $visibility ) : '';
			$class            = ( $class           != ''     ) ? 'recent-agents ' . esc_attr( $class ) : 'recent-agents';
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
			$args = array(
				'posts_per_page'      => $number,
				'no_found_rows'       => true,
				'post_status'         => 'publish',
				'ignore_sticky_posts' => true,
				'post_type'=>self::AGENT_POST_TYPE,
			);

			$q = new WP_Query($args);
				ob_start();
				?>
				<?php if($q->have_posts()):?>
					<div class="recent-agents recent-agents-slider">
						<?php if(!empty($title)):?>
						<div class="recent-agents-title"><h3><?php echo $title?></h3></div>
						<?php endif;?>
						<div class="recent-agents-content">
							<div class="caroufredsel-wrap row">
							<ul class="">
							<?php while ($q->have_posts()): $q->the_post();global $post;?>
								<?php 
									// // Check if agent has properties
									// if( !self::has_my_properties( $post->ID ) ) {
									// 	continue;
									// }

									// Variables
									$prefix = self::AGENT_META_PREFIX;

									$avatar_src = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_ID() ), 'full' );
									if( empty($avatar_src) ) {
										$avatar_src		= NOO_ASSETS_URI . '/images/default-avatar.png';
									} else {
										$avatar_src		= $avatar_src[0];
									}

									// Agent's info
									$phone			= noo_get_post_meta( get_the_ID(), "{$prefix}_phone", '' );
									$mobile			= noo_get_post_meta( get_the_ID(), "{$prefix}_mobile", '' );
									$email			= noo_get_post_meta( get_the_ID(), "{$prefix}_email", '' );
									$skype			= noo_get_post_meta( get_the_ID(), "{$prefix}_skype", '' );
									$facebook		= noo_get_post_meta( get_the_ID(), "{$prefix}_facebook", '' );
									$twitter		= noo_get_post_meta( get_the_ID(), "{$prefix}_twitter", '' );
									$google_plus	= noo_get_post_meta( get_the_ID(), "{$prefix}_google_plus", '' );
									$linkedin		= noo_get_post_meta( get_the_ID(), "{$prefix}_linkedin", '' );
									$pinterest		= noo_get_post_meta( get_the_ID(), "{$prefix}_pinterest", '' );
								?>
								<li class="col-md-4 col-sm-6">
									<article id="agent-<?php the_ID(); ?>" <?php post_class(); ?>>
										<div class="agent-featured">
											<a class="content-thumb" href="<?php the_permalink() ?>">
												<img src="<?php echo $avatar_src; ?>" alt="<?php the_title(); ?>"/>
											</a>
										</div>
										<div class="agent-wrap">
											<h2 class="agent-title">
												<a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php the_title(); ?></a>
											</h2>
											<div class="agent-excerpt">
												<?php if($excerpt = $post->post_content):?>
													<?php 
													$num_word = 20;
													$excerpt = strip_shortcodes($excerpt);
													echo '<p>' . wp_trim_words($excerpt,$num_word,'...') . '</p>';
													?>
												<?php endif;?>
											</div>
											<div class="agent-social">
												<?php echo ( !empty($facebook) ? '<a class="noo-social-facebook" href="' . $facebook . '"></a>' : '' ); ?>
												<?php echo ( !empty($twitter) ? '<a class="noo-social-twitter" href="' . $twitter . '"></a>' : '' ); ?>
												<?php echo ( !empty($google_plus) ? '<a class="noo-social-googleplus" href="' . $google_plus . '"></a>' : '' ); ?>
												<?php echo ( !empty($linkedin) ? '<a class="noo-social-linkedin" href="' . $linkedin . '"></a>' : '' ); ?>
												<?php echo ( !empty($pinterest) ? '<a class="noo-social-pinterest" href="' . $pinterest . '"></a>' : '' ); ?>
											</div>
										</div>
									</article>
								</li>
							<?php endwhile;?>
							</ul>
							</div>
							<a class="caroufredsel-prev" href="javascript:void(0)"></a>
					    	<a class="caroufredsel-next" href="javascript:void(0)"></a>
						</div>
					</div>
				<?php
				wp_reset_query();
				wp_reset_postdata();
				return ob_get_clean();
			endif;
		}

		public function login_register_shortcode($atts, $content = null){
			extract( shortcode_atts( array(
				'mode'              => 'login',
				'login_text'        => '',
				'show_register_link'=> false,
				'register_text'     => '',
				'redirect_to'       => '',
				'hide_for_login'    => false,
				'visibility'        => '',
				'class'             => '',
				'custom_style'      => ''
			), $atts ) );

			wp_enqueue_script('noo-dashboard');
			
			$visibility       = ( $visibility      != ''     ) && ( $visibility != 'all' ) ? esc_attr( $visibility ) : '';
			$class            = ( $class           != ''     ) ? 'recent-agents ' . esc_attr( $class ) : 'recent-agents';
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
			$col_class = ( $mode == 'both' ) ? 'col-md-6 col-sm-6' : 'col-md-12';
			$redirect_to = !empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : $redirect_to;
			$redirect_to = !empty( $redirect_to ) ? $redirect_to : noo_get_page_link_by_template( 'agent_dashboard.php' );
			if( $hide_for_login == true || $hide_for_login == 'true' ) {
				if( is_user_logged_in() ) {
					return;
				}
			}
				ob_start();
				?>
				<div class="noo-logreg <?php echo $mode; ?>">
					<div class="logreg-container">
						<div class="logreg-content row">
							<?php if( $mode == 'both' || $mode == 'login' ) : ?>
							<div class="login-form <?php echo $col_class; ?>">
								<form method="post" role="form" action="<?php echo wp_login_url(); ?>">
									<div class="logreg-title"><?php _e('Login Form', NOO_TEXT_DOMAIN); ?></div>
									<?php if( !empty($login_text) ) : ?>
									<p class="logreg-desc"><?php echo $login_text; ?></p>
									<?php endif; ?>
									<div class="form-message"></div>
									<div class="logreg-content">
										<div class="form-group">
											<label for="log" class="sr-only"><?php _e('Username', NOO_TEXT_DOMAIN); ?></label>
											<input type="text" class="form-control" id="log" name="log" class="" placeholder="<?php _e('Username', NOO_TEXT_DOMAIN); ?> *" required />
										</div>
										<div class="form-group">
											<label for="pwd" class="sr-only"><?php _e('Password', NOO_TEXT_DOMAIN); ?></label>
											<input type="password" class="form-control" id="pwd" name="pwd" class="" placeholder="<?php _e('Password', NOO_TEXT_DOMAIN); ?> *" required />
										</div>
									</div>
									<div class="logreg-action">
										<input type="hidden" name="rememberme" value="forever" />
										<input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>">
										<input type="hidden" name="action" value="noo_ajax_login">
										<?php wp_nonce_field('noo_ajax_login', '_noo_login_nonce'); ?>
										<input type="submit" class="btn btn-secondary btn-lg" id="login-submit" value="<?php _e('Login', NOO_TEXT_DOMAIN); ?>" />
									</div>
									<?php if( $mode == 'login' && $show_register_link ) : ?>
									<p class="logreg-desc">
									<?php $registration_url = sprintf( '<a href="%s">%s</a>', esc_url( wp_registration_url() ), __( 'Register', NOO_TEXT_DOMAIN ) ); ?>
									<?php echo $registration_url . ' | '; ?><a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" title="<?php esc_attr_e( 'Password Lost and Found', NOO_TEXT_DOMAIN ); ?>"><?php _e( 'Lost your password?', NOO_TEXT_DOMAIN ); ?></a>
									</p>
									<?php else : ?>
									<p class="logreg-desc"><?php echo __('Lost your password?', NOO_TEXT_DOMAIN); ?> <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php echo __('Click here to reset', NOO_TEXT_DOMAIN); ?></a></p>
									<?php endif; ?>
								</form>
							</div>
							<?php endif; ?>
							<?php if( $mode == 'both' || $mode == 'register' ) : ?>
							<div  class="register-form <?php echo $col_class; ?>">
								<form name="registerform" id="registerform" action="<?php echo wp_registration_url(); ?>" method="post" role="form">
									<div class="logreg-title"><?php _e('Register Form', NOO_TEXT_DOMAIN); ?></div>
									<?php if( !empty($register_text) ) : ?>
									<p class="logreg-desc"><?php echo $register_text; ?></p>
									<?php endif; ?>
									<div class="form-message"></div>
									<div class="logreg-content">
										<div class="form-group">
											<label for="user_login" class="sr-only"><?php _e('Username', NOO_TEXT_DOMAIN); ?></label>
											<input type="text" class="form-control" id="user_login" name="user_login" class="" placeholder="<?php _e('Username', NOO_TEXT_DOMAIN); ?> *" required />
										</div>
										<div class="form-group">
											<label for="user_email" class="sr-only"><?php _e('Your Email', NOO_TEXT_DOMAIN); ?></label>
											<input type="email" class="form-control" id="user_email" name="user_email" class="" placeholder="<?php _e('Your Email', NOO_TEXT_DOMAIN); ?> *" required />
										</div>
									</div>
									<div class="logreg-action">
										<input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>">
										<input type="hidden" name="action" value="noo_ajax_register">
										<?php wp_nonce_field('noo_ajax_register', '_noo_register_nonce'); ?>
										<input type="submit" class="btn btn-secondary btn-lg" id="register-submit" value="<?php _e('Register Account', NOO_TEXT_DOMAIN); ?>" />
									</div>
								</form>
							</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<?php
			return ob_get_clean();
		}

		public static function get_package_id( $agent_id = null ) {
			if( empty( $agent_id ) ) {
				$user_id = get_current_user_id();
				if( !empty($user_id) ) {
					$agent_id = get_user_meta( $user_id, '_associated_agent_id', true );
				}
			}

			if( empty( $agent_id ) ) {
				return 0;
			}

			return intval( noo_get_post_meta( $agent_id, '_membership_package', 0 ) );
		}

		public static function get_listing_remain( $agent_id = null ) {
			if( empty( $agent_id ) ) {
				$user_id = get_current_user_id();
				if( !empty($user_id) ) {
					$agent_id = get_user_meta( $user_id, '_associated_agent_id', true );
				}
			}

			if( !NooMembership::is_membership() ) {
				return 0;
			}

			$listing_remain = !empty( $agent_id ) ? noo_get_post_meta( $agent_id, '_listing_remain' ) : '';
			if( $listing_remain === '' || $listing_remain === null ) {
				$package_id = !empty( $agent_id ) ? self::get_package_id( $agent_id ) : '';
				if( empty( $package_id ) ) {
					$freemium_enabled = (bool) ( esc_attr( get_option( 'noo_membership_freemium' ) ) );
					$freemium_listing_num = $freemium_enabled ? intval( get_option( 'noo_membership_freemium_listing_num' ) ) : 0;
					$freemium_listing_unlimited = $freemium_enabled ? (bool) get_option( 'noo_membership_freemium_listing_unlimited' ) : false;

					$listing_remain = $freemium_listing_unlimited ? -1 : $freemium_listing_num;
				} else {
					$package_prefix = NooMembership::MEMBERSHIP_META_PREFIX;
					$listing_num = intval( noo_get_post_meta( $package_id, "{$package_prefix}_listing_num", 0 ) );
					$listing_unlimited = (bool) noo_get_post_meta( $package_id, "{$package_prefix}_listing_num_unlimited", false );

					$listing_remain = $listing_unlimited ? -1 : $listing_num;
				}
			}

			return intval( $listing_remain );
		}

		public static function get_featured_remain( $agent_id = null ) {
			if( empty( $agent_id ) ) {
				$user_id = get_current_user_id();
				if( !empty($user_id) ) {
					$agent_id = get_user_meta( $user_id, '_associated_agent_id', true );
				}
			}

			if( !NooMembership::is_membership() ) {
				return 0;
			}

			$featured_remain = !empty( $agent_id ) ? noo_get_post_meta( $agent_id, '_featured_remain' ) : '';
			if( $featured_remain === '' || $featured_remain === null ) {
				$package_id = !empty( $agent_id ) ? self::get_package_id( $agent_id ) : '';
				if( empty( $package_id ) ) {
					$freemium_enabled = (bool) ( esc_attr( get_option( 'noo_membership_freemium' ) ) );
					$featured_remain = $freemium_enabled ? intval( get_option( 'noo_membership_freemium_featured_num' ) ) : 0;
				} else {
					$package_prefix = NooMembership::MEMBERSHIP_META_PREFIX;
					$featured_remain = intval( noo_get_post_meta( $package_id, "{$package_prefix}_featured_num", 0 ) );
				}
			}

			return intval( $featured_remain );
		}

		public static function get_expired_date( $agent_id = null ) {
			if( empty( $agent_id ) ) {
				$user_id = get_current_user_id();
				if( !empty($user_id) ) {
					$agent_id = get_user_meta( $user_id, '_associated_agent_id', true );
				}
			}

			if( empty( $agent_id ) ) {
				return false;
			}

			if( !NooMembership::is_membership() ) {
				return false;
			}

			$package_id = intval( noo_get_post_meta( $agent_id, '_membership_package' ) );
			if( empty( $package_id ) ) {
				return false;
			}

			$package_prefix = NooMembership::MEMBERSHIP_META_PREFIX;
			$activation_date = intval( noo_get_post_meta( $agent_id, '_activation_date' ) );
			$interval = intval( noo_get_post_meta( $package_id, "{$package_prefix}_interval" ) );
			if( $interval == -1 ) { // Unlimited
				return false;
			}

			$interval_unit = esc_attr( noo_get_post_meta( $package_id, "{$package_prefix}_interval_unit" ) );

			$unit_seconds = 0;
			switch ($interval_unit){
				case 'day':
				$unit_seconds = 60*60*24;
				break;
				case 'week':
				$unit_seconds = 60*60*24*7;
				break;
				case 'month':
				$unit_seconds = 60*60*24*30;
				break;    
				case 'year':
				$unit_seconds = 60*60*24*365;
				break;    
			}

			$expired_date = $activation_date + $interval * $unit_seconds;

			return $expired_date;
		}

		public static function is_dashboard() {
			if( !is_page() ) {
				return false;
			}
			$page_template = noo_get_post_meta(get_the_ID(), '_wp_page_template', 'default');
			return ( strpos($page_template, 'dashboard') !== false );
		}

		public static function has_my_properties( $agent_id = null ) {
			if( empty( $agent_id ) ) {
				return false;
			}

			$args = array(
				'post_type' => 'noo_property',
				'meta_query' => array(
					array(
						'key' => '_agent_responsible',
						'value' => (string) $agent_id,
						'compare' => 'EXISTS',
						)
					)
				);
			$query = new WP_Query( $args );
			return $query->found_posts;
		}

		public static function has_associated_user( $agent_id = null ) {
			if( empty( $agent_id ) ) {
				return false;
			}

			$associated_user_id = noo_get_post_meta( get_the_ID(), '_associated_user_id' );
			$has_user = !empty( $associated_user_id ) ? (bool) get_user_by( 'id', (int)$associated_user_id ) : false;

			return $has_user;
		}

		public static function is_expired( $agent_id = null ) {
			$expired_date = self::get_expired_date( $agent_id );

			if( $expired_date == false ) return false;

			$now = time();

			return ( $expired_date - $now ) < 0;
		}

		public static function can_add( $agent_id = null ) {
			$membership_type = NooMembership::get_membership_type();
			if( $membership_type == 'none' ) {
				return false;
			}

			if( !NooMembership::is_membership() ) {
				return true;
			}

			if( self::is_expired( $agent_id ) ) {
				return false;
			}

			$listing_remain = self::get_listing_remain( $agent_id );

			return ( $listing_remain == -1 || $listing_remain > 0 );
		}

		public static function can_edit( $agent_id = null ) {
			return !self::is_expired( $agent_id );
		}

		public static function can_delete( $agent_id = null ) {
			return self::can_edit( $agent_id );
		}

		public static function can_set_featured( $agent_id = null ) {
			if( empty( $agent_id ) ) {
				$user_id = get_current_user_id();
				if( !empty($user_id) ) {
					$agent_id = get_user_meta( $user_id, '_associated_agent_id', true );
				}
			}

			if( empty( $agent_id ) ) {
				return false;
			}

			if( !NooMembership::is_membership() ) {
				return false;
			}

			if( self::is_expired( $agent_id ) ) {
				return false;
			}

			$featured_remain = self::get_featured_remain( $agent_id );
			return ( $featured_remain == -1 || $featured_remain > 0 );
		}

		public static function is_owner( $agent_id = null, $prop_id = null ) {
			if( empty( $agent_id ) ) {
				$user_id = get_current_user_id();
				if( !empty($user_id) ) {
					$agent_id = get_user_meta( $user_id, '_associated_agent_id', true );
				}
			}

			if( empty( $agent_id ) || empty( $prop_id ) ) {
				return false;
			}

			return $agent_id == intval( noo_get_post_meta( $prop_id, '_agent_responsible' ) );
		}

		public static function get_membership_info( $agent_id = null ) {
			if( empty( $agent_id ) ) {
				$user_id = get_current_user_id();
				if( !empty($user_id) ) {
					$agent_id = get_user_meta( $user_id, '_associated_agent_id', true );
				}
			}

			$agent_listing_remain = !empty( $agent_id ) ? noo_get_post_meta( $agent_id, '_listing_remain' ) : '';
			$agent_featured_remain = !empty( $agent_id ) ? noo_get_post_meta( $agent_id, '_featured_remain' ) : '';

			$return = array();
			$return['type'] = NooMembership::get_membership_type();
			if( $return['type'] == 'membership' ) {
				$freemium_enabled = (bool) ( esc_attr( get_option( 'noo_membership_freemium' ) ) );
				$freemium_listing_num = $freemium_enabled ? intval( get_option( 'noo_membership_freemium_listing_num' ) ) : 0;
				$freemium_listing_unlimited = $freemium_enabled ? (bool) get_option( 'noo_membership_freemium_listing_unlimited' ) : false;
				$freemium_featured_num = $freemium_enabled ? intval( get_option( 'noo_membership_freemium_featured_num' ) ) : 0;

				if( $freemium_listing_unlimited ) {
					$freemium_listing_num = -1;
				}

				$agent_package = array();
				$package_id = !empty( $agent_id ) ? self::get_package_id( $agent_id ) : '';
				if( empty( $package_id ) ) {
					$agent_package['package_id'] = '';
					$agent_package['package_title'] = __('Free Membership', NOO_TEXT_DOMAIN);
					
					$agent_package['listing_included'] = $freemium_listing_num;
					$agent_package['listing_remain'] = ( $agent_listing_remain === '' || $agent_listing_remain === null ) ? $freemium_listing_num : intval( $agent_listing_remain );

					$agent_package['featured_included'] = $freemium_featured_num;
					$agent_package['featured_remain'] = ( $agent_featured_remain === '' || $agent_featured_remain === null ) ? $freemium_featured_num : intval( $agent_featured_remain );
					$agent_package['expired_date'] = __('Never', NOO_TEXT_DOMAIN); // Never
				} else {
					$agent_package['package_id'] = $package_id;
					$agent_package['package_title'] = get_the_title( $package_id );

					$package_prefix = NooMembership::MEMBERSHIP_META_PREFIX;
					$listing_num = intval( noo_get_post_meta( $package_id, "{$package_prefix}_listing_num", 0 ) );
					$listing_unlimited = (bool) noo_get_post_meta( $package_id, "{$package_prefix}_listing_num_unlimited", false );
					$featured_num = intval( noo_get_post_meta( $package_id, "{$package_prefix}_featured_num", 0 ) );

					if( $listing_unlimited ) {
						$listing_num = -1;
					}
					
					$agent_package['listing_included'] = $listing_num;
					$agent_package['featured_included'] = $featured_num;

					if( self::is_expired($agent_id) ) {
						$agent_package['listing_remain'] = 0;
						$agent_package['featured_remain'] = 0;
						$agent_package['expired_date'] = -1; // Expired
					} else {
						$agent_package['listing_remain'] = ( $agent_listing_remain === '' || $agent_listing_remain === null ) ? $listing_num : $agent_listing_remain;
						$agent_package['featured_remain'] = ( $agent_featured_remain === '' || $agent_featured_remain === null ) ? $featured_num : $agent_featured_remain;

						$expired_date = self::get_expired_date( $agent_id );
						$expired_date = ( $expired_date == false ) ? __('Never', NOO_TEXT_DOMAIN) : date( _x('F d, Y', 'date', NOO_TEXT_DOMAIN ), $expired_date);
						$agent_package['expired_date'] = $expired_date;
					}
				}

				$return['data'] = $agent_package;
			} elseif( $return['type'] == 'submission' ) {
				$submission = array();
				$submission['listing_price'] = floatval( esc_attr( get_option('noo_submission_listing_price') ) );
				$submission['listing_price_text'] = NooProperty::format_price( $submission['listing_price'] );
				$submission['featured_price'] = floatval( esc_attr( get_option('noo_submission_featured_price') ) );
				$submission['featured_price_text'] = NooProperty::format_price( $submission['featured_price'] );
				
				$return['data'] = $submission;
			}

			return $return;
		}

		public static function set_agent_membership( $agent_id = null, $package_id = null, $activation_date = null ) {
			if( empty( $agent_id ) ) {
				$user_id = get_current_user_id();
				if( !empty($user_id) ) {
					$agent_id = get_user_meta( $user_id, '_associated_agent_id', true );
				}
			}

			if( empty( $agent_id ) ) {
				return false;
			}

			$agent_package = self::get_package_id( $agent_id );

			if( $package_id != $agent_package ) {
				if( empty( $package_id ) ) {
					$freemium_enabled = (bool) ( esc_attr( get_option( 'noo_membership_freemium' ) ) );
					$freemium_listing_num = $freemium_enabled ? intval( get_option( 'noo_membership_freemium_listing_num' ) ) : 0;
					$freemium_listing_unlimited = $freemium_enabled ? (bool) get_option( 'noo_membership_freemium_listing_unlimited' ) : false;

					$listing_remain = $freemium_listing_unlimited ? -1 : $freemium_listing_num;
					$featured_remain = $freemium_enabled ? intval( get_option( 'noo_membership_freemium_featured_num' ) ) : 0;

					$interval = -1;
					$interval_unit = 'day';
				} else {
					$package_prefix = NooMembership::MEMBERSHIP_META_PREFIX;
					$listing_num = intval( noo_get_post_meta( $package_id, "{$package_prefix}_listing_num", 0 ) );
					$listing_unlimited = (bool) noo_get_post_meta( $package_id, "{$package_prefix}_listing_num_unlimited", false );

					$listing_remain = $listing_unlimited ? -1 : $listing_num;
					$featured_remain = intval( noo_get_post_meta( $package_id, "{$package_prefix}_featured_num", 0 ) );

					$interval = intval( noo_get_post_meta( $package_id, "{$package_prefix}_interval", 0 ) );
					$interval_unit = intval( noo_get_post_meta( $package_id, "{$package_prefix}_interval_unit", 'day' ) );
				}

				$activation_date = empty( $activation_date ) ? time() : $activation_date; // Date down to second

				update_post_meta( $agent_id, '_membership_package', $package_id );
				update_post_meta( $agent_id, '_listing_remain', $listing_remain );
				update_post_meta( $agent_id, '_featured_remain', $featured_remain );
				update_post_meta( $agent_id, '_activation_date', $activation_date );
				update_post_meta( $agent_id, '_membership_interval', $interval );
				update_post_meta( $agent_id, '_membership_interval_unit', $interval_unit );
			}
		}

		public static function revoke_agent_membership( $agent_id = null, $package_id = null ) {
			if( empty( $agent_id ) ) {
				$user_id = get_current_user_id();
				if( !empty($user_id) ) {
					$agent_id = get_user_meta( $user_id, '_associated_agent_id', true );
				}
			}

			if( empty( $agent_id ) || empty( $package_id ) ) {
				return false;
			}

			$agent_package = self::get_package_id( $agent_id );

			if( $package_id == $agent_package ) {
				$freemium_enabled = (bool) ( esc_attr( get_option( 'noo_membership_freemium' ) ) );
				$freemium_listing_num = $freemium_enabled ? intval( get_option( 'noo_membership_freemium_listing_num' ) ) : 0;
				$freemium_listing_unlimited = $freemium_enabled ? (bool) get_option( 'noo_membership_freemium_listing_unlimited' ) : false;

				$listing_remain = $freemium_listing_unlimited ? -1 : 0;
				$featured_remain = 0;

				$interval = -1;
				$interval_unit = 'day';

				$activation_date = time(); // Date down to second

				update_post_meta( $agent_id, '_membership_package','' );
				update_post_meta( $agent_id, '_listing_remain', $listing_remain );
				update_post_meta( $agent_id, '_featured_remain', $featured_remain );
				update_post_meta( $agent_id, '_activation_date', $activation_date );
				update_post_meta( $agent_id, '_membership_interval', $interval );
				update_post_meta( $agent_id, '_membership_interval_unit', $interval_unit );
			}
		}

		public static function set_property_status( $agent_id = null, $prop_id = null, $status_type = '' ) {
			if( empty( $agent_id ) ) {
				$user_id = get_current_user_id();
				if( !empty($user_id) ) {
					$agent_id = get_user_meta( $user_id, '_associated_agent_id', true );
				}
			}

			if( empty( $agent_id ) || empty( $prop_id ) || empty( $status_type ) ) {
				return false;
			}

			if( !self::is_owner( $agent_id, $prop_id ) ) {
				return false;
			}

			switch( $status_type ) {
				case 'listing':
					update_post_meta( $prop_id, '_paid_listing', 1 );
					break;
				case 'featured':
					update_post_meta( $prop_id, '_featured', 1 );
					break;
				case 'both':
					update_post_meta( $prop_id, '_paid_listing', 1 );
					update_post_meta( $prop_id, '_featured', 1 );
					break;
			}
		}

		public static function revoke_property_status( $agent_id = null, $prop_id = null, $status_type = '' ) {
			if( empty( $agent_id ) ) {
				$user_id = get_current_user_id();
				if( !empty($user_id) ) {
					$agent_id = get_user_meta( $user_id, '_associated_agent_id', true );
				}
			}

			if( empty( $agent_id ) || empty( $prop_id ) || empty( $status_type ) ) {
				return false;
			}

			if( !self::is_owner( $agent_id, $prop_id ) ) {
				return false;
			}

			switch( $status_type ) {
				case 'listing':
					update_post_meta( $prop_id, '_paid_listing', '' );
					break;
				case 'featured':
					update_post_meta( $prop_id, '_featured', '' );
					break;
				case 'both':
					update_post_meta( $prop_id, '_paid_listing', '' );
					update_post_meta( $prop_id, '_featured', '' );
					break;
			}
		}

		public static function decrease_listing_remain( $agent_id = null ) {
			if( empty( $agent_id ) ) {
				$user_id = get_current_user_id();
				if( !empty($user_id) ) {
					$agent_id = get_user_meta( $user_id, '_associated_agent_id', true );
				}
			}

			if( empty( $agent_id ) ) {
				return false;
			}

			if( !NooMembership::is_membership() ) {
				return false;
			}

			$listing_remain = self::get_listing_remain( $agent_id );
			if( $listing_remain == -1) {
				// unlimited
			} else {
				$new_listing_remain = max( 0, $listing_remain - 1 );
				update_post_meta( $agent_id, '_listing_remain', $new_listing_remain );
			}
		}

		public static function decrease_featured_remain( $agent_id = null ) {
			if( empty( $agent_id ) ) {
				$user_id = get_current_user_id();
				if( !empty($user_id) ) {
					$agent_id = get_user_meta( $user_id, '_associated_agent_id', true );
				}
			}

			if( empty( $agent_id ) ) {
				return false;
			}

			if( !NooMembership::is_membership() ) {
				return false;
			}

			$featured_remain = self::get_featured_remain( $agent_id );
			$new_featured_remain = max( 0, $featured_remain - 1 );
			update_post_meta( $agent_id, '_featured_remain', $new_featured_remain );
		}

		public static function getMembershipPaymentURL( $agent_id = null, $package_id = null, $is_recurring = false, $recurring_time = 0 ) {
			if( empty( $agent_id ) ) {
				$user_id = get_current_user_id();
				if( !empty($user_id) ) {
					$agent_id = get_user_meta( $user_id, '_associated_agent_id', true );
				}
			}

			if( empty( $agent_id ) || empty( $package_id ) ) {
				return false;
			}

			$agent		= get_post( $agent_id );
			$package	= get_post( $package_id );
			if( !$agent || !$package ) {
				return false;
			}

			$billing_type			= $is_recurring ? 'recurring' : 'onetime';
			$total_price			= floatval( noo_get_post_meta( $package_id, '_noo_membership_price', '' ) );
			$title					= $agent->post_title . ' - Purchase package: ' . $package->post_title;
			$new_order_ID			= NooPayment::create_new_order( 'membership', $billing_type, $package_id, $total_price, $agent_id, $title );

			if( !$new_order_ID ) {
				return false;
			}

			$order					= array( 'ID' => $new_order_ID );
			$order['name']			= $agent->post_title;
			$order['email']			= esc_attr( noo_get_post_meta( $agent_id, '_noo_agent_email', '' ) );
			$order['item_name']		= __( 'Membership Payment', NOO_TEXT_DOMAIN );
			$order['item_number']	= $package->post_title;
			$order['amount']		= $total_price;
			$order['return_url']	= noo_get_page_link_by_template( 'agent_dashboard.php' );
			$order['cancel_url']	= noo_get_page_link_by_template( 'agent_dashboard.php' );
			if( $is_recurring ) {
				$order['is_recurring']	= $is_recurring;
				$order['p3']			= intval( noo_get_post_meta( $package_id, '_noo_membership_interval' ) );
				$order['t3']			= esc_attr( noo_get_post_meta( $package_id, '_noo_membership_interval_unit' ) );
				switch( $order['t3'] ) {
					case 'day':
					$order['t3'] = 'D';
					break;
					case 'week':
					$order['t3'] = 'W';
					break;
					case 'month':
					$order['t3'] = 'M';
					break;
					case 'year':
					$order['t3'] = 'Y';
					break;
				}

				$order['src']		= 1;
				$order['srt']		= $recurring_time;
				$order['sra']		= 1;
			}

			$nooPayPalFramework = nooPayPalFramework::getInstance();

			return $nooPayPalFramework->getPaymentURL( $order );
		}

		public static function getListingPaymentURL( $agent_id = null, $prop_id = null, $total_price = 0, $is_publish = false, $is_featured = false ) {
			if( empty( $agent_id ) ) {
				$user_id = get_current_user_id();
				if( !empty($user_id) ) {
					$agent_id = get_user_meta( $user_id, '_associated_agent_id', true );
				}
			}

			if( empty( $agent_id ) || empty( $prop_id ) || empty( $total_price ) ) {
				return false;
			}

			if( !$is_publish && !$is_featured ) {
				return false;
			}

			if( !NooAgent::is_owner( $agent_id, $prop_id ) ) {
				return false;
			}

			$agent		= get_post( $agent_id );
			$property	= get_post( $prop_id );
			if( !$agent || !$property ) {
				return false;
			}

			$payment_type			= '';
			if( $is_publish && $is_featured ) {
				$payment_type		= 'both';
			} elseif( $is_publish ) {
				$payment_type		= 'listing';
			} elseif( $is_featured) {
				$payment_type		= 'featured';
			}

			$title					= $agent->post_title . ' - Payment for ' . $property->post_title;
			$new_order_ID			= NooPayment::create_new_order( $payment_type, '', $prop_id, floatval( $total_price ), $agent_id, $title );

			if( !$new_order_ID ) {
				return false;
			}

			$order					= array( 'ID' => $new_order_ID );
			$order['name']			= $agent->post_title;
			$order['email']			= esc_attr( noo_get_post_meta( $agent_id, '_noo_agent_email', '' ) );
			$order['item_name']		= sprintf( __( 'Listing Payment for %s', NOO_TEXT_DOMAIN ), $property->post_title );
			$order['item_number']	= '';
			if( $is_publish ) $order['item_number'] .= __('Publish listing', NOO_TEXT_DOMAIN);
			if( $is_featured ) $order['item_number'] .= __(' and make it Featured', NOO_TEXT_DOMAIN);
			$order['amount']		= floatval( $total_price );
			$order['return_url']	= noo_get_page_link_by_template( 'agent_dashboard.php' );
			$order['cancel_url']	= noo_get_page_link_by_template( 'agent_dashboard.php' );

			$nooPayPalFramework = nooPayPalFramework::getInstance();

			return $nooPayPalFramework->getPaymentURL( $order );
		}
	}
endif;

new NooAgent();

if(!class_exists('NooMembership')) :
	class NooMembership {

		const MEMBERSHIP_POST_TYPE = 'noo_membership';
		const MEMBERSHIP_META_PREFIX = '_noo_membership';

		public function __construct() {

			if( self::is_membership() ) {
				add_action('init', array(&$this,'register_post_type'));

				if( is_admin() ) {
					
					// Membership
					add_filter( 'enter_title_here', array (&$this,'custom_enter_title') );
					add_action ( 'add_meta_boxes', array (&$this,'remove_meta_boxes' ), 20 );
					add_action ( 'add_meta_boxes', array (&$this, 'add_meta_boxes' ), 30 );

					// Ajax to create new membership on Agent edit page.
					add_action( 'wp_ajax_noo_create_membership', 'NooMembership::create_membership' );
				}
			}

			//hook into the query before it is executed to add the filter for not paid properties.
			add_action( 'pre_get_posts', array (&$this, 'pre_get_posts'), 1 );

			if( self::is_submission() ) {

				// Add Paid status to Property edit
				add_action ( 'add_meta_boxes', array (&$this, 'add_meta_boxes_property' ), 30 );
			}
		}

		public function register_post_type () {
			// Text for NOO Membership Packages.
			$noo_membership_package_labels = array(
				'name' => __('Membership Packages', NOO_TEXT_DOMAIN),
				'singular_name' => __('Membership Package', NOO_TEXT_DOMAIN),
				'menu_name' => __('Membership Packages', NOO_TEXT_DOMAIN),
				'all_items' => __('Membership Packages', NOO_TEXT_DOMAIN),
				'add_new' => __('Add New', NOO_TEXT_DOMAIN),
				'add_new_item' => __('Add New Membership Package', NOO_TEXT_DOMAIN),
				'edit_item' => __('Edit Membership Package', NOO_TEXT_DOMAIN),
				'new_item' => __('New Membership Package', NOO_TEXT_DOMAIN),
				'view_item' => __('View Membership Package', NOO_TEXT_DOMAIN),
				'search_items' => __('Search Membership Package', NOO_TEXT_DOMAIN),
				'not_found' => __('No membership packages found', NOO_TEXT_DOMAIN),
				'not_found_in_trash' => __('No membership packages found in trash', NOO_TEXT_DOMAIN),
			);


			// Options
			$noo_membership_package_args = array(
				'labels' => $noo_membership_package_labels,
				'public' => true,
				'publicly_queryable' => false,
				'show_in_menu' => 'edit.php?post_type=noo_agent',
				'hierarchical' => false,
				'supports' => array(
					'title',
					'revisions'
				),
				'has_archive' => false,
			);
			
			register_post_type(self::MEMBERSHIP_POST_TYPE, $noo_membership_package_args);
		}

		public function custom_enter_title( $input ) {
			global $post_type;

			if ( self::MEMBERSHIP_POST_TYPE == $post_type )
				return __( 'Package Title', NOO_TEXT_DOMAIN );

			return $input;
		}

		public function remove_meta_boxes() {
			remove_meta_box( 'slugdiv', self::MEMBERSHIP_POST_TYPE, 'normal' );
			remove_meta_box( 'mymetabox_revslider_0', self::MEMBERSHIP_POST_TYPE, 'normal' );
		}

		public function add_meta_boxes() {

			// Declare helper object
			$prefix = self::MEMBERSHIP_META_PREFIX;
			$helper = new NOO_Meta_Boxes_Helper( $prefix, array( 'page' => self::MEMBERSHIP_POST_TYPE ) );

			// Membership metabox
			$meta_box = array(
				'id'           => "{$prefix}_meta_box_membership",
				'title'        => __( 'Package Details', NOO_TEXT_DOMAIN ),
				'context'      => 'normal',
				'priority'     => 'core',
				'description'  => '',
				'fields'       => array(
					array(
						'id' => "{$prefix}_interval",
						'label' => __( 'Package Interval', NOO_TEXT_DOMAIN ),
						'desc' => __( 'Duration time of this package.', NOO_TEXT_DOMAIN ),
						'type' => 'billing_period',
						'std' => '0',
						'callback' => 'NooMembership::render_metabox_fields'
					),
					array(
						'id' => "{$prefix}_price",
						'label' => __( 'Package Price', NOO_TEXT_DOMAIN ),
						'desc' => __( 'The price of this package.', NOO_TEXT_DOMAIN ),
						'type' => 'text',
						'std' => '20.00'
					),
					array(
						'id' => "{$prefix}_listing_num",
						'label' => __( 'Number of Listing', NOO_TEXT_DOMAIN ),
						'desc' => __( 'Number of listing available for this package.', NOO_TEXT_DOMAIN ),
						'type' => 'listing_num',
						'std' => '3',
						'callback' => 'NooMembership::render_metabox_fields'
					),
					array(
						'id' => "{$prefix}_featured_num",
						'label' => __( 'Number of Featured Properties', NOO_TEXT_DOMAIN ),
						'desc' => __( 'Number of properties can make featured with this package.', NOO_TEXT_DOMAIN ),
						'type' => 'text',
						'std' => '2'
					)
				),
			);

			$helper->add_meta_box($meta_box);

			// Membership metabox
			$meta_box = array(
				'id'           => "{$prefix}_additional_info",
				'title'        => __( 'Aditional Information', NOO_TEXT_DOMAIN ),
				'context'      => 'normal',
				'priority'     => 'default',
				'description'  => '',
				'fields'       => array(
					array(
						'id' => "{$prefix}_additional_info",
						'label' => __( 'Additional Info', NOO_TEXT_DOMAIN ),
						'desc' => __( 'Add more detail for this package.', NOO_TEXT_DOMAIN ),
						'type' => 'addable_text',
						'std' => '',
						'callback' => 'NooMembership::render_metabox_fields'
					)
				),
			);

			$helper->add_meta_box($meta_box);
		}

		public function add_meta_boxes_property() {

			// Declare helper object
			$prefix = '';
			$helper = new NOO_Meta_Boxes_Helper( $prefix, array( 'page' => 'noo_property' ) );

			// Membership metabox
			$meta_box = array(
				'id'           => "{$prefix}_meta_box_paid_status",
				'title'        => __( 'Payment Status', NOO_TEXT_DOMAIN ),
				'context'      => 'side',
				'priority'     => 'default',
				'description'  => '',
				'fields'       => array(
					array(
						'id' => "{$prefix}_paid_listing",
						'label'    => __( 'Paid Listing', NOO_TEXT_DOMAIN ),
						'desc'    => __( 'Set the submission payment status for this Property. Please remember that only Paid submision is available on the site.', NOO_TEXT_DOMAIN ),
						'type'    => 'select',
						'std'     => '0',
						'options' => array(
							array('value'=>'0','label'=>__('Not Paid', NOO_TEXT_DOMAIN)),
							array('value'=>'1','label'=>__('Paid', NOO_TEXT_DOMAIN))
						)
					),
				),
			);

			$helper->add_meta_box($meta_box);
		}

		public static function render_metabox_fields( $post, $id, $type, $meta, $std = null, $field = null ) {
			switch( $type ) {
				case 'billing_period':
					$value = $meta ? ' value="' . $meta . '"' : '';
					$value = empty( $value ) && ( $std != null && $std != '' ) ? ' placeholder="' . $std . '"' : $value;
					$unit  = esc_attr(noo_get_post_meta($post->ID, $id . '_unit', 'day'));
					echo '<div class="input-group">';
					echo '<input type="text" name="noo_meta_boxes[' . $id . ']" ' . $value . ' style="width:200px;display: inline-block;float: left;margin: 0;height:28px;"/>';
					echo '<select name="noo_meta_boxes[' . $id . '_unit]" style="width:100px;display: inline-block;float: left;margin: 0;box-shadow: none;background-color:#ddd;">';
					echo '<option value="day" ' . selected( $unit, 'day', false ) . '>' . __( 'Days', NOO_TEXT_DOMAIN) . '</option>';
					echo '<option value="week" ' . selected( $unit, 'week', false ) . '>' . __( 'Weeks', NOO_TEXT_DOMAIN) . '</option>';
					echo '<option value="month" ' . selected( $unit, 'month', false ) . '>' . __( 'Months', NOO_TEXT_DOMAIN) . '</option>';
					echo '<option value="year" ' . selected( $unit, 'year', false ) . '>' . __( 'Years', NOO_TEXT_DOMAIN) . '</option>';
					echo '</select>';
					echo '</div>';
					break;

				case 'listing_num':
					$unlimited = noo_get_post_meta( $post->ID, $id . '_unlimited', false );
					$value = $meta ? ' value="' . $meta . '"' : '';
					$value = empty( $value ) && ( $std != null && $std != '' ) ? ' placeholder="' . $std . '"' : $value;
					echo '<input type="text" name="noo_meta_boxes[' . $id . ']" ' . $value . disabled( $unlimited, true, false ) . '/>';
					echo '<label><input type="checkbox" name="noo_meta_boxes[' . $id . '_unlimited]" ' . checked( $unlimited, true, false ) . 'value="1" />';
					echo __( 'Unlimited Listing?', NOO_TEXT_DOMAIN ) . '</label>';

					echo '<script>
						jQuery( document ).ready( function ( $ ) {
							$("input[name=\'noo_meta_boxes[' . $id . '_unlimited]\']").click( function() {
								if( $(this).is(":checked") ) {
									$("input[name=\'noo_meta_boxes[' . $id . ']\']").prop("disabled", true);
								} else {
									$("input[name=\'noo_meta_boxes[' . $id . ']\']").prop("disabled", false);
								}
							});

						} );
					</script>';

					break;

				case 'addable_text':
					$max_fields = 5;
					if ( !empty( $field['max_fields'] ) && is_numeric( $field['max_fields'] ) ) {
						$max_fields = $field['max_fields'];
					}
					if( $max_fields == -1 ) $max_fields = 100;
					$meta = array();
					?>
					<div class="noo-membership-additional" data-max="<?php echo $max_fields; ?>" data-name="<?php echo $id; ?>" >
					<?php
					$count = 0;
					for( $index = 0; $index <= $max_fields; $index++ ) {
						$meta_i = noo_get_post_meta( get_the_ID(), $id . '_' . $index, '' );
						if( !empty( $meta_i ) ) {
							$count++;
							$meta[] = noo_get_post_meta( get_the_ID(), $id . '_' . $index, '' );
						}
					}
					
					foreach( $meta as $index => $meta_i ) :
					?>
						<div class="additional-field">
							<input type="text" value="<?php echo $meta_i; ?>" name="noo_meta_boxes[<?php echo $id . '_' . ( $index + 1 ); ?>]" style="max-width:350px;padding-right: 10px;display: inline-block;float: left;" />
							<input class="button button-secondary delete_membership_add_info" type="button" value="<?php _e('Delete', NOO_TEXT_DOMAIN); ?>" style="display: inline-block;float: left;" />
							<br/>
						</div>
					<?php
					endforeach;
					?>
					</div>
					<br class="clear" />
					<input type="button" value="<?php _e('Add', NOO_TEXT_DOMAIN); ?>" class="button button-primary add_membership_add_info" <?php disabled( $count >= $max_fields ); ?>/>
					<?php
					break;
			}
		}

		public static function create_membership() {  
			try{

				$prefix = self::MEMBERSHIP_META_PREFIX;
				$new_package = array(
					'post_title' => $_POST['title'],
					'post_type' => self::MEMBERSHIP_POST_TYPE,
					'post_status' => 'publish'
				);
				$new_post_ID = wp_insert_post( $new_package );
				if( $new_post_ID ) {
					update_post_meta( $new_post_ID, "{$prefix}_interval", $_POST['interval'] );
					update_post_meta( $new_post_ID, "{$prefix}_interval_unit", $_POST['interval_unit'] );
					update_post_meta( $new_post_ID, "{$prefix}_price", $_POST['price'] );
					update_post_meta( $new_post_ID, "{$prefix}_listing_num", $_POST['listing_num'] );
					update_post_meta( $new_post_ID, "{$prefix}_listing_num_unlimited", $_POST['listing_num_unlimited'] );
					update_post_meta( $new_post_ID, "{$prefix}_featured_num", $_POST['featured_num'] );

					echo $new_post_ID;
					exit();
				}
			} catch (Exception $e){  
				exit('-1');  
			}  
			exit('-1'); 
		}

		public static function get_membership_type() {
			return get_option('noo_membership_type', 'membership');
		}

		public static function is_membership() {
			return self::get_membership_type() == 'membership';
		}

		public static function is_submission() {
			return self::get_membership_type() == 'submission';
		}

		public function pre_get_posts( $query ) {
			if ( is_admin() ) {
				return;
			}

			if ( NooAgent::is_dashboard() ) {
				return;
			}

			//if is querying noo_property
			if(isset($query->query_vars['post_type']) && $query->query_vars['post_type'] == 'noo_property' ) {

				if ( !self::is_submission() ) {
					return;
				}

				$meta_query = isset( $query->meta_query ) && !empty( $query->meta_query ) ? $query->meta_query : array();
				$paid_filter = array(
						'key' => '_paid_listing',
						'value' => '1'
					);
				$meta_query[] = $paid_filter;

				$query->set('meta_query', $meta_query);
			}

			//if is querying noo_agent
			if( isset($query->query_vars['post_type']) && $query->query_vars['post_type'] == 'noo_agent' ) {
				if( $query->is_singular && $query->is_main_query() ) {
					return;
				}

				if( get_option('noo_agent_must_has_property') ) {
					global $noo_show_sold;
					$noo_show_sold = true;
					$meta_query = new WP_Query(
						array(
							'post_type'			=> 'noo_property',
							'fields'			=> 'ids',
							'posts_per_page'	=> -1,
							'meta_query'	=> array(
								'key'		=> '_agent_responsible',
								'value'		=> array( null, '', '0', 0 ),
								'compare'	=> 'NOT IN'
							)
						)
					);

					$property_list = $meta_query->posts;
					$agent_ids = array();
					if( !empty($property_list) ) {
						foreach ($property_list as $prop_id) {
							$agent_id = noo_get_post_meta($prop_id, '_agent_responsible');
							if( !in_array($agent_id, $agent_ids) && !empty( $agent_id) ) {
								$agent_ids[] = $agent_id;
							}
						}
					}

					$noo_show_sold = false;

					$query->set('post__in', $agent_ids );
				}
			}

			return;
		}
	}
endif;

new NooMembership();