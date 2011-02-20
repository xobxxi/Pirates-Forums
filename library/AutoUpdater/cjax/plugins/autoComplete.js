

function autoComplete(field,buffer) 
{
	//TODO
	//alert(field+' - '+buffer);
	
	return;
	buffer = CJAX.decode(buffer);
	var current_child,previous_child;
	var count = 0;
	var array, new_array = [];
	var element = CJAX.$(CJAX.xml('element',buffer));
	array = new_array = CJAX.util.xmlArray(CJAX.xml('elements',buffer));
	 
	if(!element) {
		return false;
	}
	if(element.getAttribute('autocomplete') !='off') {
		element.setAttribute("autocomplete", 'off');
	}
	
	CJAX.remove('auto_complete_div');
	
	var position = CJAX.get.position(element);
	var div = CJAX.$('auto_complete_div');
	
	
	var items =  function(array,text) {
		var found;
		var new_item = new_item_text = null;
		var letter = null;
		var new_data = [];
		if(text) {
			text = text.toLowerCase();
			for(i = 1; i <= new_array.length;i++) {
				new_item = new_array[i];
				if(new_item) {
					new_item_text = new_item.toLowerCase();
				}
				found = true;
					
				//make sure every letter is in the selection
				if(text && typeof new_item_text !='undefined') {
					for(x = 0; x < text.length; x++) {
						letter = text[x];
						if(new_item_text.indexOf(letter)==-1) {
							found = false;
							break;
						}
					}
					if(found) {
						if(new_item_text.indexOf(text)!=-1 && text.length < new_item_text.length) {
							new_data[i] = new_item;
						}
					}
				}
			}
		}
		
		return new_data;
	};
	
	var new_drop = function(new_data) {
		var child = [];
		var string,string2 = null;
		var  x;
		
		
		var children = div.childNodes;
		for (var i = 0; i < children.length; i++) {
			children[i].style.display = 'none';
		}
		if(!new_data || new_data.length==0) {
			div.style.display = 'none';
			return;
		} else {
			div.style.display = 'block';
		}
		
		for(i = 0; i < new_data.length;i++) {
			x = new_data[i];
			if(typeof x=='undefined') {
				continue;
			}
			child[x] = CJAX.create.div('auto_complete_div_child'+x);
			child[x].style.display = 'block';
			//child[x].id  = x;
			string = x;
			string2 = element.value;
			if(string > 1 && string.indexOf(string2) == -1) {
				child[x].style.display = 'none';
				continue;
			}
			
			with (child[x].style) {
				marginLeft = '1px';
				marginRight = '1px';
				marginTop = '1px';
				marginBottom = '1px';
				paddingLeft = '1px';
				paddingRight = '1px';
				paddingTop = '1px';
				paddingBottom = '1px';
				cursor = 'pointer';
				fontSize = '12px';
				color = '#616161';
			}
			child[x].innerHTML = x;
			child[x].onmouseover = function(e) {
				this.style.backgroundColor = '#316AC5';
				this.style.color ='#FFFFFF';
				current_child = this;
			};
			
			child[x].onmouseout = function(e) {
				this.style.backgroundColor = "#FFFFFF";
				this.style.color ='#616161';

				previous_child = this;
			};
			
			child[x].onclick = function (e) {
				element.value = this.innerHTML;
				div.style.display = 'none';
			};
			div.appendChild(child[x]);
		}
	};

	//position[4] = position[4]+element.offmarginLeft;
	element.style.position = 'relative';
	if(!div) {
		div = CJAX.create.div('auto_complete_div');
		with (div.style) {
			position = 'absolute'; 
			minWidth = element.offsetWidth-3+'px';
			maxHeight = '250px';
			overflow = 'auto'; 
			borderStyle='solid';
			borderWidth = '1px';
			borderColor = '#317082';
			backgroundColor ='#FFFFFF';
			fontSize = '0.9em';
			top = (element.offsetTop+18)+"px";
			left = element.offsetLeft+"px";
			zIndex = '99';
			display = 'none';
		}
		
		element.onblur = function(e) {
			setTimeout(function() {div.style.display ='none';},170);
		};
		
		this.dom.insertAfter(div,element);
		
		element.onkeyup = _elementKeyUp;
		

		
		function _elementKeyUp(e) {
			var keycode;
			if (window.event)  {
				keycode = window.event.keyCode;
			} else if (e) {
				keycode = e.which;
			}
			
			var next_child = last_child = null;
				
			if(keycode==40) {
				//arrow down	
				var children = div.childNodes;
				if(previous_child) {
					previous_child.style.backgroundColor = '#fff';
					previous_child.style.color ='#616161';
				}
				if(current_child) {
					current_child.style.backgroundColor = '#316AC5';
					current_child.style.color ='#FFFFFF';
					
					previous_child = current_child;
					current_child = current_child.nextSibling;
				} else {
					current_child = children[0];
					current_child.style.backgroundColor = '#316AC5';
					current_child.style.color ='#FFFFFF';
				}
				/*if(next_child) {
					div.scrollTop = next_child.offsetTop;
				}*/
				//div.scrollTop += 10
			} else if(keycode == 38) {
				//arrow up
				var children = div.childNodes;
				if(previous_child) {
					previous_child.style.backgroundColor = '#fff';
					previous_child.style.color ='#616161';
				}
				if(current_child) {
					current_child.style.backgroundColor = '#316AC5';
					current_child.style.color ='#FFFFFF';
					
					previous_child = current_child;
					current_child = current_child.previousSibling;
				} else {
					current_child = children[0];
					do {
						current_child = current_child.nextSibling;
					} while(current_child.nextSibling);
					current_child.style.backgroundColor = '#316AC5';
					current_child.style.color ='#FFFFFF';
				}
			/*	if(next_child) {
					div.scrollTop = next_child.offsetTop;
				}*/
			} else if(keycode == 13) {
				//enter
				if(current_child)  {
					this.value = current_child.innerHTML;
					setTimeout(function() {div.style.display ='none';},170);
				}
				return true;
			} else {
				new_data = items(new_array,element.value);
				new_drop(new_data);
			}
		};
		
		this.dom = function() {
			return {
				insertAfter:function(new_element,element) {
				
					//parent =  CJAX.get.property.parent(element);
					var  parent = element.parentNode;
					var ref = null;
					if(element.nextSibling) {
						ref = element.nextSibling;
					}
					if(ref.nextSibling) {
						ref = ref.nextSibling;
					}
					parent.insertBefore(new_element, ref);
				}
			};
		};

	};
};