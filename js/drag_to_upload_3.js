/**
 *  @see: http://stackoverflow.com/questions/34761241/selenium-webdriver-upload-file-by-drag-and-drop
 *  @see: http://heliumhq.com/pdfs/AutomatingGmailWithHelium.pdf
 *  
 */
var tgt=arguments[0],
	input=document.createElement('input');
	input.setAttribute("id", "upload");
	input.setAttribute("value", "");
	input.type = 'file';
		
	input.style.display = 'block';
	input.style.opacity = '1';
	input.style.visibility = 'visible';
	input.style.height = '1px';
	input.style.width = '1px';
	
	input.addEventListener('change', function(event){
  	
		var dataTransfer = {files : input.files};
		
		var emit = function(event,target){
			var evt = document.createEvent('Event');
			evt.initEvent(event, true, false);
			evt.dataTransfer = dataTransfer;
			target.dispatchEvent(evt);
		};
		emit('dragenter', tgt);
		emit('dragover', tgt);
		emit('drop', tgt);
		
		document.body.removeChild(input);
	}, false);
  
	if (document.body.childElementCount > 0) {
		document.body.insertBefore(input, document.body.childNodes[0]);
	} else {
		document.body.appendChild(input);
	}
  
  return input;
  
