//Espressioni regolari
var regex_username = /^([a-z]|[A-Z]|[0-9]|\_|\-|\.)*$/;
var regex_password = /(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])/;
var regex_email = /^\S+@\S+\.\S+$/;
/* alternativa valida ma più restrittiva del PHP: var regex_email = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*\.\w+$/; */
var regex_charCount = /[àèéìòù]/gi;

//Messaggi di errore
var ERR_USERNAME = "Errore: Nome utente non valido.";
var ERR_PASSWORD = "Errore: <span lang=\"en\">Password</span> non valida.";
var ERR_EMAIL = "Errore: <span lang=\"en\">Email</span> non valida. Lunghezza massima 128 caratteri, formato di esempio: <code>esempio@dominio.it</code>";
var ERR_CHECK_PASSWORD = "Errore: I campi <span lang=\"en\">password</span> e conferma <span lang=\"en\">password</span> non coincidono.";
var ERR_COMMENT = "Errore: Commento non valido. Il campo non può essere vuoto e non può superare i 1000 caratteri.";
var ERR_DISCLAIMER_ACCEPT = "Errore: Conferma di aver letto e compreso il <span lang=\"en\">disclaimer</span>.";
var ERR_HELPER = " (per ulteriori dettagli si vedano gli aiuti alla compilazione)";

//Placeholder per retrocompatibilità
var TEXT_GENERAL_PASSWORD = "password";
var TEXT_USERNAME_LOGIN = "inserisci il tuo nome utente";
var TEXT_USERNAME_REGISTER = "scegli un nome utente";
var TEXT_EMAIL_REGISTER = "inserisci la tua email";
var TEXT_EMAIL_EDIT_EMAIL = "inserisci la tua nuova email";
var TEXT_COMMENTO_COMMENT = "scrivi un commento alla recensione (massimo 1000 caratteri)";


//Funzione che controlla il campo "Nome utente"
function checkUsername(addHelper) {
	var x = document.getElementById("username");
	if ((x.value.length >= 4) && (regex_username.test(x.value))) {
		delErr(x);
		return true;
	}
	else {
		if (!addHelper)
			showErr(x, ERR_USERNAME);
		else
			showErr(x, ERR_USERNAME + ERR_HELPER);
		
		return false;
	}
}

//Funzione che controlla i campi "Password"
//ATTENZIONE: Se questa funzione viene chiamata da una form HTML deve essere passato come parametro l'ID corretto del campo password di riferimento
function checkPass(input, addHelper) {
	var x = document.getElementById(input);
	if ((x.value == "admin") || (x.value == "user") || ((x.value.length >= 8) && (regex_password.test(x.value)))) {
		delErr(x);
		return true;
	}
	else {
		if (!addHelper)
			showErr(x, ERR_PASSWORD);
		else
			showErr(x, ERR_PASSWORD + ERR_HELPER);
		
		return false;
	}
}

//Funzione che controlla il campo "Email"
function checkEmail() {
	if (!(Modernizr.input.required) || !(Modernizr.inputtypes.email)) {
		var x = document.getElementById("email");
		
		if (regex_email.test(x.value)) {
			delErr(x);
			return true;
		}
		else {
			showErr(x, ERR_EMAIL);
			return false;
		}
	}
	else {
		return true;
	}
}

//Funzione che controlla i campi "Conferma password"
//ATTENZIONE: Se questa funzione viene chiamata da una form HTML deve essere passato come parametro l'ID corretto del campo password a cui il campo "Conferma password" si riferisce
function checkPass2(inputPsw) {
	var x = document.getElementById("checkPassword");
	var y = document.getElementById(inputPsw);
	
	if (x.value == y.value) {
		if (!(Modernizr.input.required)) {
			if (x.value.length > 0) {
				delErr(x);
				return true;
			}
			else
			{
				showErr(x, ERR_CHECK_PASSWORD);
				return false;
			}
		}
		else {
			delErr(x);
			return true;
		}
	}
	else {
		showErr(x, ERR_CHECK_PASSWORD);
		return false;
	}
}


