/**
 * 
 */

function deleteAllCookies() {
	var c = document.cookie.split("; ");
	for (i in c)
		document.cookie = /^[^=]+/.exec(c[i])[0]
				+ "=;expires=Thu, 01 Jan 1970 00:00:00 GMT";
}

deleteAllCookies();