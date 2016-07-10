/**
 *  http://heliumhq.com/pdfs/AutomatingGmailWithHelium.pdf
 */
var tgt=arguments[0],
	input=document.createElement('input');
	input.setAttribute("id", "upload");
	input.type='file';
		
	input.style.display = 'block';
	input.style.opacity = '1';
	input.style.visibility = 'visible';
	input.style.height = '1px';
	input.style.width = '1px';
	
	input.addEventListener('change',function(event){
  	var dataTransfer={
  			dropEffect : '', 
  			effectAllowed : 'all',
  			files : input.files, 
  			items : {},
  			types : [],
  			setData : function(format,data){
  				
  			}, getData:function(format){}
  	};
	var emit = function(event,target){
		var evt = document.createEvent('Event');
		evt.initEvent(event,true,false);
		evt.dataTransfer=dataTransfer;
		target.dispatchEvent(evt);
	};
	emit('dragenter',tgt);
	emit('dragover',tgt);
	emit('drop',tgt);
	document.body.removeChild(input);
  }, false);
  
//	if (document.body.childElementCount > 0) {
//		document.body.insertBefore(input, document.body.childNodes[0]);
//	} else {
		document.body.appendChild(input);
//	}
  
  return input;
  
  /*

 def dispatch_file_drag_event(event_name, to, file_input_element):
 driver.execute_script(
 "var event = document.createEvent('CustomEvent');"
 "event.initCustomEvent(arguments[0], true, true 0);"
 "event.dataTransfer = {"
 " files: arguments[1].files"
 "};"
 ... (other code for initializing event)
 "arguments[2].dispatchEvent(event);",
 event_name, file_input_element, to
 )
  
  
Finally, we clean up after ourselves by removing the file input element we created in the first step:
driver.execute_script(
 "arguments[0].parentNode.removeChild(arguments[0]);", file_input
)

  
*/
