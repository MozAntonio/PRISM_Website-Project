//Espressioni regolari
var regex_site = /^\S+:\/\/\S+$/;

//Messaggi di errore
var ERR_GAME_TITLE = "Errore: Titolo del gioco non valido. Il campo non può essere vuoto e non può superare i 255 caratteri. (ulteriori dettagli: <a href=\"adminmanual.html#gameTitle\" target=\"_blank\" target=\"_blank\" title=\"vai a titolo gioco nel manuale in una nuova finestra\">manuale amministrativo</a>)";
var ERR_COVER_IMG = "Errore: Immagine di copertina non valida. (per ulteriori dettagli si vedano gli aiuti alla compilazione, oppure consultare il <a href=\"adminmanual.html#coverImage\" target=\"_blank\" title=\"vai a immagine copertina nel manuale in una nuova finestra\">manuale amministrativo</a>)";
var ERR_COVER_DESCR = "Errore: Descrizione della copertina non valida. Il campo non può essere vuoto e non può superare i 255 caratteri. (ulteriori dettagli: <a href=\"adminmanual.html#coverDescription\" target=\"_blank\" title=\"vai a descrizione copertina nel manuale in una nuova finestra\">manuale amministrativo</a>)";
var ERR_DEVELOPER = "Errore: Sviluppatore non valido. Il campo non può essere vuoto e non può superare i 255 caratteri. (ulteriori dettagli: <a href=\"adminmanual.html#developer\" target=\"_blank\" title=\"vai a sviluppatore nel manuale in una nuova finestra\">manuale amministrativo</a>)";
var ERR_YEAR = "Errore: Anno di uscita non valido. Il valore deve essere compreso tra il <time datetime=\"1970\">1970</time> e il <time datetime=\"2099\">2099</time> (estremi inclusi).";
var ERR_LIST_PLATFORMS = "Errore: Selezionare almeno una piattaforma.";
var ERR_LIST_GENRES = "Errore: Selezionare almeno un genere.";
var ERR_SITE = "Errore: Sito ufficiale non valido. Lunghezza massima 128 caratteri, formato di esempio: <code>protocollo://indirizzo</code> (ulteriori dettagli: <a href=\"adminmanual.html#officialSite\" target=\"_blank\" title=\"vai a sito ufficiale nel manuale in una nuova finestra\">manuale amministrativo</a>)";
var ERR_PEGI = "Errore: <acronym title=\"Pan European Game Information\">PEGI</acronym> non valido. Il campo non può avere un valore indefinito.";
var ERR_REVIEW_TITLE = "Errore: Titolo della recensione non valido. Il campo non può essere vuoto e non può superare i 255 caratteri. (ulteriori dettagli: <a href=\"adminmanual.html#reviewTitle\" target=\"_blank\" title=\"vai a titolo recensione nel manuale in una nuova finestra\">manuale amministrativo</a>)";
var ERR_REVIEW_DESCR = "Errore: Breve descrizione della recensione non valida. Il campo non può essere vuoto e non può superare i 150 caratteri. (ulteriori dettagli: <a href=\"adminmanual.html#shortDescription\" target=\"_blank\" title=\"vai a breve descrizione nel manuale in una nuova finestra\">manuale amministrativo</a>)";
var ERR_REVIEW_CONTENT = "Errore: Contenuto della recensione non valido. Il campo non può essere vuoto e non può superare i 65535 caratteri. (ulteriori dettagli: <a href=\"adminmanual.html#reviewContent\" target=\"_blank\" target=\"_blank\" title=\"vai a recensione nel manuale in una nuova finestra\">manuale amministrativo</a>)";

//Placeholder per retrocompatibilità
var TEXT_TITOLO_GIOCO_REVIEW = "inserisci il titolo del videogioco";
var TEXT_COVER_DESCR_REVIEW = "scrivi una breve descrizione (max 255 caratteri) della copertina caricata";
var TEXT_SVILUPPATORE_REVIEW = "inserisci lo sviluppatore del videogioco";
var TEXT_ANNO_USCITA_REVIEW = "2017";
var TEXT_SITO_REVIEW = "http://www.example.com";
var TEXT_TITOLO_RECENSIONE_REVIEW = "inserisci il titolo della recensione";
var TEXT_DESCR_RECENSIONE_REVIEW = "scrivi una breve descrizione (max 150 caratteri) della recensione, una specie di abstract";
var TEXT_RECENSIONE_REVIEW = "scrivi qui la tua recensione (max 65535 caratteri), ti ricordiamo che è permesso usare alcuni tag per dare una struttura al testo";


