<?php 
//@app_header; 

class cjaxFormat {
	
	private static $prompts = 0;
	
	/**
	 * Show a prompt message
	 *
	 * @param string $str
	 * @param string $title
	 * @param bol $escape
	 * @return STRING
	 */
	function prompt($msg,$title=null,$id = 'cjax_prompt_box')
	{
		self::$prompts++;
		
		$string = null;
		
		if($type = CJAX::CSS_INFO) {
				$css = "information";
		} elseif ($type == CJAX::CSS_WARNING) {
				$css = "warning";
		}
		
		$ajax = CJAX::getInstance();
		
		$msg_id = $id.self::$prompts;
		
		
		if($title) {
			$string ="<div onmousedown=\"CJAX.drag(event,'$msg_id')\" class='$css bar' style='padding: 5px 5px 0px 4px;font-weight: bold;'>$title <a href='javascript:void(0)' onclick=\"CJAX.remove('$msg_id');\"/><div style='position: relative; float: right; top: -4px' class='cjax_close'></div></a></div>";
		}
		
		$string ="
		<div id='$msg_id' class='cjax_prompt_box_class'>
		$string
		<div>
			$msg
			<div style='clear:both'></div>
		</div>
		</div>
		
		";
		return $string;
	}

			
	public function message($text,$type=CJAX::CSS_SUCCESS)
	{
		$ajax = CJAX::getInstance();
		if($type==CJAX::CSS_SUCCESS || !$type) {
            $css  = " cjax_success";
		} else if($type==CJAX::CSS_WARNING) {
			$css  = " cjax_warning";
		} else if($type==CJAX::CSS_ERROR) {
			$css  = " cjax_error";
		} else if($type==CJAX::CSS_PROCESS) {
			$css  = " cjax_process cjax_loading_f";
		} else if($type==CJAX::CSS_INFO) {
			$css  = " cjax_info";
		}
		$data ="<div class='cjax_message cjax_message_type$css'>$text</div>\n";
		
		return $data;
	}
	
	
	
}