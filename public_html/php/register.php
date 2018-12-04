<?php

/*	Questo script viene invocato per REGISTRARE un nuovo utente.
 *	Se l'utente è già autenticato lo avvisa, altrimenti:
 *	Al primo giro carica la form vuota e la stampa all'utente da compilare
 * 	Al secondo giro (con ?check in $_GET) controlla se quanto inserito è valido:
 *		se non ci sono stati errori:
 *			effettua l'inserimento nel DB,
 *			setta i parametri di sessione,
 *			effettua un redirect a questa pagina (?success in $_GET) e comunica il successo;
 *		se ci sono stati errori risolvibili dall'utente glielo comunica sotto la form vuota;
 *		se ci sono stati errori critici nel processo di controllo lo comunica al posto della form;
 *		se ci sono stati errori fatali stampa la pagina di avviso.
 */

try {
	if (file_exists("../templates/pages/register_page.txt") && file_exists("./utility.php"))
	{
		$page = file_get_contents("../templates/pages/register_page.txt");  //carico il template
		require_once("./utility.php"); //funz di stampa, isLogged, redirect
	}
	else
	{
		throw new Exception("register_page.txt e utility.php", 1); //code=1 eccezione fatale
	}
	
	//Preparo i campi per l'header
	$title = "Registrazione - Prism Game Reviews";
	$breadcrumb = "<li><a href=\"login.php\">Accedi</a></li>\n\t\t";
	$description = "Nuovo utente? Registrati al sito di Prism Game Reviews.";
	$keywords = "registrazione,primo accesso,nuovo utente,nuovo profilo,nuovo account,recensioni,videogiochi,Prism Game Reviews";
	
	//Se sono arrivato qui dopo una registrazione andata a buon fine (via PGR)
	if(isset($_GET["success"]))
	{
		//Preparo l'header della pagina di successo
		$pageH = new PageHeaderSuccess("Registrazione completata - Prism Game Reviews", $breadcrumb ."<li>Registrazione completata</li>");
		
		$text="<h1>Complimenti</h1>\n<p>Registrazione avvenuta con successo!</p>\n<nav id=\"menusuccess\">\n\t<ul>\n\t\t<li><a href=\"../index.html\">vai all'<span lang=\"en\">home page</span></a></li>\n\t\t<li><a href=\"profile.php\">vai al tuo profilo</a></li>\n\t</ul>\n</nav>";
		//Inserisco l'avviso
		insertFeedback($page, $text, "replace"); //tutto OK, registrazione riuscita
	}
	else
	{
		session_start();
		
		//Check se l'utente è già autenticato
		if(isLogged())
		{
			//Preparo l'header della pagina
			$pageH = new PageHeader($title, $breadcrumb ."<li>Registrazione</li>", $description, $keywords, false, false);
			
			$text="<h1>Oops ...</h1>\n<p>Risulti già autenticato come <em>". $_SESSION["username"] ."</em>.</p>\n<nav><a href=\"profile.php\">Vai al profilo</a></nav>";
			//Inserisco l'avviso
			insertFeedback($page, $text, "replace");
		}
		else
		{
			//Se è stata inviata una richiesta di registrazione la controllo
			if(isset($_GET["check"]))
			{
				$errore="";
				
				//Check di tutti i campi, tutti obbligatori (per coerenza questa dovrebbe essere la funzione checkRegisterForm)
				if (!(empty($_POST["email"])) && !(empty($_POST["username"])) && !(empty($_POST["password"])) && !(empty($_POST["checkPassword"])))
				{
					//Eseguo un trim sull'email
					$_POST["email"]=trim($_POST["email"]);
					
					if (file_exists("./classNuovoUtente.php"))
						require_once("./classNuovoUtente.php");
					else
						throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (classNuovoUtente.php)");
					
					//Creo un nuovo oggetto 'NuovoUtente'
					$user = new NuovoUtente($_POST["email"], $_POST["username"], $_POST["password"], $_POST["checkPassword"]);
					
					//Se non si sono verificati errori durante la creazione, procedo con l'inserimento nel DB dei dati del nuovo utente
					if($user=="")
					{
						$errore = $user->insertInDB();
						
						if($errore == "")
						{
							//PRG
							//Direttiva di reindirizzamento
							pleaseRedirect($page, "register.php?success"); //usare con cautela
						}
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
					$pageH = new PageHeaderError("Errore registrazione - Prism Game Reviews", $breadcrumb ."<li>Errore registrazione</li>");
					
					//Inserisco l'elenco degli errori sotto la form
					insertFeedback($page, $errore, "append");
				}
			}
			else //è la prima volta che arrivo su questa pagina, devo stampare la form di registrazione (e voglio indicizzare la pagina)
			{
				//Preparo l'header della pagina
				$pageH = new PageHeader($title, $breadcrumb ."<li>Registrazione</li>", $description, $keywords, true, false);
			}
		} //chiude isLogged
	} //chiuse isSuccess
	
	//Stampo la pagina (o con l'avviso o con la form di registrazione)
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