//Funzione che si occupa del controllo della lunghezza (minima e massima) di un dato campo
function checkLengthField(input, errMessage, maxLength) {
	var x = document.getElementById(input);
	
	if (maxLength == -1) { //Caso: <input type="text">
		if (countRealChar(input) <= 255) {
			if (!(Modernizr.input.required)) {
				if (x.value.length > 0) {
					delErr(x);
					return true;
				}
				else {
					showErr(x, errMessage);
					return false;
				}
			}
			else {
				delErr(x);
				return true;
			}
		}
		else {
			showErr(x, errMessage);
			return false;
		}
	}
	else { //Caso: <textarea>
		if ((x.value.length > 0) && (countRealChar(input) <= maxLength)) {
			delErr(x);
			return true;
		}
		else {
			showErr(x, errMessage);
			return false;
		}
	}
}

//Funzione che verifica se un dato file (immagine) ha un estensione supportata
function isImage(fileName) {
	var splittedFileName = fileName.split(".");
	var ext = splittedFileName[splittedFileName.length - 1].toLowerCase();
	
	if ((ext == "jpg") || (ext == "jpeg") || (ext == "png")) {
		return true;
	}
	else {
		return false;
	}
}

//Funzione che controlla il campo "CoverImg"
//N.B.: Esistono vari metodi "nodoInputFile.files[0].xxx" (dove "xxx" è una determinata proprietà del metodo), che forniscono informazioni aggiuntive al file uploadato
//Tali metodi (per controllare ad esempio: dimensione file, tipo MIME del file, dimensioni (altezza e larghezza) del file, ecc. ecc.), non sono supportati da IE < 10
function checkCoverImg(isNewReview) {
	var x = document.getElementById("coverImg");
	
	if (x.value != "") {
		if (isImage(x.value)) {
			delErr(x);
			return true;
		}
		else {
			showErr(x, ERR_COVER_IMG);
			return false;
		}
	}
	else {
		if (!(isNewReview)) {
			delErr(x);
			return true;
		}
		else {
			showErr(x, ERR_COVER_IMG);
			return false;
		}
	}
}

//Funzione che controlla il campo "Anno uscita"
function checkYear() {
	if (!(Modernizr.inputtypes.number) || !(Modernizr.input.max) || !(Modernizr.input.min) || !(Modernizr.input.step)) {
		var x = document.getElementById("annoUscita");
		
		if ((x.value >= 1970) && (x.value <= 2099) && !(isNaN(x.value)) && (isFinite(x.value))) {
			delErr(x);
			return true;
		}
		else {
			showErr(x, ERR_YEAR);
			return false;
		}
	}
	else {
		return true;
	}
}

//Funzione che controlla i campi "Piattaforma" e "Genere"
function checkList(input, err) {
	var list = document.getElementById(input);
	var x = list.children;
	var max = x.length;
	var stop = false;
	
	if (document.getElementById("err" + input) != null) {
		max--;
	}
	
	for (i = 1; !stop && (i < max); i++) {
		if (x[i].children[0].checked) {
			stop = true;
		}
	}
	
	if (stop) {
		delErr(list);
		return true;
	}
	else {
		if (document.getElementById("err" + input) == null) {
			var e = document.createElement("p");
			e.id = "err" + input;
			e.setAttribute("class", "errori");
			e.innerHTML = err;
			list.appendChild(e);
		}
		return false;
	}
}

//Funzione che controlla il campo "Sito ufficiale"
function checkSite() {
	var x = document.getElementById("sito");
	
	if (countRealChar("sito") <= 128) {
		if (!(Modernizr.inputtypes.url)) {
			if ((x.value.length == 0) || (regex_site.test(x.value))) {
				delErr(x);
				return true;
			}
			else {
				showErr(x, ERR_SITE);
				return false;
			}
		}
		else {
			delErr(x);
			return true;
		}
	}
	else {
		showErr(x, ERR_SITE);
		return false;
	}
}

