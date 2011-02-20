<?php
/** ################################################################################################**   
* Copyright (c)  2008  CJ.   
* Permission is granted to copy, distribute and/or modify this document   
* under the terms of the GNU Free Documentation License, Version 1.2   
* or any later version published by the Free Software Foundation;   
* Provided 'as is' with no warranties, nor shall the autor be responsible for any mis-use of the same.     
* A copy of the license is included in the section entitled 'GNU Free Documentation License'.   
*   
*   CJAX  3.1 RC3                $     
*   ajax made easy with cjax                    
*   -- DO NOT REMOVE THIS --                    
*   -- AUTHOR COPYRIGHT MUST REMAIN INTACT -   
*   Written by: CJ Galindo                  
*   Website: https://github.com/cjax/Cjax-Framework                     $      
*   Email: cjxxi@msn.com    
*   Date: 2/12/2007                           $     
*   File Last Changed:  02/20/2011            $     
**####################################################################################################    */   

/**
 * Load core events
 */
require_once 'core.class.php';	
class CJAX_FRAMEWORK Extends CoreEvents {

	public $message_id;
	
	
	/**
	 * 
	 * Replaces a pattern in an elements HTML.
	 */
	public function replace($element_id,$find,$replace)
	{
		$data['element'] = $element_id;
		$data['find'] = $find;
		$data['replace'] = $replace;
		
		return $this->xml($data);
	}
	
	function select($element,$options = array(),$allow_input = false)
	{
		$select['element'] = $element;
		$select['options'] = $this->mkArray($options);
		$select['allow_input'] = $allow_input;
		
		return $this->xml($select);
	}
	   
    /**
     * Submit a form
     * 
     * @param require string $url  url where the request will be sent to
     * @param require string $form_id  the form id
     * @param optional string $container_id = null  alternative element where to load the response
     * @param optional string $confirm  ask before sending the request
     * @return unknown
     */
    public function form($url,$form_id,$container_id = null,$confirm=null)
    {
        $ajax = CJAX::getInstance();
        $params = '';
        $out[] = "[url]{$url}[/url]";
        if($form_id) $out[] = "[form]{$form_id}[/form]";
        if(!is_null($container_id)) $out[] = "[container]{$container_id}[/container]";
       
        
    	if(is_bool($ajax->text) && $ajax->text===false) {
			$out[] = "[text]no_text[/text]";
		} else if($ajax->text) {
			$out[] = "[text]{$ajax->text}[/text]";	
		}
		
         if($confirm) $out[] = "[confirm]{$confirm}[/confirm]";
        
        if($ajax->debug) $out[] = "[debug]1[/debug]";   
        if($ajax->post) {
			$out[] = "[method]{$ajax->post}[/method]";
        } 
        
        if($dir = $ajax->getSetting('cjax_dir')) $out[] = "[cjax_dir]{$dir}[/cjax_dir]";
        
        if($dubug = $ajax->debug) $out[] = "[debug]{$dubug}[/debug]";
        
        return $this->xml("<do>exe_form</do><out>('".implode($out)."')</out>;");
    }
    
    
	/**
	 * assign styles to an element
	 *
	 * @param unknown_type $style
	 */
	function style($element_id,$style = array() )
	{
		$data['element'] = $element_id;
		$data['style'] = $this->mkArray($style, true);
		return $this->xml($data);
	}
    
    /**
	 * Create Ajax calls
	 *
	 * @param required string $url
	 * @param optional string $elem_id = null
	 * @param optional string $cmode = 'get'
	 * @return string
	 */	
	public function call($url,$element_id=null,$confirm=null)
	{
		$search = 'http://';
		if(strpos($url,$search)!==false) {
			$info=@parse_url($url);
			if(isset($info['query']) && $info['query']) {
				$info['path'] .= '?'.$info['query'];
			}
			if($this->post) {
				$this->post = $info['query'];
			}
			$response = $this->connect($info['host'],$info['path']);
			
			if(!$response || strpos(strtolower($response),'not found')!==false) {
				return;
			}
			
			$this->xml("<do>log</do><log>{$this->encode($response)}</log>");
			return $response;
		}
		$ajax = CJAX::getInstance();
		
		if($ajax->post)  {
			if(is_array($ajax->post)) {
				$args = "&".http_build_query($ajax->post);
				$out[] = "[args]{$args}[/args]";
				$out[] = "[mode]POST[/mode]";
			} else {
				$out[] = "[mode]POST[/mode]";
			}
		}
		
		$out[] = "[url]{$url}[/url]";
		if($element_id) $out[] = "[element]{$element_id}[/element]";
		if(is_bool($ajax->text) && $ajax->text===false) {
			$out[] = "[text]no_text[/text]";
		} else if($ajax->text) {
			$out[] = "[text]{$ajax->text}[/text]";	
		}
			
		if($ajax->debug) $out[] = "[debug]1[/debug]";
		if($confirm) $out[] = "[confirm]{$ajax->encode($confirm)}[/confirm]";
		
		if($dir = $ajax->getSetting('cjax_dir')) $out[] = "[cjax_dir]{$dir}[/cjax_dir]";
		
		$out[] = "[time]".time()."[/time]";
		
		if($ajax->loading) {
			$out[] = "[is_loading]1[/is_loading]";
		}
		
		if(isset($out) && is_array($out)) $params = implode($out);
		return $this->xml("<do>exe_html</do><out>('{$params}')</out>");
	}
	
