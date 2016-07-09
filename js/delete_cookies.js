/**
 * 
 */
function deleteAllCookies() {
	var c = document.cookie.split("; ");
	for (i in c)
		document.cookie = /^[^=]+/.exec(c[i])[0]
				+ "=;expires=Thu, 01 Jan 1970 00:00:00 GMT";
}

function deleteAllCookies2() {
    var cookies = document.cookie.split(";");

    for (var i = 0; i < cookies.length; i++) {
    	var cookie = cookies[i];
    	var eqPos = cookie.indexOf("=");
    	var name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
    	document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT";
    }
}

function deleteCookie(cookieName){
  var cookieDate = new Date ( );  // current date & time
  cookieDate.setTime (cookieDate.getTime() - 1);
  document.cookie = cookieName += "=; expires=" + cookieDate.toGMTString();
} 

deleteAllCookies();