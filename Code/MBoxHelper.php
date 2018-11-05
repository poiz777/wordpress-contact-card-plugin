<?php
	/**
	 * Created by PhpStorm.
	 * Author: Poiz Campbell
	 */


	namespace Code;

	class MBoxHelper {

		protected $domain                   = "cpl";
		protected $location                 = "side";
		protected $priority                 = "high";
		protected $headline                 = "MetaBox";
		protected $nonce_key                = 'WNQAHAPURS_NONCE';
		protected $nonce_action             = 'pz_nonce_action';
		protected $append_nonce             = false;
		protected $show_description         = false;
		protected $post_type                = "cpl_contacts";
		protected $field_key                = "pz_key";
		protected $field_type               = "text";
		protected $field_options            = [];
		protected $field_name               = "pz_key";
		protected $hide_container           = false;
		protected $post_types               = [];
		protected $selectOptions            = [];
		protected $field_description        = "";
		protected $input_placeholder_text   = "";

		/**
		 * @param array $defaultParams
		 * Hook into the appropriate actions when the class is constructed.
		 */
		public function __construct(array $defaultParams=array()) {
			$defaultsMapped     = $this->auto_map_properties($defaultParams);
			if($defaultsMapped){
				add_action( 'add_meta_boxes',   [ $this, 'add_meta_box' ] );
				add_action( 'save_post',        [ $this, 'save' ] );
			}
		}

		/**
		 * @param string $post_type
		 * ADDS THE META BOX CONTAINER.
		 */
		public function add_meta_box( $post_type ) {
			if ( in_array( $post_type, $this->post_types)) {
				add_meta_box(
				  $this->field_name . "_meta_box"
				  ,__( $this->headline, $this->domain)
				  ,array( $this, 'render_meta_box_content' )
				  ,$post_type
				  ,$this->location
				  ,$this->priority
				);
			}
		}


		public function auto_map_properties($data){
			$this->post_types   = array();
			try {
				$refClass = new \ReflectionClass( 'Code\MBoxHelper' );
				foreach ($refClass->getProperties() as $refProperty) {
					$name           = $refProperty->getName();
					if(array_key_exists($name, $data)){
						$accessorName       = "";
						$arrName            = preg_split("#_#", $name);
						foreach($arrName as $intKey=>$propSplitName){
							$accessorName  .= ucfirst($propSplitName);
						}
						$setter             = "set" .$accessorName;
						call_user_func("self::" . $setter, $data[$name]);

						if($name == "post_type"){
							$this->post_types[] = $this->post_type;
						}
					}
				}
			} catch ( \ReflectionException $e ) {
				// TODO: HANDLE EXCEPTION
			}
			return true;
		}


		/**
		 * SAVES THE META-BOX DATA WHEN THE POST IS SAVED.
		 *
		 * @param int $post_id The ID of the post being saved.
		 * @return int | mixed
		 */
		public function save( $post_id ) {
			// VERIFY THAT THIS DATA CAME FROM OUR SCREEN WITH PROPER AUTHORIZATION
			// IS THE NONCE SET?
			if ( !isset($_POST[$this->nonce_key])){
				return $post_id;
			}

			// IS THE NONCE VALID?
			if ( !wp_verify_nonce( $_POST[$this->nonce_key], $this->nonce_action ) ) {
				return $post_id;
			}

			// IF THIS IS AN AUTO-SAVE, OUR FORM HASN'T YET BEEN SUBMITTED
			// IN THIS CASE, WE DO NOTHING...
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return $post_id;
			}


			// CHECK USER'S PERMISSION.
			if ( 'page' == @$_POST['post_type'] ) {
				if ( !current_user_can( 'edit_page', $post_id ) ){
					return $post_id;
				}

			} else {
				if ( !current_user_can( 'edit_post', $post_id ) ){
					return $post_id;
				}
			}

			// NOW, IT'S SAFE TO GO AHEAD AND SAVE THE DATA NOW...
			// BUT WE NEED TO FIRST SANITIZE THE INPUT, THOUGH ;-)
			if($this->field_type == "editor"){
				$data   = isset($_POST[$this->field_name]) ? htmlspecialchars($_POST[$this->field_name]) : null;
			}else if($this->field_type == "select_multi"){
				$data   = isset($_POST[$this->field_name]) ? implode(", ", array_map('htmlspecialchars', $_POST[$this->field_name])): null;
			}else{
				$data   = isset($_POST[$this->field_name]) ? sanitize_text_field( $_POST[$this->field_name] ) : null;
			}

			// NOW WE UPDATE THE META FILE.
			update_post_meta( $post_id, $this->field_key, $data);

			// REACHING HERE WE JUST RETURN TRUE [FOR FUN] - NOT THAT IT'S USEFUL :-)
			return true;
		}


		/**
		 * RENDERS THE CONTEND OF THE META BOX.
		 *
		 * @param \WP_Post $post The post object.
		 */
		public function render_meta_box_content( $post ) {
			// RETRIEVE SAVED POST META FROM DB - IF IT EXISTS
			$value = get_post_meta( $post->ID, $this->field_key, true );

			// RENDER THE FORM FIELD USING RETRIEVED DATA
			echo '<div class="';

			if($this->hide_container){
				echo 'hidden pz-hidden';
			}else{
				echo 'pz-meta-box-wrapper';
			}
			echo '" >';
			// FIRST, WE RENDER THE DESCRIPTION IF WE HAD TO...
			if($this->show_description){
				echo '<small class="pz_small small_' . $this->field_name . '" for="' . $this->field_name . '">';
				_e( $this->field_description, $this->domain );
				echo '</small><br /><br />';
			}
			switch($this->field_type){
				case "text":
				case "number":
				case "url":
				case "password":
				case "email":
				case "tel":
				case "hidden":
					echo '<input class="meta-box-helper" type="' . $this->field_type . '" id="' . $this->field_name . '" name="' . $this->field_name . '"';
					echo ' value="' . $value . '"   />';
					break;

				case "button":
					echo '<' .  $this->field_type . ' class="meta-box-helper" onclick="return false;" id="' . $this->field_name . '" name="' . $this->field_name . '" >' . $value . '</' .  $this->field_type . '>';
					break;

				case "submit":
					echo '<input class="meta-box-helper" type="' . $this->field_type . '" onclick="return false;" id="' . $this->field_name . '" name="' . $this->field_name . '" value="' . $value . '" >';
					break;

				case "select":
					echo '<select class="meta-box-helper" id="' . $this->field_name . '" name="' . $this->field_name . '" >';
					echo $this->getSelectFieldOptions(esc_attr($value));
					echo '</select>';
					break;

				case "select_multi":
					echo '<select class="meta-box-helper" multiple="multiple" style="min-height: 300px;" id="' . $this->field_name . '" name="' . $this->field_name . '[]" >';
					echo $this->getSelectFieldOptions(esc_attr($value));
					echo '</select>';
					break;

				case "textarea":
					echo '<textarea class="meta-box-helper" id="' . $this->field_name . '" name="' . $this->field_name . '"';
					echo ' placeholder="' . __($this->input_placeholder_text, $this->domain) . '"  />';
					echo esc_attr($value)  . '</textarea>';
					break;

				case "editor":
					$val    = $value;
					$val    = (!$val)?"":$val;
					wp_editor($val, $this->field_name, array(
						'wpautop'             => TRUE,
						'media_buttons'       => TRUE,
						'default_editor'      => '',
						'drag_drop_upload'    => FALSE,
						'tinymce'             => TRUE,
						'textarea_name'       => $this->field_name,
						'textarea_rows'       => 10,
						'tabindex'            => '',
						'tabfocus_elements'   => ':prev,:next',
						'editor_css'          => '',
						'editor_class'        => '',
						'teeny'               => TRUE,
						'dfw'                 => TRUE,
						'_content_editor_dfw' => TRUE,
						'quicktags'           => TRUE
					));
					break;

				default:
					echo '<input class="meta-box-helper" type="text" id="' . $this->field_name . '" name="' . $this->field_name . '"';
					echo ' value="' . esc_attr($value) . '" />';
					break;
			}

			echo '</div>';
			if($this->append_nonce){
				wp_nonce_field($this->nonce_action, $this->nonce_key);
			}
		}

		public function getSelectFieldOptions($default){
			$strOptionsHTML      = "";

			if(!empty($this->selectOptions)){
				$strOptionsHTML .= "<option value='' >" . __("Please Select Select Option(s)", $this->domain) . "</option>" . PHP_EOL;

				foreach($this->selectOptions as $optValue=>$optLabel){
					$selected           = "";
					if($this->field_type == "select_multi"){
						$default        = is_string($default) ? explode(", ", $default) : $default;
						if(in_array($optValue, $default)){
							$selected   = " selected='selected' ";
						}
					}else{
						if($optValue == $default){
							$selected    = " selected='selected' ";
						}
					}
					$strOptionsHTML .= "<option value='{$optValue}' {$selected} >{$optLabel}</option>" . PHP_EOL;
				}
			}
			return $strOptionsHTML;
		}

		public static function generate_unique_nonce_key($length=18) {
			$characters     = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$randomString   = '';
			$returnable     = "";
			for ($i = 0; $i < $length; $i++) {
				$randomString .= $characters[rand(0, strlen($characters) - 1)];
			}
			$returnable .= $randomString . "_NONCE";
			return $returnable;
		}

		public static function addNonceField($nonceKey, $nonceAction){
			wp_nonce_field($nonceAction, $nonceKey);
		}



		/**
		 * @return string
		 */
		public function getDomain() {
			return $this->domain;
		}

		/**
		 * @return string
		 */
		public function getFieldDescription() {
			return $this->field_description;
		}

		/**
		 * @return string
		 */
		public function getFieldKey() {
			return $this->field_key;
		}

		/**
		 * @return string
		 */
		public function getFieldName() {
			return $this->field_name;
		}

		/**
		 * @return string
		 */
		public function getFieldType() {
			return $this->field_type;
		}

		/**
		 * @return string
		 */
		public function getHeadline() {
			return $this->headline;
		}

		/**
		 * @return string
		 */
		public function getLocation() {
			return $this->location;
		}

		/**
		 * @return null
		 */
		public function getNonceKey() {
			return $this->nonce_key;
		}

		/**
		 * @return array
		 */
		public function getPostTypes() {
			return $this->post_types;
		}

		/**
		 * @return string
		 */
		public function getPriority() {
			return $this->priority;
		}

		/**
		 * @return null
		 */
		public function getShowDescription() {
			return $this->show_description;
		}

		/**
		 * @return string
		 */
		public function getInputPlaceholderText() {
			return $this->input_placeholder_text;
		}

		/**
		 * @return string
		 */
		public function getPostType() {
			return $this->post_type;
		}

		/**
		 * @return array
		 */
		public function getFieldOptions() {
			return $this->field_options;
		}

		/**
		 * @return bool
		 */
		public function getHideContainer() {
			return $this->hide_container;
		}

		/**
		 * @return array
		 */
		public function getSelectOptions() {
			return $this->selectOptions;
		}

		/**
		 * @return string
		 */
		public function getNonceAction(): string
		{
			return $this->nonce_action;
		}

		/**
		 * @return bool
		 */
		public function isAppendNonce(): bool
		{
			return $this->append_nonce;
		}


		/**
		 * @param string $domain
		 */
		public function setDomain($domain) {
			$this->domain = $domain;
		}

		/**
		 * @param string $field_description
		 */
		public function setFieldDescription($field_description) {
			$this->field_description = $field_description;
		}

		/**
		 * @param string $field_key
		 */
		public function setFieldKey($field_key) {
			$this->field_key = $field_key;
		}

		/**
		 * @param string $field_name
		 */
		public function setFieldName($field_name) {
			$this->field_name = $field_name;
		}

		/**
		 * @param string $field_type
		 */
		public function setFieldType($field_type) {
			$this->field_type = $field_type;
		}

		/**
		 * @param string $headline
		 */
		public function setHeadline($headline) {
			$this->headline = $headline;
		}

		/**
		 * @param string $location
		 */
		public function setLocation($location) {
			$this->location = $location;
		}

		/**
		 * @param mixed $nonce_key
		 * @param int $nonceText
		 */
		public function setNonceKey($nonce_key, $nonceText=0){
			$this->nonce_key = ($nonceText) ? $nonce_key . "_nonce" : $nonce_key;
		}

		/**
		 * @param array $post_types
		 */
		public function setPostTypes($post_types) {
			$this->post_types = $post_types;
		}

		/**
		 * @param string $priority
		 */
		public function setPriority($priority) {
			$this->priority = $priority;
		}

		/**
		 * @param null $show_description
		 */
		public function setShowDescription($show_description) {
			$this->show_description = $show_description;
		}

		/**
		 * @param string $input_placeholder_text
		 */
		public function setInputPlaceholderText($input_placeholder_text) {
			$this->input_placeholder_text = $input_placeholder_text;
		}

		/**
		 * @param string $post_type
		 */
		public function setPostType($post_type) {
			$this->post_type = $post_type;
		}

		/**
		 * @param array $field_options
		 */
		public function setFieldOptions($field_options) {
			$this->field_options = $field_options;
		}

		/**
		 * @param bool $hide_container
		 *
		 * @return MBoxHelper
		 */
		public function setHideContainer($hide_container ) {
			$this->hide_container = $hide_container;

			return $this;
		}

		/**
		 * @param array $selectOptions
		 *
		 * @return MBoxHelper
		 */
		public function setSelectOptions( $selectOptions ) {
			$this->selectOptions = $selectOptions;

			return $this;
		}

		/**
		 * @param string $nonce_action
		 *
		 * @return MBoxHelper
		 */
		public function setNonceAction( $nonce_action ) {
			$this->nonce_action = $nonce_action;

			return $this;
		}

		/**
		 * @param bool $append_nonce
		 *
		 * @return MBoxHelper
		 */
		public function setAppendNonce( $append_nonce ) {
			$this->append_nonce = $append_nonce;

			return $this;
		}

	}
