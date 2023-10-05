<?php

/*
 * @author Anatoly Chikurov <anatoly@chikurov-seo.ru>
 */

class shopFixbackendPlugin extends shopPlugin
{	
	public function backendMenu() {
		if (!$this->getSettings('enabled')) {
			return;
		}
		
		$css = '';
		$js = '';
		$html = '';
		
		$css = $this->addStyles($css);
		$js = $this->addScripts($js);
		$html = $this->addHtml($css,$js);
		
		if ($html == '') {
			return;
		}
		return array(
			'aux_li'  => $html,
		);
    }

	public function addScripts($js) {
		if ($this->getSettings('custum_js')) {
			$js .= $this->getSettings('custum_js');
		}
		return $js;
	}
		
	public function addStyles($css) {
		//we can just copy css selectors from developer dashboard to use it in variable $css
		//later in function "changePriority" we will make each rule a higher priority
		
		if ($this->getSettings('plugins')) {
			$css .= '#wa-plugins-content #plugins-settings-form {margin-top: 20px;}';
			$css .= '#wa-plugins-content #plugins-settings-form .field {display: inline-block; width: 100%; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0;}';
			$css .= '#wa-plugins-content #plugins-settings-form .field:last-child {margin-bottom: 0; border-bottom: none;}';
			$css .= '#wa-plugins-content #plugins-settings-form .field .name {color: black; margin-bottom: 12px; line-height: 16px; font-size: 14px; position: relative; bottom: 5px; display: inline-block; margin-bottom: 0; padding-bottom: 0;}';
			$css .= '#wa-plugins-content #plugins-settings-form .field .name label {cursor: default;}';
			$css .= '#wa-plugins-content #plugins-settings-form .field .value span.hint {color: #444141; font-size: 0.9em; line-height: 1.2em; display: inline-block; margin-top: 7px;}';
			//$css .= '#wa-plugins-content #plugins-settings-form .field .value span.hint:before {content:"(";}';
			//$css .= '#wa-plugins-content #plugins-settings-form .field .value span.hint:after {content:")";}';
			$css .= '#wa-plugins-content #plugins-settings-form .field .value input, #wa-plugins-content #plugins-settings-form .field .value select {cursor: pointer;}';
			$css .= '#wa-plugins-content #plugins-settings-form .field .value textarea {min-width: 520px; max-width: 100%; box-sizing: border-box; min-height: 100px; padding: 5px;}';
			$css .= '#wa-plugins-content .form {max-width: 100%;}';
			//$css .= '.wa-inner-sidebar ul.menu-v li[id^="plugin-"].selected {box-shadow: 0 0 9px black; border-radius: 5px;}';
			//$css .= '.wa-inner-sidebar ul.menu-v li[id^="plugin-"].selected a {cursor: default; border-radius: 5px;}';
		}
		
		if ($this->getSettings('product')) {
			//product edit
			$css .= '.s-product-edit-link {float: right; height: auto;}';
			
			//plugin copychpu
			$css .= '.copychpu__generator {float: right; clear: right; margin-top: 10px; font-size: 12px; background: #f2f2f2; border: 2px solid green; border-radius: 25px;}';
			$css .= '.copychpu__link {padding: 7px 10px; display: inline-block; color: black; font-weight: 600;}';
			
			//labels
			$css .= '.s-product-form .field .name, .s-product-form .field .name span.hint, .g-seo-field__label, .g-seo-field__content {color: #000; font-size: 14px; line-height: 16px; padding-top: 4px;}';
			
			//product name 
			$css .= '.s-product-form .s-product-name-input {width: 100%; box-sizing: border-box; min-width: 100%; max-width: 100%; padding: 2px 4px;}';
			
			//plugin seo
			$css .= '#seo-product-settings-page ul[class^="field-group"] {margin: 5px 0;}'; //new SS
			
			//product summary 
			$css .= '.s-product-form #s-product-summary {min-height: 100px; max-height: 500px;}';
			
			//product inputs
			$css .= '.s-product-form input[type="text"] {padding: 2px 4px;}';

			//product title
			$css .= '.s-product-form #s-product-meta-title {width: 100%; box-sizing: border-box; padding: 2px 4px; font-weight: normal;}';
			$css .= '.s-product-form #s-product-meta-title::placeholder {font-size: 0;}';
			
			//product textareas
			$css .= '.s-product-form textarea {width: 100%; box-sizing: border-box; min-width: 100%; max-width: 100%; padding: 2px 4px; min-height: 40px;}';
			$css .= '.s-product-form textarea::placeholder {font-size: 0;}';
			
			//product tags
			$css .= '.s-product-form .tagsinput {width: 100%; box-sizing: border-box; min-width: 100%; max-width: 100%; padding: 5px 7px;}';

			//product sku
			$css .= 'table.s-product-skus td.s-sku {width: 300px; padding-right: 20px;}';
			$css .= 'table.s-product-skus td.s-sku input {width: 100%; max-width: 100%; min-width: 100%;}';
			
			//product meta og
			$css .= '#og-fieldgroup input {width: 100%; box-sizing: border-box; padding: 2px 4px;}';
			$css .= '#og-fieldgroup input::placeholder, #og-fieldgroup textarea::placeholder {font-size: 0;}';
			
			//product features
			$css .= '.s-product-form.features .fields .field {border-bottom: 1px solid #00000047; padding-bottom: 20px; margin-bottom: 15px;}';
			$css .= '.s-product-form.features .fields .field:last-child {border-bottom: none; padding-bottom: 0;}';
			$css .= '.s-product-form.features .fields .field .name span.hint {display: block; font-size: 0.8em; padding-bottom: 10px; color: #585858;}';
			$css .= '.s-product-form.features .fields .field .name span.hint:before {content: "id: "; color: black;}';
			
			//product pages
			$css .= '.s-product-form.pages .fields {width: 100%;}';
			$css .= '.s-product-form.pages .fields .field input {width: 100%; box-sizing: border-box; min-width: 100%; max-width: 100%;}';
		}
		
		if ($this->getSettings('category')) {
			//"edit category" and "delete category" buttons
			$css .= '.s-product-list-manage {background: #f0f0f0; padding: 0 10px 4px 10px; float: right;}'; //all SS
			$css .= '.s-product-list-manage a:first-child {margin-left: 0;}'; //all SS
			
			//labels
			$css .= '.s-category-settings .field .name {color: #000; font-size: 14px; line-height: 16px; padding-top: 4px;}'; //new SS
			$css .= '#s-product-list-settings-form .field .name {color: #000; font-size: 14px; line-height: 16px; padding-top: 4px;}'; //old SS
			$css .= '#s-product-list-create-form .field .name {color: #000; font-size: 14px; line-height: 16px; padding-top: 4px;}'; //old SS
			
			//fix form on old SS
			$css .= '#s-product-list-settings-form .s-dialog-form {width: 100%; box-sizing: border-box;}'; //old SS
			$css .= '#s-product-list-create-form .s-dialog-form {width: 100%; box-sizing: border-box;}'; //old SS
			
			//category name
			$css .= '.s-category-settings .s-full-width-input {width: 100%; box-sizing: border-box;}'; //new SS
			$css .= '#s-product-list-settings-form .s-full-width-input {width: 100%; box-sizing: border-box;}'; //old SS
			$css .= '#s-product-list-create-form .s-full-width-input {width: 100%; box-sizing: border-box;}'; //old SS
			
			//plugin seo
			$css .= '.s-category-settings ul[class^="field-group"] {margin: 5px 0;}'; //new SS
			$css .= '#s-product-list-settings-form ul[class^="field-group"] {margin: 5px 0;}'; //old SS
			$css .= '#s-product-list-create-form ul[class^="field-group"] {margin: 5px 0;}'; //old SS
			
			//inputs
			$css .= '.s-category-settings input {padding: 2px 4px;}'; //new SS
			$css .= '#s-product-list-settings-form input {padding: 2px 4px;}'; //old SS
			$css .= '#s-product-list-create-form input {padding: 2px 4px;}'; //old SS

			//title
			$css .= '.s-category-settings #s-meta-title {width: 100%; box-sizing: border-box; padding: 2px 4px; font-weight: normal;}'; //new SS
			$css .= '.s-category-settings #s-meta-title::placeholder {font-size: 0;}'; //new SS
			//title on "settings form"
			$css .= '#s-product-list-settings-form #s-meta-title {width: 100%; box-sizing: border-box; padding: 2px 4px; font-weight: normal;}'; //old SS
			$css .= '#s-product-list-settings-form #s-meta-title::placeholder {font-size: 0;}'; //old SS
			$css .= '#s-product-list-settings-form input[name="meta_title"] {width: 100%; box-sizing: border-box; padding: 2px 4px; font-weight: normal;}'; //old SS
			$css .= '#s-product-list-settings-form input[name="meta_title"]::placeholder {font-size: 0;}'; //old SS
			
			//title on "create form"
			$css .= '#s-product-list-create-form #s-meta-title {width: 100%; box-sizing: border-box; padding: 2px 4px; font-weight: normal;}'; //old SS
			$css .= '#s-product-list-create-form #s-meta-title::placeholder {font-size: 0;}'; //old SS
			$css .= '#s-product-list-create-form input[name="meta_title"] {width: 100%; box-sizing: border-box; padding: 2px 4px; font-weight: normal;}'; //old SS
			$css .= '#s-product-list-create-form input[name="meta_title"]::placeholder {font-size: 0;}'; //old SS
			
			//textareas
			$css .= '.s-category-settings textarea {width: 100%; box-sizing: border-box; min-width: 100%; max-width: 100%; padding: 2px 4px; min-height: 40px;}'; //new SS
			$css .= '.s-category-settings textarea::placeholder {font-size: 0;}'; //new SS
			$css .= '#s-product-list-settings-form textarea {width: 100%; box-sizing: border-box; min-width: 100%; max-width: 100%; padding: 2px 4px; min-height: 40px;}'; //old SS
			$css .= '#s-product-list-settings-form textarea::placeholder {font-size: 0;}'; //old SS
			$css .= '#s-product-list-create-form textarea {width: 100%; box-sizing: border-box; min-width: 100%; max-width: 100%; padding: 2px 4px; min-height: 40px;}'; //old SS
			$css .= '#s-product-list-create-form textarea::placeholder {font-size: 0;}'; //old SS
		}
		
		if ($this->getSettings('order')) {
			//order buttons
			$css .= 'ul.s-order-actions li .button {margin: 3px 10px; cursor: pointer; min-width: 130px; text-align: center; box-shadow: none;}';
			$css .= 'ul.s-order-actions li .button i {opacity: 1;}';
			$css .= 'ul.s-order-actions li .button i:first-child {margin-left: 15px;}';
			$css .= 'ul.s-order-actions li .button:hover {box-shadow: 0 0 10px #00000059; opacity: 0.9;}';
			
			//custumer
			$css .= '.s-order .details h3 {font-size: 16px;}';
			$css .= '.s-order .details h3 a {display: block; margin-bottom: 10px; font-size: 19px;}';
			$css .= '.s-order .details h3 .hint {font-size: 14px; color: black; font-style: normal; font-weight: 600;}';
			$css .= '.s-order .profile .details {display: inline-block; background: #f0f0f0; padding: 10px 15px; min-width: 550px; margin-bottom: 20px; margin-left: 15px;}';
			
			//custumer categories
			$css .= '.s-order .fixbackend__custumer_categories {margin-bottom: 25px; padding-top: 25px; font-style: italic; display: inline-block; min-width: 550px; width: 100%;}';
			$css .= '.s-order .fixbackend__custumer_category {margin-top: 7px; font-weight: 600; font-style: normal;}';
			
			//custumer cateogories (if first plugin)
			$css .= '.s-order .profile + .fixbackend__custumer_categories {margin-left: 65px; margin-top: -20px; background: #f0f0f0; padding: 10px 15px; width: auto;}';
			
			$css .= '.s-order .profile .s-customer-top-field-list li {margin-bottom: 10px; font-size: 16px;}';
			$css .= '.s-order .profile .s-customer-top-field-list li:last-child {margin-bottom: 0;}';
			$css .= '.s-order .profile .s-customer-top-field-list li i {position: relative; top: 3px;}';
			$css .= '.s-order .profile {margin-bottom: 0;}';
			
			//order table
			$css .= '#s-order-items tr td:nth-child(n+3) {width: 120px;}';
			
			//product name
			$css .= '#s-order-items tr td a {margin-bottom: 5px; font-size: 16px; display: inline-block;}';
			
			//product sku
			$css .= '#s-order-items tr td br + span.hint {color: black; font-size: 14px; margin: 5px 0; display: inline-block; background: #f2f7ff; padding: 5px 12px; border-radius: 10px;}';
			
			//products in order count
			$css .= '#s-order-items tr.s-product-wrapper td.align-right:nth-child(3) {color: black; font-size: 30px;}'; //new SS
			$css .= '#s-order-items tr.s-product-wrapper td.align-right span.gray {font-size: 14px; color: #aaa; position: relative; bottom: 6px;}'; //new SS
			$css .= '#s-order-items tr[data-id] td.align-right:nth-child(3) {color: black; font-size: 30px;}'; //old SS
			$css .= '#s-order-items tr[data-id] td.align-right span.gray {font-size: 14px; color: #aaa; position: relative; bottom: 6px;}'; //old SS
			
			//selected order
			//$css .= 'ul.s-orders li.selected {box-shadow: 0 0 9px black; border-radius: 5px; z-index: 130; cursor: default;}';
			//$css .= 'ul.s-orders li.selected a {cursor: default;}';
			
			//plugin bnpcomments 
			$css .= '#s-plugin-bnpcomments-form {margin-top: 20px;}';
			$css .= '#s-plugin-bnpcomments-comment {width: 100%; min-width: 100%; max-width: 100%; min-height: 70px; max-height: 200px; padding: 5px 7px; margin: 0; box-sizing: border-box; border: 1px solid;}';
			$css .= '.bnpcomments-body .dialog-window {min-width: 40%; left: 30%; max-height: 220px; min-height: 220px; height: 220px;}';
			$css .= '.bnpcomments-body .dialog-window .dialog-buttons-gradient {padding: 20px; text-align: center;}';
			$css .= '.bnpcomments-body .dialog-window input[type="submit"], .bnpcomments-body .dialog-window input[type="button"] {margin-right: 10px; cursor: pointer;}';
			$css .= '.bnpcomments-all tr td {min-width: 100px;}';
			$css .= '.bnpcomments-all tr td:last-child {max-width: 80px; width: 80px; min-width: 80px; padding: 0; text-align: center;}';
			$css .= '.bnpcomments-body .editable-comment, .bnpcomments-body .editable-comment textarea {width:100%; min-width:100%; max-width:100%;}';
			$css .= '.bnpcomments-body .editable-comment textarea {min-height: 120px; max-height: 120px; height: 120px; padding: 5px 7px;}';
		}
		
		if ($this->getSettings('button_hover')) {
			$css .= '.button {box-shadow: none; cursor: pointer;}';
			$css .= 'input[type="button"] {cursor: pointer;}';
			$css .= 'input[type="checkbox"] {cursor: pointer;}';
			$css .= '.button:hover {box-shadow: 0 0 10px #00000059; opacity: 0.9;}';
		}
		
		if ($this->getSettings('elements_selected')) {
			$css .= 'ul li.selected:not(#wa-app-shop):not([data-type="category"]):not(.bottom-padded):not(.top-padded) {box-shadow: 0 0 5px inset #757575; border-radius: 5px; cursor: default;}';
			$css .= '#s-category-list-block li.selected > a {box-shadow: 0 0 5px inset #757575; border-radius: 5px; cursor: default;}';
			$css .= '.wa-inner-sidebar ul.menu-v li.selected a {background: rgba(0,0,0,0.07);}';
			$css .= '.s-nolevel2-sidebar ul.menu-v li.selected a {background: rgba(0,0,0,0.07);}';
		}
		
		if ($this->getSettings('category_list')) {
			//category list wrap
			$css .= '#s-category-list-block {padding-right: 20px;}';
			
			//category list dragable
			$css .= 'ul.menu-v li.drag-newposition {height: 15px;}';
			$css .= 'ul.menu-v li.drag-newposition.active {background: #BB8; border-top: 0;}';
			
			//category list dragable
			$css .= '#s-category-list-block ul.menu-v.with-icons li {padding-top: 0; padding-bottom: 0;}';
			
			//category list gray icon
			$css .= 'ul.menu-v li[data-type="category"] i.rarr, ul.menu-v li[data-type="category"] i.darr {border: 4px solid white; cursor: pointer; left: 0; top: -2px; border-radius: 50%;}';
			$css .= 'ul.menu-v li[data-type="category"] i.rarr:hover, ul.menu-v li[data-type="category"] i.darr:hover {box-shadow: 0 0 10px black;}';
			
			//category list gray hover
			$css .= '.s-collection-list ul.menu-v li a.gray:hover {color: red;}';
			
			//category list add new
			$css .= '.s-collection-list .dr a .count {position: absolute; top: 7px; right: -30px;}';
			$css .= '.s-collection-list .dr a .count .add {display: block; border: 3px solid white; position: relative; bottom: 4px; left: 1px; border-radius: 50%;}';
			$css .= '.s-collection-list .dr a .count .add:hover {box-shadow: 0 0 10px black; cursor: pointer;}';
			
			//category count
			$css .= '.s-collection-list .count {position: reative; top: 5px; color: black;}';
			
			//category list add new
			$css .= '.s-collection-list ul.menu-v li a.gray i.folder {opacity: 0.5;}';
			
			//widen
			$css .= '#s-category-list-widen-arrows {opacity: 1; bottom: 50px; background: #f0f0f0; text-align: left; font-size: 60px; padding: 5px 20px 20px; line-height: 30px; box-shadow: 0 0px 15px rgba(0,0,0,0.3); border-radius: 0 5px 0 0;}';
			$css .= '#s-category-list-widen-arrows a {font-size: 1em;}';
		}

		if ($this->getSettings('table')) {
			//plugin productfeatures
			$css .= '#productfeatures-plugin-dialog .field .name {color: black;}';
			$css .= '#productfeatures-plugin-dialog .features-form div.field {display: inline-block; width: 100%;}';
			$css .= '#productfeatures-plugin-dialog .field .name {color: black; min-width: 170px; padding-top: 1px;}';
			$css .= '#productfeatures-plugin-dialog .field .name .hint {color: #585858; font-size: 0.8em; display: block; padding-bottom: 10px;}';
			$css .= '#productfeatures-plugin-dialog .field .name .hint:before {content: "id: "; color: black;}'; 
			$css .= '#productfeatures-plugin-dialog .field .value label {max-width: 600px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;}';
			
			//pencil
			$css .= '#product-list tr .show-on-hover, #product-list li .show-on-hover {visibility: visible; opacity: 1;}';
			
			//inputs
			$css .= '#s-product-list-table-container .drag-handle input {width: 17px; height: 17px; }';
			$css .= '#s-product-list-skus-container .drag-handle input {width: 17px; height: 17px; }';
			
			//changed inputs on table "skus"
			$css .= '#s-product-list-skus-container input.s-changed-input {font-weight: normal; box-shadow: 0 0 10px #17ff00; border-color: #17ff00;}';
		
			//placeholders
			$css .= '#s-product-list-skus-container .s-product-sku-purchase-price input::placeholder {font-size: 0;}';
			$css .= '#s-product-list-skus-container .s-product-sku-compare-price input::placeholder {font-size: 0;}';
			$css .= '#s-product-list-skus-container .s-product-sku-price input::placeholder {color: red;}';
			
			//td
			$css .= '#s-product-list-table-container table.zebra tr:hover td {background: #92929285;}';
			
			//td name 
			$css .= '#s-product-list-table-container .s-product-name {border-right: 1px solid black;}';
		}
		
		if ($this->getSettings('product_hints')) {
			//product menu
			$css .= '#s-product-edit-menu li.services {border-bottom: 2px solid red;}';
			$css .= '#s-product-edit-menu li.related {border-bottom: 2px solid red;}';
			$css .= '#s-product-edit-menu li.pages {border-bottom: 2px solid red;}';
			
			//product tags
			$css .= '.s-product-form .tagsinput {border: 2px solid red; height: 40px;}';
			
			//product title
			$css .= '.s-product-form #s-product-meta-title {border: 2px solid red;}';
			$css .= '.s-product-form #s-product-meta-title::placeholder {font-size: 0;}';
			
			//product seo name
			$css .= '.s-product-form .g-seo-field__content input {border: 2px solid red;}';
			$css .= '.s-product-form div[class*="smarty-textarea"] {border: 2px solid red;}';
			
			//product meta description
			$css .= '.s-product-form #s-product-meta-description {border: 2px solid red; height: 40px;}';
			$css .= '.s-product-form #s-product-meta-description::placeholder {font-size: 0;}';
			
			//product meta keywords
			$css .= '.s-product-form #s-product-meta-keywords {border: 2px solid red; height: 40px;}';
			$css .= '.s-product-form #s-product-meta-keywords::placeholder {font-size: 0;}';
			
			//product params 
			$css .= '.s-product-form textarea[name="product[params]"] {border: 2px solid red; height: 40px;}';
			
			//product meta og
			$css .= '#og-fieldgroup input, #og-fieldgroup textarea {border: 2px solid red;}';
		}
		
		if ($this->getSettings('list_and_features_hints')) {
			//lists
			$css .= '#s-set-list-block ul.menu-v.with-icons li[id*="_auto_"] {background: #ff000054;}';
			$css .= '#s-set-list-block ul.menu-v.with-icons li[id*="_hand_"] {background: #04ff3a26;}';
			
			//product features
			$css .= '.s-product-form.features .fields .field[data-code*="_auto_"] {background: #ff000054;}';
			$css .= '.s-product-form.features .fields .field[data-code*="_hand_"] {background: #04ff3a26;}';
			
			//plugin productfeatures
			$css .= '#productfeatures-plugin-dialog .features-form div.field {display: inline-block; width: 100%;}';
			$css .= '.features-form div.field[data-code*="_auto_"] {background: #ff000054;}';
			$css .= '.features-form div.field[data-code*="_hand_"] {background: #04ff3a26;}';
		}
		if ($this->getSettings('list_hide_id')) {
			$css .= '#s-set-list-block ul.menu-v.with-icons li span.hint {display: none;}';
		}
		
		if ($this->getSettings('mainmenu_links')) {
			//mainmenu links
			$css .= '#mainmenu ul.tabs li:not(.s-openstorefront) a {padding: 0 5px; font-size: 13px; max-width: 100px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;}';
			
			#mainmenu
			$css .= '#mainmenu {border-top: 1px solid #b9b9b9;}';
		}
		
		//hide columns in table "table"
		if ($this->getSettings('table_hide_name') || $this->getSettings('table_hide_price') || $this->getSettings('table_hide_stock')) {
			$css .= $this->cssTableTable();
		}
		
		//hide columns in table "skus"
		if ($this->getSettings('tableskus_hide_name') || $this->getSettings('tableskus_hide_purchase_price') || $this->getSettings('tableskus_hide_compare_price') || $this->getSettings('tableskus_hide_price') || $this->getSettings('tableskus_hide_stock')) {
			$css .= $this->cssTableSkus();
		}

		$css = $this->changePriority($css);
		
		if ($this->getSettings('custum_css')) {
			$css .= $this->getSettings('custum_css');
		}
		return $css;
	}
	