//Funzione che aggiuge tutti i placeholder alla form: Login
function addAllPlacehoderFormLogin() {
	addPlaceholder("username", TEXT_USERNAME_LOGIN);
	addPlaceholder("password", TEXT_GENERAL_PASSWORD);
}

//Funzione che rimuove tutti i placeholder alla form: Login
function removeAllPlacehoderFormLogin() {
	removePlaceholder("username", TEXT_USERNAME_LOGIN);
	removePlaceholder("password", TEXT_GENERAL_PASSWORD);
}

//Funzione che si occupa del controllo sulla form di "login"
function checkFormLogin() {
	if (!(Modernizr.placeholder)) {
		removeAllPlacehoderFormLogin();
	}
	
	var validUsername = checkUsername(false);
	var validPass = checkPass("password", false);
	
	if (!(Modernizr.placeholder)) {
		addAllPlacehoderFormLogin();
	}
	
	return (validUsername && validPass);
}

//Funzione che aggiuge tutti i placeholder alla form: Register
function addAllPlacehoderFormRegister() {
	addPlaceholder("username", TEXT_USERNAME_REGISTER);
	addPlaceholder("email", TEXT_EMAIL_REGISTER);
	addPlaceholder("password", TEXT_GENERAL_PASSWORD);
	addPlaceholder("checkPassword", TEXT_GENERAL_PASSWORD);
}

//Funzione che rimuove tutti i placeholder alla form: Register
function removeAllPlacehoderFormRegister() {
	removePlaceholder("username", TEXT_USERNAME_REGISTER);
	removePlaceholder("email", TEXT_EMAIL_REGISTER);
	removePlaceholder("password", TEXT_GENERAL_PASSWORD);
	removePlaceholder("checkPassword", TEXT_GENERAL_PASSWORD);
}

//Funzione che si occupa del controllo sulla form di "registrazione"
function checkFormRegister() {
	if (!(Modernizr.placeholder)) {
		removeAllPlacehoderFormRegister();
	}
	
	var validUsername = checkUsername(true);
	var validEmail = checkEmail();
	var validPass = checkPass("password", true);
	var validPass2 = checkPass2("password");
	
	if (!(Modernizr.placeholder)) {
		addAllPlacehoderFormRegister();
	}
	
	return (validUsername && validEmail && validPass && validPass2);
}

//Funzione che aggiuge tutti i placeholder alla form: EditEmail
function addAllPlacehoderFormEmail() {
	addPlaceholder("email", TEXT_EMAIL_EDIT_EMAIL);
	addPlaceholder("checkPassword", TEXT_GENERAL_PASSWORD);
}

//Funzione che rimuove tutti i placeholder alla form: EditEmail
function removeAllPlacehoderFormEmail() {
	removePlaceholder("email", TEXT_EMAIL_EDIT_EMAIL);
	removePlaceholder("checkPassword", TEXT_GENERAL_PASSWORD);
}

//Funzione che si occupa del controllo sulla form di "modifica email"
function checkFormEmail() {
	if (!(Modernizr.placeholder)) {
		removeAllPlacehoderFormEmail();
	}
	
	var validEmail = checkEmail();
	var validPass = checkPass("checkPassword", false);
	
	if (!(Modernizr.placeholder)) {
		addAllPlacehoderFormEmail();
	}
	
	return (validEmail && validPass);
}

//Funzione che aggiuge tutti i placeholder alla form: EditPassword
function addAllPlacehoderFormPassword() {
	addPlaceholder("oldPassword", TEXT_GENERAL_PASSWORD);
	addPlaceholder("newPassword", TEXT_GENERAL_PASSWORD);
	addPlaceholder("checkPassword", TEXT_GENERAL_PASSWORD);
}

//Funzione che rimuove tutti i placeholder alla form: EditPassword
function removeAllPlacehoderFormPassword() {
	removePlaceholder("oldPassword", TEXT_GENERAL_PASSWORD);
	removePlaceholder("newPassword", TEXT_GENERAL_PASSWORD);
	removePlaceholder("checkPassword", TEXT_GENERAL_PASSWORD);
}

