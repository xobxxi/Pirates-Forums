<?php

class SmiliesDropdown_Listener
{

	public static function template_hook($name, &$contents, array $params, XenForo_Template_Abstract $template)
	{
		switch ($name)
		{
			case 'editor_js_setup':
					$search         = "\t});\n";
					$replace        = $template->create('editor_smiliesDropdown_js_setup')->render();
					$contents       = str_replace($search, $search . $replace, $contents);
			break;
		}
	}
}