	public function cssTableTable() {
		$table_css = '';
		$table_hint_text = '';
		$table_hint_words = '';
		$table_hint_count_words = 0;
		
		if ($this->getSettings('table_hide_name')) {
			//table td name
			$table_css .= '#s-product-list-table-container .s-product-name, #s-product-list-table-container th[title="Название"], #s-product-list-table-container th.min-width:first-child + th {display: none;}';
			$table_hint_words .= ', «Название»';
			$table_hint_count_words ++;
		}
		
		if ($this->getSettings('table_hide_price')) {
			//table td price
			$table_css .= '#s-product-list-table-container .s-product-price, #s-product-list-table-container th[title="Цена"] {display: none;}';
			$table_hint_words .= ', «Цена»';
			$table_hint_count_words ++;
		}
		
		if ($this->getSettings('table_hide_stock')) {
			//table td stock
			$table_css .= '#s-product-list-table-container .s-product-stock, #s-product-list-table-container th[title="В наличии"] {display: none;}';
			$table_hint_words .= ', «В наличии»';
			$table_hint_count_words ++;
		}
		
		if ($table_hint_count_words > 0) {
			if ($table_hint_count_words == 1) {
				$table_hint_phrase = 'скрыт столбец:';
			} else {
				$table_hint_phrase = 'скрыты столбцы:';
			}
			
			$table_hint_words = substr($table_hint_words, 2);
			$table_hint_text = '"Через плагин «Доработки интерфейса бекенда» в таблице (вид: «Таблица») '.$table_hint_phrase.' '.$table_hint_words.'."';
			$table_css .= '#s-product-list-table-container {position: relative; padding-top: 80px;}';
			$table_css .= '#s-product-list-table-container:before {position: absolute; top: 0; left: 0; content: '.$table_hint_text.'; padding: 5px 10px; color: black; background: #ff000014; font-size: 14px; border: 1px solid red; font-style: italic;}';
		}
		return $table_css;
	}
	
