<?php
// Heading
$_['heading_title']       						= '<span style="color:#449DD0; font-weight:bold">Translit</span><span style="font-size:12px; color:#999"> by <a href="http://www.opencart.com/index.php?route=extension/extension&filter_username=Dreamvention" style="font-size:1em; color:#999" target="_blank">Dreamvention</a></span>'; 
$_['heading_title_main']  						= 'Translit';

// Text
$_['text_edit']            						= 'Edit Translit settings';
$_['text_modules']         						= 'Modules';
$_['text_settings']       						= 'Settings';
$_['text_translit_symbol']   					= 'Translit Symbols';
$_['text_translit_language_symbol']				= 'Translit Language Symbols';
$_['text_trim_symbol']   						= 'Trim Symbols';
$_['text_instructions']   						= 'Instructions';
$_['text_module']              					= 'Module';
$_['text_yes'] 									= 'Yes';
$_['text_no'] 									= 'No';
$_['text_enabled']          					= 'Enabled';
$_['text_disabled']          					= 'Disabled';
$_['text_transform_none']          				= 'No Transform';
$_['text_transform_lower_to_upper']     		= 'Lower To Upper';
$_['text_transform_upper_to_lower']     		= 'Upper To Lower';
$_['text_delete_translit_symbol']       		= 'Are you sure you want to remove the Translit Symbol?';
$_['text_delete_translit_language_symbol'] 		= 'Are you sure you want to remove the Translit Language Symbol?';
$_['text_delete_trim_symbol']       			= 'Are you sure you want to remove the Trim Symbol?';
$_['text_powered_by']               			= 'Tested with <a href="https://shopunity.net/">Shopunity.net</a><br/>Find more extensions at <a href="https://dreamvention.ee/">Dreamvention.com</a>';
$_['text_instructions_full'] 					= '
<div class="row">
	<div class="col-sm-2">
		<ul class="nav nav-pills nav-stacked">
			<li class="active"><a href="#vtab_instruction_install"  data-toggle="tab">Installation and Updating</a></li>
			<li><a href="#vtab_instruction_setting" data-toggle="tab">Settings</a></li>
			<li><a href="#vtab_instruction_translit_symbol" data-toggle="tab">Translit Symbols</a></li>
			<li><a href="#vtab_instruction_translit_language_symbol" data-toggle="tab">Translit Language Symbols</a></li>
			<li><a href="#vtab_instruction_trim_symbol" data-toggle="tab">Trim Symbols</a></li>
			<li><a href="#vtab_instruction_connect" data-toggle="tab">Connection</a></li>
		</ul>
	</div>
	<div class="col-sm-10">
		<div class="tab-content">
			<div id="vtab_instruction_install" class="tab-pane active">
				<div class="tab-body">
					<h3>Installation</h3>
					<ol>
						<li>Unzip distribution file.</li>
						<li>Upload everything from the folder <code>UPLOAD</code> into the root folder of you shop.</li>
						<li>Goto admin of your shop and navigate to extensions -> modules -> Translit.</li>
						<li>Click install button.</li>
					</ol>
					<div class="bs-callout bs-callout-info">
						<h4>Note!</h4>
						<p>Our installation process requires you to have access to the internet because we will install all the required dependencies before we install the module.</p>
					</div>
					<div class="bs-callout bs-callout-warning">
						<h4>Warning!</h4>
						<p>If you get an error on this step, be sure to make you <code>DOWNLOAD</code> folder (usually in system folder of you shop) writable.</p>
					</div>
					<h3>Updating</h3>
					<ol>
						<li>Unzip distribution file.</li>
						<li>Upload everything from the folder <code>UPLOAD</code> into the root folder of you shop.</li>
						<li>Click overwrite for all files.</li>
					</ol>
					<div class="bs-callout bs-callout-info">
						<h4>Note!</h4>
						<p>Although we follow strict standards that do not allow feature updates to cause a full reinstall of the module, still it may happen that major releases require you to uninstall/install the module again before new feature take place.</p>
					</div>
					<div class="bs-callout bs-callout-warning">
						<h4>Warning!</h4>
						<p>If you have made custom corrections to the code, your code will be rewritten and lost once you update the module.</p>
					</div>
				</div>
			</div>
			<div id="vtab_instruction_setting" class="tab-pane">
				<div class="tab-body">
					<h3>Settings</h3>
					<p>Here you can set the default values for the following options:</p>
					<ol>
						<li><strong>Translit Symbols</strong> converts special characters (%, &, ", etc.) to the characters shown on the tab "Translit Symbols".</li>
						<li><strong>Translit Language Symbols</strong> converts language characters to the characters shown on the tab "Translit Language Symbols".</li>
						<li><strong>Transform Language Symbols</strong> converts the language characters to large or small characters.</li>
					</ol>		
					<div class="bs-callout bs-callout-info">
						<h4>Note!</h4>
						<p>These options can be overwritten by those modules that use module Translit.</p>
					</div>
				</div>
			</div>
			<div id="vtab_instruction_translit_symbol" class="tab-pane">
				<div class="tab-body">
					<h3>Translit Symbols</h3>
					<p>Here you can add, edit and delete Translit Symbols. To add the Translit Symbol, press button <span class="btn btn-primary btn-xs"><i class="fa fa-plus-circle"></i></span>. Then you must specify the fields "Input Symbol" and "Output Symbol". To remove the Translit Symbol, press button <span class="btn btn-danger btn-xs"><i class="fa fa-minus-circle"></i></span> in right of the Translit Symbol, that you want to delete.</p>
					<div class="bs-callout bs-callout-info">
						<h4>Note!</h4>
						<p>Translit Symbols also include symbol of line break (\n, \r) and symbol tab (\t).</p>
					</div>
					<div class="bs-callout bs-callout-warning">
						<h4>Warning!</h4>
						<p>Be careful with edit Translit Symbols parameters. After removal of the important parameters can be broken work of those modules that use module Translit.</p>
					</div>
				</div>
			</div>
			<div id="vtab_instruction_translit_language_symbol" class="tab-pane">
				<div class="tab-body">
					<h3>Translit Language Symbols</h3>
					<p>Here you can add, edit and delete Translit Language Symbols. To add the Translit Language Symbol, press button <span class="btn btn-primary btn-xs"><i class="fa fa-plus-circle"></i></span>. Then you must specify the fields "Input Symbol" and "Output Symbol". To remove the Translit Language Symbol, press button <span class="btn btn-danger btn-xs"><i class="fa fa-minus-circle"></i></span> in right of the Translit Language Symbol, that you want to delete.</p>
				</div>
			</div>
			<div id="vtab_instruction_trim_symbol" class="tab-pane">
				<div class="tab-body">
					<h3>Trim Symbols</h3>
					<p>Here you can add, edit and delete Trim Symbols. To add the Trim Symbol, press button <span class="btn btn-primary btn-xs"><i class="fa fa-plus-circle"></i></span>. Then you must specify the fields "Symbol". To remove the Trim Symbol, press button <span class="btn btn-danger btn-xs"><i class="fa fa-minus-circle"></i></span> in right of the Trim Symbol, that you want to delete.</p>
				</div>
			</div>
			<div id="vtab_instruction_connect" class="tab-pane">
				<div class="tab-body">
					<h3>Connection</h3>
					<p>Translit can be connected in the admin and catalog:</p>
					<p>
					if (file_exists(DIR_APPLICATION . \'model/extension/module/d_translit.php\')) {<br/>
					$translit_data = array(<br/>
					    \'translit_symbol_status\' => $translit_symbol_status, //(true, false)<br/>
						\'translit_language_symbol_status\' => $translit_language_symbol_status, //(true, false)<br/>
						\'transform_language_symbol_id\' => $transform_language_symbol_id //(0 - no convert, 1 - convert to large, 2 - convert to small)<br/>
						\'trim_symbol_status\' => $trim_symbol_status, //(true, false)<br/>
					);<br/>
					$this->load->model(\'extension/module/d_translit\');<br/>
					$text_new = $this->model_extension_module_d_translit->translit($text_old, $translit_data);<br/>
					}
					</p>
					<p>If values of $translit_data are not specified, the transliteration will happen with the default values from the tab "Settings".</p>
				</div>
			</div>
		</div>
	</div>
</div>';
$_['text_not_found'] 							= '
<div class="jumbotron">
<h1>Please install Shopunity</h1>
<p>Before you can use this module you will need to install Shopunity. Simply download the archive for your version of opencart and install it view Extension Installer or unzip the archive and upload all the files into your root folder from the UPLOAD folder.</p>
<p><a class="btn btn-primary btn-lg" href="https://shopunity.net/download" target="_blank">Download</a></p>
</div>';	

// Entry
$_['entry_translit_symbol']						= 'Translit Symbols';
$_['entry_translit_language_symbol']			= 'Translit Language Symbols';
$_['entry_transform_language_symbol']			= 'Transform Language Symbols';
$_['entry_trim_symbol']							= 'Trim Symbols';
$_['entry_input_symbol']						= 'Input Symbol';
$_['entry_ouput_symbol']						= 'Output Symbol';
$_['entry_symbol']								= 'Symbol';

// Button		
$_['button_save'] 								= 'Save';
$_['button_save_and_stay'] 						= 'Save and Stay';
$_['button_cancel'] 							= 'Cancel';
$_['button_add_translit_symbol'] 				= 'Add Translit Symbol';
$_['button_delete_translit_symbol'] 			= 'Delete Translit Symbol';
$_['button_add_translit_language_symbol'] 		= 'Add Translit Language Symbol';
$_['button_delete_translit_language_symbol']	= 'Delete Translit Language Symbol';
$_['button_add_trim_symbol'] 					= 'Add Trim Symbol';
$_['button_delete_trim_symbol'] 				= 'Delete Trim Symbol';

// Success
$_['success_save']        						= 'Success: You have modified module Translit!';

// Error
$_['error_warning']          					= 'Warning: Please check the form carefully for errors!';
$_['error_permission']    						= 'Warning: You do not have permission to modify module Translit!';

?>