//Funzione che si occupa del controllo sulla form di "modifica password"
function checkFormPassword() {
	if (!(Modernizr.placeholder)) {
		removeAllPlacehoderFormPassword();
	}
	
	var validOldPass = checkPass("oldPassword", true);
	var validNewPass = checkPass("newPassword", true);
	var validNewPass2 = checkPass2("newPassword");
	
	if (!(Modernizr.placeholder)) {
		addAllPlacehoderFormPassword();
	}
	
	return (validOldPass && validNewPass && validNewPass2);
}

//Funzione che si occupa del controllo sulla form di "unsubscribe"
function checkFormUnsubscribe() {
	if (!(Modernizr.input.required)) {
		var x = document.getElementById("ack");
		
		if (x.checked) {
			delErr(x);
			return true;
		}
		else {
			showErr(x, ERR_DISCLAIMER_ACCEPT);
			return false;
		}
	}
	else {
		return true;
	}
}


//Funzione che si occupa di mostrare all'utente le specifiche dell'errore commesso durante la compilazione di un campo
function showErr(input, err) {
	if (document.getElementById("err" + input.id) == null) {
		var p = input.parentNode;
		var e = document.createElement("p");
		e.id = "err" + input.id;
		e.setAttribute("class", "errori");
		e.innerHTML = err;
		p.appendChild(e);
	}
}

//Funzione che si occupa di eliminare l'eventuale errore commesso durante la compilazione di un campo, perchè non più presente
function delErr(input) {
	var x = document.getElementById("err" + input.id);
	
	if (x != null) {
		x.parentNode.removeChild(x);
	}
}

//Funzione che si occupa di eliminare gli eventuali errori emessi lato PHP, per lasciare spazio ad eventuali nuovi errori lato JS
function delErrPHP() {
	if (typeof(document.querySelector(".erroriForm")) != "undefined") {
		document.querySelector(".erroriForm").innerHTML = "";
	}
}


//Funzione che aggiunge ad un determinato blocco identificato da un ID un button-link per mostrare/nascondere il blocco stesso
function addLinkShowHide(input) {
	if (document.getElementById(input) != null) {
		var p = document.getElementById(input).parentNode;
		var newButton = document.createElement("button");
		
		newButton.id = "link" + input;
		newButton.setAttribute("type", "button");
		newButton.setAttribute("name", "link" + input);
		newButton.setAttribute("value", "fieldHelper");
		newButton.setAttribute("class", "fieldHelper");
		newButton.setAttribute("onclick", "changeVisibility('" + input + "'); return false;");
		newButton.innerHTML = "mostra aiuti compilazione";
		
		p.insertBefore(newButton, p.children[0]);
		document.getElementById(input).className = "hideHelp";
		
		var x = p.children[1];
		if ((x != null) && (x.className == "skipHelper")) {
			x.className += " hideHelp";
		}
	}
}

//Funzione che cambia le classi per la visibilità di un determinato blocco identificato da un ID
function changeVisibility(input) {
	var x = document.getElementById(input);
	
	if (x.className == "hideHelp") {
		if (x.parentNode.children[1].className == "skipHelper hideHelp") {
			x.parentNode.children[1].className = "skipHelper showHelp";
		}
		x.className = "showHelp";
		document.getElementById("link" + input).innerHTML = "nascondi aiuti compilazione";
	}
	else {
		if (x.parentNode.children[1].className == "skipHelper showHelp") {
			x.parentNode.children[1].className = "skipHelper hideHelp";
		}
		x.className = "hideHelp";
		document.getElementById("link" + input).innerHTML = "mostra aiuti compilazione";
	}
}

//Cambia l'attributo "class" del menu per rendere l'hamburger nel menu mobile
function changeVisibilityMenu() {
	var x = document.getElementById("mainMenu");
	var y = document.getElementById("menuIcon");
	
	if (x.className == "hideMenu") {
		x.setAttribute("class", "showMenu");
		y.setAttribute("title", "nascondi menu");
	}
	else {
		x.setAttribute("class", "hideMenu");
		y.setAttribute("title", "mostra menu");
	}
}

