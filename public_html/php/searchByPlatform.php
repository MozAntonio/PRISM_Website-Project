<?php

/*	Questo script viene invocato per mostrare le PIATTAFORME per effettuare la ricerca di una recensione.
 *	Prende tutte le piattaforme dal database e le stampa a video in un elenco composto da link
 *	[Nota: Ci saranno sia i link alle piattaforme (qualunque versione), sia i link alle piattaforme con una specifica versione]
 *		se non ci sono stati errori presenta l'elenco di link;
 *		se ci sono stati errori critici lo comunica al posto del contenuto della pagina;
 *		se ci sono stati errori fatali stampa la pagina di avviso.
 */

try {
	//Controllo di avere tutti i file di cui necessito
	if (file_exists("../templates/pages/searchByPlatform_page.txt") && file_exists("./utility.php"))
	{
		$page = file_get_contents("../templates/pages/searchByPlatform_page.txt"); //carico il template
		require_once("./utility.php"); //la funzione di stampa
	}
	else
	{
		throw new Exception("searchByPlatform_page.txt e utility.php", 1); //code=1 eccezione fatale
	}
	
	if (file_exists("./connection.php"))
		require_once("./connection.php");
	else
		throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (connection.php)");
	
	
	$conn=dbConnect();
	
	//Prima ottengo le famiglie/categorie di piattaforme
	$queryGroup = "select Nome from PIATTAFORMA group by Nome;";
	
	if(!($resultGroup = $conn->query($queryGroup)))
	{
		throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $queryGroup ."</code> recuperando dal <span lang=\"en\">database</span> i nomi delle piattaforme per generare l'elenco di ricerca [#". $conn->errno ." - <span lang=\"en\">". $conn->error ."</span>]", 3); //code=3 --> connection open
	}
	
	//Poi ottengo tutti i dati delle piattaforme
	$query = "select * from PIATTAFORMA;";
	
	if(!($result = $conn->query($query)))
	{
		throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> recuperando dal <span lang=\"en\">database</span> i nomi e le versioni delle piattaforme per generare l'elenco di ricerca [#". $conn->errno ." - <span lang=\"en\">". $conn->error ."</span>]", 3); //code=3 --> connection open
	}
	else
	{
		//Se tutto è andato bene, posso procedere con la creazione del contenuto della pagina
		
		//Preparo l'array per l'ordinamento dell'oggetto: $resultGroup
		$correctOrderGroup = orderResultMysqli($resultGroup, "Nome");
		$totalGroup = $resultGroup->num_rows; //equivalente a: "$totalGroup = count($correctOrderGroup);"
		
		//Preparo l'array per l'ordinamento dell'oggetto: $result
		$correctOrder = orderResultMysqli($result, "Nome", "AnnoRilascio");
		$total = $result->num_rows; //equivalente a: "$total = count($correctOrder);"
		$numRows=0;
		
		$tempPlatform="<ul class=\"navList\">\n";
		for($i=0; $i < $totalGroup; $i++)
		{
			//Seleziono tramite l'iteratore di "$resultGroup" la riga corretta (in base all'ordinamento effettuato)
			$posGroup = $correctOrderGroup[$i];
			$resultGroup->data_seek($posGroup);
			$rowGroup = $resultGroup->fetch_array(MYSQLI_ASSOC);
			
			//Seleziono tramite l'iteratore di "$result" la riga corretta (in base all'ordinamento effettuato)
			/* Questa operazione, di competenza del ciclo interno, mi è necessaria per impostare correttamente il link dei casi in cui: "versione == all"
				Ora:	la famiglia avrà IDPiattaforma coincidente a IDPiattaforma del primo membro della famiglia/gruppo e versione=all
						ogni membro della famiglia/gruppo avrà IDPiattaforma=versione (con versione!=all) */
			$pos = $correctOrder[$numRows];
			$result->data_seek($pos);
			$row = $result->fetch_array(MYSQLI_ASSOC);
			
			//Preparo il link generato dal ciclo esterno (caso: "versione == all")
			$paramLink = "showPlatformResults.php?piattaforma=". $row["IDPiattaforma"]. "&versione=all";
			$tempPlatform = $tempPlatform ."\t\t<li><a href=\"". $paramLink ."\">". $rowGroup["Nome"] ."</a>\n";
			$tempPlatform = $tempPlatform ."\t\t\t<ul>\n";
			
			while(($row["Nome"] == $rowGroup["Nome"]) && ($numRows < $total))
			{
				$paramLink = "showPlatformResults.php?piattaforma=". $row["IDPiattaforma"] ."&versione=". $row["IDPiattaforma"];
				$tempPlatform = $tempPlatform ."\t\t\t\t<li><a href=\"". $paramLink ."\">". $row["Nome"] ." ". $row["Versione"] ."</a></li>\n";
				
				$numRows++;
				
				if($numRows < $total)
				{
					$pos = $correctOrder[$numRows];
					$result->data_seek($pos);
					$row = $result->fetch_array(MYSQLI_ASSOC);
				}
				
				/* in alternativa questo while può essere riscritto come segue per evitare un controllo ad ogni giro:
					while(($row["Nome"] == $rowGroup["Nome"]))
					{
						[...]
						
						if($numRows < $total) //non è l'ultimo, devo procedere
						{
							[...]
						}
						else //ultimo, mi fermo
						{
							$row["Nome"] = true;
							$rowGroup["Nome"] = false;
						}
					}
				*/
			}
			
			$tempPlatform = $tempPlatform ."\t\t\t</ul>\n\t\t</li>\n";
		}
		$tempPlatform = $tempPlatform ."\t</ul>";
		
		$resultGroup->free();
		$result->free();
	}
	
	$conn->close();
	
	//Inserisco il content
	$page = str_replace("_CONTENT_", $tempPlatform, $page);
	
	//Completo e stampo la pagina
	$title = "Ricerca per piattaforma - Prism Game Reviews";
	$breadcrumb = "<li>Piattaforma</li>";
	$description = "Seleziona la piattaforma per la quale desideri visualizzare i videogiochi recensiti su Prism Game Reviews.";
	$keywords = "piattaforma,console,xbox,playstation,windows,recensioni,videogiochi,Prism,Prism Game Reviews";
	$pageH = new PageHeader($title, $breadcrumb, $description, $keywords, true, true);
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