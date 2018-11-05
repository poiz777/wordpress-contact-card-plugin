<?php
/**
 * Created by PhpStorm.
 * Author: Poiz Campbell
 */

namespace Code;

class PoizContactCardHelper {

	public static function getContactCardManagementPane($addFooter=true) {
		$manPane    = "<div class='pz-setting-menu-base' id='pz-setting-menu-base'>";
		$manPane   .= "<div style='width:100%;margin:0;padding:0;'>";

		$manPane   .= "<div class='col-md-12 pz-admin-list-box'>";
		$manPane   .= "<h1 class='pz-heading-title'><span class='pz-complot'>" .__('Contact Card', 'cpl') . ": </span>" . __('Configuration Pane', 'cpl') . ".</h1>";
		$manPane   .= "<ul class='list-group list-unstyled pz-admin-list-group'>";
		$manPane   .= "<li class='list-group-item pz-admin-list-item col-md-12 pz-active active'><a href='' class='pz-settings-link'><span class='pz-icon-box'><span class='fa fa-cogs fa-2x'></span></span><span class='pz-text-box'>" . __("Basic Settings", "cpl") . "</span></a></li>";
		$manPane   .= "</ul>";
		$manPane   .= "</div>";     // ADMIN LIST BOX

		$manPane   .= "<div class='clear-float'></div>";
		$manPane   .=  self::renderCoreSettingsInterface();
		$manPane   .= ($addFooter) ? self::getPluginFooter() : "";

		$manPane   .= "</div>";     // 100% WIDE DIV
		$manPane   .= "</div>";     // PZ MENU BASE


		return $manPane;
	}