//Funzione che al caricamento di una qualsiasi pagina aggiunge il button e gli attributi/eventi per il menu ad hamburger
function addEventsHamburger() {
	var x = document.getElementById("mainMenu");
	var y = document.getElementById("login");
	
	if ((x != null) && (y != null)) {
		
		var newButton = document.createElement("button");
		newButton.id = "menuIcon";
		newButton.setAttribute("type", "button");
		newButton.setAttribute("name", "Icona Menu");
		newButton.setAttribute("value", "menuIcon");
		newButton.setAttribute("title", "mostra menu");
		newButton.setAttribute("class", "showMenu");
		newButton.setAttribute("onclick", "changeVisibilityMenu()");
		newButton.innerHTML = "Menu";

		var newLi = document.createElement("li");
		newLi.appendChild(newButton);
		y.children[0].insertBefore(newLi, y.children[0].children[0]);
		
		x.setAttribute("class", "hideMenu");
	}
}


//Funzione che aggiunge il placeholder indicato al campo richiesto
function addPlaceholder(input, placeMessage) {
	var x = document.getElementById(input);
	
	if (x.value.length == 0) {
		x.value = placeMessage;
		x.setAttribute("class", "placeholderMessage");
	}
}

//Funzione che elimina il placeholder indicato al campo richiesto
function removePlaceholder(input, placeMessage) {
	var x = document.getElementById(input);
	
	if (x.value == placeMessage) {
		x.value = "";
		x.className = "";
	}
}