	public function cssTableSkus() {
		$table_css = '';
		$table_hint_text = '';
		$table_hint_words = '';
		$table_hint_count_words = 0;
		
		if ($this->getSettings('tableskus_hide_name')) {
			//table td name
			$table_css .= '#s-product-list-skus-container .s-product-name, #s-product-list-skus-container th[title="Название"], #s-product-list-skus-container th.min-width:first-child + th {display: none;}';
			$table_hint_words .= ', «Название»';
			$table_hint_count_words ++;
		}
		
		if ($this->getSettings('tableskus_hide_purchase_price')) {
			//table td purchase price
			$table_css .= '#s-product-list-skus-container .s-product-sku-purchase-price, #s-product-list-skus-container th[title="Закупочная цена"] {display: none;}';
			$table_hint_words .= ', «Закупочная цена»';
			$table_hint_count_words ++;
		}
		
		if ($this->getSettings('tableskus_hide_compare_price')) {
			//table td compare price
			$table_css .= '#s-product-list-skus-container .s-product-sku-compare-price, #s-product-list-skus-container th[title="Зачеркнутая цена"] {display: none;}';
			$table_hint_words .= ', «Зачеркнутая цена»';
			$table_hint_count_words ++;
		}
		
		if ($this->getSettings('tableskus_hide_price')) {
			//table td price
			$table_css .= '#s-product-list-skus-container .s-product-price, #s-product-list-skus-container .s-product-sku-price, #s-product-list-skus-container th[title="Цена"] {display: none;}';
			$table_hint_words .= ', «Цена»';
			$table_hint_count_words ++;
		}
		
		if ($this->getSettings('tableskus_hide_stock')) {
			//table td stock
			$table_css .= '#s-product-list-skus-container .s-product-stock, #s-product-list-skus-container th[title="В наличии"] {display: none;}';
			$table_css .= '#s-product-list-skus-container .s-product-sku-price + td {display: none;}';
			
			$table_hint_words .= ', «В наличии»';
			$table_hint_count_words ++;
		}
		
		if ($table_hint_count_words > 0) {
			if ($table_hint_count_words == 1) {
				$table_hint_phrase = 'скрыт столбец:';
			} else {
				$table_hint_phrase = 'скрыты столбцы:';
			}
			
			$table_hint_words = substr($table_hint_words, 2);
			$table_hint_text = '"Через плагин «Доработки интерфейса бекенда» в таблице (вид: «Артикулы») '.$table_hint_phrase.' '.$table_hint_words.'."';
			$table_css .= '#s-product-list-skus-container {position: relative; padding-top: 80px;}';
			$table_css .= '#s-product-list-skus-container:before {position: absolute; top: 0; left: 0; content: '.$table_hint_text.'; padding: 5px 10px; color: black; background: #ff000014; font-size: 14px; border: 1px solid red; font-style: italic;}';
		}
		return $table_css;
	}
	
