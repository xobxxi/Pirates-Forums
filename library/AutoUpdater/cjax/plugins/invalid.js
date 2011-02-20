

function invalid(elements)
{
	var err;
	CJAX.script.load(CJAX.dir+'/plugins/invalidate/css/invalidate.css');
	setTimeout(function() {
		CJAX.css.getClass('.msg','invalidate.css');
	},50);
	for(x in elements) {
		err =  "<div class='msg'><span class='invalidate'><div class='msg_text'>"+elements[x]+"</div></span></div>";
		apply(x,err);
	}
}

function apply(field,error)
{
	var element = CJAX.is_element(field,false);
	if(!element) return false;
	var default_border_color = element.style.borderColor;
	var default_element_class = element.className;
	var msg_css;
	element.className = element.className+' invalid';
	var parent = CJAX.get.property.parent(element);
	var parent_id = 'x_parent';
	field = field.replace(/\[|\]/g,'_');
	
	var dim = CJAX.get.position(element);
	if(CJAX.defined(dim.left)) {
		dim.left = dim.left+element.scrollWidth+element.clientLeft;
	} else {
		dim.left  = element.scrollWidth+element.clientLeft;
	}
	if(CJAX.is_element(field+'_label',false)) {
		
		setTimeout(function() {
			msg_css = CJAX.css.getClass('.msg','invalidate.css');
			if(dim.left > 800) {
				msg_css.style.backgroundImage = "url('../images/msg2.png')";
			} else {
				msg_css.style.backgroundImage = "url('../images/msg1.png')";
			}
		}
		,100);
		
		var label = CJAX.is_element(field+'_label',false);
		CJAX.update(label,error);
		label.className = label.id;
		label.style.display = 'block';
		return;
	}
	var label = CJAX.create.div(field+'_label',parent_id,false);
	
	//get the y coordinate of the element on the page
	var y = CJAX.get.y(element);

	var _left = dim.left;
	//too much to the right
	if(dim.left > 800) {
		dim.left = (dim.left - element.scrollWidth) - 360;
		y = y - 10;
		setTimeout(function() {
			msg_css = CJAX.css.getClass('.msg','invalidate.css');
			msg_css.style.backgroundImage = "url('../images/msg2.png')";
		},100);
	}
	var label_class = CJAX.css.add('.'+label.id,'cjax');
	label_class.style.position = 'absolute';
	label_class.style.left = dim.left+'px';
	label_class.style.top = y+'px';
	label_class.style.display = 'block';
	label.className = label.id;
	
	CJAX.update(label,error);
	element.focus();
	element.onblur = element.onkeydown = _clearMsg;
	function _clearMsg() {
		if(element.value !='') {
			var label = CJAX.create.div(field+'_label',parent_id,false);
			element.style.borderColor = default_border_color;
			element.className = default_element_class;
			label.style.display = 'none';
		}
	};
}