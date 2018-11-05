<?php

	namespace Code;


	/**
	 * OptionsBridge
	 **/
	class ContactCardOptionsBridge {

		/**
		 * @var array
		 */
		protected $optionsBank	            = [];
		protected $contactCardOptionKeys    = [
			'cpl_team_group_children',
			'cpl_c_card_option_show_position',
			'cpl_c_card_option_activate_v_card',
			'cpl_c_card_option_show_group_title',
			'cpl_c_card_option_max_cards_per_row',
			'cpl_c_card_option_allow_non_grouped',
			'cpl_c_card_option_show_phone_number',
		];

		public function __construct(){
			$this->initializeOptionsBank();
		}

		public function initializeOptionsBank(){
			// FIRST EXTRACT ALL RELEVANT OPTIONS AND DYNAMICALLY SET THEM:
			foreach($this->contactCardOptionKeys as $optionKey){
				$this->optionsBank[$optionKey] = get_option($optionKey);
			}
			return $this->optionsBank;
		}

		public function autoSetProperties($arrData){
			if(!is_null($arrData)){
				foreach($arrData as $prop=>$val){
					if(array_key_exists($prop, $this->optionsBank)){
						$this->optionsBank[$prop]    = $val;
					}
				}
			}
		}

		public function __get($name) {
			if(array_key_exists($name, $this->optionsBank)){
				return $this->optionsBank[$name];
			}
			return null;
		}

		public function __set($name, $value) {
			if(array_key_exists($name, $this->optionsBank)){
				$this->optionsBank[$name]   = $value;
			}
		}

		public function getOptionsBank() {
			return $this->optionsBank;
		}

	} 
