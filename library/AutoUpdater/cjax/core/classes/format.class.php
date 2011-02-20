<?php 
/** ################################################################################################**   
* Copyright (c)  2008  CJ.   
* Permission is granted to copy, distribute and/or modify this document   
* under the terms of the GNU Free Documentation License, Version 1.2   
* or any later version published by the Free Software Foundation;   
* Provided 'as is' with no warranties, nor shall the autor be responsible for any mis-use of the same.     
* A copy of the license is included in the section entitled 'GNU Free Documentation License'.   
*   
*   CJAX  3.1                $     
*   ajax made easy with cjax                    
*   -- DO NOT REMOVE THIS --                    
*   -- AUTHOR COPYRIGHT MUST REMAIN INTACT -   
*   Written by: CJ Galindo                  
*   Website: cjax.net                     $      
*   Email: cjxxi@msn.com    
*   Date: 2/12/2007                           $     
*   File Last Changed:  02/19/2011            $     
**####################################################################################################    */    

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