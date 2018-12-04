<?php

/*	Questo script viene invocato per INSERIRE una nuova recensione.
 *	Al primo giro carica la form vuota e la stampa all'utente da compilare
 * 	Al secondo giro (con ?check in $_GET) controlla se quanto inserito è valido:
 *		se non ci sono stati errori effettua l'inserimento nel DB, effettua un redirect a questa pagina (?success in $_GET) e comunica il successo;
 *		se ci sono stati errori risolvibili dall'utente glielo comunica sotto la form vuota;
 *		se ci sono stati errori critici nel processo di controllo lo comunica al posto della form;
 *		se ci sono stati errori fatali stampa la pagina di avviso.
 */

session_start(); //Avvia la sessione

try {
	if (file_exists("../templates/pages/newReview_page.txt") && file_exists("./utility.php"))
	{
		$page = file_get_contents("../templates/pages/newReview_page.txt"); //carico il template
		require_once("./utility.php"); //le funzioni di stampa e isLogged
	}
	else
	{
		throw new Exception("newReview_page.txt e utility.php", 1); //code=1 eccezione fatale
	}
	
	//Preparo i campi per l'header
	$title = "Nuova recensione - Prism Game Reviews";
	$breadcrumb = "<li><a href=\"profile.php\">Profilo</a></li>\n\t\t";
	$description = "Pagina preposta alla creazione e successivo inserimento di una nuova recensione sul sito di Prism Game Reviews.";
	$keywords = "crea,inserisci,recensione,nuova recensione,gioco,videogiochi,Prism Game Reviews";
	
	if(!(isLogged()))
	{
		$text="<h1>Sezione riservata</h1>\n<p>Non risulti autenticato.</p>\n<nav>\n\t<ul>\n\t\t<li><a href=\"login.php\">Accedi</a></li>\n\t\t<li><a href=\"register.php\">Registrati</a></li>\n\t</ul>\n</nav>";
	}
	elseif($_SESSION["admin"]==false)
	{
		$text="<h1>Sezione riservata</h1>\n<p>Risulti autenticato come: <em>". $_SESSION["username"] ."</em>.</p>\n<p>Questa sezione è riservata agli amministratori. Se in possesso di credenziali amministrative si prega di uscire e rieffettuare l'accesso.</p>\n<nav><a href=\"profile.php\">Vai al profilo</a></nav>";
	}
	else
	{
		$text="";
	}
	
	if($text != "")
	{
		//Preparo l'header della pagina di errore
		$pageH = new PageHeaderError("Sezione riservata - Prism Game Reviews", $breadcrumb ."<li>Sezione riservata</li>");
		
		//Inserisco l'avviso
		insertFeedback($page, $text, "replace");
	}
	elseif(isset($_GET["success"])) //Se sono arrivato qui dopo un inserimento andato a buon fine (via PGR)
	{
		//Recupero l'id della recensione
		$idRecensione = -1;
		if(isset($_SESSION["idRecensione"]))
			$idRecensione = $_SESSION["idRecensione"];
		
		//Preparo l'header della pagina di successo
		$pageH = new PageHeaderSuccess("Inserimento completato - Prism Game Reviews", $breadcrumb ."<li>Inserimento completato</li>");
		
		$text = "<h1>Complimenti</h1>\n<p>L'inserimento della recensione è avvenuto con successo!</p>\n<nav id=\"menusuccess\">\n\t<a href=\"readReview.php?id=". $idRecensione ."\">vai alla recensione</a>\n</nav>";
		//Inserisco l'avviso: tutto OK, inserimento riuscito
		insertFeedback($page, $text, "replace");
	}
	else
	{
		//Se è stata inviata una nuova recensione la controllo
		if(isset($_GET["check"]))
		{
			if (file_exists("./checkReviewForm.php"))
				require_once("./checkReviewForm.php"); //la funzione di controllo form
			else
				throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (checkReviewForm.php)");
			
			$id = NULL; //mi preparo un id in cui ricevere l'id di destinazione per side effect
			
			//Chiamo la funzione di controllo della form in modalità inserimento
			$errore = checkReviewForm("inserisci", $id);
			
			//Verifico se sono stati identificati errori in quanto mandato dall'utente
			if($errore == "")
			{
				//PRG
				//Salvo in sessione l'id a cui andare dopo la GET
				$_SESSION["idRecensione"] = $id;
				
				//Direttiva di reindirizzamento
				pleaseRedirect($page, "newReview.php?success"); //usare con cautela
			}
			else //Ci sono stati errori, li accodo alla form
			{
				if (file_exists("./loadReviewForm.php"))
					require_once("./loadReviewForm.php"); //la funzione che mi carica le checkbox nella form di inserimento
				else
					throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (loadReviewForm.php)");
				
				//Completo la form con le checkbox
				loadNewReviewForm($page);
				
				//Preparo l'header della pagina di errore
				$pageH = new PageHeaderError("Errore inserimento - Prism Game Reviews", $breadcrumb ."<li>Errore inserimento</li>");
				
				//Inserisco l'elenco degli errori sotto la form
				insertFeedback($page, $errore, "append");
			}
		}
		else //è la prima volta che arrivo su questa pagina e quindi carico la form d'inserimento
		{
			if (file_exists("./loadReviewForm.php"))
				require_once("./loadReviewForm.php"); //la funzione che mi carica le checkbox nella form di inserimento
			else
				throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (loadReviewForm.php)");
			
			//Completo la form con le checkbox
			loadNewReviewForm($page);
			
			//Preparo l'header della pagina
			$pageH = new PageHeader($title, $breadcrumb ."<li>Nuova recensione</li>", $description, $keywords, false, false);
		}
	} //chiuse isSuccess
	
	//Stampo la pagina (o con l'avviso o con la form di inserimento recensione)
	$pageH->printer($page);
	
} //chiude try