//Funzione che controlla il campo "PEGI"
function checkPEGI() {
	var x = document.getElementById("pegi");
	if (x.value != "") {
		delErr(x);
		return true;
	}
	else {
		showErr(x, ERR_PEGI);
		return false;
	}
}


//Funzione che all'onsubmit segnala l'eventuale presenza di errori nei campi di formReview
function showErrEnd(input) {
	var isNewReview = true;
	if (input == "editreview") {
		isNewReview = false;
	}
	
	var success = checkFormReview(isNewReview);
	
	if (!success) {
		if (document.getElementById("errorJS") == null) {
			var p = document.getElementById(input).parentNode;
			var e = document.createElement("p");
			e.id = "errorJS";
			e.setAttribute("class", "errori");
			e.innerHTML = "ERRORE: Si sono verificati degli errori durante la compilazione dei campi.";
			p.insertBefore(e, p.children[3]);
		}
	}
	else {
		var x = document.getElementById("errorJS");
		if (x != null) {
			x.parentNode.removeChild(x);
		}
	}
	
	return success;
}

//Funzione che aggiuge tutti i placeholder alla form: NewReview ed EditReview
function addAllPlacehoderFormReview() {
	addPlaceholder("titoloGioco", TEXT_TITOLO_GIOCO_REVIEW);
	addPlaceholder("coverDescr", TEXT_COVER_DESCR_REVIEW);
	addPlaceholder("sviluppatore", TEXT_SVILUPPATORE_REVIEW);
	addPlaceholder("annoUscita", TEXT_ANNO_USCITA_REVIEW);
	addPlaceholder("sito", TEXT_SITO_REVIEW);
	addPlaceholder("titoloRecensione", TEXT_TITOLO_RECENSIONE_REVIEW);
	addPlaceholder("descrRecensione", TEXT_DESCR_RECENSIONE_REVIEW);
	addPlaceholder("recensione", TEXT_RECENSIONE_REVIEW);
}

//Funzione che rimuove tutti i placeholder alla form: NewReview ed EditReview
function removeAllPlacehoderFormReview() {
	removePlaceholder("titoloGioco", TEXT_TITOLO_GIOCO_REVIEW);
	removePlaceholder("coverDescr", TEXT_COVER_DESCR_REVIEW);
	removePlaceholder("sviluppatore", TEXT_SVILUPPATORE_REVIEW);
	removePlaceholder("annoUscita", TEXT_ANNO_USCITA_REVIEW);
	removePlaceholder("sito", TEXT_SITO_REVIEW);
	removePlaceholder("titoloRecensione", TEXT_TITOLO_RECENSIONE_REVIEW);
	removePlaceholder("descrRecensione", TEXT_DESCR_RECENSIONE_REVIEW);
	removePlaceholder("recensione", TEXT_RECENSIONE_REVIEW);
}


//Funzione che si occupa del controllo sulla form di "crea/modifica recensione"
function checkFormReview(isNewReview) {
	if (!(Modernizr.placeholder)) {
		removeAllPlacehoderFormReview();
	}
	
	var validGameTitle = checkLengthField("titoloGioco", ERR_GAME_TITLE, -1);
	
	var validCoverImg = false;
	if (isNewReview) {
		validCoverImg = checkCoverImg(true);
	}
	else {
		validCoverImg = checkCoverImg(false);
	}
	
	var validCoverDescr = checkLengthField("coverDescr", ERR_COVER_DESCR, 255);
	var validDeveloper = checkLengthField("sviluppatore", ERR_DEVELOPER, -1);
	var validYear = checkYear();
	var validListPlatforms = checkList("listPlatforms", ERR_LIST_PLATFORMS);
	var validListGenres = checkList("listGenres", ERR_LIST_GENRES);
	var validSite = checkSite();
	var validPEGI = checkPEGI();
	var validReviewTitle = checkLengthField("titoloRecensione", ERR_REVIEW_TITLE, -1);
	var validReviewDescr = checkLengthField("descrRecensione", ERR_REVIEW_DESCR, 150);
	var validReviewContent = checkLengthField("recensione", ERR_REVIEW_CONTENT, 65535);
	
	if (!(Modernizr.placeholder)) {
		addAllPlacehoderFormReview();
	}
	
	return (validGameTitle && validCoverImg && validCoverDescr && validDeveloper && validYear && validListPlatforms && validListGenres && validSite && validPEGI && validReviewTitle && validReviewDescr && validReviewContent);
}