	public static function renderCoreSettingsInterface($asString=true){
		ob_start();
		settings_fields( 'cpl-c-card-settings-core' );
		$cpSettingsNonce    = ob_get_clean();

		$NO                 = __('No',              'cpl');
		$YES                = __('Yes',             'cpl');
		$i18nCpSave         = __('Save Changes',    'cpl');
		$cloudPix           = $GLOBALS['CONTACT_CARD_PLUGIN']['images_url'] . 'light_piercing_clouds.jpg';
		$formPostURL        = 'options.php';
		$fieldsOutput       = '';
		$arrBuildOptions    = [
			'cpl_c_card_option_activate_v_card'     => [
				'fieldType'     => 'select',
				'option'        => get_option('cpl_c_card_option_activate_v_card'),
				'label'         =>  __('Activate VCard Download', 'cpl') . '?',
				'selectOptions' => [
					'0' => $NO,
					'1' => $YES,
				],
			],

			'cpl_c_card_option_allow_non_grouped'   => [
				'fieldType'     => 'select',
				'option'        => get_option('cpl_c_card_option_allow_non_grouped'),
				'label'         =>  __('Render un-grouped Card Entries', 'cpl') . '?',
				'selectOptions' => [
					'0' => $NO,
					'1' => $YES,
				],
			],

			'cpl_c_card_option_show_group_title'    => [
				'fieldType'     => 'select',
				'option'        => get_option('cpl_c_card_option_show_group_title'),
				'label'         =>  __('Display Group Title above each Group', 'cpl') . '?',
				'selectOptions' => [
					'0' => $NO,
					'1' => $YES,
				],
			],

			'cpl_c_card_option_show_position'       => [
				'fieldType'     => 'select',
				'option'        => get_option('cpl_c_card_option_show_position'),
				'label'         =>  __('Show Position on the Flip-Side of Card', 'cpl') . '?',
				'selectOptions' => [
					'0' => $NO,
					'1' => $YES,
				],
			],

			'cpl_c_card_option_show_phone_number'   => [
				'fieldType'     => 'select',
				'option'        => get_option('cpl_c_card_option_show_group_title'),
				'label'         =>  __('Show Phone-Number on Reverse Side', 'cpl') . '?',
				'selectOptions' => [
					'0' => $NO,
					'1' => $YES,
				],
			],

			'cpl_c_card_option_max_cards_per_row'   => [
				'fieldType'     => 'select',
				'option'        => get_option('cpl_c_card_option_max_cards_per_row'),
				'label'         =>  __('Max. Number of Cards Per Row', 'cpl'),
				'selectOptions' => [
					'6' => '6',
					'4' => '4',
					'3' => '3',
					'2' => '2',
					'1' => '1',
				],
			],
		];

		foreach($arrBuildOptions as $fieldName => $arrFieldData){
			if(isset($arrFieldData['fieldType']) && $arrFieldData['fieldType'] == 'select'){
				$selectOptions  = self::getSelectOptions($arrFieldData['selectOptions'], $arrFieldData['option']);
				$fieldsOutput  .=<<<FOP
					<div class="form-group pz-form-group">
						<label class="pz-form-label" id="lbl_{$fieldName}" for="{$fieldName}">
							<span class="pz-label-name">{$arrFieldData['label']}</span>
						</label>
						<select name="{$fieldName}" id="{$fieldName}" class="form-control">
							{$selectOptions}
						</select>
					</div>
FOP;
			}
		}

		$usage              = __("Usage Instructions", 'cpl');
		$usageInstruction1  = __("First go to the Contact Card Menu <i class='wp-menu-image dashicons-before dashicons-id'></i> & select the «Team Group» Option to create Individual Categories (Team Groups)", 'cpl');
		$usageInstruction2  = __("Then add Contacts as needed. After that, simply enter the Shortcode <strong>[contact_cards group='Group Name']</strong>. Where <strong>group</strong> is the Group you created using the «Team Group» Taxonomy.", 'cpl');
		$usageInstruction3  = __("This Plugin also allows you to download the Contact-Person's platform-independent VCard, hence the name: <strong>Contact Card</strong>. Rolling the mouse over the Thumbnail flips the backside of the Image where all Contact information are rendered", 'cpl');
		$csInterface        = <<<CSI
	<div class="wrap pz-col-12 pz-global-wrapper">
		<h2 class='pz-plg-title-head pz-hidden' id='pz-plg-title-head'>&nbsp;</h2> 

		<section id="pz-form-wrapper" class="pz-form-wrapper">
			<form id="pz-core-settings-form" class="pz-core-settings-form form-horizontal"  method="post" action="{$formPostURL}">
				<section class="pz-section-left" id="pz-section-left">
					<div class="pz-editor-group">
						<label class="" id="" for="">
						<div class="pz-editor-box" id="pz-editor-box" style="">
							<img src="{$cloudPix}" alt="" class="" style="width:100%;height:auto;" />
						</div>
					</div>
					<div class="pz-editor-box" id="pz-editor-box" style="">
						<h1  class="pz-usage-head pz-plg-title-head" id="pz-usage-head">{$usage}</h1>
						<aside class="pz-usage-instruction">
							<ol>
								<li>{$usageInstruction1}</li>
								<li>{$usageInstruction2}</li>
								<li>{$usageInstruction3}</li>
							</ol>
						</aside>
					</div>
				</section>
				

				<section class="pz-section-right" id="pz-section-right">
					{$fieldsOutput}
					
					<div class="form-group pz-form-group">
						<label class="pz-hidden" id="" for=""></label>
						<input type="submit" name="submit" class="button-primary" id="submit" value="{$i18nCpSave}" placeholder=""/>
					</div>
				</section>
				{$cpSettingsNonce}
			</form>
		</section>
	</div>
CSI;
		return ($asString) ? $csInterface : ["uiView" => $csInterface];
	}

	public static function getGridFromRowCount($count){
		if(12%$count == 0){
			return (int)12/$count;
		}
		return null;
	}

	private static function getSelectOptions($arrOptValues, $default){
		$selectOptions  = "";
		foreach($arrOptValues as $optValue=>$optLabel){
			$selected       = ($optValue == $default) ? " selected='selected' " : "";
			$selectOptions .= "<option value='{$optValue}' {$selected}>{$optLabel}</option>";
		}
		return $selectOptions;
	}

	private static function getPluginFooter(){
		$footerLogo = $GLOBALS['CONTACT_CARD_PLUGIN']['images_url'] . 'logos/poiz_web_app_dev_logo.png';
		// FOOTER
		$uiDisplay  = "<div class='clear-float'></div>";
		$uiDisplay .= '<section class="pz-settings-footer-box"  id="pz-settings-footer-box" >';
		$uiDisplay .= "<div class='col-md-6 pz-copyright-box'>&copy; " . date("Y") . ", &nbsp; Poiz Campbell</div>";
		$uiDisplay .= "<div class='col-md-6 pz-logo-box'><img src='{$footerLogo}' alt='Complot Logo' class='complot-logo' id='complot-logo'></div>";
		$uiDisplay .= '</section>';
		return $uiDisplay;

	}
}