catch(Exception $e)
{
	//Se l'eccezione non è stata generata da un errore fatale (quindi è un errore critico)
	if($e->getCode() != 1)
	{
		//Prepariamo l'header della pagina con l'eccezione
		$pageH = new PageHeaderException($breadcrumb);
		
		//Mi occupo di gestire un errore critico (ovvero bloccante ma che almeno posso comunicare)
		//Creazione di un messaggio di errore generico per l'utente, i dettagli dell'errore dovrebbero essere stampati sul file di log
		$erroreCritico="<h1>Errore critico</h1>\n<p>Il sistema è temporaneamente non disponibile. Si prega di riprovare più tardi.</p>"."<p>Errore critico.</p><p>Dettagli: ". $e->getMessage() .".</p>";
		
		insertFeedback($page, $erroreCritico, "replace"); //inserisco l'elenco degli errori bloccanti
		$pageH->printer($page); //stampo la pagina
	}
	else //L'eccezione è stata generata da un errore fatale
	{
		/* Se si è arrivati qui ci sono problemi grossi, e questa funzionalità è totalmente inutilizzabile non si possono neanche stampare gli errori all'utente */
		//Costruisco l'errore fatale
		$erroreFatale="<li><span lang=\"en\">file</span> necessari per l'esecuzione minima mancanti.</li><li><span lang=\"en\">file</span>: ". $e->getMessage() ."</li><li>richiesti dallo script: ". basename($e->getFile()) ."</li><li>alla riga: ". $e->getLine() ."</li>";
		//Stampa in una pagina completamente nuova, ma valida, il messaggio fatale e termina l'esecuzione in modo anomalo
		echo("<!DOCTYPE html><html lang=\"it\"><head><meta charset=\"utf-8\"/><meta name=\"robots\" content=\"none\"/><title>Errore fatale - Prism Game Reviews</title></head><body><h1>Errore fatale</h1><p>Il sistema è temporaneamente non disponibile. Si prega di riprovare più tardi.</p><p>Dettagli: </p><ul>". $erroreFatale ."</ul></body></html>");
		exit(1);
	}
}
?>