//Funzione che inizializza in modo corretto i contatori di caratteri dei relativi campi per la form di "crea/modifica recensione"
function initializeReview() {
	charCount("titoloGioco");
	charCount("coverDescr");
	charCount("sviluppatore");
	charCount("sito");
	charCount("titoloRecensione");
	charCount("descrRecensione");
	charCount("recensione");
}

//Funzione che al caricamento della pagina di "crea/modifica recensione" inietta gli eventi e il codice js ad essa relativi
function addEventsReview(isNewReview) {
	addEventsHamburger();
	
	if (document.getElementById("formReview") != null) {
		addLinkShowHide("aiutiCoverImg");
		
		document.getElementById("titoloGioco").setAttribute("onblur", "checkLengthField('titoloGioco', ERR_GAME_TITLE, -1)");
		document.getElementById("coverDescr").setAttribute("onblur", "checkLengthField('coverDescr', ERR_COVER_DESCR, 255)");
		document.getElementById("sviluppatore").setAttribute("onblur", "checkLengthField('sviluppatore', ERR_DEVELOPER, -1)");
		document.getElementById("annoUscita").setAttribute("onblur", "checkYear()");
		
		var list = document.getElementById("listPlatforms").children;
		for (i = 1; i < list.length; i++) {
			list[i].children[0].setAttribute("onchange", "checkList('listPlatforms', ERR_LIST_PLATFORMS)");
		}
		
		list = document.getElementById("listGenres").children;
		for (i = 1; i < list.length; i++) {
			list[i].children[0].setAttribute("onchange", "checkList('listGenres', ERR_LIST_GENRES)");
		}
		
		document.getElementById("sito").setAttribute("onblur", "checkSite()");
		document.getElementById("pegi").setAttribute("onchange", "checkPEGI()");
		document.getElementById("titoloRecensione").setAttribute("onblur", "checkLengthField('titoloRecensione', ERR_REVIEW_TITLE, -1)");
		document.getElementById("descrRecensione").setAttribute("onblur", "checkLengthField('descrRecensione', ERR_REVIEW_DESCR, 150)");
		document.getElementById("recensione").setAttribute("onblur", "checkLengthField('recensione', ERR_REVIEW_CONTENT, 65535)");
		
		if (isNewReview) {
			document.getElementById("formReview").setAttribute("onsubmit", "delErrPHP(); return showErrEnd('newreview');");
			document.getElementById("coverImg").setAttribute("onchange", "checkCoverImg(true)");
		}
		else {
			document.getElementById("formReview").setAttribute("onsubmit", "delErrPHP(); return showErrEnd('editreview');");
			document.getElementById("coverImg").setAttribute("onchange", "checkCoverImg(false)");
		}
		
		if (!(Modernizr.placeholder)) {
			addAllPlacehoderFormReview();
			
			document.getElementById("titoloGioco").setAttribute("onfocus", "removePlaceholder('titoloGioco', TEXT_TITOLO_GIOCO_REVIEW)");
			document.getElementById("titoloGioco").setAttribute("onblur", "checkLengthField('titoloGioco', ERR_GAME_TITLE, -1); addPlaceholder('titoloGioco', TEXT_TITOLO_GIOCO_REVIEW);");
			document.getElementById("coverDescr").setAttribute("onfocus", "removePlaceholder('coverDescr', TEXT_COVER_DESCR_REVIEW)");
			document.getElementById("coverDescr").setAttribute("onblur", "checkLengthField('coverDescr', ERR_COVER_DESCR, 255); addPlaceholder('coverDescr', TEXT_COVER_DESCR_REVIEW);");
			document.getElementById("sviluppatore").setAttribute("onfocus", "removePlaceholder('sviluppatore', TEXT_SVILUPPATORE_REVIEW)");
			document.getElementById("sviluppatore").setAttribute("onblur", "checkLengthField('sviluppatore', ERR_DEVELOPER, -1); addPlaceholder('sviluppatore', TEXT_SVILUPPATORE_REVIEW);");
			document.getElementById("annoUscita").setAttribute("onfocus", "removePlaceholder('annoUscita', TEXT_ANNO_USCITA_REVIEW)");
			document.getElementById("annoUscita").setAttribute("onblur", "checkYear(); addPlaceholder('annoUscita', TEXT_ANNO_USCITA_REVIEW);");
			document.getElementById("sito").setAttribute("onfocus", "removePlaceholder('sito', TEXT_SITO_REVIEW)");
			document.getElementById("sito").setAttribute("onblur", "checkSite(); addPlaceholder('sito', TEXT_SITO_REVIEW);");
			document.getElementById("titoloRecensione").setAttribute("onfocus", "removePlaceholder('titoloRecensione', TEXT_TITOLO_RECENSIONE_REVIEW)");
			document.getElementById("titoloRecensione").setAttribute("onblur", "checkLengthField('titoloRecensione', ERR_REVIEW_TITLE, -1); addPlaceholder('titoloRecensione', TEXT_TITOLO_RECENSIONE_REVIEW);");
			document.getElementById("descrRecensione").setAttribute("onfocus", "removePlaceholder('descrRecensione', TEXT_DESCR_RECENSIONE_REVIEW)");
			document.getElementById("descrRecensione").setAttribute("onblur", "checkLengthField('descrRecensione', ERR_REVIEW_DESCR, 150); addPlaceholder('descrRecensione', TEXT_DESCR_RECENSIONE_REVIEW);");
			document.getElementById("recensione").setAttribute("onfocus", "removePlaceholder('recensione', TEXT_RECENSIONE_REVIEW)");
			document.getElementById("recensione").setAttribute("onblur", "checkLengthField('recensione', ERR_REVIEW_CONTENT, 65535); addPlaceholder('recensione', TEXT_RECENSIONE_REVIEW);");
		}
		
		if (Modernizr.oninput) {
			var nodeText = "";
			
			var x = document.getElementById("titoloGioco");
			x.setAttribute("oninput", "charCount('titoloGioco')");
			nodeText = x.parentNode.previousElementSibling.innerHTML;
			x.parentNode.previousElementSibling.innerHTML = nodeText + " ( <span id=\"char_titoloGioco\">0</span>/255 caratteri )";
			
			x = document.getElementById("coverDescr");
			x.setAttribute("oninput", "charCount('coverDescr')");
			nodeText = x.parentNode.previousElementSibling.innerHTML;
			x.parentNode.previousElementSibling.innerHTML = nodeText + " ( <span id=\"char_coverDescr\">0</span>/255 caratteri )";
			
			x = document.getElementById("sviluppatore");
			x.setAttribute("oninput", "charCount('sviluppatore')");
			nodeText = x.parentNode.previousElementSibling.innerHTML;
			x.parentNode.previousElementSibling.innerHTML = nodeText + " ( <span id=\"char_sviluppatore\">0</span>/255 caratteri )";
			
			x = document.getElementById("sito");
			x.setAttribute("oninput", "charCount('sito')");
			nodeText = x.parentNode.previousElementSibling.innerHTML;
			x.parentNode.previousElementSibling.innerHTML = nodeText + " ( <span id=\"char_sito\">0</span>/128 caratteri )";
			
			x = document.getElementById("titoloRecensione");
			x.setAttribute("oninput", "charCount('titoloRecensione')");
			nodeText = x.parentNode.previousElementSibling.innerHTML;
			x.parentNode.previousElementSibling.innerHTML = nodeText + " ( <span id=\"char_titoloRecensione\">0</span>/255 caratteri )";
			
			x = document.getElementById("descrRecensione");
			x.setAttribute("oninput", "charCount('descrRecensione')");
			nodeText = x.parentNode.previousElementSibling.innerHTML;
			x.parentNode.previousElementSibling.innerHTML = nodeText + " ( <span id=\"char_descrRecensione\">0</span>/150 caratteri )";
			
			x = document.getElementById("recensione");
			x.setAttribute("oninput", "charCount('recensione')");
			nodeText = x.parentNode.previousElementSibling.innerHTML;
			x.parentNode.previousElementSibling.innerHTML = nodeText + " ( <span id=\"char_recensione\">0</span>/65535 caratteri )";
			
			initializeReview();
		}
	}
}