<?php
	/*
	Plugin Name: Contact Card
	Plugin URI: poiz.me
	Description: A Minimal Plugin for parsing & displaying grouped Contact Data via Shortcode. <strong>Usage: </strong> First go to the Contact Card Menu & select the «Team Group» Option to create Individual Categories (Team Groups). Then add Contacts as needed. After that, simply enter the Shortcode <strong>[contact_cards group="Group Name"]</strong>. Where <strong>group</strong> is the Group you created using the «Team Group» Taxonomy. This Plugin also allows you to download the Contact-Person's platform-independent VCard, hence the name: Contact Card. Rolling the mouse over the Thumbnail flips the backside of the Image where all Contact information are rendered.
	Author:  Poiz Campbell
	Author URI: http://poiz.me
	Version: 1.0.0
	*/

	use Code\vCard,
		Code\MBoxHelper,
		Code\ContactCardOptionsBridge   as COB,
		Code\PoizContactCardHelper      as PCH;

	if(!function_exists("integrateCodeLibraries")){
		function integrateCodeLibraries() {
			require_once __DIR__    . "/vendor/autoload.php";
			require_once ABSPATH    . "/wp-includes/pluggable.php";
		}
	}

	integrateCodeLibraries();

	if(isset($_GET['vcard']) && !is_admin()){
		$wwUID      = $_GET['vcard'];
		$objWW      = get_post($wwUID);
		
		if($objWW && !empty($objWW)) {
			$wwID       = $objWW->ID;
			$name       = get_the_title($wwID);
			$arrNames   = explode(" ", $name);
			$lName      = array_pop($arrNames);
			$fName      = implode(" ", $arrNames);
			$telNum     = ($ot = get_post_meta($wwID, 'cpl_contacts_telephone', true))?$ot:null;
			$officeTel  = (stristr($telNum, "+41 62"))? $telNum : NULL;
			$mobileTel  = (!$officeTel) ? $telNum : NULL;
			$pix_location = ABSPATH .  str_replace(get_site_url(), "", get_the_post_thumbnail_url($objWW, "post-thumbnail"));
			
			$vcPayload = [
				'display_name' => get_the_title($wwID),
				'first_name' => $fName,
				'last_name' => $lName,
				'additional_name' => NULL,
				'name_prefix' => NULL,
				'name_suffix' => NULL,
				'nickname' => NULL,
				'title' => NULL,
				'role' => get_post_meta($wwID, 'cpl_contacts_diploma', true),
				'department' => NULL,
				'company' => "Woodwork AG",
				'work_po_box' => NULL,
				'work_extended_address' => NULL,
				'work_address' => NULL,
				'work_city' => NULL,
				'work_state' => NULL,
				'work_postal_code' => NULL,
				'work_country' => NULL,
				'home_po_box' => NULL,
				'home_extended_address' => NULL,
				'home_address' => NULL,
				'home_city' => NULL,
				'home_state' => NULL,
				'home_postal_code' => NULL,
				'home_country' => NULL,
				'office_tel' => $officeTel,
				'home_tel' => NULL,
				'cell_tel' => $mobileTel,
				'fax_tel' => NULL,
				'pager_tel' => NULL,
				'email1' => get_post_meta($wwID, 'cpl_contacts_email', true),
				'url' => get_site_url(),
				'photo' => $pix_location,
				'birthday' => NULL,
				'timezone' => NULL,
				'sort_string' => NULL,
				'note' => NULL,
			];
			
			createVCard($vcPayload);
			exit;
		}
	}
	
	$cplContactPluginURL  = plugin_dir_url(__FILE__);
	$GLOBALS['CONTACT_CARD_PLUGIN']    = [
		"plg_url"       => $cplContactPluginURL,
		"plg_dir"       => plugin_dir_path(__FILE__),
		"css_url"       => $cplContactPluginURL . "assets/css/",
		"js_url"        => $cplContactPluginURL . "assets/js/",
		"img_url"       => $cplContactPluginURL . "assets/img/",
		"images_url"    => $cplContactPluginURL . "assets/images/",
	];

	/********************************************************************/
	/**********************CUSTOM POST TYPE: BEGIN***********************/
	/********************************************************************/
	function mapRolesAndCapabilities(){
		add_role('site_manager', 'Site Manager',
			[
				'read'           => TRUE,
				'edit_posts'     => TRUE,
				'delete_posts'   => TRUE,
				'edit_plugins'   => TRUE,
				'manage_options' => TRUE,
			]);
		
		$role1  = get_role('site_manager');
		$role1->add_cap('manage_contact_card');
		
		$role2  = get_role('administrator');
		$role2->add_cap('manage_contact_card');
		
	}

	function ContactCardPostTypes() {
		register_post_type('cpl_contacts',
			array(
				'label'                 => __('Contact Cards',  'cpl'),
				'description'           => __('Contact Cards',  'cpl'),
				'public'                => false,
				'show_ui'               => true,
				'show_in_menu'          => true,
				'capability_type'       => 'post',
				'hierarchical'          => true,
				'rewrite'               => array('slug' => 'kontakt', 'with_front'=>true),
				'query_var'             => true,
				'has_archive'           => true,
				'publicly_queryable'    => false,
				'menu_icon'             => "dashicons-id",
				'exclude_from_search'   => true,
				'supports'              => array('title', 'revisions', 'thumbnail', 'page-attributes'),
				'taxonomies'            => array("cpl_team_group"),
				'labels'                => array(
					'name'                => __('Contact Cards',                      'cpl'),
					'singular_name'       => __('Contact Card',                       'cpl'),
					'menu_name'           => __('Contact Card',                       'cpl'),
					'add_new'             => __('New Contact',                       'cpl'),
					'add_new_item'        => __('New Contact Card',                  'cpl'),
					'edit'                => __('Edit',                               'cpl'),
					'edit_item'           => __('Edit Contact Card',                  'cpl'),
					'new_item'            => __('New Contact',                       'cpl'),
					'view'                => __('View Contact Card',                  'cpl'),
					'view_item'           => __('View Contact Card',                  'cpl'),
					'search_items'        => __('Search Contact Card',                'cpl'),
					'not_found'           => __('No Contact Card Found',        'cpl'),
					'not_found_in_trash'  => __('No Contact Card found in Trash',     'cpl'),
					'parent'              => __('Parent Contact Card',                'cpl'),
				),
			)
		);
		
		register_taxonomy('cpl_team_group',array(
			0 => 'cpl_contacts',
		),array( 'hierarchical' => true, 'label' => 'Team Group','show_ui' => true, 'query_var' => true,'rewrite' => array('slug' => 'team-group'),'singular_label' => 'Team Group') );
	}
	
	function integrateAdminMetaBoxes() {
		$arrMetaBoxes       = [
			'name'      => [
				"domain"               => "cpl",
				"post_type"            => "cpl_contacts",
				"priority"             => "low",
				"headline"             =>  __("Vorname / Name", "cpl"),
				"field_key"            => "cpl_contacts_name",
				"field_type"           => "text",
				"field_name"           => "cpl_contacts_name",
				"location"             => "advanced",
				"field_description"    =>  __("Vorname / Name", "cpl"),
				'show_description'     => true,
			],

			'eMail'     => [
				"domain"               => "cpl",
				"post_type"            => "cpl_contacts",
				"priority"             => "low",
				"headline"             =>  __("E-Mail", "cpl"),
				"field_key"            => "cpl_contacts_email",
				"field_type"           => "text",
				"field_name"           => "cpl_contacts_email",
				"location"             => "advanced",
				"field_description"    =>  __("E-Mail", "cpl"),
				'show_description'     => true,
			],

			'telephone' => [
				"domain"               => "cpl",
				"post_type"            => "cpl_contacts",
				"priority"             => "low",
				"headline"             =>  __("Telefon", "cpl"),
				"field_key"            => "cpl_contacts_telephone",
				"field_type"           => "text",
				"field_name"           => "cpl_contacts_telephone",
				"location"             => "advanced",
				"field_description"    =>  __("Telefon", "cpl"),
				'show_description'     => true,
			],

			'ordering'  => [
				"domain"               => "cpl",
				"post_type"            => "cpl_contacts",
				"priority"             => "low",
				"headline"             =>  __("Reihenfolge", "cpl"),
				"field_key"            => "cpl_contacts_ordering",
				"field_type"           => "number",
				"field_name"           => "cpl_contacts_ordering",
				"location"             => "advanced",
				"field_description"    =>  __("Reihenfolge", "cpl"),
				'show_description'     => true,
				"input_placeholder_text"    => "0",
			],

			'diploma'   => [
				"domain"               => "cpl",
				"post_type"            => "cpl_contacts",
				"priority"             => "low",
				"headline"             =>  __("Zertifizierung", "cpl"),
				"field_key"            => "cpl_contacts_diploma",
				"field_type"           => "text",
				"field_name"           => "cpl_contacts_diploma",
				"location"             => "advanced",
				"field_description"    =>  __("Zertifizierung", "cpl"),
				'show_description'     => true,
			],

			'miniProfile'   => [
				"domain"               => "cpl",
				"post_type"            => "cpl_contacts",
				"priority"             => "low",
				"headline"             =>  __("Mini-Profile", "cpl"),
				"field_key"            => "cpl_contacts_mini_profile",
				"field_type"           => "editor",
				"field_name"           => "cpl_contacts_mini_profile",
				"location"             => "advanced",
				"field_description"    =>  __("<strong>Important:</strong><br>This Field is for Internal use only. <br>It is not intended to be displayed on the Frontend! You may thus leave it blank.", "cpl"),
				'show_description'     => true,
				"append_nonce"         => true,
			],

		];

		foreach($arrMetaBoxes as $mBoxKey=>$mBoxConfig){
			new MboxHelper($mBoxConfig);
		}
	}
	
	function ContactCardShortCode($attributes){
		/**
		 * @var \WP_Post $post
		 * @var string $group
		 */
		extract( shortcode_atts(['group'=> null], $attributes,'contact_cards'));
		
		$taxonomy           = "cpl_team_group";
		$postType           = "cpl_contacts";
		$options            = (new COB())->getOptionsBank();
		$maxCardsPerRow     = ($tmp = $options['cpl_c_card_option_max_cards_per_row'])  ? $tmp  : "3";
		$grid               = ($tmp = PCH::getGridFromRowCount($maxCardsPerRow) )       ? $tmp  : "4";
		$activateVCard      = ($tmp = $options['cpl_c_card_option_activate_v_card'])    ? $tmp  : false;
		$allowNonGrouped    = ($tmp = $options['cpl_c_card_option_allow_non_grouped'])  ? $tmp  : false;
		$showGroupTitle     = ($tmp = $options['cpl_c_card_option_show_group_title'])   ? $tmp  : false;
		$showPhoneNumber    = ($tmp = $options['cpl_c_card_option_show_phone_number'])  ? $tmp  : false;
		$showPosition       = ($tmp = $options['cpl_c_card_option_show_position'])      ? $tmp  : false;

		$output             = "";
		$taxTerm            = getTaxonomyDataForCustomPostType();
		$termTax            = array_flip($taxTerm);
		$termID             = isset($termTax[$group]) ? $termTax[$group] : null;
		$args 		        = [
			"post_type"         => $postType,
			'post_status'       => 'publish',
			"posts_per_page"    => -1,
			'meta_key'          => 'cpl_contacts_ordering',
			'orderby'           => [
				'meta_value_num'    => "ASC",
				'post_date'         => "DESC",
				'ID'                => "DESC"
			],
			'order'             => 'ASC',
			'tax_query'         => [
				[
					'taxonomy' => $taxonomy,
					'field'    => 'id',
					'terms'    => array($termID),
				],
			],
		];
		if($allowNonGrouped){
			unset($args['tax_query']);
		}
		
		$cplPromoQuery      = new \WP_Query($args);
		$loopCount          = 0;
		$n                  = 1;
		
		if($cplPromoQuery->have_posts()):
			$output            .= "<div class='pz-contact-main-wrapper' id='pz-contact-main-wrapper'>" . PHP_EOL;
			$output            .= "<div class='col-md-12 pz-contact-slot-block no-lr-pad' id='pz-contact-slot-block'>" . PHP_EOL;
			
			// GROUP HEADING TITLE :
			if($showGroupTitle){
				$output            .= "<div class='col-md-12 pz-contact-main-head-pod no-lr-pad' id='pz-contact-main-head-pod'>" . PHP_EOL;
				$output            .= "<h2 class='pz-contact-main-heading no-lr-pad' id='pz-contact-main-heading'>" . PHP_EOL;
				$output            .= __($group, "cpl") . PHP_EOL;
				$output            .= "</h2>" . PHP_EOL;
				$output            .= "</div>" . PHP_EOL;
			}
			
			while($cplPromoQuery->have_posts()) : $cplPromoQuery->the_post();
				$n          = ($n > $maxCardsPerRow) ? 1 : $n;
				$pid        = get_the_ID();
				$output    .= injectQColumnWrapper($maxCardsPerRow, $loopCount, "pz-contact-wrapper-block col-md-12 pz-col-12", $loopCount, "section") . PHP_EOL;
				
				// OPEN pz-contact-slot (DIV)
				$output    .= "<div class='col-md-{$grid} pz-contact pz-contact-{$n} pz-contact-slot pz-contact-slot-" . $pid . "'>" . PHP_EOL;
				
				$output    .= "<div class='flip-container' ontouchstart='this.classList.toggle(\"hover\");'>" . PHP_EOL;
				$output    .= "<div class='front'><!-- front content -->" . PHP_EOL;
				$output    .= "<figure class='pz-figure' style='margin:0;'><img class='pz-contact-pix ww-pix pz-contact-pix-"      . $pid . "' src='" . get_the_post_thumbnail_url($pid, "medium") .  "' alt='" . get_the_title($pid) . "'  /></figure>" . PHP_EOL;
				$output    .= "<h3 class='pz-contact-name'>"        . get_the_title($pid)   . "</h3>"   . PHP_EOL;
				$output    .= "</div>" . PHP_EOL;
				
				$output    .= "<div class='back'> <!-- back content -->" . PHP_EOL;
				$output    .= "<div class='back-content'>" . PHP_EOL;
				$output    .= "<img class='pz-back-pix pz-back-pix-"      . $pid . "' src='" . get_the_post_thumbnail_url($pid, "medium") .  "' alt='" . get_the_title($pid) . "'  />" . PHP_EOL;
				$output    .= "<div class='pz-back-text-box'>";
				$output    .= "<h3 class='pz-contact-name'>"        . get_the_title($pid)   . "</h3>"   . PHP_EOL;

				if($showPosition){
					if($position = get_post_meta($pid, 'cpl_contacts_diploma', true) ){
						$output    .= "<span class='pz-contact-position'>{$position}</span>" . PHP_EOL;
					}
				}
				if($showPhoneNumber) {
					if($tl = get_post_meta($pid, 'cpl_contacts_telephone', true)){
						$output .= "<span class='pz-contact-position'><span class='fa fa-phone'></span> " . $tl . "</span>" . PHP_EOL;
					}
				}
				if($activateVCard) {
					$output .= "<a class='pz-v-card' href=\"?vcard={$pid}\" ><span class='fa fa-vcard'></span> " . __( "vCard", "cpl" ) . "</a>" . PHP_EOL;
				}
				if($cEMail  = get_post_meta($pid, 'cpl_contacts_email', true)) {
					$output .= "<a class='pz-contact-mail' href=\"mailto:{$cEMail}\" ><span class='fa fa-envelope'></span> " . __("Mail", "cpl") . "</a>" . PHP_EOL;
				}
				
				$output    .= "</div>" . PHP_EOL;
				$output    .= "</div>" . PHP_EOL;
				$output    .= "</div>" . PHP_EOL;
				
				// CLOSE pz-contact-wrap (DIV)
				$output    .= "</div>" . PHP_EOL;
				
				$output    .= "</div>" . PHP_EOL;
				$loopCount++;
				$n++;
			
			endwhile;

			$output        .= "</section>" . PHP_EOL;
			$cplPromoQuery->reset_postdata();
			$output            .= "<div style='clear:both;'></div>" . PHP_EOL;
			$output            .= "</div>" . PHP_EOL;
			$output            .= "</div>" . PHP_EOL;
		endif;
		
		return $output;
	}
	
	function getTaxonomyDataForCustomPostType($postType="cpl_contacts", $taxonomy="cpl_team_group"){
		/**
		 * @var \WP_Post $post
		 */
		wp_reset_postdata();
		$args 		        = array(
			"post_type"         => $postType,
			"taxonomy"          => $taxonomy,
			'post_status'       => 'publish',
			"posts_per_page"    => -1,
			'orderby'           => array(
				"title"           => "ASC",
				'post_date'       => 'DESC',
				'ID'              => 'ASC'
			),
		);
		
		$key                = "cpl_contacts_ordering";
		$arrTaxData         = array();
		$arrTaxDataReturn   = array();
		$arrSortOpts        = array();
		$cplQuery           = new \WP_Query($args);
		$bIntCounter        =  1000000;
		$affOrderKey        = "cpl_contacts_orderinga";
		
		foreach ($cplQuery->posts as $post) {
			$arrTaxPayload      = get_the_terms($post->ID, $taxonomy);
			if(is_array($arrTaxPayload)) {
				foreach($arrTaxPayload as $iKey=>$objTaxPayload){
					$tempArr[$objTaxPayload->term_id] = $objTaxPayload;
					## AVAILABLE PROPERTIES: term_id, name, slug, term_group, term_taxonomy_id, taxonomy, description, parent, count, object_id, filter:
					if ($objTaxPayload) {
						if (!array_key_exists($objTaxPayload->term_id, $arrTaxData)) {
							$tid                = $objTaxPayload->term_id;
							$tName              = $objTaxPayload->name;
							$tax                = $objTaxPayload->taxonomy;
							$arrTaxData[$tid]   = $tName;
							$getFieldParamNr2   = $tax . "_" . $tid;
							
							if ($oKey = get_post_meta($post->ID, $affOrderKey, true)) {
								$zlr_tax_order = array($affOrderKey => $oKey);
							} else {
								$zlr_tax_order = get_option("taxonomy_$tid");
							}
							
							if (isset($zlr_tax_order[$key]) && ($zlr_tax_order[$key] != "" || !empty($zlr_tax_order[$key]))) {
								$orderNum = $zlr_tax_order[$key];
								$arrSortOpts[$orderNum] = $tid;
							} else {
								$arrSortOpts[$bIntCounter] = $tid;
							}
						}
					}
					$bIntCounter++;
				}
			}
			
		}
		
		ksort($arrSortOpts, SORT_NUMERIC);
		
		foreach ($arrSortOpts as $orderNum=>$tmID) {
			if(!in_array($tmID, $arrTaxDataReturn)){
				$arrTaxDataReturn[$tmID] = $arrTaxData[$tmID];
			}
		}
		wp_reset_postdata();
		return $arrTaxDataReturn;
	}
	
	function injectQColumnWrapper($cols_per_row, $closePoint, $cssClass="pz-wrapper-block col-md-12", $nthElem="0", $wrapper="div"){
		$blockDisplay       = "";
		if( ($closePoint == 0) ){
			$blockDisplay   = "<{$wrapper} class='" . $cssClass . " box-lvl-" . $nthElem . "'>"  . PHP_EOL;
		}else if( ($closePoint % $cols_per_row) == 0 && ($closePoint != 0) ){
			$blockDisplay   = "</{$wrapper}><{$wrapper} class='" . $cssClass . " box-lvl-" . $nthElem . "'>"  . PHP_EOL;
		}
		return $blockDisplay;
	}
	
	function ContactCardResourcesEmbed() {
		wp_enqueue_style( 'fa-frontend_css',            $GLOBALS['CONTACT_CARD_PLUGIN']['css_url']  . 'font-awesome.min.css',  array(), '' );
		wp_enqueue_style( 'tbs-grid-css',               $GLOBALS['CONTACT_CARD_PLUGIN']['css_url'] . 'pz-grids.css',  array(), '' );
		wp_enqueue_style( 'cpl-contact-front-end-css',  $GLOBALS['CONTACT_CARD_PLUGIN']['css_url'] . 'cpl-contact-fe.css',                   array(), '' );

		wp_enqueue_script( 'cpl-contact-front-end-js',  $GLOBALS['CONTACT_CARD_PLUGIN']['js_url']  . 'cpl-contact-fe.js',                    array( 'jquery' ) );
	}
	
	### MANAGEMENT MENU HOOKS
	function registerContactCardSettings(){
		if (current_user_can('manage_contact_card')) {
			//REGISTER POIZ CONTACT CARD SETTINGS
			register_setting( 'cpl-c-card-settings-core',        'cpl_c_card_option_show_position');
			register_setting( 'cpl-c-card-settings-core',        'cpl_c_card_option_activate_v_card');
			register_setting( 'cpl-c-card-settings-core',        'cpl_c_card_option_show_group_title');
			register_setting( 'cpl-c-card-settings-core',        'cpl_c_card_option_max_cards_per_row');
			register_setting( 'cpl-c-card-settings-core',        'cpl_c_card_option_show_phone_number');
			register_setting( 'cpl-c-card-settings-core',        'cpl_c_card_option_allow_non_grouped');
		}
	}

	### MANAGEMENT MENU HOOKS
	function addContactCardManagementMenu() {
		// CREATE TOP-LEVEL SETTINGS PAGE:
		add_menu_page(  "Poiz Contact Forms Settings",
			__("C-Card Options", "cpl"),
			"administrator",
			"poiz-contact-card-config",
			"renderContactCardSettingsPage",
			"\"class='hidden-img' style='display:none'/><i class='pz-admin-icon fa fa-cogs' style='display:block;padding:25% 10%;'></i><null class=\"hidden"
		);
	}

	function renderContactCardSettingsPage(){
		echo PCH::getContactCardManagementPane(true);
	}

	function integrateContactCardAdminStyles() {
		wp_enqueue_style( 'fa-admin_css',       $GLOBALS['CONTACT_CARD_PLUGIN']['css_url']  . 'font-awesome.min.css',  array(), '' );
		wp_enqueue_style( 'pz-admin_css',       $GLOBALS['CONTACT_CARD_PLUGIN']['css_url']  . 'pz-contact-admin-style.css',  array(), '' );
	}

	function integrateContactCardAdminNotices() {
		settings_errors();
	}
	
	function encodeVCardData($vCardData){
		return base64_encode(serialize($vCardData));
	}
	
	function decodeVCardData($encodedData){
		return unserialize(base64_decode($encodedData));
	}
	
	function createVCard($arrVCardConfig){
		require_once __DIR__ . "/Code/vCard.class.php";
		$vc     = new vCard();
		$vc->set("filename", "");
		$vc->set("class", "PUBLIC");
		$vc->set("data", $arrVCardConfig);
		$vc->download();
	}

	function loadPluginTextDomain() {
		load_plugin_textdomain( 'cpl', false, dirname( plugin_basename(__FILE__) ) . '/lang/' );
	}

	/********************************************************************/
	/***********************CUSTOM POST TYPE: END************************/
	/********************************************************************/
	
	
	/********************************************************************/
	/******************ACTION HOOKS DECLARATIONS: BEGIN******************/
	/********************************************************************/
	add_action('plugins_loaded',                'loadPluginTextDomain');
	add_action('init',                          'ContactCardPostTypes');
	add_shortcode('contact_cards','ContactCardShortCode');
	add_action('wp_enqueue_scripts',            'ContactCardResourcesEmbed');
	
	register_activation_hook( __FILE__,'mapRolesAndCapabilities' );
	
	
	if ( is_admin() && current_user_can('manage_contact_card')) {
		// CREATE THE META-BOXES FOR USE IN PLUGIN MANAGEMENT:
		add_action('admin_init',                    'integrateAdminMetaBoxes');

		// CREATE MANAGEMENT SETTINGS:
		add_action( 'admin_init',                   'registerContactCardSettings' );

		// CREATE MANAGEMENT MENU:
		add_action('admin_menu',                    'addContactCardManagementMenu');

		// INTEGRATE ADMIN STYLES & SCRIPTS:
		add_action('admin_enqueue_scripts',         'integrateContactCardAdminStyles' );

		// INTEGRATE ADMIN NOTICES:
		add_action('admin_notices',                 'integrateContactCardAdminNotices');
	}
	/********************************************************************/
	/*******************ACTION HOOKS DECLARATIONS: END*******************/
	/********************************************************************/
