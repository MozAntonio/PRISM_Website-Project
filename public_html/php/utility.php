<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *																									 *
 *	CONTENUTO DEL FILE																				 *
 *	- (bool) isLogged(void) ............................................................. line: 17	 *
 *	- (void) insertFeedback(string &$page, string $text [, string $modo="replace"]) ..... line: 32	 *
 *	- require_once("classPageHeader.php"); .............................................. line: 60	 *
 *	- (void) function pleaseRedirect(string &$page, string $destURL [, int $delaySec = 30 [, bool $replacePrevious=true [, int $httpCode = 303]]]) ..... line: 69	 *
 *																									 *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */





/*	Controlla se l'utente è loggato.
 *
 *	Comportamento:
 *		controlla se i campi dati di $_SESSION sono settati, e ritorna true.
 *		altrimenti ritorna false.
 */
function isLogged():bool
{
	return isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["admin"]);
}





/*	Inserisce un messaggio in una pagina per side-effect.
 *
 *	Richiede:
 *		$page la pagina da modificare per inserire il messaggio (per riferimento)
 *		$text il messaggio da inserire
 *		$modo indica la modalità di inserimento del messaggio dentro una pagina
 *			valori permessi:
 *				- append: inserisce sotto la form
 *				- replace: (default) sostituisce l'intero content della pagina (facendo sparire il precedente contenuto)
 *
 *	Comportamento:
 *		modo:
 *			- replace: sostituisce tutto ciò che si trova tra l'apertura del 'div id=content' e l'apertura del footer con il messaggio (via regex)
 *			- append: aggiunge sotto il tag di chiusura di ogni form presente il messaggio
 */
function insertFeedback(string &$page, string $text, string $modo="replace")
{
	if (strtolower($modo) == "append") //Preparo la pagina aggiungendo il messaggio sotto la form
		$page=str_replace("<div class=\"erroriForm\">", "<div class=\"erroriForm\">". $text, $page);
	
	else //Preparo la pagina sostituendo il content con il messaggio //($modo=="replace")
		$page=preg_replace("/<div id=\"content\"[\s|\S]*<footer>/", "<div id=\"content\" class=\"feedback\">\n". $text ."\n</div> <!-- chiude content -->\n<footer>", $page);
}





if (file_exists("classPageHeader.php"))
	require_once("classPageHeader.php"); //classi per gli header di pagina
else
	throw new Exception("classPageHeader.php", 1); //code=1 eccezione fatale





/*	Invia una richiesta http di reindirizzamento al client (ATTENZIONE: usare con cautela).
 *
 *	Richiede:
 *		$page la pagina da modificare e stampare in caso di fallback (stringa per riferimento)
 *		$destURL stringa contenente l'url a cui rimandare (con eventuali parametri di $_GET già in coda)
 *		$delaySec (opzionale, default 30) intero, numero di secondi prima del refresh in caso di fallback
 *		$replacePrevious (opzionale, default true) bool che indica se sovrascrivere precedenti direttive header di tipo compatibile
 *		$httpCode (opzionale, default 303) indica il codice della richiesta http, il default di php è 302, 303 è più corretto
 *
 *	Comportamento:
 *		se non ci sono già state direttive header effettua un redirect a $destURL tramite la funzione header() di php dopo aver eliminato l'eventuale contenuto del buffer di output
 *		non potendo emettere header se non come prima cosa, se è già stato stampato qualcosa (o inviato un header) parte il comportamento di fallback:
 *			ristampa la pagina ricevuta in input con l'aggiunta di un tag meta refresh di $delaySec secondi,
 *			informa l'utente che verrà reindirizzato e gli propone un link da seguire manualmente per sveltire la procedura
 *		termina l'esecuzione dello script in modo corretto con exit(0);
 *
 *	Debug:
 *		in modalità debug (scommentare il blocco apposito) o redirecta via direttiva header o lancia un'eccezione contenente
 *			file e riga in cui è stato stampato il contenuto (o l'header) che invalida il redirect e porterebbe alla fallback
 */
function pleaseRedirect(string &$page, string $destURL, int $delaySec=30, bool $replacePrevious=true, int $httpCode=303)
{
/* //DEBUG
	string $file;
	int $line;
	if(!(headers_sent($file, $line)))
	{
		$destURL = "LOCATION: " . $destURL;
		header($destURL, $replacePrevious, $httpCode);
		exit;
	}
	else
		throw new Exception("headers have already been sent!<br />file: ".$file."<br />line: ".$line);
*/
	
	//Se non sono già state mandate direttive header
	if(!(headers_sent()))
	{
		$destURL = "LOCATION: ". $destURL;
		ob_end_clean(); //pulisco i buffer per sicurezza
		header($destURL, $replacePrevious, $httpCode);
	}
	else //Metodo alternativo di reindirizzamento
	{
		//Infilo dentro un meta refresh
		$metarefresh = "<meta http-equiv=\"refresh\" content=\"". $delaySec ."; url=". $destURL ."\">\n\t<meta name=\"description\"";
		$page = str_replace("<meta name=\"description\"", $metarefresh, $page);
		
		//Preparo il contenuto della pagina da stampare
		$text = "<a href=\"". $destURL ."\">Premi qui per procedere</a> se il reindirizzamento automatico non avviene entro ". $delaySec ." secondi.";
		insertFeedback($page, $text, "replace"); //avviso l'utente che verrà reindirizzato
		
		//Completo e stampo la pagina
		$title = "Reindirizzamento...";
		$breadcrumb = "<li>Reindirizzamento in corso</li>";
		$description = "Questa è una pagina di REINDIRIZZAMENTO, la risorsa richiesta o cercata sarà disponibile a breve.";
		$keywords = "reindirizzamento,attesa,redirect,wait,noindex,nofollow,Prism Game Reviews";
		$pageH = new PageHeader($title, $breadcrumb, $description, $keywords, false, false);
		$pageH->printer($page);
	}
	exit(0); //per sicurezza dopo la direttiva header location
}
?>