//@app_header;

/**
 * a small portion of this package is source from phpjs.org project.
 */

function CJAX_FRAMEWORK() {
	this.name		=	'cjax';
	this.debug = false;
	var CJAX_CACHE = [];
	var _FUNCTION;
	var __base__;
	var parameters;
	this.COMPONENTS = [];
	this.EXTENSIONS = [];
	this.vars = [];
	this.message_id = 0;
	this.left_delimeter = "<";
	this.right_delimeter = ">";
	this.split_delimiter = "|";
	this.messages = [];
	this.loading = true;
	this.clicked;
	this.bind = [];
	this.IS_POST = false;
	this.cache_calls = [];
	this.chache_templates = [];
	this.dir;
	this.styles = [];
	//collection of elements that are using listeners
	this.current_element = [];
	
	this.timer;
	
	//don't change these
	var FLAG_NO_FLAG = 0;
	var FLAG_WAIT = 1;
	var FLAG_NO_WAIT = 2;
	var FLAG_ELEMENT_BY_ID = 3;
	var FLAG_ELEMENT_BY_CLASS = 4;
	var FLAG_CLEAR_TIMEOUT = false;
	
	var FLAG_DEBUG_DISABLED = 0;
	var FLAG_DEBUG_ENABLED = 1;
	
	var FLAGS = {
		"ELEMENT_GETTER":"ELEMENT_GETTER",
		"FLAGS_WAIT":"FLAGS_WAIT"
	};
	
	//holds the internal cjax style
	var HELPER_STYLE;
	
	/**
	 * 
	 * Submit a form  usage:  $ajax->submit('form_id');
	 */
	this.submit			=		function( buffer )
	{
		var element = debug_element = CJAX.xml('element',buffer);
		element = CJAX.$(element);
		if(element) {
			element.submit();
		} else {
			if(CJAX.debug) {
				alert("Form element "+debug_element+" not found in document");
			}
		}
	};

	this.value  	=		function (buffer)
	{
		var options = CJAX.util.array(CJAX.xml('options',buffer));
		CJAX.set.value(CJAX.xml('element',buffer),CJAX.xml('value',buffer),options);
	};
	
	
	
	this.focus			=		function( buffer ) 
	{
		var element = CJAX.is_element(buffer,false);
		if(element){
			element.focus();
		}
	};
	
	this.remove		=		function( buffer ) {
		var element = CJAX.is_element(buffer,false);
		
    	if(!element && buffer.indexOf(CJAX.split_delimiter)!=-1) {
    		//recursibly remove binded elements
    		var bind = CJAX.xml('element',buffer).split(CJAX.split_delimiter);
    		var len =CJAX.util.count(bind);
    		var i = 0;
    		for(x in bind) {
    			if(i>= len)break;
    			i++;
    			 element = CJAX.is_element(bind[x],false);
    			 if(element) {
    				 element.style.display = 'none';
    				// element.parentNode.removeChild( element );
    			 }
    		}
    		return;
    	}
    	if( !element ) return false;
		element.parentNode.removeChild( element );
	};
	
	this.util		=		function()
	{
		return {
				isXML: function(data) {
					if(typeof data !='string') {
						return false;
					}
					if(data.indexOf(CJAX.left_delimeter)!=-1 && data.indexOf(CJAX.right_delimeter)!=-1) {
						return true;
					}
					return false;
				},
				/**
				*  input will be something like:
				*  <arr><key>1</key><value>value 1</value><key>2</key><value>value 2</value></arr>
				*  output will be an array
				*/
				array: function(buffer) {
					if(CJAX.xml('json',buffer)) {
						return eval("("+CJAX.decode(CJAX.xml('json',buffer))+")");
					}
					var array = CJAX.xml('arr',buffer,true);
					
					var xml_arr = [];
					for(x in array) {
						xml_arr[x] = array[x];
					}
					
					var k,v;
					var xml_arr = [];
					
					for(x in array) {
						if(CJAX.util.isXML(array[x])) {
							k = CJAX.xml('k',array[x]);
							v = CJAX.xml('v',array[x]);
							xml_arr[k] = v;
						}
					}
					return xml_arr;
				},
				strrpos: function ( haystack, needle, offset) {
				    var i = haystack.lastIndexOf( needle, offset );
				    return i >= 0 ? i : false;
				},
				encode: function(str) {
					str = escape(str);
					str = str.replace('+', '%2B');
					str = str.replace('%20', '+');
					str = str.replace('*', '%2A');
					str = str.replace('/', '%2F');
					str = str.replace('@', '%40');
					str = str.replace('(', '%28');
					str = str.replace('!', '%21');
					str = str.replace(')', '%29');
					
					return str;
				},
				toCursor: function(element,myValue) {
					element = CJAX.is_element(element);
					var current_pos = element.scrollTop;
					//IE support
					if (document.selection) {
						element.focus();
						sel = document.selection.createRange();
						sel.text = myValue;
					}
					else if (element.selectionStart || element.selectionStart == '0') {
						
						var startPos = element.selectionStart;
						var endPos = element.selectionEnd;
						element.value = element.value.substring(0, startPos)
						+ myValue
						+ element.value.substring(endPos, element.value.length);
						} else {
							element.value += myValue;
					}
					try {
					element.scrollTop = current_pos;
					}catch(e) {}
				},
				injectXML:function(buffer,xml) {
					
					/*//Injects xml into a xml string, needsw to provide with a json object.
					 * var xml = {"rel":rel};
					var data = '';
					for(x in new_xml) {
						data += CJAX.left_delimeter+x+CJAX.right_delimeter+new_xml[x]+CJAX.left_delimeter+'/'+x+CJAX.right_delimeter;
					}
					buffer = buffer.replace(/<\/out>/gi,data+'</out>');
					return buffer;*/
				
					buffer = buffer.replace(/<\/out>/gi,xml+'</out>');
					return buffer;
				},
				array2xml: function(array) {
					if(CJAX.php.is_array(array)) {
						var str = '';
						for(x in array) {
							str = str+"<"+x+">"+array[x]+"</"+x+">";
						}
						return str;
					}
				},
				replace_encode: function(buffer, unencoded)
				{
					var new_buffer = buffer.replace("<encode>"+unencoded+"</encode>",CJAX.util.encode(unencoded));
					
					return new_buffer;
				},
				eval: function (source) 
				{
					
					var new_data = CJAX.decode(source).replace(/\n\r/ig,"");
					new_data = new_data.replace(/[\n\r\t]/gm,""); 
					new_data = new_data.replace(/\t/gm," "); 
					
					try {
						eval(new_data);
					} catch(e) {
						console.info("Eval could not load: %s", new_data, e);
					}
				},
				count: function(mixed_var,mode){
			    var key, cnt = 0; 
			    if (mixed_var === null){
			        return 0;
			    } else if (mixed_var.constructor !== Array && mixed_var.constructor !== Object){
			        return 1;    }
			 
			    if (mode === 'COUNT_RECURSIVE') {
			        mode = 1;
			    }    if (mode != 1) {
			        mode = 0;
			    }
			 
			    for (key in mixed_var){        if (mixed_var.hasOwnProperty(key)) {
			            cnt++;
			            if ( mode==1 && mixed_var[key] && (mixed_var[key].constructor === Array || mixed_var[key].constructor === Object) ){
			                cnt += this.util.count(mixed_var[key], 1);
			            }        }
			    }
			 
			    return cnt;			
			},
			print: function(buffer) {
				/**
				 * this function prompts printer to print specified content but still needs more testing.
				 */
				var element = CJAX.is_element(CJAX.xml('element',buffer)); 
				var container_id = CJAX.xml('container_id',buffer);
				var container = CJAX.is_element(container_id); 
				
				var iframe_id = container_id+'_frame';
				var iframe = CJAX.is_element(iframe_id);
				if(!iframe)  {
					iframe = CJAX.create.frame(iframe_id);
					//iframe.src = container.innerHTML;
					iframe.width = '0px';
					iframe.height = '0px';
					container.appendChild(iframe);
					iframe.focus();
				}
				//window.print();
				var content=container.innerHTML;
				//var pwin=window.open('','print_content','width=100,height=100');
				
				 var oDoc = (iframe.contentWindow || iframe.contentDocument);
		        if (oDoc.document) {
		        	oDoc = oDoc.document;
		        }
				oDoc.write("<html><head><title>Copy</title>");
				oDoc.write("</head><body onload='this.focus(); this.print();'>");
				oDoc.write(content + "</body></html>");	    
				oDoc.close();
					
			}
		};
	}();
	
	/**
	 * what this function does:
	 * 
	 * Takes 2 arguments with the API -  a selectbox and the id of another selectbox
	 * and loads an array into the second selectbox depending on what is choosen in the first one
	 * if there are no records to display, then it will convert the second selectbox into a text input
	 * so the user can enter the record instead
	 * 
	 * @param buffer
	 */
	this.select		=			function( buffer )
	{
		var element_id;
		var element = CJAX.is_element(element_id = CJAX.xml('element',buffer),false); 
		if(!element) {
			alert("CJAX Error -  Element "+ element_id+" not found");
			return;
		}
		
		var allow_input = CJAX.xml('allow_input',buffer);
		var options = CJAX.util.array(CJAX.xml('options',buffer));
		var options_count = CJAX.util.count(options);
		
		if(element.type=='select-one') {
			element.style.display = 'inline';
			if(options_count) {
				element.options.length = 0;
				var x = 0;
				for ( var i in options ) {
					x++;
					if(options_count==x) {
						break;
					}
					addOption(element,options[i],i);
				}
			} else {
				if(allow_input) {
					make_input(element);
				} else {
					element.style.display = 'none';
				}
			}
		} else {
			if(element.type=='text') {
				if(CJAX.util.count(options)) {
					var obj = document.createElement("SELECT"); 
					obj.name = element.name;
					obj.id = element.id; 
					var div = CJAX.get.property.parent(element);
			
					div.appendChild(obj);
					div.style.display = 'block';
					//obj.style.width = '200px'; 
					obj.className = element.className;
					CJAX.remove(element); element = obj; 
					if(element) { 
						element.options.length = 0;
					}
					var x = 0;
					for ( var i in options ) { 
						x++;
						if(options_count==x) {
							break;
						}
						addOption(element,options[i],i);
					}
				}
			} else {
				if(!CJAX.util.count(options)) {
					make_input(element);
				}
			}
		}
			function make_input(element) {
				var obj = document.createElement("INPUT");
				obj.type = 'text'; 
				obj.name = element.name;
				obj.id = element.id;
				obj.className = element.className;
				obj.style.width = element.offsetWidth+'px';
				
				var div = CJAX.get.property.parent(element);
	
				div.appendChild(obj); 
				CJAX.remove(element); 
				element = obj;
				div.style.display = 'block';
			}
			
			function addOption(selectbox,text,value ) {
				var optn = document.createElement("OPTION"); 
				optn.text = text; 
				optn.value = value;
				selectbox.options.add(optn);
			}

	};
	
	this.eval		=			function( buffer )
	{
		CJAX.util.eval(buffer);
	};

	/**
	* Display a message in the middle of the screen
	*/
	this.message		=		function( buffer , time) 
	{
		if(CJAX.php.is_array(buffer)) {
			buffer = CJAX.util.array2xml(buffer);
		}
		
		var pos = [];
		var element = (CJAX.xml('element',buffer)?CJAX.xml('element',buffer):'cjax_message');
		
		var div = CJAX.create.div(element);
		
		//optional properties
		pos['top'] = CJAX.xml('top',buffer);
		pos['left'] = CJAX.xml('left',buffer);
		if(pos['left']=='50%' && !pos['marginLeft']) {
			pos['marginLeft'] = '-25%';
		}
		if(!CJAX.defined(buffer)) {
			var buffer = '';
		}
		if(!CJAX.util.isXML(buffer)) {
			data = buffer;
		} else {
			var data = CJAX.xml('data',buffer);
			data = CJAX.decode( data );
		}
		if(!CJAX.defined(time) || !time) {
		  var time = CJAX.xml('time',buffer);
		}
		div.innerHTML = data;
		CJAX.set.center(div,pos);
		div.style.zIndex = '5999';
		//div.style.display = 'block';
		//time in seconds to remove the message from the screen
		var seconds = CJAX.xml('seconds',buffer);
		if( time > 0 && (seconds > time) || (!seconds && time)){
			if(CJAX.message_id) {
				clearTimeout(CJAX.message_id);
		    }
			CJAX.messages[CJAX.message_id]= setTimeout(function(){
				CJAX.is_element(element,false).innerHTML='';
			}
			,time*1000);
		}
		
		return div;
	};
	
	this.overLayContent		=		function( buffer )
	{
		var msg = [];
		var url = CJAX.xml('url',buffer);
		var cjax_dir = CJAX.xml('cjax_dir',buffer);
		var use_cache = CJAX.xml('cache',buffer);
		var top = '72px';//CJAX.xml('top',buffer);
		var options = CJAX.xml('options',buffer);
		options = CJAX.util.array(options);
		
		for(x in options) {
			msg[x] = options[x];
		}
		if(!CJAX.defined(msg.top)) {
			msg['top'] = top;
		}
		if(!CJAX.defined(msg.left)) {
			msg['position'] = 'relative';
			msg['left'] = '50%';
			msg['marginLeft'] = '-25%';
		}
		var response = null;
		var content = CJAX.xml('content',buffer);
		var call = "[url]"+url+"[/url][element]cjax_overlay_content[/element][cjax_dir]"+cjax_dir+"[/cjax_dir]";
		
		if(!url && !content) {
			return CJAX._removeOverLay();
		}
		function _options() {
			if(options.transparent || options.color) {
				var _opacity =_alpha =_color = null;
				if(!options.transparent) {
					options.transparent = 80;
				}
				_opacity = parseFloat("0."+parseFloat(options.transparent));
				_alpha = 'alpha(opacity='+parseInt(options.transparent)+')';
				_color = options.color;
				
				var overlay_class = CJAX.css.add('.overlay_class','cjax');
				if(overlay_class) {
					with (overlay_class.style) {
						display = 'block';
						position = 'absolute';
						top = 0;
						left= 0;
						width= '100%';
						height = '150%';
						Zindex = 5000;
						marginBottom = '0px';
						if(options.transparent) {
							opacity = _opacity;
							filter = _alpha;
						}
						if(_color) {
							backgroundColor =_color;
						}
			        }
					CJAX.$('cjax_overlay').className = 'overlay_class';
				}
			}
		}
		
		content = CJAX.decode(content);
		if(use_cache && CJAX.chache_templates['cache_overlay']) {
			var template = CJAX.chache_templates['cache_overlay'];
		} else {
			var template = CJAX.xml('template',buffer);
			template = template.replace(CJAX.util.encode('[CONTENT]'),content);
			CJAX.chache_templates['content'] = template;
		}
		
		_options();
		msg['data'] = CJAX.decode(template);
		msg['element'] = "cjax_message_overlay";
		
		CJAX.message(msg);
		CJAX.$('cjax_overlay').style.display = 'block';
		
		CJAX.chache_templates['content'] = CJAX.$('cjax_overlay_content').innerHTML = content;
	
		
	};
	
	this.overLay		=		function ( buffer )
	{
		var msg = [];
		var url = CJAX.xml('url',buffer);
		var cjax_dir = CJAX.xml('cjax_dir',buffer);
		var use_cache = CJAX.xml('cache',buffer);
		var top = '72px';//CJAX.xml('top',buffer);
		var options = CJAX.xml('options',buffer);
		options = CJAX.util.array(options);
		
		for(x in options) {
			msg[x] = options[x];
		}
		if(!CJAX.defined(msg.top)) {
			msg['top'] = top;
		}
		var response = null;
		var content = CJAX.xml('content',buffer);
		var call = "[url]"+url+"[/url][element]cjax_overlay_content[/element][cjax_dir]"+cjax_dir+"[/cjax_dir]";
		
		if(!url) {
			return CJAX._removeOverLay();
		}
		function _options() {
			if(options.transparent || options.color) {
				var _opacity =_alpha =_color = null;
				if(!options.transparent) {
					options.transparent = 80;
				}
				_opacity = parseFloat("0."+parseFloat(options.transparent));
				_alpha = 'alpha(opacity='+parseInt(options.transparent)+')';
				_color = options.color;
				
				var overlay_class = CJAX.css.add('.overlay_class','cjax');
				if(overlay_class) {
					with (overlay_class.style) {
						display = 'block';
						position = 'absolute';
						top = 0;
						left= 0;
						width= '100%';
						height = '150%';
						Zindex = 5000;
						marginBottom = '0px';
						if(options.transparent) {
							opacity = _opacity;
							filter = _alpha;
						}
						if(_color) {
							backgroundColor =_color;
						}
			        }
					CJAX.$('cjax_overlay').className = 'overlay_class';
				}
			}
		}
		if(use_cache && CJAX.chache_templates['overlay']) {
			if(CJAX.cache_calls[call]) {
				CJAX.process_all(CJAX.cache_calls[call]);
			}
			//(response);
			if(CJAX.chache_templates['cache_overlay']) {
				var template = CJAX.chache_templates['cache_overlay'];
			}
		} else {
			var template = CJAX.xml('template',buffer);
			
			CJAX.chache_templates['overlay'] = template;

			if(!response ) {
				setTimeout(function() {
				content = CJAX.exe_html(call,'overlay');
				},170);
			}
			
			var content = null;
			var interval_max = 100; //10 seconds
			var interval_count = 0;
			
			//monitor AJAX response...
			var interval = setInterval(function() {
				interval_count++;
				content = CJAX.cache_calls[call];
				
				if(content) {
					if(!CJAX.chache_templates['cache_overlay']) {
						CJAX.chache_templates['cache_overlay'] = CJAX.$('cjax_message_overlay').innerHTML;
					}
					CJAX.chache_templates['overlay'] = CJAX.$('cjax_message_overlay').innerHTML;
					clearInterval ( interval );
				}
				if(interval_count > interval_max) {
					alert("Could not load overlay. Please try again.");
					clearInterval ( interval );
				}
			},100);
		}
		
		_options();
		CJAX.$('cjax_overlay').style.display = 'block';
		msg['data'] = CJAX.decode(template);
		msg['element'] = "cjax_message_overlay";
		
		CJAX.message(msg);
		
	};
	
	this._removeOverLay		=		function()
	{
		CJAX.$('cjax_overlay').style.display = 'none';
		CJAX.message("<element>cjax_message_overlay</element>");
		//if default window is open, hide it.
		CJAX.message("<element>cjax_message</element>");
		//default class 
		CJAX.$('cjax_overlay').className = 'cjax_overlay';
	};
	
	
    this.AddEventTo     =       function( buffer ,decode) 
    {
    	if(typeof decode=='undefined') var decode = true;
    	if(decode) {
    		buffer = CJAX.decode(buffer);
    	}
    	var array = CJAX.xml('array',buffer);
    	var element = CJAX.xml('element_event',buffer);
    	
    	if(CJAX.debug) {
    		console.log("AddEventTo is fired, and executing:",buffer);
    	}
    	//binding elements
    	if(element.indexOf(CJAX.split_delimiter)!=-1) {
    		var bind = element.split(CJAX.split_delimiter);
    		var elem,rel,xml;
    		var new_buffer;
    		var len =CJAX.util.count(bind);
    		var i = 0;
    		for(x in bind) {
    			if(i>= len)break;
    			i++;
    			new_buffer = buffer.replace(element, bind[x]);
    			
    			elem = CJAX.is_element(bind[x],false);
    			if(elem) {
    				rel = elem.getAttribute('rel');
    				if(rel) {
    					xml = '[rel]'+rel+'[/rel]';
    					new_buffer = CJAX.util.injectXML(new_buffer,xml);
    				}
    			}
    			CJAX.AddEventTo(new_buffer,false);
    		}
    		return;
    	}
    	var alt_element = CJAX.xml('alt_element',buffer);
    	if( !element && !alt_element){
    		if(CJAX.debug) {
    			console.log('no element:',buffer);
    		}
    		return;
    	}
    	if(array) {
        	var arr = CJAX.unserialize(array);
        	var alt = e = _element = fn = null;
        	interval = 150;
        	interval_result = null;
        	var counter = 0;
        	
        	for(x in arr) {
        		fn = CJAX.xml('_do_',arr[x] );
        		_element = CJAX.$(CJAX.xml('alt_element',arr[x]));
        		
        		if(!_element) {
        			e = CJAX.xml('alt_event',arr[x]);
        			
        			if(e && !_element) {
        				interval_result = setInterval( function() {
	        				if(_element =  CJAX.$( CJAX.xml('alt_element',arr[x]) ) ) {
	        					if(CJAX.debug) {
	                				console.log('CJAX.set.event #1 to element',element, fn);
	                			}
	        					CJAX.set.event(_element , e ,arr[x]);
	        					clearInterval(interval_result);
	        				} else {
	        					counter++;
	        					if(counter > (interval * 10)) {
	        						clearInterval(interval_result);
	        					}
	        				}
	        			},interval);
        			} else {

        				if(CJAX.debug) {
            				console.log('CJAX.set.event #2 to element',element, fn);
            			}
        				CJAX.set.event(CJAX.$( _element ), e ,arr[x]);
        			}
        		} else {
        			if(CJAX.php.isNumeric(x)) {
        				e = CJAX.xml('alt_event',arr[x]);
        				
        			} else  {
        				e = x;
        			}
        			//alert(arr[x]);
        			if(CJAX.debug) {
        				console.log('CJAX.set.event #3 to element',element, fn);
        			}
        			CJAX.set.event(CJAX.$( element ), e ,arr[x]);
        		}
        		
        	
        	}
    	} else {

    		 var fn = CJAX.xml('do', buffer );
    		 var evento = CJAX.xml('event_action',buffer);
    		 var method = CJAX.xml('event_method',buffer);

    		 if( !evento ) evento = 'load';	
    		 if(CJAX.xml('observe', buffer)) {
    	    	CJAX.$( element );
    			var observe = setInterval(
    			function() {
    				if(CJAX.$( element )) {
    					//	CJAX._EventCache.flush(event_id);
    					if(CJAX.debug) {
    						console.log('CJAX.set.event #4 to element',element, fn);
    					}
    	   				event_id = CJAX.set.event(CJAX.$( element ),evento,method);
    					clearInterval(observe);
    				} 
    			},400);
    		} else {
    			if(CJAX.debug) {
    				console.log('CJAX.set.event #5','to element: ',element, fn);
    			}
	       	 	CJAX.set.event(CJAX.$( element ),evento,method);
	        }
    	}
    };
    
	this.click		=		function( buffer ) {
		if(CJAX.$(buffer)){
			var elem = CJAX.$(buffer);
			
		} else {
			var item = CJAX.xml('element',buffer);
			elem = CJAX.$( item ); 
		}
		if( elem )elem.click();
	};
	
	/**
	 * @deprecated
	 */
	this.__textbox		=		function( buffer ) {
	  	var id = CJAX.xml('element',buffer);
	  	var parent = CJAX.xml('parent',buffer); 
	  	var label = CJAX.xml('label',buffer);
	  	var textbox = CJAX.create.textbox(id,parent,label);
	 	if( textbox ) {
		  var value = CJAX.xml('value',buffer);
		  var _class = CJAX.xml('class',buffer);  
	 	}
	};
	
	
/*	this.set_value		=		function ( buffer ,_value) {
		return CJAX.set.value(element,_value);
	};*/
	
	this._ElementList		=		function() {
		var elems = [];
		return {
			get_return : function() {
				elems['text']			= 'string';
				elems['select-one'] 	= 'string';
				elems['select-multiple']= 'string';
				elems['password']		= 'string';
				elems['hidden']			= 'string';
				elems['textarea']		= 'string';
				elems['button']			= 'string';
				elems['submit']			= 'string';
				elems['checkbox']		= 'boolean';
				elems['radio']			= 'boolean';
				return elems;
			},
			types : function () {
				elems[1]='text';
				elems[2]='select-one';
				elems[3]='select-multiple';
				elems[4]='password';
				elems[5]='hidden';
				elems[6]='textarea';
				elems[7]='button';
				elems[8]='submit';
				elems[9]='checkbox';
				elems[10]='radio';
				return elems;				
			}
		};
	
	}();

	
	this.is_cjax		=		function(buffer) {
		if( !CJAX.xml(this.name,(CJAX.defined(buffer)?buffer:null)) ){ return false; }
		return true;
	};
	
	this.resetDelimeters		=		function(left,right)
	{
		if(CJAX.defined(left) && CJAX.defined(right)) {
			CJAX.left_delimeter = left;
			CJAX.right_delimeter = right;	
		}	else {
			CJAX.left_delimeter = "<";
			CJAX.right_delimeter = ">";
		}
	};
	
	this.get_function		=		function(buffer) {
		return CJAX.xml( 'do' ,buffer);
	};
	
	
	/*String.prototype.append		=		function(tag,value) {
		return this.concat(this,'<'+tag+'>'+value+'</'+tag+'>'); 
	};*/
	
	
	

    this._addEvent		 =           function( obj, type, fn) 
    {
    	if(!CJAX.defined(id)) {
    		var id = null;
    	}
		if(type.substring(0, 2) == "on"){
			type = type.substring(2);
        }
        if (obj.addEventListener) {
            try {
                    obj.addEventListener( type, fn, false );
                }
                catch( e ){alert("CJAX: Error - addEvent "+e );}
            return CJAX._EventCache.add(obj, type, fn);
        } else if (obj.attachEvent) {
            obj["e"+type+fn] = fn;
            obj[type+fn] = function() { obj["e"+type+fn]( window.event ); };
            obj.attachEvent( "on"+type, obj[type+fn] );
            CJAX._EventCache.add(obj, type, fn);
        } else {
            obj["on"+type] = obj["e"+type+fn];
        }
    };
    
    var listEvents = [];
    this._EventCache         =           function(){
        return {
            listEvents : listEvents,
            add : function(node, sEventName, fHandler){
                return listEvents.push( arguments );
            },
            flush : function( event_id ){
                if(typeof event_id =='undefined') var event_id;
                var i, item;
                for(i = listEvents.length - 1; i >= 0; i = i - 1){
                    item = listEvents[i];
                    if(item[0].removeEventListener){
                        item[0].removeEventListener(item[1], item[2], item[3]);
                    };
                    if(item[1].substring(0, 2) != "on"){
                        item[1] = "on" + item[1];
                    };
                    if(item[0].detachEvent){
                        //item[0].detachEvent(item[1], item[2]);
                        item[0].detachEvent(item[1], item[0][eventtype+item[2]]);
                    };
                    item[0][item[1]] = null;
                };
            }
        };
    }();
	
	/**
	 * Util get
	 */
	this.get		=		function() {
		return {
			extension: function(path) {
			var pos = CJAX.util.strrpos(path,'.');
			if(!pos) {
				return '';
			}
				return CJAX.php.substr(path,pos,path.length);
			},
			byClassName:function(theClass,tag)
			{
				var allHTMLTags = new Array();
				if(tag == null ) {
					var tag = "*";
				}
				var allHTMLTags=document.getElementsByTagName(tag);

				for (i=0; i<allHTMLTags.length; i++) {
					if (allHTMLTags[i].className==theClass) {
						return allHTMLTags[i];
					}
				}
			}
			,
			dirname : function (path,loops) {
				var self = CJAX.get.selfpath();
				if(!self || !path) {
					return false;
				}
				path.match(/(.*)[\/\\]/)[1];
				if( loops ){
					for(var i = 0; i < loops-1; i++){
					try{
						path = path.match( /(.*)[\/\\]/ )[1];
						}
						catch( e ) {}
					}
				}
			    return path;
			 },
			 document : function(frame) {
				if(CJAX.defined(frame) && frame) {
					var iframeDoc;
					if (this.contentDocument) {
						iframeDoc = this.contentDocument;
					}
					else if (this.contentWindow) {
						iframeDoc = this.contentWindow.document;
					}
					else if (window.frames[this.name]) {
						iframeDoc = window.frames[this.name].document;
					}
					return iframeDoc.document;
				}
				return document.body;
			}
			,basepath : function () {
				var path = CJAX.get.selfpath();
				path = CJAX.get.dirname(path,3);
				if(path) {
					var len = path.substr(path.length - 4);
					if(len=='core') {
					//if cjax is called from a parent-child file
						path = CJAX.get.dirname(path,2);
					}
				} else {
					path = 'cjax';
				}
				return path;
			},
			basename : function(path, suffix) {
			    var b = path.replace(/^.*[\/\\]/g, '');
			    if (typeof(suffix) == 'string' && b.substr(b.length-suffix.length) == suffix) {
			        b = b.substr(0, b.length-suffix.length);
			    }
			    return b;
			}
			,scripts : {
				src : function () {
						var paths = [];
						var script;
						var scripts = CJAX.elem_docs( 'script' );
						for( var i = 0; i < scripts.length; i++ ){
							script = scripts[i];
							if(script.src) paths[i] = script.src;
						}
						return paths;
					}
			},
			selfpath : function() {
				var script;
				var name = 'cjax.js';
				var src;
				
				script = CJAX.$('cjax_lib');
				
				if(script) {
					src = script.src;
					var f = src.substr(0,src.indexOf(name));
					
					return f;
				}
				
				var scripts = document.getElementsByTagName('script');
				for( var i = 0; i < scripts.length; i++ ){
					script = scripts[i];
					src = script.src;
					if(CJAX.get.basename(src)==name) {
						var f = src.substr(0,src.indexOf(name));
						return f;
					}
				}
			},
			value : function(elem,verbose) {
				var type = (typeof elem);
				if( typeof verbose == 'undefined') { verbose = true; }
				if( type.indexOf( 'object' ) == -1) {var elem = CJAX.$(elem,verbose);}
				return elem.value;
			},
			 position : function(obj) {
				var curleft = curtop = 0;
				if (obj.offsetParent) {
					do {
						curleft += obj.offsetLeft;
						curtop += obj.offsetTop;
					} while (obj = obj.offsetParent);
					var r = [];
					r['top'] = curtop;
					r['left'] = curleft;
					return r;
				}
			},
			y: function(element) {
				var iReturnValue = 0;
				while( element != null ) {
					iReturnValue += element.offsetTop;
					element = element.offsetParent;
				}
				return iReturnValue;
			},
			property : {
				readonly: function(elem,verbose) {
					var type = (typeof elem);
					if( typeof verbose == 'undefined') { verbose = true; }
					if( type.indexOf( 'object' ) == -1) {var elem = CJAX.$(elem,verbose);}
					return elem.readOnly;
				}
				,
				enabled: function(elem,verbose) {
					var type = (typeof elem);
					if( typeof verbose == 'undefined') { verbose = true; }
					if( type.indexOf( 'object' ) == -1) {var elem = CJAX.$(elem,verbose);}
					return (elem.disabled)? false : true;
				},
				disabled: function(elem,verbose) {
					var type = (typeof elem);
					if( typeof verbose == 'undefined') { verbose = true; }
					if( type.indexOf( 'object' ) == -1) {var elem = CJAX.$(elem,verbose);}
					return elem.disabled;
				},style : function(elem,verbose) {
					var type = (typeof elem);
					if( typeof verbose == 'undefined') { verbose = true; }
					if( type.indexOf( 'object' ) == -1) {var elem = CJAX.$(elem,verbose);}
					return elem.style;
				}
				, parent : function(elem,verbose) {
					var type = (typeof elem);
					if( typeof verbose == 'undefined') { verbose = true; }
					if( type.indexOf( 'object' ) == -1) {var elem = CJAX.$(elem,verbose);}
					return elem.parentNode;
				}, position : function(elem,verbose) {
					var type = (typeof elem);
					if( typeof verbose == 'undefined') { verbose = true; }
					if( type.indexOf( 'object' ) == -1) {var elem = CJAX.$(elem,verbose);}
					var pos = [];
					var  curleft = curtop = curright = curdown = 0;
					if ( elem.offsetParent ) {
						do {
								curleft += elem.offsetLeft;
								curtop += elem.offsetTop;
						} while (elem = elem.offsetParent);		
						pos[0] = curleft;
						pos[4] = curtop;
						return pos;
					}
				}
				},parent : function(elem,type_of) {
					var type = (typeof elem);
					if( typeof verbose == 'undefined') { verbose = true; }
					if( type.indexOf( 'object' ) == -1) {var elem = CJAX.$(elem,verbose);}
					var parent;
					if(CJAX.get.isType(elem.parentNode,type_of)) {
						return elem.parentNode;
					} else {
						var giveup = 30;
						var i = 0;
						while(!CJAX.get.isType(elem,type_of) && !elem) {
							i++;
							parent = 	elem.parentNode;
							elem = elem.parentNode;
							if(i >= giveup) {
								break;
							}
						}
						return parent;
					}
				},
			isType: function(element,element_type) {
				if(!element)  return false;
				var type = (typeof element);
				if(element_type=='table') {
					if(element.rows.length) {
						return true;
					}
				}
				if( type.indexOf( element_type ) == -1) { return false; }
				return true;
			}
			,
			properties : function(elem,verbose) {
				var type = (typeof elem);
				if( typeof verbose == 'undefined') { verbose = true; }
				if( type.indexOf( 'object' ) == -1) {var elem = CJAX.$(elem,verbose);}
				var p = [];
				p['id'] = elem.id;
				p['name'] = elem.name;
				p['readonly'] = elem.readOnly;
				p['disabled'] = CJAX.elements.disabled(elem,false);
				p['enabled'] = p['disabled']? false: true;
				p['value'] = elem.value;
				return p;
			},
			cache : function( key ) {
				return CJAX.cache.get( key );
			}
		};
	}();
	
	   
	this.uniqid		=		function uniqid()
	{
		var newDate = new Date;
		return newDate.getTime();
	};
	
	/**
	 * Util Set
	 */
	this.set				=			function() {
		return {
			event : function(element,event,method,cache_id){
				if(CJAX.debug) {
					console.log("set.even setting event for -..:",element);
				}
				if( !element ) return false;
				var element = CJAX.is_element( element );
				var event_id = CJAX.uniqid();
				
				
				var f = method.toString();
				f = f.substr(0,f.indexOf('('));
				//rtrim
				f = f.replace(/\s+$/,"");
				
				
				if(f =='function') {
				    CJAX._addEvent(element,event,eval(method));
				} else {
					var href;
				    if(href = element.href) {
				        
				        
				       /* if(!CJAX.ie || (CJAX.ie && href.indexOf('#')==-1)) {
				        	//removes all clickable events
				        	//element.onclick = function() {return false;}
				        }*/
				        if(href.indexOf('#')==-1) {//avoid removing internal anchors
				        	
				        	
				        	
				        	//removes all clickable events
				        	element.onclick = function() {return false;};
				        } else {
				        	element.href= 'javascript:void(0)';
				        }
				    } else {
				        if(element.type && (element.type == 'checkbox' || element.type=='radio')) {
			                element.onclick = function() {return true;};
			            } else {
			            	if(element.tagName=='LI') {
			            		//for now do nothing...
			            		element.onclick = function() {return false;};
			            	}  else {
			            		element.onclick = function() {return false;};
			            	}
			            }
		            }
					return CJAX._addEvent(element,event,function() {
						method = method.replace(/\[_do_\]/g,"<do>");
						method = method.replace(/\[\/_do_\]/g,"</do>");
						method = method.replace(/\<_do_\>/g,"<do>");
						method = method.replace(/\<\/_do_\>/g,"</do>");
						method =  method.replace(/\n/g,"");
						
						if(CJAX.util.isXML(method)) {
							if(!CJAX.is_cjax(method)) {
								method = "<cjax>"+method+"</cjax>";
							}
						
							
							CJAX.process(method,'set.event',element);
						} else {
							eval(method);
						}
						
					},((typeof cache_id !='undefined')? cache_id: null));
				}
			}
			,value : function(element,_value,options){
				if(typeof options =='undefined') var options = [];
				var element = CJAX.is_element(element);
			
				//var clear = CJAX.xml("clear",buffer);
				//var select = CJAX.xml("select",buffer);
				
				
				if( !element ) return false;
				for(x in CJAX._ElementList.get_return()) {
					if(x === element.type) {
						switch ( CJAX._ElementList.get_return()[x] ) {
							case 'string':
								element.value = _value;
								if(options.clear_text) {
									element.onclick = function() {
		                                if(element.value==_value) {
		                                	element.value = '';
		 
		                                }
		                            };
								}
								if(options.select_text) {
									element.focus();
									element.select();
		                         }
							break;
							case 'boolean':
								var check = (_value == 'true' || _value==1 || _value===true)? true:false;
								element.checked = check;
							break;
						}
						return true;
					} 
				}
			}
			,type: function(elem,new_type,verbose){
				if( !elem ) return false;
				var elem = CJAX.is_element(elem,verbose);
				if( elem ) { elem.type = new_type; return true;}
				return false;
			}
			,name : function(elem,new_name,verbose){
				if( !elem ) return false;
				var elem = CJAX.is_element(elem,verbose);
				if( elem ) {elem.name = new_name; return true;}
				return false;
			},style : function(elem,new_name,verbose){
				/**
				*TODO
				**/
			}, 'class': function(element,_class){
				element = CJAX.is_element(element,false);
				if(element) {
					element.className = _class;
				}
			}, title: function(element,title){
				element = CJAX.is_element(element,false);
				if(element) {
					element.setAttribute('title',CJAX.decode(title));
				}
			}
			,property: {
					focus : 
					function(elem,verbose){
						if( !elem ) return false;
						var elem = CJAX.is_element(elem,verbose);
						if(elem && window.focus())
						{
							elem.focus();
							return true;
						}
						return false;
					}
			}
			,center : function(obj,pos) {
				if(typeof pos == 'undefined') var pos = [];
				var element = CJAX.is_element(obj);
			    element.style.position ='absolute';
		        var ctop = (screen.height /4);
		        if(CJAX.defined(pos.top) && pos.top) {
		        	var _top = pos.top;
		        	if(CJAX.php.isNumeric(_top)) {
		        		_top = _top+'px';
		        	}
		        } else {
		        	var  _top = CJAX.getY()+ctop+'px';
		        }
		        if(CJAX.defined(pos.left) && pos.left) {
		        	var _left = pos.left;
		        	if(pos.marginLeft) {
		        		var _margin_left = pos.marginLeft;
		        	} else {
		        		var _margin_left = '';
		        	}
		        } else {
		        	var  _left = '50%';
		        	var _margin_left = '-'+((element.offsetWidth / 2))+'px';
		        }
		        if(pos.marginLeft) {
		        	_margin_left = pos.marginLeft;
		        }
		        with (element.style) {
			        top = _top;
			        left = _left;
			        maxWidth = '800px';
		        }
		        if(_margin_left && _margin_left !='0px') {
		        	element.style.marginLeft = _margin_left;
		        }
               return element;
			}
		};
	}();
	
	/**
	 * util Create
	 */
	this.create		=		function() {
		return{
			script: function( path ) {
				if(!CJAX.script.loaded( path )) {
					return CJAX.script.load( path );
				}
			},
			div:function(id,parent,append) {
				if(typeof append == 'undefined') var append = true;
				var element = CJAX.is_element(id,false);
				if(!parent || parent == 'body') {
					parent = CJAX.elem_docs( 'body' )[0];
				} else {
					if( !parent ) parent = CJAX.is_element(parent,false);
				}
				if( !parent )return false;
				if(element && parent){	
					if( append ) {
						parent.appendChild( element );
					} else {
						CJAX.elem_docs( 'body' )[0].appendChild( element );
					}
					return element;
				}
				var div = document.createElement( 'div' );
				div.setAttribute('id',id);
				 
				if( append ) { 
					parent.appendChild( div );
				} else {
					CJAX.elem_docs( 'body' )[0].appendChild( div );
				}
				return div;
			},
			select: function(id,parent) {
				var select;
				if(select = CJAX.is_element(id)) {
					return select;
				}
				select = document.createElement('select');
				select.name = id;
				select.id = id;
				
				return select;	
			},
			span:function(id,parent) {
				var element = CJAX.is_element( id );
				if(!parent || parent == 'body') parent = CJAX.elem_docs( 'body' );
				else parent = CJAX.is_element(parent,false);
				if( !parent )return false;
				if(element && parent)
				{
					parent.appendChild( element );
					return element;
				}
				var div = document.createElement( 'span' );
				div.setAttribute('id',id);
				parent.appendChild( div );
				return div;
			},
			textbox:function(id,parent,label) {
				//make sure the element doesnt exist before it tries to create it
				var elem = elem_doc(id,false);
				if( elem ){return elem;}
				var parent = elem_doc( parent );
				if( label ){
					var olabel = document.createElement( 'LABEL' );
					olabel.setAttribute('id','label_'+id); 
					olabel.setAttribute('for',id); 
					olabel.innerHTML = label;
					parent.appendChild( olabel ); 
				}
				var textbox = document.createElement( 'INPUT' );
				textbox.setAttribute('type','text');
				textbox.setAttribute('id',id);
				parent.appendChild( textbox ); 
				return textbox;
			},
			frame:function(id,parent,src) {
				if(!id) {
					var id = 'cjax_iframe';
				}
				var f = CJAX.$(id,false);
				if(!f) {
					f = document.createElement("IFRAME");
					f.setAttribute("id",id);
					f.setAttribute("name",id);
					if(typeof src !='undefined' && src) {
						f.setAttribute("src",src);
					}
					if(typeof parent !='undefined' && parent) {
					 CJAX.$(parent).appendChild(f);
					}
				}
				return f;
			},
			form:function(id) {
				if(!id) {
					var id = 'cjax_form';
				}
				var form = CJAX.$(id);
				if(!form) {
					form = document.createElement("FORM");
					form.method = "POST";
					form.enctype = "multipart/form-data";
					form.id = id;
					form.name = id;
				}
				return form;	
			}
		};
	}();
	
	/**
	 * Util php..  mimics php functions
	 */
	this.php		=		function()
	{
		return {
			 implode: function(glue, pieces) {
			    var i = '', retVal='', tGlue='';
			    if (arguments.length === 1) {        pieces = glue;
			        glue = '';
			    }
			    if (typeof(pieces) === 'object') {
			        if (pieces instanceof Array) {            return pieces.join(glue);
			        }
			        else {
			            for (i in pieces) {
			                retVal += tGlue + pieces[i];                tGlue = glue;
			            }
			            return retVal;
			        }
			    }    else {
			        return pieces;
			    }
			},
		    isNumeric: function(n) {
		    	var n2 = n;
				n = parseFloat(n);
				return (n!='NaN' && n2==n);
		    },
			/*
			* checks to see if specific data is an array
			*/
			is_array: function( element ) {
				if(CJAX.util.isXML(element) && CJAX.xml('json', element)) {
					return true;
				} else {
					return typeof(element)=='object'&&(element instanceof Array);
				}
			},
			in_array: function ( subject , array ) {
				//var len = array.length;
				for (x in array) {
					if (x == subject ){ return true;}
				}
				return false;
			},
			trim: function( data ) {
			    if ( !data ) return;
			    while (data[0] == ' ' || data[0] == '\n') data = data.substr( 1 ); var l = data.length-1;
			    while (l > 0 && data[l] == ' ' || data[l] == '\n') l--;
			    return this.substring(0, l+1);
			},
			rtrim: function(str,replace) {
				if(!replace) {
				       var replace = " ";
				 }
				return  str.replace(/\s+$/,"");
			},
			ltrim: function(string,replace) {
			   if(!replace) {
			       var replace = " ";
			   }
			   return string.replace(/replace/ig,string);
			},
			empty: function ( mixed_var ) {
		   		return ( mixed_var === "" || mixed_var === 0   || mixed_var === "0" || mixed_var === null  || mixed_var === false  ||  ( is_array(mixed_var) && mixed_var.length === 0 ) );
			},
			substr: function( f_string, f_start, f_length ) {
			    if(f_start < 0) {
			        f_start += f_string.length;
			    }
			
			    if(f_length == undefined) {
			        f_length = f_string.length;
			    } else if(f_length < 0){
			        f_length += f_string.length;
			    } else {
			        f_length += f_start;
			    }
			
			    if(f_length < f_start) {
			        f_length = f_start;
			    }
			    return f_string.substring(f_start, f_length);
			}
		};
		
	}();
	
	/**
	 * util cache
	 */
	this.cache			=		function() {
		return{
			all : CJAX_CACHE
			,
			add : function(key, value,replace){
					if(typeof replace == 'undefined') var replace = true;
					if(CJAX_CACHE['cache_close'] == 1) {
						return false;
					}
					if((!CJAX_CACHE[key] && replace===true) && value) {
						CJAX_CACHE[key]=value;
					}
				}
			,
			get : function( key ){
					if(CJAX_CACHE[key]) {
						return CJAX_CACHE[key];
					} else {
						return '';
					}
			},
			flush : function(){
				var i, item;
				for(i = CJAX_CACHE.length - 1; i >= 0; i = i - 1){
					item = CJAX_CACHE[i];
					if(delete item[0]){};
					if(item[1].substring(0, 2) != "on"){
						item[1] = "on" + item[1];
					};
					delete item[0][item[1]];
				};
			},
			close : function(){
				CJAX_CACHE['cache_close'] = 1;
			},
			open : function()
			{
				CJAX_CACHE['cache_close'] = 0;
			}
		};
	}();

	
	this.defined		=		function(obj) {
		return (typeof obj!='undefined')? true:false;
	};
	
	/**
	 * Util script
	 */
	this.script		=		function() {
		return {
			loaded : function ( path ,force) {
				if(!CJAX.defined(path)) {
					return false;
				}
				//Loaded by CJAX
				if(!CJAX.defined(force)) {
					return (path.loaded())? true:false;
				} 
				//Loaded on the document
				var scripts = CJAX.elem_docs( 'script' );
				var s;
				if(scripts.length){
					for(var i = 0; i < scripts.length; i++ ){
						s = scripts[i];
						if(s.src==path) return s;
					}
				}
				return false;
				
			},
			load: function(script,f,parameters,bypass) {
				if(CJAX.defined(CJAX.vars['loaded'][script])) {
					return true;
				}
				if(!CJAX.defined(parameters)) var parameters = '';
				var type =  CJAX.xml('ctype',parameters);
				if(CJAX.defined(bypass) && bypass) {
					var s = CJAX.script.loaded( script ,'function');
					if ( s )  return s;
				}
				var head = CJAX.elem_docs( 'head' )[0];
				var file_ext = CJAX.get.extension(script);
				if(file_ext=='.css') {
					var s = document.createElement("link");
					s.setAttribute("rel", "stylesheet");
					s.setAttribute("type", "text/css");
					s.setAttribute("href", script);
				} else {
					var s = document.createElement( 'script' );
					s.type = 'text/javascript';
					s.src= script;
				}

				if(!CJAX.defined(CJAX.vars['loaded'][script])) {
					CJAX.vars['loaded'][script] = [];
					CJAX.vars['loaded'][script]['function'] = [];
					CJAX.vars['loaded'][script]['function']['src'] = script;
					CJAX.vars['loaded'][script]['function']['function'] = f;
				}
				head.appendChild( s );
				return s;
			},
			reload: function(script,id) {
				if(!CJAX.defined(parameters)) {
					var parameters = '';
				}
				var file_ext = CJAX.get.extension(script);
				if(file_ext=='.css') {
					var s = document.createElement("link");
					s.setAttribute("rel", "stylesheet");
					s.setAttribute("type", "text/css");
					s.setAttribute("href", script);
				} else {
					var s = document.createElement( 'script' );
					s.type = 'text/javascript';
					s.src= script;
					s.id= id;
				}
				var head = CJAX.elem_docs('body')[0];
				head.appendChild( s );
				return s;
			},
			action : function(f,p,s){
				try {
					if ( s ) {
						setTimeout(f+'("'+p+'")',s*1000);
					}else{
						eval(f+'("'+p+'")');
					}
				}
				catch( e ){ alert('unabled to load function: '+ f.toUpperCase()+ ' '+e); }
			}
		};
	}();
	
	
	/**
	 * Process all commands and pass them to CJAX.process which processes them 1 at a time
	 */
	this.process_all		=		function ( actions , debug) 
	{
		if (!CJAX.is_cjax(actions)){ return; }
		if(!CJAX.defined(loading)) var loading = false;
		if(caller==null) var caller = 'unkonwn';
		if(debug!=null) {
			CJAX.debug = debug;
		}
		if(CJAX.debug) {
			console.log('initiating process_all', 'initiated by:',caller);
		}
		
		//remove all the output except the cjax buffer
		
		var start = "<xml class='cjax'>";
		var start_point =  actions.indexOf(start);
		
		if(start_point !=-1) {
			//remove <xml class='cjax'></xml> from the data
			actions = actions.substr(start_point+start.length,actions.length-6);
			actions = actions.substr(0,actions.length-6); //revemo the /</xml> part
		}
		
		var values = CJAX.xml(CJAX.name,actions,true);
		if(CJAX.debug) {
			console.log('process_all command count:',values.length);
		}
		var buffer;
		
		if(!loading) {
			//Not loading means that it needs a little extra time to process any returned html so that elements within can be used.
			
			//since the content that we are possibly going to play with isn't loaded yet, give a second or 2
			setTimeout(function(){
				for(var i = 0; i < values.length;i++) {
					buffer = values[i];
					var method = CJAX.xml('do',buffer);
					
					if(CJAX.debug) {
						console.log('call #',i,'process_all not loading mode','calling:',method);
					}
					CJAX.process( '<cjax>'+buffer+'<caller>process_all</caller></cjax>', 'process_all');
			
				}
			}
			,200);
			
		} else {
			
			var no_wait_list = CJAX.functionsNoWait();
			var method;
			var _wait;
			
			
			for(var i = 0; i < values.length;i++) {
				_wait = true;
				buffer = values[i];
				var method = CJAX.xml('do',buffer);
				
				if(!method) {
					console.log('process_all skip:',buffer);
					continue;
				}
				var flags = CJAX.xml('flags',buffer);
				
				if(flags) {
					flags = CJAX.util.array(flags);
					if(flags) {
						if(flags.FLAG_WAIT  == FLAG_NO_WAIT) {
							_wait = false;
						}
					}
				}
				if(CJAX.debug) {
					console.log('#',i,'process_all in loading mode','calling:',method);
				}
				if( !_wait) {
					CJAX.quickProcess( '<cjax>'+buffer+'</cjax>' ,method, 'process_all-quick');
					if(CJAX.debug) {
						console.log(CJAX.method ,'quickProcess');
					}
					continue;
				} else {
					if(no_wait_list[method]) {
						CJAX.quickProcess( '<cjax>'+buffer+'</cjax>' ,method,'process_all-quick');
						continue;
					}
				}
				
				CJAX.process( '<cjax>'+buffer+'</cjax>', 'process_all');
				
			}
		}
		CJAX.timer = 0;
		
		return values;
	};
	
	/**
	 * Function listed here will not have to go through any timer in case timers are present.
	 */
	this.functionsNoWait		=		function()
	{
		var fs = {"debug_env":1,"debug_test":1};
		return fs;
	};

	/**
	 * Proccess specific command.
	 * buffer is the command
	 * caller, any function caller reference.
	 * alt_element - an alternative element that is being used.
	 */	
	this.process		=		function( buffer , caller, alt_element) 
	{
		if(!CJAX.defined(caller)) {
			var caller = 'default-';
		}
		if(!CJAX.is_cjax(buffer)) {
			alert('no cjax - caller: '+caller+'\n'+buffer);return false ;
		};
		
		if(caller=='set.evet') {
			console.log("####process: caller",caller,buffer);
		}
		
		if(buffer==null) var buffer = '';
		if(encoded==null) var encoded = '';
		CJAX.method = CJAX.get_function(buffer);
		if(!CJAX.method) return false;
		var PREFIX = 'CJAX.';
		var f = _FUNCTION = PREFIX+CJAX.method;
		var ext = CJAX.xml('extension',buffer);
		if(CJAX.xml('debug',buffer)) {
			CJAX.debug = true;
		}
		var seconds = 0;
		
		var _wait = true;
		
		var flags = CJAX.xml('flags',buffer);
		if(flags) {
			flags = CJAX.util.array(flags,true);
			if(flags) {
				if(flags.FLAG_WAIT  == FLAG_NO_WAIT) {
					_wait = false;
				}
			}
		}

		var wait = CJAX.wait(buffer);
		if(_wait) {
			if(wait) {
				seconds = wait; 
			} else {
		    	var seconds = CJAX.xml('seconds',buffer);
		    	if(!seconds && CJAX.timer) {
		    		seconds = parseInt(CJAX.timer);
		    	} 
			}
			if(CJAX.debug) {
				console.log(CJAX.method ,"waits :",seconds,'caller:',caller);
			}
		} else {
			if(CJAX.debug) {
				console.log(CJAX.method ,'no wait time','caller:',caller);
			}
		}
	
		
		//If a magic call not defined in php.
		if(CJAX.xml('__call',buffer)) {
			CJAX.method = CJAX.method.replace("_",".");
			console.log('Magic Method:',PREFIX+CJAX.method);
			var a =	CJAX.decode(CJAX.xml('a',buffer));
			if(CJAX.php.is_array(a)) {
				a = CJAX.util.array(a);
			} else if(a.indexOf("\n")!=-1) {
				a = CJAX.xml('a',buffer);
			}
 			var b = CJAX.decode(CJAX.xml('b',buffer));	
			var c = CJAX.decode(CJAX.xml('c',buffer));
			var d = CJAX.decode(CJAX.xml('d',buffer));
			var e = CJAX.decode(CJAX.xml('e',buffer));
			var f = CJAX.decode(CJAX.xml('f',buffer));
			
			
			var is_buffer = CJAX.xml('buffer',buffer);
			if(is_buffer) {
				if(seconds){
	                   return setTimeout( function() {
	                	   eval(PREFIX+CJAX.method+'("'+buffer+'")');
	                   },seconds*1000);
					} else {
						return eval(PREFIX+CJAX.method+'("'+buffer+'")');
					}
			} else {
				
				try {
					if(seconds){
	                   return setTimeout( function() {
	                	   eval(PREFIX+CJAX.method+'(a,b,c,d,e,f)');
	                   },seconds*1000);
					} else {
						return eval(PREFIX+CJAX.method+'(a,b,c,d,e,f)');
					}
				} catch(e) {
					console.log("Magic Method",PREFIX+CJAX.method,'could not be initiated.',e.message);
				}
			}
			return;
		}
		
		//If it is a plugin..
		if(CJAX.xml('is_plugin',buffer)) {
			var file = CJAX.xml('file',buffer);
			
			var a =	CJAX.decode(CJAX.xml('a',buffer));
			if(CJAX.php.is_array(a)) {
				a = CJAX.util.array(a);
			}
			var b = CJAX.decode(CJAX.xml('b',buffer));	
			var c = CJAX.decode(CJAX.xml('c',buffer));
			var d = CJAX.decode(CJAX.xml('d',buffer));
			var e = CJAX.decode(CJAX.xml('e',buffer));
			var f = CJAX.decode(CJAX.xml('f',buffer));
			var path = __base__+'/plugins/'+file;
			var plugin = CJAX.xml( 'do' ,buffer);
			
			//for some reason a different method was returned, regain the original value
			CJAX.script.load(path);
			
			
			var interval_max = 100;
			var interval_count = 0;
			
			var interval = setInterval(function() {
				interval_count++;
				
				try {
					if(typeof eval(plugin)=='function') {
						try {
							if(seconds){
				                 return setTimeout( function() {
			                	   eval(plugin+'(a,b,c,d,e,f)');
			                	   clearInterval ( interval );
			                   },seconds*1000);
							} else {
								eval(plugin+'(a,b,c,d,e,f)');
								clearInterval ( interval );
								return;
							}
						}catch(e) {
							alert("Error#4 Plugin Error: "+plugin+': '+e);
							clearInterval ( interval );
						}
						clearInterval ( interval );
					}
					if(interval_count >= interval_max) {
						alert("Error#3 Could not load plugin "+file+".");
						clearInterval ( interval );
					}
				} catch(e) {
					alert("Error#4 Could not load plugin "+file+". "+e);
					clearInterval ( interval );
				}
			},100);
			
			return;
		}
		
		//If it is a util
		if(CJAX.method.indexOf('_')!=-1 && (typeof eval(PREFIX+CJAX.method)!='function')) {
			CJAX.method = (CJAX.method.replace("_","."));
			try {
				var x = (typeof eval(PREFIX+CJAX.method)=='function');
			} catch(e) {
				return alert("CJAX: Util "+CJAX.method+"() does not exist");
			}
			if(typeof eval(PREFIX+CJAX.method)=='function') {
				var is_buffer = CJAX.xml('buffer',buffer);
				if(is_buffer) {
					if(seconds){
		                   return setTimeout( function() {
		                	   eval(PREFIX+CJAX.method+'("'+buffer+'")');
		                   },seconds*1000);
						} else {
							return eval(PREFIX+CJAX.method+'("'+buffer+'")');
						}
				} else {
					var a = CJAX.xml('a',buffer);
					var b = CJAX.xml('b',buffer);	
					var c = CJAX.xml('c',buffer);
					var d = CJAX.xml('d',buffer);
					var e = CJAX.xml('e',buffer);
					var f = CJAX.xml('f',buffer);
					
					if(seconds){
	                   return setTimeout( function() {
	                	   eval(PREFIX+CJAX.method+'(a,b,c,d,e,f)');
	                   },seconds*1000);
					} else {
						return eval(PREFIX+CJAX.method+'(a,b,c,d,e,f)');
					}
				}
			}
			return;
		}
		
		//If it is a method
		if(typeof eval( f ) === 'function' ) {
			try{
				if(seconds){
                   setTimeout(PREFIX+CJAX.method+'("'+buffer+'")',seconds*1000);
					
				} else {
					if(alt_element) {
						this.current_element = alt_element;
					}
					eval(PREFIX+CJAX.method+'("'+buffer+'")');
				}
				
			}
			catch( e ) {
				if(CJAX.xml('encode',buffer)) {
					buffer = CJAX.util.replace_encode( buffer,CJAX.xml('encode',buffer));
				}
				
				try {
					if(alt_element) {
						this.current_element = alt_element;
					}
					eval(PREFIX+CJAX.method+'("'+buffer+'")');
				} catch( e ) {
					
					alert('#process unabled to load function: '+ CJAX.method+'();  '+e);
				}
			}
		} else {
			alert("CJAX XML-Processor:"+CJAX.method+' function not found.');
		}
		
	};

	/**
	 * The quick version of CJAX.process..  for known functions that don't require all the processing.
	 */
	this.quickProcess		=		function( buffer , _do_,caller) 
	{

		if(!CJAX.defined(caller)) {
			var caller = 'default-';
		}
		if(!CJAX.is_cjax(buffer)) {
			alert('no cjax - caller: '+caller+'\n'+buffer);return false ;
		};
		
		if(typeof buffer =='undefined') var buffer = '';
		
		CJAX.method = CJAX.get_function(buffer);
		
		if(!CJAX.method) return false;
		var PREFIX = 'CJAX.';
		var f = _FUNCTION = PREFIX+CJAX.method;

		
		//If it is a method
		if(typeof eval( f ) === 'function' ) {
			try{
				if(seconds){
                   setTimeout(PREFIX+CJAX.method+'("'+buffer+'")',seconds*1000);
					
				} else {
				   eval(PREFIX+CJAX.method+'("'+buffer+'")');
				}
				
			}
			catch( e ) {
				if(CJAX.xml('encode',buffer)) {
					buffer = CJAX.util.replace_encode( buffer,CJAX.xml('encode',buffer));
				}
				
				try {
					 eval(PREFIX+CJAX.method+'("'+buffer+'")');
				} catch( e ) {
					
					alert('#process unabled to load function: '+ CJAX.method+'();  '+e);
				}
			}
		} else {
			alert("CJAX XML-Processor:"+CJAX.method+' function not found.');
		}
	};

    
	this.xml		=		function (start , buffer , loop , caller) {
		if(!buffer) return;
		if(loop == null) var loop = 0;
		if(typeof start=='undefined') return '';
		if(caller == null) var caller = 'unknown';
		if(!buffer || !start) return '';
		var real_var = start;
		var end = CJAX.left_delimeter+'/'+start+CJAX.right_delimeter;
		start = CJAX.left_delimeter+start+CJAX.right_delimeter;
		try {
			var loc_start = buffer.indexOf( start );
		} catch(e) {
			
			alert("CJAX: XML-tag"+start+" - '"+buffer+"' is not valid xml source.");
			return;
		}
		var start_len = start.length;
		var end_len = end.length;
		var loc_end = buffer.indexOf( end );
		var middle = loc_end - loc_start - end_len +1;
		if (loc_start == -1 || loc_end ==-1) return '';
		var _new_var = buffer.substr(loc_start+start_len,middle);
		var string_len = loc_start+start_len+_new_var.length+start_len;
		
		if(loop) {
			var myarr = [];
			var i = 0;
			var value;
			var hold = buffer;
			while(CJAX.xml(real_var,hold) && hold) {
				value = CJAX.xml(real_var,hold,0,'CJAX.xml');
				hold = hold.substr((loc_start+start_len)+value.length+end_len);
				myarr[i] = value;
				
				i++;
			}
			if(CJAX.debug) {
				console.log("xml count:",i, 'for tag:',real_var);
			}
			return (myarr)?myarr:'';
		}
		return _new_var;
	};
	
	
	
	/**
	* hide elements
	* usage:
	* CJAX.hide('element_id');
	* or
	* for an group of elements using the same name
	* CJAX.hide('element_name','element_tag'); //element_tag is needed for IE only 
	*
	*/
	this.hide	=	function(buffer,tag) {
		//if( !verbose ) var verbose = true;
		if(!CJAX.defined(tag)) {
			var tag = null;
		}
		/*if(!CJAX.util.isXML(buffer)) {
			var elements = CJAX.getbyname(buffer,tag);
			if(elements) {
				for(var i=0; i < elements.length; i++) {
					element = elements[i];
					element.style.display = 'none';
				}
				return;
			}
		}*/
		var elem = CJAX.is_element(buffer,false); 
		
		if( !elem ) {
			elem = CJAX.xml('element',buffer);

			elem = CJAX.$( elem ); 
		} 
		if(!elem) {
			alert('CJAX hide - element '+buffer+' not found');
		}
		if( elem ) elem.style.display = 'none'; 
	};
	
	this.getbyname	=    function(name,tag){
        var x=document.getElementsByName(name); 
        
        if(x.length) {
        	return x;
        } else if(x && tag) {
     		var elements = document.getElementsByTagName(tag);
     		var new_elements = [];
     		var element;  
     		var x = 0;
		    for(var i = 0; i < elements.length; i++) {
		    	element = elements[i];
		        if(element.name == name) { 
		           new_elements[x] = element; 
		            x = x+1;
		         } 
		        
		    }
		    return new_elements;
        }
    };
	
	this.show	=	function(buffer) {
		//if( !verbose ) var verbose = true;

		var elem = CJAX.is_element(buffer,false); 
		
		if( !elem ) {
			elem = CJAX.xml('data',buffer);

			elem = CJAX.$( elem ); 
		} 
		if(!elem) {
			alert('CJAX show - element '+buffer+'+ found');
		}
		if( elem ) elem.style.display = 'block'; 
	};
	
	/**
	 * check all checkboxes or check none all are checked
	 */
	this.check	=	function(buffer) {
		//if( !verbose ) var verbose = true;
		var new_element,check;
		var elem = CJAX.is_element(buffer,false); 
		
		if( !elem ) {
			elem = CJAX.xml('data',buffer);

			elem = CJAX.$( elem ); 
		} 
		
		if(!elem) {
			if(CJAX.util.count(elem = document.getElementById(buffer+'[0]'))) {
				if(elem.nodeName !='INPUT') {
					return;
				}
				var i = 0;
				
				//var new_element = CJAX.get.property.parent(elem);
				var elements = CJAX.elem_docs(elem.nodeName);
				
				if(CJAX.clicked.type !='checkbox') {
					if(elem.checked) {
						check = false;
					} else {
						check = true;
					}
				} else {
					check = elem.checked;
				}
				for(x in elements) {
					new_element = elements[x];					
					if(new_element.type=='checkbox' && new_element.id.indexOf(buffer)!=-1) {
						new_element.checked = check;
					}
				}
				return;
			}
		} else {
			check = true;
			var pass = false;
			var elements = elem.getElementsByTagName("input");
			check = elements[0].checked? false: true;
			for(var i = 0; i < elements.length; i++) {
				new_element = elements[i];
				if(new_element.type=='checkbox') {
					new_element.checked = check;
				}
			}
		}
	};
	
	this._nextObject = function(obj) {
		var n = obj;
		do n = n.nextSibling;
		while (n && n.nodeType != 1);
		return n;
	};
	 
	this._previousObject = function(obj) {
		var p = obj;
		do p = p.previousSibling;
		while (p && p.nodeType != 1);
		return p;
	};
	
	
	
	this.css		=		function(_class,title)  {
		return {
				add:function(_class,title) {
				//if(typeof title=='undefined') var title = 'cjax';
				if(HELPER_STYLE && title=='cjax') {
					if(HELPER_STYLE.cssRules) {
						if(!CJAX.css.get(_class,title)) {
							HELPER_STYLE.insertRule(_class+' { }', 0);
						}
						return CJAX.css.get(_class,title);
					} else {
						//for IE
						if(!CJAX.css.get(_class,title)) {
							HELPER_STYLE.addRule(_class, null,0);
						}
						return CJAX.css.get(_class,title);
					}
				}
				
				function _create(title) {
					var style = document.createElement( 'style' );
					style.type = 'text/css';style.rel = 'stylesheet';style.media = 'screen';style.setAttribute('title',title);return style;
				}
				var styles = document.styleSheets;
				var style;
				for (var i = 0; i < styles.length; i++ ) {
					if(styles[i].title == title ) {
						style = styles[i];
						break;
					}
				}
				
				var head = CJAX.elem_docs('head')[0];
				if(!CJAX.defined(style)) {
					var obj = _create(title);
					head.appendChild( obj );
				}
				
				//first for FF
				if(obj.sheet) {
					var style = HELPER_STYLE = obj.sheet;
					style.insertRule(_class+' { }', 0);
				} else {
					//for IE
					var style = HELPER_STYLE = obj.styleSheet;
					style.addRule(_class, null,0);
				}
				
				var new_style =  CJAX.css.get( _class ,title);
				
				return new_style;
			},
			getClass: function(_class,css_file) {
				if(typeof css_file =='undefined') var css_file = null;
				
				if(CJAX.defined(CJAX.styles[_class])) {
					return CJAX.styles[_class];
				}
				
				var styles = document.styleSheets;
				var style;
				var rules;
				for (var i = 0; i < styles.length; i++ ) {
					style = styles[i];
					rules = style.cssRules;
					
					if(css_file && style.href) {
						var base = CJAX.get.basename(style.href);
						if(css_file != base) {
							continue;
						}
					}
					if(!CJAX.defined(rules.length)) {
						continue;
					}
					//css file is too big
					if(rules.length && rules.length > 50) {
						continue;
					}
					for(var i = 0; i < rules.length;i++) {
						
						if(typeof rules[i]=='number' || typeof rules[i]=='function') {
							continue;
						}
						if(!CJAX.defined(rules[i].selectorText)) {
							continue;
						}
						
						try {
							if(rules[i].selectorText==_class) {
								CJAX.styles[_class] = rules[i];
								return rules[i];
							}
						}catch(e) {}
					}
					
				}			
			},
			get: function(_class,css_title) {
				if(typeof css_title == 'undefined') var css_title = null;
				
				if(css_title=='cjax') {
					var style = HELPER_STYLE;
					
					var rule;
					if(style.cssRules){
						for(x in style.cssRules) {
							rule = style.cssRules[x];
							if(CJAX.defined(rule) && CJAX.defined(rule.selectorText) && rule.selectorText.toLowerCase() == _class) {
								return style.cssRules[x];
							}
						}
					} else {
						//for IE
						for(x in style.rules) {
							rule = style.rules[x];
							if(CJAX.defined(rule) && CJAX.defined(rule.selectorText) && rule.selectorText.toLowerCase() == _class) {
								return style.rules[x];
							}
						}
					}
				}
			}
		};
	
	}();

	this.turn		=		function( elem ) {
		if( elem ) {
			if (!elem.type) return false;
			switch ( elem.type ) {
				case 'checkbox':
				case 'radio':
					return true;
				break;
				default:
				return false;
			}
		}
		return false;
	};
	
	this.passvalue		=		function(elem,verbose) {
		if ( typeof verbose=='undefined' ) var verbose = true;
		var obj = CJAX.elem_doc(elem,verbose);
		if ( obj ) {
			switch ( obj.type ) {
				case 'text':
				case 'select-one':
				case 'select-multiple':
				case 'password':
				case 'textarea':
				case 'hidden':
					return escape(obj.value);
				break;
				case 'checkbox':
				return (obj.checked)? 1:0;
				break;
				case 'radio':
					var elements = document.getElementsByTagName("input");
					var element;
					if(elements.length) {
						for(var i = 0; i < elements.length; i++) {
							element = elements[i];
							if(element.type !='radio') continue;
							if(element.checked) {
								return element.value;
							}
						}
					}
					return (obj.checked)? 1:0;
				break;
			}
		}
	};
	
	this.AJAX		=		function() {
		xmlhttp = false;
		
		if (typeof XMLHttpRequest!='undefined') {
			xmlhttp = new XMLHttpRequest ();
		} else {
			try{
				xmlhttp = new ActiveXObject ("Msxml2.XMLHTTP");
			}
			catch ( e ){
				try{
					xmlhttp = new ActiveXObject ("Microsoft.XMLHTTP");
				}
				catch ( e ){
					xmlhttp = false;
				}
			}
			if (!xmlhttp && typeof XMLHttpRequest!='undefined') xmlhttp = new XMLHttpRequest ();
		}
		return xmlhttp;
	};

	this.intval		=		function( number ) {
		 var ret =  isNaN( number )? false:true;
		 if( ret ) { return number; } else { return 0; } 
	};
	
	this.wait		=		function( buffer ) {
		if(typeof buffer=='undefined') {
			CJAX.timer = 0;
			CJAX.waiting = false;
			console.log("Timer has be reset.");
			return 0;
		}
		if(FLAG_CLEAR_TIMEOUT) {
			CJAX.timer = 0;
		}
		var count = CJAX.intval(CJAX.xml('timeout',buffer));
		FLAG_CLEAR_TIMEOUT = CJAX.xml('clear',buffer);
		if(!count) {
			return;
		}
		CJAX.waiting = true;
		if(!CJAX.timer) {
			CJAX.timer = parseInt(CJAX.xml('timeout',buffer));
		} else {
			CJAX.timer = parseInt(CJAX.timer)+parseInt(CJAX.xml('timeout',buffer));
		}
		return CJAX.timer;
	};

	/**
	* return an element object can pass an string as id or an object
	**/
	this.is_element			=			function(element,verbose) {
		if(!element) {
			return;
		}
		if( typeof verbose === 'undefined') { var verbose = true; }
		if( verbose ) if( !element ){alert('invalid input on function: '+_FUNCTION+' :  not an element ');return;}
		var _element;
		var type = (typeof element);
		if( type.indexOf( 'object' ) != -1) return element;
		_element = CJAX.$(element,verbose);
		if(_element) return _element;
		
		_element = CJAX.xml('element',element);
		if(_element) return CJAX.$(_element,false);
		
		return;
	};
	
	/**
	* Decodes encoded data that passed by parameter
	* data delivered from php
	**/
	this.decode = function( data ) {
		if(typeof data =='string') {
			data = unescape(data);
			data = data.replace(/\+/gi," ");
			data = data.replace("/\[plus\]/gi","+");
		}
		return data;
	 };
	 
	 /**
	  * This function is not fully implemented nor tested.
	  * replace a pattern in a elements innerHTML
	  */
	 this.replace		=		function(buffer)
	 {
		 var element_id = CJAX.xml('element',buffer);
		 var element = CJAX.is_element(element_id);
		 if(!element) {
			 console.warning("Element not found:",element_id);
			 return;
		 }
		 var find = CJAX.xml('find',buffer);
		 var replace = CJAX.xml('replace',buffer);
		 
		 var data = element.innerHTML;
		 var pattern = new RegExp(find,"gms");
		 data = data.replace(pattern,replace);
		 element.innerHTML = data;
	 };
	 
	 this.style			=		function(buffer)
	 {
		 var element = CJAX.is_element(buffer,false);
		 var style = CJAX.xml('style',buffer);
		 
		 if(element && style) {
			 style = CJAX.util.array(style);
			 for(x in style) {
				 element.style[x] = style[x];
			 }
		 }
	 };
	 
	/**
	* update an element on the page
	**/
	this.update		=		function(buffer,data) 
	{
		var FLAG_ELEMENT_GETTER = CJAX.xml('FLAG_ELEMENT_GETTER',buffer);
		
		var element_getter = CJAX.xml('element',buffer);
		
		if(FLAG_ELEMENT_GETTER == FLAG_ELEMENT_BY_CLASS) {
			var element = CJAX.get.byClassName(element_getter);
		} else {
			var element = CJAX.is_element(buffer,false);
		}
		if( !element ) return false;
		if(CJAX.util.isXML(buffer) &&  !data )  {
			var data = CJAX.xml('data',buffer);
		}
		
		var raw = unescape(data);
		
		if(raw.substr(0,2)=='+=') {
			data = element.innerHTML+data;
		}
		
		data = CJAX.decode( data );

		element.innerHTML = data;
		if(CJAX.util.isXML(buffer) && CJAX.xml('display',buffer)) {
			element.style.display = '';
		}
	};
	
	/**
	* load external script into page head
	*/
	this.load_script = function ( params ) {
		if(typeof params =='undefined' || !params) return false;
		var url = CJAX.xml('script',params);
		if(!url) {
			url = params;
		}
		if(CJAX.strpos(url,'__domain__') !==false) {
			url = url.replace('__domain__',CJAX.get.dirname(document.baseURI));
		}
		if( !url.loaded() ) {
			var s = CJAX.script.load( url );
		}
	};
	
	// http://kevin.vanzonneveld.net
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // *     example 1: strpos('Kevin van Zonneveld', 'e', 5);
    // *     returns 1: 14
	this.strpos		=		function(haystack, needle, offset)
	{
		if(!CJAX.defined(offset)) {
			var offset = 0;
		}
		var i = haystack.indexOf( needle, offset ); // returns -1
		return i >= 0 ? i : false;
	};
	
	/*
	* displays an alert message with passed data
	*/
	this.alert		=		function ( buffer ,caller) {
		var msg = CJAX.xml('msg',buffer);
		msg = CJAX.decode( msg );
		if(!msg) {
			if(CJAX.debug) {

				console.log('CJAX:alert',' request was empty');
			}
			return;
			if(typeof caller !='undefined') {
				msg = msg+ ' - '+caller;
			}
		}
		alert( msg );
	};
	
	/**
	* redirected to specified location
	*/
	this.location		=		function( buffer ) {
		var destination = CJAX.xml('url',buffer);
		window.location = destination;	
	};
	
	function var_dump( obj ) {
		if(typeof obj == "object") {
			return "Type: "+typeof( obj )+((obj.constructor) ? "\nConstructor: "+obj.constructor : "")+"\nValue: " + obj;
		} else {
			return "Type: "+typeof( obj )+"\nValue: "+obj;
		}
	};
	
	this.getSelectedRadio		=		function( buttonGroup ) {
	   if (buttonGroup[0]) {
	      for (var i=0; i<buttonGroup.length; i++) {
	         if (buttonGroup[i].checked) {
	            return i;
	         }
	      }
	   } else {
	      if (buttonGroup.checked) { return 0; } 
	   }
	   return -1;
	} ;
	
	this.getSelectedRadioValue		=		function( buttonGroup ) {
	   var i = CJAX.getSelectedRadio( buttonGroup );
	   if (i == -1) {
	      return "";
	   } else {
	      if (buttonGroup[i]) {
	         return buttonGroup[i].value;
	      } else {
	         return buttonGroup.value;
	      }
	   }
	};

	this.form_get_elements_url		=		function( frm_object ) {
		var frm_url = '';
		var elem;
		var value;
		var c = 0;
		var f = CJAX.is_element(frm_object,false);
		if(!f)  {
			alert('The form you tried to submit was not found in the document.');
			return;
		}
		var form =  f.getElementsByTagName("*");
		for(var n = 0; n < form.length; n++){
			if(form[n].id =='undefined' && form[n].name =='undefined') continue;
			c++;
			elem = form[n];
			elem_id = elem.id;
			elem_type = elem.type;
			elem_value = elem.value;
			elem_len = elem.length;
			elem_id = elem.id;
			if((elem.id && elem.name !='') || (elem.id == 'undefined' && elem.name !='')){
				elem_id = elem.name; 
			}
			/*if(!elem_id) {
				continue;
			}*/
			switch ( elem.type ) {
				case 'checkbox':
					if(!elem.value) {
						if(!elem.checked) {
							continue;
						}
					}
					if(elem.value) {
						if(typeof elem.value=='string' && elem.value=='0') {
							value = ((elem.checked)? 1:0);
						} else  {
							if(elem.checked) {
								value = elem.value;
							} else {
								value = '';
							}
						}
					} else {
						var alt_value = elem.id.match(/[a-zA-Z0-9_]+\[(.+)\]/i,"$1");
						if(CJAX.defined(alt_value[1])) {
							value = alt_value[1];
						} else {
							value = ((elem.checked)? 1:0);
						}
					}
					
				break;
						case 'text':
						case 'select-one':
						case 'textarea':
							value = elem.value;
				break;
				case 'radio':
				
					if(CJAX.getSelectedRadio( elem ) === -1)continue;
					
					value = CJAX.getSelectedRadioValue( elem );
					break;
				default:
					value = encodeURI(elem.value);
			}
			if(value !='undefined' && elem_id) frm_url += "&"+elem_id + "="+ value;
		}
		return frm_url;
	};

	this.loadScreen		=		function(buffer) {
		CJAX.$('cjax_overlay').style.display = 'block';
	};
	
	
	this.exe_form		=		function( params ) {
		params = CJAX.decode(params);
		CJAX.resetDelimeters("[","]");
		
		
		var destino = CJAX.xml('url',params);
		var related = CJAX.xml('rel',params);
		
		var frm = CJAX.xml('form',params);
		var container = CJAX.xml('container',params);
		
		var text = CJAX.xml('text',params);
		if( !text ) text = 'Loading...';
		if(text =='no_text') text = null;
		
		
		if(text) {
			CJAX.msg.loading(text,true);
		}
		
		var _confirm = CJAX.xml('confirm',params);
		
		var dir = CJAX.xml('cjax_dir',params);
		
		var debug = CJAX.xml('debug',params);
		
		if(_confirm) {
			CJAX.$('cjax_overlay').style.display = 'block';
			
			if(!window.confirm(_confirm)) {
				CJAX.resetDelimeters();
				CJAX.$('cjax_overlay').style.display = 'none';
				return true;
			} else {
				CJAX.$('cjax_overlay').style.display = 'none';
			}
		}
		
		
		var image = CJAX.xml('image',params);
		var mode  = CJAX.xml('method',params);
		
		if( !mode ) mode = 'get';
		var file_form = null;
		var frame = null;
		var files = false;
		var first_destino = null;
		var form = null;
		if(!frm && CJAX.current_element) {
			form = CJAX.current_element.form;
		}
		if(frm || form) {
			var url ='';
			var elem_value = '';
			var is_my_radio = new Array( 10 );
			var splitter;
			var assign = '=';
			if(!form) {
				if(!CJAX.is_element(frm) ) {
					form = document.forms[frm];
					if(!form) {
						form = CJAX.$(frm);
					}
				} else {
					form = CJAX.is_element(frm);
				}
			}
			
			if( !form || !form.elements) {
				
				var url = CJAX.form_get_elements_url( frm );
				if( !url ){ alert('CJAX: invalid form or form is empty'); return false; }
			} else {
				var elems =  form.elements? form.elements: elems;
			
				var form_len = elems.length;
				
				rel = form.getAttribute('rel');
				
				for (var n=0; n < form_len; n++) {
					splitter = '&';
					elem  = elems[n];
					elem_id = elem.id;
					elem_type = elem.type;
					elem_name =  elem.name;
					elem_value = elem.value;
					elem_len = elem.length;
					if(!elem_type)continue;
					if(elem_type=='file') {
						files = true;
					}
					if(elem_id && elem_name)elem_id = elem_name;
					if(!elem_id && elem_name)elem_id = elem_name;
					
					if(!elem_id) {
						continue;
					}
					
					//Try to detect CKEDITOR
					try{
						if (elem_id && typeof CKEDITOR !='undefined' && typeof eval("CKEDITOR.instances."+elem_id) != 'undefined') {
							elem_type = 'ckeditor';
							//eval("CKEDITOR.instances."+elem_id+".updateElement()");
						}
					} catch(e) {}
					
					switch ( elem_type ) {
					//Detect CKEDITOR value
						case 'ckeditor':
							elem_value =(eval("CKEDITOR.instances."+elem_id+".getData()"));
						break;
						case 'checkbox':
							//if has no value, then not send
							if(!elem.value) {
								if(!elem.checked) {
									continue;
								}
							}
							//if has value, then give that value
							if(elem.value) {
								if(typeof elem.value=='string' && elem.value=='0') {
									elem_value = ((elem.checked)? 1:0);
								} else  {
									if(elem.checked) {
										elem_value = elem.value;
									} else {
										elem_value = '';
									}
								}
							} else {
								//send the id as value
								var alt_value = elem.id.match(/[a-zA-Z0-9_]+\[(.+)\]/i,"$1");
								if(CJAX.defined(alt_value[1])) {
									elem_value = alt_value[1];
								} else {
									elem_value = ((elem.checked)? 1:0);
								}
							}
						
						break;
						case 'text':
						case 'select-one':
						case 'textarea':
							assign='=';
							elem_value = elem.value;
						break;
						case 'radio':					
							if(CJAX.getSelectedRadio( elem ) != -1) {
								if(CJAX.getSelectedRadioValue( elem )) 
								elem_value = CJAX.getSelectedRadioValue( elem ); assign='=';
							}else{
								splitter =''; elem_id =''; elem_value =''; assign='';
							}
							break;
						case 'hidden':
							elem_value = elem.value;
							break;
						default:
							elem_value = elem.value;
					}
					if(typeof elem_value =='string' && elem_value.indexOf('&') !=-1) {
						elem_value = escape(elem_value);
					}
					
					url += splitter;
					url += elem_id + assign + encodeURI(elem_value);
					assign = '=';
				}

				if(files) {
					var element = null;
					var f_count = 0;
					var form_destino = '';
					for(var i = 0; i < form.length; i++) {
						element = form[i];
						if(element.type=='file') {
							if(element.value) {
								f_count++;
							}
							form_destino += '&a[]='+element.value;
							if(element.id && !element.name) {
								element.name = element.id;
							} else if(element.name && !element.id) {
								element.id = element.name;
							}
						}
					}
					form_destino = destino+form_destino+'&cjax_iframe=1';
					if(dir){
						form_destino += "&cjax_dir="+dir;
					}
					if(!f_count) {
						//There are no files to upload
						files = false;
					} else {
						iframe = CJAX.create.frame('frame_upload');
						iframe.width = '400px';
						iframe.height = '200px';
						//iframe.src = __base__+'/core/templates/iframe.html';
						form.appendChild(iframe);
						if(!debug) {
							iframe.style.display = 'none';
						}
						//alert(form_destino);
						with(form) {
							method ='POST';
							action = destino+'&cjax_dir=examples&cjax_iframe=1';
							encoding = "multipart/form-data";
							enctype = "multipart/form-data";
							target = iframe.id;
						}
					}
				}
			}
			
			if(related) {
				destino += '&'+related;
			}
			var first_destino = destino;
			destino += url;
	  	}
		if (mode.toLowerCase()  != "get" && destino.length > 1200) {
			CJAX.IS_POST = true;
		}

		var parts = destino.split("&");
		var part;
		if(parts) {
			for(x in parts) {
				if(typeof parts[x] =='string' && parts[x]) {
					if(!part) {
						part = CJAX._value(parts[x]);
					} else {
						part += "&"+CJAX._value(parts[x]);
					}
				}
			}
		}

		if(part) {
			destino = part;
		}
		CJAX.resetDelimeters();
		
		if( container ){
			container = CJAX.$( container );
			if( !container ) return false;
		}
		
		
		if(dir) {
			destino += "&cjax_dir="+dir;
			first_destino += "&cjax_dir="+dir;
			first_destino += "&cjax="+CJAX.microtime(true);
		}
		
		if(!CJAX.HTTP_REQUEST_INSTANCE) CJAX.HTTP_REQUEST_INSTANCE = CJAX.AJAX();
	
		if(destino.indexOf('&') != -1) {
		  destino += "&cjax="+CJAX.microtime(true);
		}
		
		if (!mode.toLowerCase()  == "get" && destino.length < 1200) {
			//reset instance
			CJAX.HTTP_REQUEST_INSTANCE.onreadystatechange = function () {};
			CJAX.HTTP_REQUEST_INSTANCE.open ('GET', destino);
		} else {
			CJAX.IS_POST = true;
			CJAX.HTTP_REQUEST_INSTANCE.open ('POST', first_destino,true);
			if (CJAX.HTTP_REQUEST_INSTANCE.overrideMimeType) {
				CJAX.HTTP_REQUEST_INSTANCE.overrideMimeType('text/html');
			}
			CJAX.HTTP_REQUEST_INSTANCE.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			CJAX.HTTP_REQUEST_INSTANCE.setRequestHeader("Content-length", destino.length);
		
			if(destino.length > 1500) {
				CJAX.HTTP_REQUEST_INSTANCE.setRequestHeader("Connection", "Keep-Alive");
			} else {
				CJAX.HTTP_REQUEST_INSTANCE.setRequestHeader("Connection", "Closed");
			}
		}
		
		//don't change this header or you will get a security error.
		CJAX.HTTP_REQUEST_INSTANCE.setRequestHeader('X-Requested-With', 'CJAX FRAMEW0RK //@version;');
		
		CJAX.HTTP_REQUEST_INSTANCE.onreadystatechange = function () {
			
			if(CJAX.HTTP_REQUEST_INSTANCE.readyState) {
				if(CJAX.HTTP_REQUEST_INSTANCE.readyState < 4) {
					if(CJAX.debug) {
						console.log("Waiting for response..");
					}
				} else {
					switch(CJAX.HTTP_REQUEST_INSTANCE.status) {
					case 200:
						var txt =  buffer = CJAX.HTTP_REQUEST_INSTANCE.responseText;
						CJAX.msg.clear();
						
						CJAX.process_all(buffer);
						
						txt = unescape(txt);
						
						if( container ) container.innerHTML = txt;
			     		if (CJAX.debug) CJAX.alert('Debug: - exe_form - '+container.innerHTML);
			     		
						break;
					case 400:
						CJAX.msg.error('Error: The server returned a "Bad Request" status message 400.');
						break;
					case 404:
						CJAX.msg.error('Error: File not found '+destino);
						break;
					case 403:
						CJAX.msg.error('Error: Access to this request is forbidden');
						break;
					case 500:
						CJAX.msg.error("Error: The server encountered an unexpected Error with status 500. See server log for details.");
						break;
					case 503:
						CJAX.msg.error("Error: Gateway timeout.");
						break;
					}
				}
			}
			return;
		};
		
		CJAX.HTTP_REQUEST_INSTANCE.send ( ((CJAX.IS_POST)? destino:null) );
		if(files) {
			var _submit		=	function () {
				form.submit();
				if(iframe) {
					var content = null;
					var interval_max = 300;
					var interval_timeout = 200;
					var interval_count = 0;
					
					
					var interval = setInterval(function() {
						interval_count++;
						try {
							
							content = iframe.contentWindow.document.body.innerHTML;
							content = content.replace("&lt;","<");
							content = content.replace("&gt;",">");
						} catch(err) {
							alert("CJAX: Error - uploading files "+err);
							clearInterval ( interval );
						}
						if(content) {
							
							console.log('iframe response:',content);
							var values = CJAX.xml(CJAX.name,content,true);
							
							for(var i = 0; i < values.length;i++) {
								buffer = values[i];
								var method = CJAX.xml('do',buffer);
								if(CJAX.debug) {
									console.log('#',i,'process_all in loading mode','calling:',method);
								}
								CJAX.process( '<cjax>'+buffer+'</cjax>', 'process_all');
								
							}
							clearInterval ( interval );
						}
						if(interval_count > interval_max) {
							alert("Uploading Failed.");
							clearInterval ( interval );
						}
					},interval_timeout);
				}
			
			};
			setTimeout(function(){_submit();},1000);
		}
	};
	

	this.msg		=		function()
	{
		return {
			warning:function(txt) {
				CJAX.message("<cjax><data><div class='cjax_message cjax_message_type cjax_warning'><div>"+txt+"</div></div></data></cjax>");
			},
			error:function(txt) {
				CJAX.message("<cjax><data><div class='cjax_message cjax_message_type cjax_error'><div>"+txt+"</div></div></data></cjax>");
			},
			success:function(txt) {
				CJAX.message("<cjax><data><div class='cjax_message cjax_message_type cjax_success'><div>"+txt+"</div></div></data></cjax>");
			},
			loading:function(txt,wait_cursor) {
				if(typeof wait_cursor !='undefined' && wait_cursor) {
					document.body.style.cursor = 'wait';
				}
				CJAX.message("<cjax><data><div class='cjax_message cjax_message_type cjax_loading'>"+txt+"</div></data></cjax>");
			},
			clear:function() {
				CJAX.message();
				document.body.style.cursor = 'default';
			}
		
		};
		
	}();
	
	
	this.log		=		function(buffer)
	{
		if(typeof console!='undefined') {
			console.log(buffer);
		}
	};
	
	this.$					=		function(e,v) {
		if(!e) {
			return;
		}
		if(typeof v == 'undefined') {
			var v = false;
		}
		return CJAX.elem_doc(e,v);
	};
	
	this.elem_doc		=		function(id_obj,verbose) {
		var type = (typeof elem);
		if( typeof verbose == 'undefined' && CJAX.debug) { verbose = true; }
		if( type.indexOf( 'object' ) == -1) {var elem = document.getElementById(id_obj);}
		if(typeof id_obj == 'undefined' || id_obj===null) {
			if( verbose ) alert('Element not found'); 
		 	return false;
		}
		
		if( !elem ){
			if( verbose ) CJAX.alert('CJAX: Element "'+id_obj+'" not found on document');
			return false;
		}
		return elem;
	};
	
	this.elem_docs		=		function(id_obj,verbose) {
		if(typeof verbose =='undefined') verbose = true;	
		var obj = document.getElementsByTagName(id_obj);
		if( !obj ) {
			if( verbose ) CJAX.alert('CJAX: Element '+id_obj+' not found on document');
			return;
		}			
		return obj;
	};
	
	this.getY		=		function () {
		 var scrOfY = 0;
	  if( typeof( window.pageYOffset ) == 'number' ) {
	    //Netscape compliant
	    scrOfY = window.pageYOffset;
	  } else if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
	    //DOM compliant
	    scrOfY = document.body.scrollTop;
	  } else if( document.documentElement && ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) {
	    //IE6 standards compliant mode
	    scrOfY = document.documentElement.scrollTop;
	  }
	  return scrOfY;
	};
		
	this.getX 	=	 function() {
		var scrOfX = 0;
		if( typeof( window.pageYOffset ) == 'number' ) {
		//Netscape 
			scrOfX = window.pageXOffset;
		} else if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
		//DOM 
			scrOfX = document.body.scrollLeft;
		} else if( document.documentElement && ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) {
		//IE6 standards compliant 
			scrOfX = document.documentElement.scrollLeft;
		}
		return scrOfX;
	};
	    
    this.unserialize		=		function(data) {
        // http://kevin.vanzonneveld.net
        // +     original by: Arpad Ray (mailto:arpad@php.net)
        // +     improved by: Pedro Tainha (http://www.pedrotainha.com)
        // +     bugfixed by: dptr1988
        // +      revised by: d3x
        // +     improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // +      input by: Brett Zamir
        // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // %            note: We feel the main purpose of this function should be to ease the transport of data between php & js
        // %            note: Aiming for PHP-compatibility, we have to translate objects to arrays 
        // *       example 1: unserialize('a:3:{i:0;s:5:"Kevin";i:1;s:3:"van";i:2;s:9:"Zonneveld";}');
        // *       returns 1: ['Kevin', 'van', 'Zonneveld']
        // *       example 2: unserialize('a:3:{s:9:"firstName";s:5:"Kevin";s:7:"midName";s:3:"van";s:7:"surName";s:9:"Zonneveld";}');
        // *       returns 2: {firstName: 'Kevin', midName: 'van', surName: 'Zonneveld'}
        
        var error = function (type, msg, filename, line){throw new window[type](msg, filename, line);};
        var read_until = function (data, offset, stopchr){
            var buf = [];
            var chr = data.slice(offset, offset + 1);
            var i = 2;
            while (chr != stopchr) {
                if ((i+offset) > data.length) {
                    error('Error', 'Invalid');
                }
                buf.push(chr);
                chr = data.slice(offset + (i - 1),offset + i);
                i += 1;
            }
            return [buf.length, buf.join('')];
        };
        var read_chrs = function (data, offset, length){
            var buf;
            
            buf = [];
            for(var i = 0;i < length;i++){
                var chr = data.slice(offset + (i - 1),offset + i);
                buf.push(chr);
            }
            return [buf.length, buf.join('')];
        };
        var _unserialize = function (data, offset){
            var readdata;
            var readData;
            var chrs = 0;
            var ccount;
            var stringlength;
            var keyandchrs;
            var keys;
     
            if(!offset) offset = 0;
            var dtype = (data.slice(offset, offset + 1)).toLowerCase();
            
            var dataoffset = offset + 2;
            var typeconvert = new Function('x', 'return x');
            
            switch(dtype){
                case "i":
                    typeconvert = new Function('x', 'return parseInt(x)');
                    readData = read_until(data, dataoffset, ';');
                    chrs = readData[0];
                    readdata = readData[1];
                    dataoffset += chrs + 1;
                break;
                case "b":
                    typeconvert = new Function('x', 'return (parseInt(x) == 1)');
                    readData = read_until(data, dataoffset, ';');
                    chrs = readData[0];
                    readdata = readData[1];
                    dataoffset += chrs + 1;
                break;
                case "d":
                    typeconvert = new Function('x', 'return parseFloat(x)');
                    readData = read_until(data, dataoffset, ';');
                    chrs = readData[0];
                    readdata = readData[1];
                    dataoffset += chrs + 1;
                break;
                case "n":
                    readdata = null;
                break;
                case "s":
                    ccount = read_until(data, dataoffset, ':');
                    chrs = ccount[0];
                    stringlength = ccount[1];
                    dataoffset += chrs + 2;
                    
                    readData = read_chrs(data, dataoffset+1, parseInt(stringlength));
                    chrs = readData[0];
                    readdata = readData[1];
                    dataoffset += chrs + 2;
                    if(chrs != parseInt(stringlength) && chrs != readdata.length){
                        error('SyntaxError', 'String length mismatch');
                    }
                break;
                case "a":
                    readdata = {};
                    
                    keyandchrs = read_until(data, dataoffset, ':');
                    chrs = keyandchrs[0];
                    keys = keyandchrs[1];
                    dataoffset += chrs + 2;
                    
                    for(var i = 0;i < parseInt(keys);i++){
                        var kprops = _unserialize(data, dataoffset);
                        var kchrs = kprops[1];
                        var key = kprops[2];
                        dataoffset += kchrs;
                        
                        var vprops = _unserialize(data, dataoffset);
                        var vchrs = vprops[1];
                        var value = vprops[2];
                        dataoffset += vchrs;
                        
                        readdata[key] = value;
                    }
                    
                    dataoffset += 1;
                break;
                default:
                    error('SyntaxError', 'Unknown / Unhandled data type(s): ' + dtype);
                break;
            }
            return [dtype, dataoffset - offset, typeconvert(readdata)];
        };
        return _unserialize(data, 0)[2];
    };
    
	this.microtime		=		function(get_as_float) {
	    // Returns either a string or a float containing the current time in seconds and microseconds  
	    // 
	    // version: 1009.2513
	    // discuss at: http://phpjs.org/functions/microtime    // +   original by: Paulo Freitas
	    // *     example 1: timeStamp = microtime(true);
	    // *     results 1: timeStamp > 1000000000 && timeStamp < 2000000000
	    var now = new Date().getTime() / 1000;
	    var s = parseInt(now, 10);
	    return (get_as_float) ? now : (Math.round((now - s) * 1000) / 1000) + ' ' + s;
	};
	
    /**
     * Params:
     * url,rel,confirm
     */
    this.exe_html		=		function( params ,caller ) {
		params = CJAX.decode(params);
		var msg = null, x, response;
		
		if(caller==null) {
			var caller = 'unknown';
		}
		
	
		
		if(CJAX.debug) {
			console.log('CJAX.set.event','exe_html','called by '+caller);
		}
		
		CJAX.resetDelimeters("[","]");
		var destino = CJAX.xml('url',params);
		destino = destino.replace(/\&amp\;/gi,"&");
		var related = CJAX.xml('rel',params);
		var _confirm = CJAX.xml('confirm',params);
		var stamp = CJAX.xml('stamp',params);
		
		var is_loading  =  CJAX.xml('is_loading',params);
		if(!is_loading) is_loading = false;
		
		if(related) {
			destino += '&'+related;
		}
		
		if(_confirm) {
			CJAX.$('cjax_overlay').style.display = 'block';
			
			if(!window.confirm(_confirm)) {
				CJAX.resetDelimeters();
				CJAX.$('cjax_overlay').style.display = 'none';
				return true;
			} else {
				CJAX.$('cjax_overlay').style.display = 'none';
			}
		}
		var parts = destino.split("&");
		var part;
		if(parts) {
			for(x in parts) {
				if(typeof parts[x] =='string' && parts[x]) {
					if(!part) {
						part = CJAX._value(parts[x]);
					} else {
						part += "&"+CJAX._value(parts[x]);
					}
				}
			}
		}
		if(part) {
			destino = part;
			destino = (eval("'"+destino+"'"));
		}
		var text = CJAX.xml('text',params);
		if( !text || text==1) text = 'Loading...';
		if(text =='no_text') text = null;
		
		if(text) {
			CJAX.msg.loading(text,true);
		}
		var mode  = (CJAX.xml('mode',params)? CJAX.xml('mode',params):'get');
		var args = CJAX.xml('args',params);
		var image = CJAX.xml('image',params);
		var element =  CJAX.xml('element',params);
		var dir = CJAX.xml('cjax_dir',params);
		
		CJAX.resetDelimeters();
		
		if(x =  element ) {
			var element = CJAX.is_element( element ,false);
			if( !element ) {
				alert("CJAX Error: Element "+x+ " not found");
				return false;
			}
		}
		
		CJAX.HTTP_REQUEST_INSTANCE = this.AJAX ();
		
		if(destino.indexOf('&') != -1) {
			var ms=+new Date().getTime();
			if(dir) {
				destino += "&cjax_dir="+dir;
			}
			destino += "&cjax="+CJAX.microtime(true);
		}
		
		if (mode.toLowerCase()  == "get") {
			CJAX.HTTP_REQUEST_INSTANCE.open (mode, destino); //ms="+new Date().getTime());
		} else {
			CJAX.IS_POST = true;
			if (CJAX.HTTP_REQUEST_INSTANCE.overrideMimeType) {
				//http_request.overrideMimeType('text/xml');
				CJAX.HTTP_REQUEST_INSTANCE.overrideMimeType('text/html');
			}
			var full_url = destino+args;
			CJAX.HTTP_REQUEST_INSTANCE.open (mode, destino,true);
			CJAX.HTTP_REQUEST_INSTANCE.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			CJAX.HTTP_REQUEST_INSTANCE.setRequestHeader("Content-length", full_url.length);
			CJAX.HTTP_REQUEST_INSTANCE.setRequestHeader("Connection", "Keep-Alive");
		}

		CJAX.HTTP_REQUEST_INSTANCE.setRequestHeader('X-Requested-With', 'CJAX FRAMEW0RK //@version;');
		
		try{
			CJAX.HTTP_REQUEST_INSTANCE.onreadystatechange = function () {
				
				if(CJAX.HTTP_REQUEST_INSTANCE.readyState) {
					if(CJAX.HTTP_REQUEST_INSTANCE.readyState < 4) {
						if(CJAX.debug) {
							console.log("Waiting for response..");
						}
					} else {
						switch(CJAX.HTTP_REQUEST_INSTANCE.status) {
						case 200:
							
							var txt =  CJAX.HTTP_REQUEST_INSTANCE.responseText;
							
							CJAX.msg.clear();
						
							CJAX.process_all(txt);
						
							txt =  unescape(txt);
							CJAX.cache_calls[params] = response = txt;
							if( element ){
								try { 
						      		element.innerHTML = txt;
									}catch(err)   { alert("Error - Cant not use element. "+err);  }
					      	}
							if (CJAX.debug) {
								console.log("response text:",txt);
							}
				     		
							break;
						case 400:
							CJAX.msg.error('Error: The server returned a "Bad Request" status message 400.');
							break;
						case 404:
							var msg = 'CJAX Error: File not found '+destino;
							if( element ) {
								if(!element.type) { alert('exe_html: element type is'+ element + msg ); return false; }
							}	
							CJAX.msg.error('Error: File not found '+destino);
							break;
						case 403:
							CJAX.msg.error('Error: Access to this request is forbidden');
							break;
						case 500:
							CJAX.msg.error("Error: The server encountered an unexpected Error with status 500. See server log for details.");
							break;
						case 503:
							CJAX.msg.error("Error: Gateway timeout.");
							break;
						default:
							CJAX.msg.warning("Error: Server responded with a unsual response, see available server error logs for details.");
							break;
						}
					
					}
				}
				
				return;
			};
	    } catch( err ) {
			alert('CJAX: Error - "'+err.description+'"'+"\n"+err.message); 
		}
		
		
		CJAX.HTTP_REQUEST_INSTANCE.send ( ((CJAX.IS_POST)? full_url:null) );
		
		return response;
	};
    
	/**
	 * Read and process values from a URL
	 */
	this._value	=	function(buffer,type) {
		var fpoint;
		fpoint = buffer.indexOf("|");
		if(fpoint !=-1) {
			
			var v = buffer.match(/\|(.+)\|/i,"$1");
			
			if(CJAX.defined(CJAX.$(v[1]))) {
				if(element = CJAX.$(v[1])) {
					switch(element.type) {
					case 'checkbox':
						buffer = buffer.replace(v[0], "'+escape(CJAX.$('"+element.id+"').checked?1:0)+''+'");
					break;
						default:
						buffer = buffer.replace(v[0], "'+escape(CJAX.$('"+element.id+"').value)+''+'");
					}
				} else {
					var radios = document.getElementsByName(v[1]);
					if(radios) {
						var element;
						var check = '';
			
						for(var i = 0; i < radios.length; i++) {
							element = radios[i];
							if(element.checked) {
								check = element.value;
								break;
							}
							
						}
						
						buffer = buffer.replace(v[0], check);
					}
				}
			}
			return buffer;
		}
		return buffer;
	};
	
	
	function __construct() {
		//if(CJAX.script.load(__base__+"/components/extensions.js")) CJAX.COMPONENTS['extensions'] = 1;
		
		CJAX.script.load(__base__+'/core/js/cjax.js.php?update='+CJAX.microtime(true));
		//not meant to be a formatted, but it can be used as a helper to display
		//well formatted messages
		CJAX.script.load(__base__+'/core/css/cjax.css');
		
		document.onclick = myClickListener;
		function myClickListener(e) {
			var eventIsFiredFromElement;
			if(e==null) {
				eventIsFiredFromElement = event.srcElement;
			} else {
				eventIsFiredFromElement = e.target;
			}
			CJAX.clicked =eventIsFiredFromElement;
		}
		var div = CJAX.create.div('cjax_overlay');
		div.className = 'cjax_overlay';
		div.onclick = CJAX._removeOverLay;
	};

	this.initiate			=			function() {
		/**
		* Deal with Firebug not being present
		*/
		if (!window.console || !console.firebug) {
			 (function(w) { var fn, i = 0;
				 if (!w.console) w.console = {};
				 fn = ['assert', 'clear', 'count', 'debug', 'dir', 'dirxml', 'error', 'getFirebugElement', 'group', 'groupCollapsed', 'groupEnd', 'info', 'log', 'notifyFirebug', 'profile', 'profileEnd', 'time', 'timeEnd', 'trace', 'warn'];
				 for (i = 0; i < fn.length; ++i) if (!w.console[fn[i]]) { w.console[fn[i]] = function() {}; }
		 	})(window);
		}
		
		var cjax_css = CJAX.css.add('.cjax','cjax');
		if(cjax_css) {
			cjax_css.style.position = 'absolute';
			cjax_css.style.visibility = 'hidden';
			cjax_css.style.display = 'none';
		}
		CJAX.set.event(window,'load',CJAX.onStartEvents);
	};
	
	this.onStartEvents		=		function() {
		 var nav=navigator.userAgent.toLowerCase();
		 this.ie     = ((nav.indexOf("msie") != -1) && (nav.indexOf("opera") == -1));
		__base__ = CJAX.dir = CJAX.get.basepath();
		CJAX.vars['loaded'] = [];
		__construct();
	};
	
}

var CJAX = new CJAX_FRAMEWORK();	
CJAX.initiate();