	private static $overLay = array();
	/**
	 * 
	 * Enter description here ...
	 * @param $url
	 * @param $use_cahe
	 * @param $options
	 * Accepted  $options Example
	 *  $options['top'] = '50px';
		$options['left'] = '100px';
		$options['transparent'] = '60%'; // from 1 transparent to 100 solid, how transparent should it be? default is 80.
		$options['color'] = '#FF8040'
	 */
	function overLay($url = null,$use_cahe = false,$options = array())
	{
		if($options) {
			if(is_array($options)) {
				foreach($options as $k =>$v ) {
					$data[$k] = $v;
				}
				$data['options'] = $this->mkArray($options,true);
			} else {
				$pieces = explode("&",$options);
				if(!empty($pieces)) {
					foreach($pieces as $k => $v) {
						$v = explode("=",$v);
						if(!empty($v) && isset($v[0]) && isset($v[1])) {
							$_options[$v[0]] = $v[1];
						}
					}
				}
				$data['options'] = $this->mkArray($_options,true);
			}
		} 
		
		$pieces = explode("&",$url);
		$url_data = array();
		if(!empty($pieces)) {
			foreach($pieces as $k => $v) {
				$v = explode("=",$v);
				if(!empty($v) && isset($v[0]) && isset($v[1])) {
					$url_data[$v[0]] = $v[1];
				}
			}
		}
		$data['url'] = $url;
		$data['cache'] = $use_cahe;
		if($url) {
			$data['template'] = $this->encode(file_get_contents(CJAX_DIR_CORE."/templates/overlay.html"));
		}
		if(!isset($url_data['cjax_dir'])) {
			if($dir = $this->getSetting('cjax_dir')) $data['cjax_dir'] = $dir;
		}
		return $this->xml($data);
	}
	
	function overlayContent($content = null,$options = null)
	{
		$_options = array();
		
		$data['do'] = 'overLay';
		$data['content'] = $this->encode($content);
		//$data['cache'] = $use_cahe;
		
		
		if($options) {
			if(is_array($options)) {
				foreach($options as $k =>$v ) {
					$data[$k] = $v;
				}
				$data['options'] = $this->mkArray($options,true);
			} else {
				$pieces = explode("&",$options);
				if(!empty($pieces)) {
					foreach($pieces as $k => $v) {
						$v = explode("=",$v);
						if(!empty($v) && isset($v[0]) && isset($v[1])) {
							$_options[$v[0]] = $v[1];
						}
					}
				}
				$data['options'] = $this->mkArray($_options,true);
			}
		} 
		
		$data['template'] = "<encode>{$this->encode(file_get_contents(CJAX_DIR_CORE."/templates/overlay.html"))}</encode>";
		
		if(!isset($url_data['cjax_dir'])) {
			if($dir = $this->getSetting('cjax_dir')) $data['cjax_dir'] = $dir;
		}
		return $this->xml($data);
	}
	
	
	public function submit($form_id)
	{
		return $this->xml("<element>$form_id</element>");	
	}
	
	/**
	 * evets:
	 * 
	 * click
	 * change
	 * keydown
	 * keypress
	 * 
	 * @param $element
	 * @param $actions
	 * @param $event
	 * @return unknown_type
	 */
	public function action($element,$actions,$event="click")
	{
		return $this->Exec($element, $actions, $event);
	}
	