//Funzione che conta il numero totale di caratteri già scritti durante la compilazione del campo indicato come parametro
//NOTA: Il carattere apostrofo è contato come "&apos;" (6 caratteri), e le lettere accentate "à , è , é , ì , ò , ù" (minuscole e/o maiuscole) sono contate come 2 caratteri ognuna
function countRealChar(input) {
	var x = document.getElementById(input).value;
	var totalChar = x.length; //qualsiasi carattere, anche lettere accentate ed apostrofo, vengono contate (già) una volta
	
	var numAccentChar = x.match(regex_charCount);
	if (numAccentChar != null) {
		totalChar = totalChar + (numAccentChar.length); //aumento il totale considerando il doppio peso delle lettere accentate
	}
	
	var numApos = x.match(/'/g);
	if (numApos != null) {
		totalChar = totalChar + ((numApos.length) * 5); //aumento il totale considerando l'apostrofo come "carattere di peso uguale a 6 caratteri"
	}
	
	return totalChar;
}

//Funzione che mostra all'utente i caratteri già scritti durante la compilazione del campo indicato come parametro
function charCount(input) {
	document.getElementById("char_" + input).innerHTML = countRealChar(input);
}


//Funzione che al caricamento della pagina di "login" inietta gli eventi e il codice js ad essa relativi
function addEventsLogin() {
	addEventsHamburger();
	
	if (document.getElementById("formLogin") != null) {
		document.getElementById("formLogin").setAttribute("onsubmit", "delErrPHP(); return checkFormLogin();");
		//document.getElementById("username").setAttribute("onblur", "checkUsername(false)");
		//document.getElementById("password").setAttribute("onblur", "checkPass('password', false)");
		
		if (!(Modernizr.input.autofocus)) {
			document.getElementById("username").focus();
		}
		
		if (!(Modernizr.placeholder)) {
			addAllPlacehoderFormLogin();
			
			document.getElementById("username").setAttribute("onfocus", "removePlaceholder('username', TEXT_USERNAME_LOGIN)");
			document.getElementById("username").setAttribute("onblur", "addPlaceholder('username', TEXT_USERNAME_LOGIN)");
			//document.getElementById("username").setAttribute("onblur", "checkUsername(false); addPlaceholder('username', TEXT_USERNAME_LOGIN);");
			document.getElementById("password").setAttribute("onfocus", "removePlaceholder('password', TEXT_GENERAL_PASSWORD);");
			document.getElementById("password").setAttribute("onblur", "addPlaceholder('password', TEXT_GENERAL_PASSWORD);");
			//document.getElementById("password").setAttribute("onblur", "checkPass('password', false); addPlaceholder('password', TEXT_GENERAL_PASSWORD);");
		}
	}
}

//Funzione che viene invocata all'evento "onreset" della form di registrazione per reimpostare tutto allo stato iniziale
function resetAllFormRegister() {
	//Elimino eventuali errori JS
	delErr(document.getElementById("username"));
	delErr(document.getElementById("email"));
	delErr(document.getElementById("password"));
	delErr(document.getElementById("checkPassword"));
	
	//Elimino eventuali errori PHP
	delErrPHP();
	
	//Nascondo gli eventuali aiuti alla compilazione attualmente visibili e ripristino il corretto contenuto dei <button>
	document.getElementById("linkaiutiUsername").innerHTML = "mostra aiuti compilazione";
	document.getElementById("aiutiUsername").setAttribute("class", "hideHelp");
	document.getElementById("linkaiutiPassword").innerHTML = "mostra aiuti compilazione";
	document.getElementById("aiutiPassword").setAttribute("class", "hideHelp");
}

//Funzione che al caricamento della pagina di "registrazione" inietta gli eventi e il codice js ad essa relativi
function addEventsRegister() {
	addEventsHamburger();
	
	if (document.getElementById("formRegister") != null) {
		addLinkShowHide("aiutiUsername");
		addLinkShowHide("aiutiPassword");
		
		document.getElementById("formRegister").setAttribute("onsubmit", "delErrPHP(); return checkFormRegister();");
		document.getElementById("formRegister").setAttribute("onreset", "resetAllFormRegister()");
		document.getElementById("username").setAttribute("onblur", "checkUsername(true)");
		document.getElementById("email").setAttribute("onblur", "checkEmail()");
		document.getElementById("password").setAttribute("onblur", "checkPass('password', true)");
		document.getElementById("checkPassword").setAttribute("onblur", "checkPass2('password')");
		
		if (!(Modernizr.placeholder)) {
			addAllPlacehoderFormRegister();
			
			document.getElementById("username").setAttribute("onfocus", "removePlaceholder('username', TEXT_USERNAME_REGISTER)");
			document.getElementById("username").setAttribute("onblur", "checkUsername(true); addPlaceholder('username', TEXT_USERNAME_REGISTER);");
			document.getElementById("email").setAttribute("onfocus", "removePlaceholder('email', TEXT_EMAIL_REGISTER)");
			document.getElementById("email").setAttribute("onblur", "checkEmail(); addPlaceholder('email', TEXT_EMAIL_REGISTER);");
			document.getElementById("password").setAttribute("onfocus", "removePlaceholder('password', TEXT_GENERAL_PASSWORD);");
			document.getElementById("password").setAttribute("onblur", "checkPass('password', true); addPlaceholder('password', TEXT_GENERAL_PASSWORD);");
			document.getElementById("checkPassword").setAttribute("onfocus", "removePlaceholder('checkPassword', TEXT_GENERAL_PASSWORD);");
			document.getElementById("checkPassword").setAttribute("onblur", "checkPass2('password'); addPlaceholder('checkPassword', TEXT_GENERAL_PASSWORD);");
		}
	}
}

//Funzione che al caricamento della pagina di "modifica dati profilo" inietta gli eventi e il codice js ad essa relativi
function addEventsEditProfile() {
	addEventsHamburger();
	
	//Caso 1: Form di modifica email
	if (document.getElementById("formEmail") != null) {
		
		document.getElementById("formEmail").setAttribute("onsubmit", "delErrPHP(); return checkFormEmail();");
		document.getElementById("email").setAttribute("onblur", "checkEmail()");
		document.getElementById("checkPassword").setAttribute("onblur", "checkPass('checkPassword', false)");
		
		if (!(Modernizr.placeholder)) {
			addAllPlacehoderFormEmail();
			
			document.getElementById("email").setAttribute("onfocus", "removePlaceholder('email', TEXT_EMAIL_EDIT_EMAIL)");
			document.getElementById("email").setAttribute("onblur", "checkEmail(); addPlaceholder('email', TEXT_EMAIL_EDIT_EMAIL);");
			document.getElementById("checkPassword").setAttribute("onfocus", "removePlaceholder('checkPassword', TEXT_GENERAL_PASSWORD);");
			document.getElementById("checkPassword").setAttribute("onblur", "checkPass('checkPassword', false); addPlaceholder('checkPassword', TEXT_GENERAL_PASSWORD);");
		}
	}
	else {
		//Caso 2: Form di modifica password
		if (document.getElementById("formPassword") != null) {
			addLinkShowHide("aiutiPassword");
			
			document.getElementById("formPassword").setAttribute("onsubmit", "delErrPHP(); return checkFormPassword();");
			document.getElementById("oldPassword").setAttribute("onblur", "checkPass('oldPassword', true)");
			document.getElementById("newPassword").setAttribute("onblur", "checkPass('newPassword', true)");
			document.getElementById("checkPassword").setAttribute("onblur", "checkPass2('newPassword')");
			
			if (!(Modernizr.placeholder)) {
				addAllPlacehoderFormPassword();
				
				document.getElementById("oldPassword").setAttribute("onfocus", "removePlaceholder('oldPassword', TEXT_GENERAL_PASSWORD);");
				document.getElementById("oldPassword").setAttribute("onblur", "checkPass('oldPassword', true); addPlaceholder('oldPassword', TEXT_GENERAL_PASSWORD);");
				document.getElementById("newPassword").setAttribute("onfocus", "removePlaceholder('newPassword', TEXT_GENERAL_PASSWORD);");
				document.getElementById("newPassword").setAttribute("onblur", "checkPass('newPassword', true); addPlaceholder('newPassword', TEXT_GENERAL_PASSWORD);");
				document.getElementById("checkPassword").setAttribute("onfocus", "removePlaceholder('checkPassword', TEXT_GENERAL_PASSWORD);");
				document.getElementById("checkPassword").setAttribute("onblur", "checkPass2('newPassword'); addPlaceholder('checkPassword', TEXT_GENERAL_PASSWORD);");
			}
		}
		else {
			//Caso 3: Form di unsubscribe
			if (document.getElementById("formUnsubscribe") != null) {
				
				document.getElementById("formUnsubscribe").setAttribute("onsubmit", "delErrPHP(); return checkFormUnsubscribe();");
				//document.getElementById("ack").setAttribute("onchange", "checkFormUnsubscribe()");
			}
		}
	}
}


//Funzione che si occupa del controllo sulla form di "inserisci commento"
function checkFormComment() {
	if (!(Modernizr.placeholder)) {
		removePlaceholder("commento", TEXT_COMMENTO_COMMENT);
	}
	
	var validComment = false;
	var x = document.getElementById("commento");
	
	if ((x.value.length > 0) && (countRealChar("commento") <= 1000)) {
		delErr(x);
		validComment = true;
	}
	else {
		showErr(x, ERR_COMMENT);
		validComment = false;
	}
	
	if (!(Modernizr.placeholder)) {
		addPlaceholder("commento", TEXT_COMMENTO_COMMENT);
	}
	
	return validComment;
}

//Funzione che al caricamento della pagina di "leggi recensione" inietta gli eventi e il codice js ad essa relativi
function addEventsComment() {
	addEventsHamburger();
	
	//Solo se è presente la form di "inserisci commento"
	if (document.getElementById("formComment") != null) {
		addLinkShowHide("aiutiCommento");
		
		document.getElementById("formComment").setAttribute("onsubmit", "delErrPHP(); return checkFormComment();");
		
		if (!(Modernizr.placeholder)) {
			addPlaceholder("commento", TEXT_COMMENTO_COMMENT);
			
			document.getElementById("commento").setAttribute("onfocus", "removePlaceholder('commento', TEXT_COMMENTO_COMMENT)");
			document.getElementById("commento").setAttribute("onblur", "addPlaceholder('commento', TEXT_COMMENTO_COMMENT)");
		}
		
		if (Modernizr.oninput) {
			var x = document.getElementById("commento");
			x.setAttribute("oninput", "charCount('commento')");
			x.parentNode.previousElementSibling.innerHTML = "Commento ( <span id=\"char_commento\">0</span>/1000 caratteri )";
			
			charCount("commento");
		}
	}
}