	public function changePriority($css) {
		//add "!important"
		$css  =  str_replace(" !important","",$css);
		$css  =  str_replace("!important","",$css);
		$css  =  str_replace(";"," !important;",$css); 
		
		//add "html body"
		$css  =  'html body ' . $css;
		$css  =  str_replace("}","}html body ",$css);
		$css = substr($css,0,-10);

		return $css;
	}
	
	public function addHtml($css,$js) {
		
		$html = '';
		
		if ($css != '') {
			$html .= PHP_EOL.PHP_EOL.'<!-- start FIXBACKEND PLUGIN CSS-->'.PHP_EOL.'<style>'.$css.'</style>'.PHP_EOL.'<!-- /end FIXBACKEND PLUGIN CSS-->'.PHP_EOL.PHP_EOL;
		}
		if ($js != '') {
			$html .= PHP_EOL.PHP_EOL.'<!-- start FIXBACKEND PLUGIN JS-->'.PHP_EOL.$js.PHP_EOL.'<!-- /end FIXBACKEND PLUGIN JS-->'.PHP_EOL.PHP_EOL;
		}
		return $html;
	}
	
	public function backendOrder($order) {
		if (!$this->getSettings('enabled')) {
			return;
		}
		if (!$this->getSettings('order')) {
			return;
		}
		
		$html = $this->addCustumerCategories($order);
		
        return array(
            'info_section' => $html,
        );
    }
	
	public function addCustumerCategories($order) {
		$custumer_id = $order['contact_id'];
		$ccm = new waContactCategoriesModel();
        $category_ids = $ccm->getContactCategories($custumer_id);
		
		$categories_html = '';
		$categories_count = 0;
		foreach ($category_ids as $i) {
			if ($i['app_id'] == 'shop') {
				$name = '';
				$icon = '';
				if ($i['name']) {
					$name = $i['name'];
				}
				if ($i['icon']) {
					$icon = $i['icon'];
				}
				$categories_html .= '<div class="fixbackend__custumer_category"><i class="icon16 '.$icon.'"></i>'.$name.'</div>'; 
				$categories_count ++; 
			}
		}
		
		$html = '';
        if ($categories_html != '') {
			if ($categories_count > 1) {
				$word = 'Категории';
			} else {
				$word = 'Категория';
			}
			$html = '<div class="fixbackend__custumer_categories">'.$word.' покупателя: '.$categories_html.'</div>';
		}
		
		return $html;
	}
}