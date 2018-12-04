<?php

/*	Questo script viene invocato per AUTENTICARE un utente.
 *	Se l'utente è già autenticato lo avvisa, altrimenti:
 *	Al primo giro carica la form vuota e la stampa all'utente da compilare
 * 	Al secondo giro (con ?check in $_GET) controlla se quanto inserito è valido:
 *		se non ci sono stati errori setta i parametri di sessione ed effettua un redirect al profilo
 *		se ci sono stati errori risolvibili dall'utente glielo comunica sotto la form vuota;
 *		se ci sono stati errori critici nel processo di controllo lo comunica al posto della form;
 *		se ci sono stati errori fatali stampa la pagina di avviso.
 */

session_start(); //Avvia la sessione

try {
	if (file_exists("../templates/pages/login_page.txt") && file_exists("./utility.php"))
	{
		$page = file_get_contents("../templates/pages/login_page.txt"); //carico il template
		require_once("./utility.php"); //funz di stampa, isLogged, redirect
	}
	else
	{
		throw new Exception("login_page.txt e utility.php", 1); //code=1 eccezione fatale
	}
	
	//Preparo i campi per l'header
	$title = "Accedi - Prism Game Reviews";
	$breadcrumb = "";
	$description = "Accedi al tuo profilo sul sito di Prism Game Reviews.";
	$keywords = "accesso,login,utente,profilo,account,recensioni,videogiochi,Prism Game Reviews";
	
	//Se l'utente è già autenticato
	if(isLogged())
	{
		//Preparo l'header della pagina
		$pageH = new PageHeader($title, $breadcrumb ."<li>Accedi</li>", $description, $keywords, false, false);
		
		$text="<h1>Oops ...</h1>\n<p>Risulti già autenticato come <em>". $_SESSION["username"] ."</em>.</p>\n<nav><a href=\"profile.php\">Vai al profilo</a></nav>";
		//Inserisco l'avviso
		insertFeedback($page, $text, "replace");
	}
	else
	{
		//Tentativo di login
		if(isset($_GET["check"]))
		{
			$errore="";
			
			//Check di tutti i campi, tutti obbligatori (per coerenza questa dovrebbe essere la funzione checkLoginForm)
			if (!(empty($_POST["username"])) && !(empty($_POST["password"])))
			{
				if (file_exists("./classUtente.php"))
					require_once("./classUtente.php");
				else
					throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (classUtente.php)");
				
				//Creo un nuovo oggetto 'Utente'
				$user = new Utente($_POST["username"], $_POST["password"]);
				
				//Se non si sono verificati errori durante la creazione, procedo con l'autenticazione dell'utente
				if($user == "")
				{
					$errore = $user->authenticate();
					
					if($errore == "")
						pleaseRedirect($page, "profile.php"); //redirect a profilo
				}
				else
				{
					//Errore in uno dei campi dati
					$errore="<p>Si sono verificati i seguenti errori: </p>". $user;
				}
			}
			else
			{
				$errore="<p>Compilare tutti i campi, nessun campo è opzionale.</p>";
			}
			
			if($errore != "")
			{
				//Preparo l'header della pagina di errore
				$pageH = new PageHeaderError("Errore accesso - Prism Game Reviews", $breadcrumb ."<li>Errore accesso</li>");
				
				//Inserisco l'elenco degli errori sotto la form
				insertFeedback($page, $errore, "append");
			}
		}
		else //è la prima volta che arrivo su questa pagina, devo stampare la form di login (e voglio indicizzare la pagina)
		{
			//Preparo l'header della pagina
			$pageH = new PageHeader($title, $breadcrumb ."<li>Accedi</li>", $description, $keywords, true, true);
		}
	} //chiude isLogged
	
	//Stampo la pagina (o con l'avviso o con la form di login)
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