	/**
	 * Alias for actions
	 * 
	 * wait() - currently does not work in binded actions.
	 * 
	 * @param $element
	 * @param $actions
	 * @param $event
	 */
	function Exec($element ,$actions , $event="click")
	{
		$xdata = array();
		if($event) {
			$this->event = $event;
		}
		$CJAX  = CJAX::getInstance();
		$keys =  0;
		$events = array();
		
		if($actions && is_array($actions)) {
			
			if(!is_array($actions[key($actions)])) {
				$actions = array($actions);
			}
			
			foreach($actions as $k => $v) {
				foreach($v as $defined_event => $v2) {
					$keys++;
					if(!is_numeric($k)) {
						$_element = $k;
					} else {
						$_element = $element;
					}
					
					$xdata[$k] = str_replace(
						array('<do>','</do>','[do]','[/do]'),
						array('<_do_>','</_do_>','[_do_]','[/_do_]'),$v2);
						
					$xdata[$k] .="<alt_element>$_element</alt_element><alt_event>$defined_event</alt_event>";
					
					if(isset($events[$k."-".$_element."-".$defined_event])) {
						$events[$_element."-".$defined_event][] = "<binded>{$xdata[$k]}</binded>";
						unset($xdata[$k]);
					} else {
						$events[$k."-".$_element."-".$defined_event][] = "<binded>{$xdata[$k]}</binded>";
					}
				}
			}
			
			$xdata = serialize($xdata);
			
			$actions = "<array>$xdata</array>";
		} else {
			$actions = str_replace("<do>","[_do_]",$actions);
			$actions = str_replace("</do>","[/_do_]",$actions);
		}
		$this->AddEventTo($element,$actions,$event);
		
		
		$this->removeCache(($keys)? $keys: (is_array($actions)? count(array_keys($actions)) : 1 ));
		return $xdata;
	}
	
	public function reset($element_id)
	{
		$this->xml("<element>$element_id</element>");
	}
	

	/**
	 * Just like form() , but this function is optimized to be loaded on the fly
	 *
	 * @param unknown_type $url
	 * @param unknown_type $form_id
	 * @param unknown_type $container_id
	 * @param unknown_type $mode
	 * @return unknown
	 */
	public function callEvent($url,$element_id = null)
	{
		$out[] = "<url>$url</url>";
		if($element_id) $out[] = "<element>{$element_id}</element>";
		if($this->text) $out[] = "<text>{$this->text}</text>";
		if ($this->image) $out[]  = "<image>$this->image</image>";
		if(isset($cmode)) $out[] = "<mode>$cmode</mode>";
		if ($this->debug) $out[] = "<debug>true</debug>";
		if(isset($out) && is_array($out)) $params  = implode($out);
		$return = "('{$params}')";
		$return ="CJAX.exe_html".$return.";";
		return $return;
	}
	
	public function addCallEvent($element,$url)
	{
		$this->AddEventTo($element,$this->encode($this->callEvent($url)),'addCallEvent','click');
	}
	
	/**
	 * Set debug mode on/off
	 *
	 */
	public function debug($on=true)
	{
		$this->xml("<debug>".(($on)? 1:0)."</debug>");
	}
	
	/**
	 * set the focus to an element
	 *
	 * var $element_id
	 */
	public function focus($element_id)
	{
		$this->xml("<element>$element_id</element>");
	}
	
	/**
	 * Display a message in the middle of the screen
	 *
	 * @param string $data
	 * @param integer $seconds if specified, this is the number of seconds the message will appear in the screen
	 * then it will dissapear.
	 */
	public function message($data,$seconds=3,$id='cjax_message')
	{
		$data = $this->encode($data);
		return $this->xml("<data>$data</data><time>{$seconds}</time><element>$id</element>");
	}
	
	public function __call($mehod,$params)
	{
		$list = array();
		if(isset($params[0])) {
			if(is_array($params[0])) {
				$list['a']  = $this->mkArray($params[0],true);
			} else {
				$list['a']  = $this->encode($params[0]);
			}
		}
		if(isset($params[1])) {
			if(is_array($params[1])) {
				$list['b'] =  $this->mkArray($params[1],true);
			} else {
				$list['b'] =  $this->encode($params[1]);
			}
		}
		if(isset($params[2])) {
			if(is_array($params[2])) {
				$list['c'] = $this->mkArray($params[2],true);
			} else {
				$list['c'] = $this->encode($params[2]);
			}
		}
		if(isset($params[3])) {
			if(is_array($params[3])) {
				$list['d'] = $this->mkArray($params[3],true);
			} else {
				$list['d'] = $this->encode($params[3]);
			}
		}
		if(isset($params[4])) {
			if(is_array($params[4])) {
				$list['e'] = $this->mkArray($params[4],true);
			} else {
				$list['e'] = $this->encode($params[4]);
			}
		}
		if(isset($params[5])) {
			if(is_array($params[5])) {
				$list['f'] = $this->mkArray($params[5],true);
			} else {
				$list['f'] = $this->encode($params[5]);
			}
		}
		$list = $this->xmlIt($list);
		
		$plugin = plugin::getInstance();
		if($plugin->exists($mehod)) {
			$data['do'] = $mehod;
			$data['is_plugin'] = 1;
			$data['data'] = $list;
			$data['file'] = $plugin->file($mehod);
			$this->xml($data);
		} else {
			$this->xml("<do>$mehod</do><__call>1</__call><data>$list</data>");
		}
	}
	
