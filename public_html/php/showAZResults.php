<?php

/*	Questo script viene invocato per mostrare i titoli dei giochi che iniziano con un CARATTERE (per effettuare la ricerca di una recensione).
 *	Se in GET non è stato passato alcun carattere, oppure viene passato un valore errato, mostra un messaggio di errore
 *	Prende tutti i giochi che iniziano con il carattere indicato dal database e li stampa a video come coppia link-descrizioneHTML
 *		se non ci sono stati errori presenta i risultati paginandoli ogni 10;
 *		se ci sono stati errori critici lo comunica al posto del contenuto della pagina;
 *		se ci sono stati errori fatali stampa la pagina di avviso.
 */

try {
	//Controllo di avere tutti i file di cui necessito
	if (file_exists("../templates/pages/showResults_page.txt") && file_exists("./utility.php"))
	{
		$page = file_get_contents("../templates/pages/showResults_page.txt"); //carico il template
		require_once("./utility.php"); //la funzione di stampa
	}
	else
	{
		throw new Exception("showResults_page.txt e utility.php", 1); //code=1 eccezione fatale
	}
	
	$errore="";
	$breadcrumb = "<li><a href=\"../searchByAZ.html\"><abbr title=\"Lettera dell'Alfabeto\">A-Z</abbr></a></li>\n\t\t";
	
	if(!(empty($_GET["carattere"])) && (((ctype_alpha($_GET["carattere"])) && (strlen($_GET["carattere"]) == 1)) || (strtolower($_GET["carattere"]) == "all") || (strtolower($_GET["carattere"]) == "numeri e simboli")))
	{
		if (file_exists("./connection.php"))
			require_once("./connection.php");
		else
			throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (connection.php)");
		
		
		$conn=dbConnect();
		
		if(strtolower($_GET["carattere"]) == "all")
		{
			$getChar = "Mostra Tutto";
			
			/* OPZIONE 2: Gestisce tutti i casi, sia in presenza di caratteri alfabetici, sia in presenza di numeri e/o simboli */
			$queryParameter = "IS NOT NULL";
		}
		elseif(strtolower($_GET["carattere"]) == "numeri e simboli")
		{
			$getChar = "Numeri e Simboli";
			
			/* OPZIONE 2: Gestisce tutti i casi, sia in presenza di caratteri alfabetici, sia in presenza di numeri e/o simboli */
			$queryParameter = "not rlike '^[a-z]'";
		}
		else
		{
			$getChar = "Lettera ". strtoupper($_GET["carattere"]);
			
			/* OPZIONE 1: Gestisce tutti i casi, ma solo in presenza di caratteri alfabetici (non numeri e/o simboli) */
			//$queryParameter = strtolower($_GET["carattere"]);
			/* OPZIONE 2: Gestisce tutti i casi, sia in presenza di caratteri alfabetici, sia in presenza di numeri e/o simboli */
			$queryParameter = "like '". strtolower($_GET["carattere"]) ."%'";
		}
		
		//Prepariamo l'header
		$title = "Risultati per ". $getChar ." - Prism Game Reviews";
		$breadcrumb = $breadcrumb ."<li>". $getChar ."</li>";
		$description = "Risultati della ricerca per ". $getChar .", dei giochi recensiti su Prism Game Reviews.";
		$keywords = "risultati,az,alfabetico,". strtolower($getChar). ",ricerca,recensione,videogiochi,search,character,Prism Game Reviews";
		$pageH = new PageHeader($title, $breadcrumb, $description, $keywords, true, true);
		
		
		//Prepariamo il content
		
		//Aggiungo il titolo ai risultati
		if($getChar == "Mostra Tutto")
			$contentPage = "<h1>Tutte le recensioni</h1>\n\t";
		else
			$contentPage = "<h1>". $getChar ."</h1>\n\t";
		
		//Query per ottenere il totale dei record che dovranno essere paginati
		/* OPZIONE 1: Gestisce tutti i casi, ma solo in presenza di caratteri alfabetici (non numeri e/o simboli) */
		//$query = "select count(*) from GIOCO where Titolo like '". $queryParameter ."%' or Titolo like '<span lang=\"__\">". $queryParameter ."%' or Titolo like '<acronym title=\"%\">". $queryParameter ."%' or Titolo like '<abbr title=\"%\">". $queryParameter ."%' or Titolo like '<span lang=\"__\"><acronym title=\"%\">". $queryParameter ."%' or Titolo like '<span lang=\"__\"><abbr title=\"%\">". $queryParameter ."%';";
		/* OPZIONE 2: Gestisce tutti i casi, sia in presenza di caratteri alfabetici, sia in presenza di numeri e/o simboli */
		$query = "select count(*) from GIOCO where TitoloOrdinamento ". $queryParameter .";";
		
		if(!($result = $conn->query($query)))
		{
			throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> contando il numero totale di giochi che iniziano con \"". $getChar ."\" per paginare i risultati [#". $conn->errno ." - <span lang=\"en\">". $conn->error ."</span>]", 3); //code=3 --> connection open
		}
		
		$totalRows = $result->fetch_row()[0];
		$result->free();
		
		//Se c'è almeno un record nella tabella gioco che ha il titolo che inizia con il carattere richiesto: continuo!
		//Altrimenti stampo un messaggio all'utente che non ci sono risultati
		if($totalRows > 0)
		{
			//Decido il numero massimo di risultati per pagina
			$numPerPage = 10;
			$showMenuPagination = false;
			
			if($totalRows > $numPerPage)
			{
				if (file_exists("./classPaginazione.php"))
					require_once("./classPaginazione.php");
				else
					throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (classPaginazione.php)");
				
				$showMenuPagination = true;
				
				//Imposto la pagina attuale
				if(empty($_GET["p"]) || !(ctype_digit($_GET["p"])))
					$currentPage = 1;
				else
					$currentPage = $_GET["p"];
				
				//Setto il link di reindirizzamento delle pagine
				$linkTo = "showAZResults.php?carattere=". strtolower($_GET["carattere"]);
				
				//Creo un nuovo oggetto 'Paginazione'
				$pager = new Paginazione($currentPage, $totalRows, $numPerPage, $linkTo);
				
				//Ottengo il valore del campo offset da usare nella successiva query
				$offsetValue = $pager->getOffsetValue();
			}
			else
			{
				$offsetValue = 0;
			}
			
			//Ora prelevo dal DB, a partire dal record numero "$offsetValue", i successivi "$numPerPage" record da paginare
			/* OPZIONE 1: Gestisce tutti i casi, ma solo in presenza di caratteri alfabetici (non numeri e/o simboli) */
			//$query = "select G.IDGioco as ID, G.Titolo as TitoloGioco, R.DescrizioneHTML as Descrizione from GIOCO G join RECENSIONE R on (G.IDGioco=R.ID) where G.Titolo like '". $queryParameter ."%' or G.Titolo like '<span lang=\"__\">". $queryParameter ."%' or G.Titolo like '<acronym title=\"%\">". $queryParameter ."%' or G.Titolo like '<abbr title=\"%\">". $queryParameter ."%' or G.Titolo like '<span lang=\"__\"><acronym title=\"%\">". $queryParameter ."%' or G.Titolo like '<span lang=\"__\"><abbr title=\"%\">". $queryParameter ."%' order by G.TitoloOrdinamento asc limit ". $numPerPage ." offset ". $offsetValue .";";
			/* OPZIONE 2: Gestisce tutti i casi, sia in presenza di caratteri alfabetici, sia in presenza di numeri e/o simboli */
			$query = "select G.IDGioco as ID, G.Titolo as TitoloGioco, R.DescrizioneHTML as Descrizione from GIOCO G join RECENSIONE R on (G.IDGioco=R.ID) where G.TitoloOrdinamento ". $queryParameter ." order by G.TitoloOrdinamento asc limit ". $numPerPage ." offset ". $offsetValue .";";
			
			if(!($result = $conn->query($query)))
			{
				throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> prelevando dal <span lang=\"en\">database</span>, a partire dal <span lang=\"en\">record</span> numero ". $offsetValue .", i successivi ". $numPerPage. " <span lang=\"en\">record</span> da paginare [#". $conn->errno ." - <span lang=\"en\">". $conn->error ."</span>]", 3); //code=3 --> connection open
			}
			else
			{
				while($row = $result->fetch_array(MYSQLI_ASSOC))
				{
					$contentPage = $contentPage ."<article>\n\t\t<header><h2><a href=\"readReview.php?modo=3&ricerca=". strtolower($_GET["carattere"]) ."&id=". $row["ID"] ."\">". $row["TitoloGioco"] ."</a></h2></header>\n\t\t<p>". $row["Descrizione"] ."</p>\n\t</article>\n\t";
				}
				
				$result->free();
			}
			
			//Aggiungo in fondo ai risultati l'elenco della paginazione solo se i risultati superano il valore "$numPerPage"
			if($showMenuPagination)
			{
				$contentPage = $contentPage . $pager->printPagination();
			}
		}
		else //Comunico all'utente che non ci sono risultati
		{
			if($getChar != "Mostra Tutto")
				$contentPage = $contentPage ."<p>Nessun risultato trovato nella ricerca per: \"". $getChar ."\"</p>";
			else
				$contentPage = $contentPage ."<p>Il sito <span lang=\"en\">Prism Game Reviews</span> non contiene ancora nessuna recensione.</p>";
		}
		
		$conn->close();
		
		//Inserisco il content
		$page = str_replace("_CONTENT_", $contentPage, $page);
	}
	else
	{
		$errore="carattere non valido";
		
		//Prepariamo l'header della pagina di errore
		$title = "Risultati per ". $errore ." - Prism Game Reviews";
		$breadcrumb = $breadcrumb ."<li>Carattere non valido</li>";
		$pageH = new PageHeaderError($title, $breadcrumb);
		
		//Inserisco l'avviso
		//$page = str_replace("_CONTENT_", "<p>Il carattere di inizio selezionato non è valido. Si prega di riprovare.</p>", $page);
		insertFeedback($page, "<h1>Oops ...</h1>\n<p>Il carattere di inizio selezionato non è valido. Si prega di riprovare.</p>", "replace");
	}
	
	//Stampo la pagina
	$pageH->printer($page);
	
}
catch(Exception $e)
{
	if($e->getCode() == 3) //code=3 eccezione sollevata mentre una connessione è aperta
		$conn->close(); //Si è verificato un problema con l'esecuzione della query, chiudo la connessione
	
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