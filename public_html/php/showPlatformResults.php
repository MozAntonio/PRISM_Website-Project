<?php

/*	Questo script viene invocato per mostrare i titoli dei giochi che vengono eseguiti su una PIATTAFORMA (per effettuare la ricerca di una recensione).
 *	Se in GET non è stata passata alcuna piattaforma, oppure viene passato un valore errato, mostra un messaggio di errore
 *	Prende tutti i giochi che vengono eseguiti sulla piattaforma indicata dal database e li stampa a video come coppia link-descrizioneHTML
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
	$breadcrumb = "<li><a href=\"searchByPlatform.php\">Piattaforma</a></li>\n\t\t";
	
	if(!(empty($_GET["piattaforma"])) && !(empty($_GET["versione"])) && (ctype_digit($_GET["piattaforma"])) && ((strtolower($_GET["versione"]) == "all") || (ctype_digit($_GET["versione"]))))
	{
		if (file_exists("./connection.php"))
			require_once("./connection.php");
		else
			throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (connection.php)");
		
		
		$conn=dbConnect();
		
		//NOTA: Se il parametro $_GET["versione"] è diverso da "all", allora il parametro $_GET["piattaforma"] non viene mai utilizzato
		
		//L'utente può aver selezionato:
		//1) Una famiglia/categoria di piattaforme
		//2) Una piattaforma con una specifica versione
		if(strtolower($_GET["versione"]) == "all")
		{
			//Ottengo dal database il record che corrisponde alla famiglia/categoria di piattaforme selezionata dall'utente
			$query = "select Nome from PIATTAFORMA where IDPiattaforma=". $_GET["piattaforma"] ." group by Nome;";
		}
		else
		{
			//Ottengo dal database il record che corrisponde alla piattaforma (con versione annessa) precedentemente selezionata dall'utente
			$query = "select * from PIATTAFORMA where IDPiattaforma=". $_GET["versione"] .";";
		}
		
		if(!($result = $conn->query($query)))
		{
			throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> recuperando dal <span lang=\"en\">database</span> l'<abbr title=\"identificatore\">ID</abbr> della piattaforma (con l'eventuale versione) selezionata per generare l'elenco di risultati [#". $conn->errno ." - <span lang=\"en\">". $conn->error ."</span>]", 3); //code=3 --> connection open
		}
		
		if($result->num_rows > 0)
		{
			$row = $result->fetch_array(MYSQLI_ASSOC);
			$result->free();
			
			
			//Prepariamo l'header
			if(strtolower($_GET["versione"]) == "all")
				$platform = $row["Nome"];
			else
				$platform = $row["Nome"] ." ". $row["Versione"];
			
			$title = "Risultati per ". strip_tags($platform) ." - Prism Game Reviews";
			$breadcrumb = $breadcrumb ."<li>". $platform ."</li>";
			$description = "Risultati della ricerca per la piattaforma: ". strip_tags($platform) .", dei giochi recensiti su Prism Game Reviews.";
			$keywords = "risultati,". strip_tags($platform) .",piattaforma,ricerca,recensione,videogiochi,search,platform,Prism Game Reviews";
			$pageH = new PageHeader($title, $breadcrumb, $description, $keywords, true, true);
			
			
			//Prepariamo il content
			
			//Aggiungo il titolo ai risultati
			$contentPage = "<h1>". $platform ."</h1>\n\t";
			
			//Query per ottenere il totale dei record che dovranno essere paginati
			if(strtolower($_GET["versione"]) == "all")
			{
				//$query = "select count(ViewESECUZ.IDGioco) from (select E.IDGioco from PIATTAFORMA P join ESECUZIONE E on (P.IDPiattaforma=E.IDPiattaforma) join GIOCO G on (E.IDGioco=G.IDGioco) where P.Nome='". $row["Nome"] ."' group by E.IDGioco) as ViewESECUZ;"; //Opzione 1: meno efficiente, più chiara
				$query = "select count(ViewESECUZ.IDGioco) from (select E.IDGioco from ESECUZIONE E join PIATTAFORMA P on (E.IDPiattaforma=P.IDPiattaforma) where P.Nome='". $row["Nome"] ."' group by E.IDGioco) as ViewESECUZ;"; //Opzione 2: più efficiente, meno chiara
			}
			else
			{
				//$query = "select count(*) from ESECUZIONE E join GIOCO G on (E.IDGioco=G.IDGioco) where E.IDPiattaforma=". $_GET["versione"] .";"; //Opzione 1: meno efficiente, più chiara
				$query = "select count(*) from ESECUZIONE where IDPiattaforma=". $_GET["versione"] .";"; //Opzione 2: più efficiente, meno chiara
			}
			
			if(!($result = $conn->query($query)))
			{
				throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> contando il numero totale di giochi con piattaforma ". $row["Nome"] ." oppure con l'<abbr title=\"identificatore\">ID</abbr> della piattaforma ". $_GET["piattaforma"] ." (e versione ". strtolower($_GET["versione"]) .") per paginare i risultati [#". $conn->errno ." - <span lang=\"en\">". $conn->error ."</span>]", 3); //code=3 --> connection open
			}
			
			$totalRows = $result->fetch_row()[0];
			$result->free();
			
			//Se c'è almeno un record nella tabella gioco che viene eseguito sulla piattaforma richiesta: continuo!
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
					$linkTo = "showPlatformResults.php?piattaforma=". $_GET["piattaforma"] ."&versione=". strtolower($_GET["versione"]);
					
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
				if(strtolower($_GET["versione"]) == "all")
				{
					$query = "select G.IDGioco as ID, G.Titolo as TitoloGioco, R.DescrizioneHTML as Descrizione from RECENSIONE R join GIOCO G on (R.ID=G.IDGioco) join (select E.IDGioco from ESECUZIONE E join PIATTAFORMA P on (E.IDPiattaforma=P.IDPiattaforma) where P.Nome='". $row["Nome"] ."' group by E.IDGioco) as ViewESECUZ on (G.IDGioco=ViewESECUZ.IDGioco) order by G.TitoloOrdinamento asc limit ". $numPerPage ." offset ". $offsetValue .";";
				}
				else
				{
					$query = "select G.IDGioco as ID, G.Titolo as TitoloGioco, R.DescrizioneHTML as Descrizione from RECENSIONE R join GIOCO G on (R.ID=G.IDGioco) join ESECUZIONE E on (G.IDGioco=E.IDGioco) where E.IDPiattaforma=". $_GET["versione"] ." order by G.TitoloOrdinamento asc limit ". $numPerPage ." offset ". $offsetValue .";";
				}
				
				if(!($result = $conn->query($query)))
				{
					throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> prelevando dal <span lang=\"en\">database</span>, a partire dal <span lang=\"en\">record</span> numero ". $offsetValue .", i successivi ". $numPerPage. " <span lang=\"en\">record</span> da paginare [#". $conn->errno ." - <span lang=\"en\">". $conn->error ."</span>]", 3); //code=3 --> connection open
				}
				else
				{
					while($row = $result->fetch_array(MYSQLI_ASSOC))
					{
						$contentPage = $contentPage ."<article>\n\t\t<header><h2><a href=\"readReview.php?modo=1&ricerca=". $_GET["piattaforma"] ."-". strtolower($_GET["versione"]) ."&id=". $row["ID"] ."\">". $row["TitoloGioco"] ."</a></h2></header>\n\t\t<p>". $row["Descrizione"] ."</p>\n\t</article>\n\t";
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
				$contentPage = $contentPage ."<p>Nessun risultato trovato nella ricerca per piattaforma: ". $platform ."</p>";
			}
			
			$conn->close();
			
			//Inserisco il content
			$page = str_replace("_CONTENT_", $contentPage, $page);
		}
		else
		{
			$conn->close();
			$errore="piattaforma non valida";
		}
	}
	else
	{
		$errore="piattaforma non valida";
	}
	
	if($errore != "")
	{
		//Prepariamo l'header della pagina di errore
		$title = "Risultati per ". $errore ." - Prism Game Reviews";
		$breadcrumb = $breadcrumb ."<li>Piattaforma non valida</li>";
		$pageH = new PageHeaderError($title, $breadcrumb);
		
		//Inserisco l'avviso
		//$page = str_replace("_CONTENT_", "<p>La piattaforma selezionata non è valida. Si prega di riprovare.</p>", $page);
		insertFeedback($page, "<h1>Oops ...</h1>\n<p>La piattaforma selezionata non è valida. Si prega di riprovare.</p>", "replace");
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