	/**
	 * Update any element on the page by specifying the element ID
	 * Usage:  $ajax->update('element_id',$content);
	 * @param string $obj
	 * @param string $data
	 */
	public function update($element,$data=null)
	{
		$data = $this->encode( $data );
		$new['element'] = $element;
		$new['data'] = $data;
		return $this->xml($new);
	}
	
	
	/**
	 * Send a click event to an element
	 *
	 * @param string $element_id
	 */
	public function click($element_id)
	{
		$this->xml("<element>$element_id</element>");
	}
	
	/**
	 * Add event to elements
	 * --
	 * AddEventTo();
	 * 
	 * @param string $element
	 * @param string $event
	 * @param string $method
	 */
	public function AddEventTo($element,$action,$event='onclick')
	{
		$action = $this->encode($action);
		return $this->xml("<element_event>$element</element_event><event_method>$action</event_method><event_action>$event</event_action>");
	}
	
	
/*	*//**
	 * When checking for inputs on the back-end to return a user friendly-error
	 * use invalidate, this function is to highlight a specified text element if the input does not validate
	 * 
	 *
	 * @param string $elem [ELEMENT ID]
	 *//*
	function invalidate($element_id,$data='')
	{
		$data = $this->encode($data);
		$this->xml("<element>$element_id</element><error>$data</error>");
	}*/
	
	
	
	/**
	 * Hide any element on the page
	 *
	 * @param string $element_id
	 */
	public function hide($element_id)
	{
		$this->xml("<element>$element_id</element>");
	}
	
	
	/**
	 * *set value to an element
	 * @param string $element_id
	 * @param string $value
	 */
	public function value($element,$value='',$clear_default=false,$select_text=false)
	{
		$options['clear_text'] = $clear_default;
		$options['select_text'] = $select_text;
		
		$this->xml("<element>{$element}</element><value>{$this->encode($value)}</value><options>{$this->mkArray($options,true)}</options>");
	}
	
	
	/**
	 * This function is to get content of files or send large amount of data
	 *
	 */
	public function updateContent($element,$data,$itsSource=false)
	{
		if($itsSource) $data  = $this->syntax_hilight($data);
		$data = urlencode($data);
		$this->xml("<element>$element</element><data>$data</data>");
	}

	
	/**
	 * Will execute a command in a specified amouth of time
	 * e.g $ajax->wait(5);
	 * Will wait 5 seconds before executes the next CJAX command
	 * 
	 * @param integer $seconds
	 */
	
	public function wait($seconds,$clear = false)
	{
		if(!$seconds) {
			return false;
		}
		$data['timeout'] = $seconds;
		$data['clear'] = $clear;
		$this->xml($data);
		
		return $this;
	}
	
	/**
	 * This is an alias for remove function.
	 * will remove an specified element from the
	 * page.
	 *
	 * @param string $obj
	 */
	public function destroy($obj)
	{
		$this->remove($obj);
	}
	
	/**
	 * Will remove an specified element from the page
	 *
	 * @param string $obj
	 */
	public function remove($obj)
	{
		 $this->xml("<do>remove</do><element>$obj</element>");
	}

	/**
	 * Redirect the page.
	 * this is a recommended alternative to the built-in php function Header(); 
	 * 
	 * @param string $where [URL]
	 */
	public function location($where = "")
	{
		 self::xml("<url>$where</url>");
		 
		 return $this;
	}
	
	/**
	 * Alert a message
	 *
	 * @param string $message
	 */
	public function alert($message)
	{
		$message = $this->encode($message);
		return $this->xml("<msg>$message</msg>");
	}
	
	/**
	 * Will dynamically load external javascript files into the page, hiding the source code.
	 *
	 * @param string $path
	 * @param optional string $append_tag
	 */
	public function load_script($src,$use_domain = false)
	{
		if($use_domain){
			$use_domain ='__domain__';
		} else {
			$use_domain = '';
		}
		$this->xml("<script>$use_domain/$src</script>");
	}
	
	public function auto_suggest($element,$url = null)
	{
		$this->xml("<element>$element</element><url>$url</url>");
	}
	
	public function printDoc($container) 
	{
		$this->xml("<container_id>$container</container_id>");
	}
	
	function __construct()
	{
		parent::__construct();
	}
}