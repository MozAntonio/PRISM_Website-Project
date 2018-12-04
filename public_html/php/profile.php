<?php

/*	Questo script viene invocato per caricare il PROFILO utente.
 *	Se l'utente non è autenticato lo rimanda alla login, altrimenti:
 *	Se è arrivato chiedendo il logout, distruggo la sessione e lo rimanda alla login,
 *	Sennò carica il profilo adeguato al livello di privilegi dell'utente (admin o user)
 */

session_start(); //Avvia la sessione

try {
	if (file_exists("../templates/pages/profile_page.txt") && file_exists("./utility.php"))
	{
		$page = file_get_contents("../templates/pages/profile_page.txt"); //carico la pagina dove stampare l'eventuale fallback
		require_once("./utility.php"); //funz di stampa, isLogged, redirect
	}
	else
	{
		throw new Exception("profile_page.txt e utility.php", 1); //code=1 eccezione fatale
	}
	
	//Se l'utente non è autenticato
	if(!(isLogged()))
	{	
		//Direttiva di reindirizzamento
		pleaseRedirect($page, "login.php");
	}
	elseif(isset($_POST["logout"])) //Se l'utente ha chiesto il logout
	{
		//Logout
		session_unset();
		session_destroy();
		
		//Direttiva di reindirizzamento
		pleaseRedirect($page, "login.php");
	}
	else
	{
		if (file_exists("./connection.php"))
			require_once("./connection.php");
		else
			throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (connection.php)");
		
		$conn=dbConnect();
		
		//Carico i dati del profilo
		$query = "select * from UTENTE where Username='". $_SESSION["username"] ."';";
		
		if(!($result = $conn->query($query)))
		{
			throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> per il recupero dal <span lang=\"en\">database</span> dei dati dell'utente per mostrarli nella pagina del profilo [#". $conn->errno ." - <span lang=\"en\">". $conn->error ."</span>]", 3); //code=3 --> connection open
		}
		else
		{
			$row = $result->fetch_array(MYSQLI_ASSOC);
			$result->free();
		}
		
		//Conto il numero di commenti dell'utente
		$query = "select count(*) from COMMENTO where Utente='". $_SESSION["username"] ."';";
		
		if(!($result = $conn->query($query)))
		{
			throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> per il recupero dal <span lang=\"en\">database</span> del numero di commenti scritti dall'utente per mostrarlo nella pagina del profilo [#". $conn->errno ." - <span lang=\"en\">". $conn->error ."</span>]", 3); //code=3 --> connection open
		}
		else
		{
			$numComments = $result->fetch_row()[0];
			$result->free();
		}
		
		//Prepariamo il content
		$page = str_replace("_USERNAME_", $row["Username"], $page);
		$page = str_replace("_EMAIL_", $row["Email"], $page);
		$page = str_replace("_DATAISCRIZIONE_", "<time datetime=\"". $row["DataIscrizione"] ."\">". date_format(date_create($row["DataIscrizione"]), "d-m-Y") ."</time>", $page);
		$page = str_replace("_NUMCOMMENT_", $numComments, $page);
		
		//Se è amministratore aggiungo le cosa da Admin
		if($_SESSION["admin"]==true)
		{
			//Conto il numero di recensioni pubblicate dall'utente
			$query = "select count(*) from RECENSIONE where Autore='". $_SESSION["username"] ."';";
			
			if(!($result = $conn->query($query)))
			{
				throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> per il recupero dal <span lang=\"en\">database</span> del numero di recensioni pubblicate dall'utente amministratore per mostrarlo nella pagina del profilo [#". $conn->errno ." - <span lang=\"en\">". $conn->error ."</span>]", 3); //code=3 --> connection open
			}
			else
			{
				$numReviews = $result->fetch_row()[0];
				$result->free();
			}
			
			$sezAdmin = "<nav>\n\t<ul>\n\t\t<li><a href=\"newReview.php\">Crea una recensione</a></li>\n\t\t<li><a href=\"../adminmanual.html\">Manuale amministrativo</a></li>\n\t</ul>\n</nav>";
			$page = str_replace("_SEZIONEAMMINISTRATIVA_", "<h2>Sezione amministrativa</h2>\n". $sezAdmin, $page);
			$page = str_replace("_NUMREVIEW_", "<dt>Recensioni pubblicate:</dt>\n\t<dd>". $numReviews ."</dd>", $page);
		}
		else //Altrimenti profilo standard
		{
			$page = str_replace("_SEZIONEAMMINISTRATIVA_", "", $page);
			$page = str_replace("_NUMREVIEW_", "", $page);
		}
		
		$conn->close();
		
		//Completo e stampo la pagina
		$title = "Profilo - Prism Game Reviews";
		$breadcrumb = "<li>Profilo</li>";
		$description = "Pagina personale sul sito di Prism Game Reviews.";
		$keywords = "profilo,account,esci,gestione,Prism Game Reviews,Prism,recensioni,videogiochi,video giochi";
		$pageH = new PageHeader($title, $breadcrumb, $description, $keywords, false, false);
		$pageH->printer($page);
		
	} //chiude l'else di notIsLogged
} //chiude try

catch(Exception $e)
{
	if($e->getCode() == 3) //code=3 eccezione sollevata mentre una connessione è aperta
		$conn->close(); //Si è verificato un problema con l'esecuzione della query, chiudo la connessione
	
	//Se l'eccezione non è stata generata da un errore fatale (quindi è un errore critico)
	if($e->getCode() != 1)
	{
		//Prepariamo l'header della pagina con l'eccezione
		$pageH = new PageHeaderException();
		
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