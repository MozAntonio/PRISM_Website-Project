<?php

/*	Questo script viene invocato per MODIFICARE una recensione già presente.
 *	Al primo giro carica la form con i dati da modificare e la stampa all'utente
 * 	Al secondo giro (con ?check in $_GET) controlla se quanto inserito è valido:
 *		se non ci sono stati errori effettua le modifiche nel DB e comunica il successo;
 *		se ci sono stati errori risolvibili dall'utente glielo comunica sotto la form azzerandone le modifiche;
 *		se ci sono stati errori critici nel processo di controllo lo comunica al posto della form;
 *		se ci sono stati errori fatali stampa la pagina di avviso.
 */

session_start(); //Avvia la sessione

try {
	if (file_exists("../templates/pages/editReview_page.txt") && file_exists("./utility.php"))
	{
		$page = file_get_contents("../templates/pages/editReview_page.txt"); //carico il template
		require_once("./utility.php"); //le funzioni di stampa e isLogged
	}
	else
	{
		throw new Exception("editReview_page.txt e utility.php", 1); //code=1 eccezione fatale
	}
	
	//Preparo i campi per l'header
	$title = "Modifica recensione - Prism Game Reviews";
	$breadcrumb = "";
	$description = "Pagina preposta alla modifica di una recensione sul sito di Prism Game Reviews.";
	$keywords = "modifica,aggiorna,recensione,gioco,videogiochi,Prism Game Reviews";
	
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
	elseif(isset($_GET["success"])) //Se sono arrivato qui dopo una modifica andata a buon fine (via PGR)
	{
		//Recupero l'id della recensione
		$idRecensione = -1;
		if(isset($_SESSION["idRecensione"]))
			$idRecensione = $_SESSION["idRecensione"];
		
		$linkToEditedReview = "<a href=\"readReview.php?id=". $idRecensione ."\">";
		
		//Preparo l'header della pagina di successo
		$breadcrumb = "<li>". $linkToEditedReview ."Recensione (". $idRecensione .")</a></li>\n\t\t";
		$pageH = new PageHeaderSuccess("Modifica completata - Prism Game Reviews", $breadcrumb ."<li>Modifica completata</li>");
		
		$text = "<h1>Complimenti</h1>\n<p>La modifica della recensione è avvenuta con successo!</p>\n<nav id=\"menusuccess\">\n\t". $linkToEditedReview ." torna alla recensione</a>\n</nav>";
		//Inserisco l'avviso: tutto OK, modifica riuscita
		insertFeedback($page, $text, "replace");
	}
	else //o check o prima volta
	{
		//Controllo se mi hanno passato un id valido
		if(empty($_POST["id"]) || !(ctype_digit($_POST["id"])))
		{
			//Prepariamo l'header della pagina di errore
			$breadcrumb = "<li>Recensione (<abbr title=\"non definito\">ND</abbr>)</li>\n\t\t";
			$pageH = new PageHeaderError("Recensione non valida - Prism Game Reviews", $breadcrumb ."<li>Recensione non valida</li>");
			
			$text="<h1>Oops ...</h1>\n<p>Non risulta selezionata nessuna recensione valida, pertanto la modifica non è possibile.</p>";
			//Inserisco l'avviso
			insertFeedback($page, $text, "replace");
		}
		else //l'id è valido
		{
			//Recupero l'id della recensione da modificare
			$id = $_POST["id"];
			
			//Se è stata inviata una modifica ad una recensione la controllo
			if(isset($_GET["check"]))
			{
				if (file_exists("./checkReviewForm.php"))
					require_once("./checkReviewForm.php"); //la funzione di controllo form
				else
					throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (checkReviewForm.php)");
				
				//Chiamo la funzione di controllo della form in modalità modifica
				$errore = checkReviewForm("modifica", $id);
				
				//Verifico se sono stati identificati errori in quanto mandato dall'utente
				if($errore == "")
				{
					//PRG
					//Salvo in sessione l'id a cui andare dopo la GET
					$_SESSION["idRecensione"] = $id;
			
					//Direttiva di reindirizzamento
					pleaseRedirect($page, "editReview.php?success"); //usare con cautela
				}
				else //Ci sono stati errori, li accodo alla form
				{
					if (file_exists("./loadReviewForm.php"))
						require_once("./loadReviewForm.php"); //la funzione che mi carica i dati associati a id nella form di modifica
					else
						throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (loadReviewForm.php)");
					
					$errLoad = loadEditReviewForm($page, $id); //completo la form con i dati associati a id
					if($errLoad == "") //se non ci sono stati errori nel caricare i dati
					{
						//Preparo l'header della pagina di errore
						$breadcrumb = "<li><a href=\"readReview.php?id=". $id ."\">Recensione (". $id .")</a></li>\n\t\t";
						$pageH = new PageHeaderError("Errore modifica - Prism Game Reviews", $breadcrumb ."<li>Errore modifica</li>");
						
						$text="<p>ATTENZIONE: tutte le modifiche apportate (sia corrette sia errate) sono state annullate a causa di alcuni errori. Si consideri pertanto di essere alla prima modifica.</p>";
						//Inserisco l'elenco degli errori sotto la form
						insertFeedback($page, $text.$errore, "append");
					}
					else
					{
						//Prepariamo l'header della pagina di errore
						$breadcrumb = "<li>Recensione (<abbr title=\"non definito\">ND</abbr>)</li>\n\t\t";
						$pageH = new PageHeaderError("Recensione non valida - Prism Game Reviews", $breadcrumb ."<li>Recensione non valida</li>");
						
						//Inserisco l'avviso
						insertFeedback($page, $errLoad, "replace");
					}
				}
			}
			else //è la prima volta che arrivo su questa pagina e quindi carico la form di modifica
			{
				if (file_exists("./loadReviewForm.php"))
					require_once("./loadReviewForm.php"); //la funzione che mi carica i dati associati a id nella form di modifica
				else
					throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (loadReviewForm.php)");
				
				$errLoad = loadEditReviewForm($page, $id); //completo la form con i dati associati a id
				if($errLoad == "") //se non ci sono stati errori
				{
					//Preparo l'header della pagina
					$breadcrumb = "<li><a href=\"readReview.php?id=". $id ."\">Recensione (". $id .")</a></li>\n\t\t";
					$pageH = new PageHeader($title, $breadcrumb ."<li>Modifica recensione</li>", $description, $keywords, false, false);
				}
				else
				{
					//Prepariamo l'header della pagina di errore
					$breadcrumb = "<li>Recensione (<abbr title=\"non definito\">ND</abbr>)</li>\n\t\t";
					$pageH = new PageHeaderError("Recensione non valida - Prism Game Reviews", $breadcrumb ."<li>Recensione non valida</li>");
					
					//Inserisco l'avviso
					insertFeedback($page, $errLoad, "replace");
				}
			}
		} //chiude l'else del test sull'id
	} //chiude isSuccess

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