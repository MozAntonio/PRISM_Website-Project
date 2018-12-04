<?php

/*	Questo script viene invocato per mostrare i GENERI per effettuare la ricerca di una recensione.
 *	Prende tutti i generi dal database e li stampa a video in un elenco composto da link
 *		se non ci sono stati errori presenta l'elenco di link;
 *		se ci sono stati errori critici lo comunica al posto del contenuto della pagina;
 *		se ci sono stati errori fatali stampa la pagina di avviso.
 */

try {
	//Controllo di avere tutti i file di cui necessito
	if (file_exists("../templates/pages/searchByGenre_page.txt") && file_exists("./utility.php"))
	{
		$page = file_get_contents("../templates/pages/searchByGenre_page.txt"); //carico il template
		require_once("./utility.php"); //la funzione di stampa
	}
	else
	{
		throw new Exception("searchByGenre_page.txt e utility.php", 1); //code=1 eccezione fatale
	}
	
	if (file_exists("./connection.php"))
		require_once("./connection.php");
	else
		throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (connection.php)");
	
	
	$conn=dbConnect();
	
	//Ottengo tutti i dati dei generi
	$query = "select * from GENERE;";
	
	if(!($result = $conn->query($query)))
	{
		throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> recuperando dal <span lang=\"en\">database</span> i generi per generare l'elenco di ricerca [#". $conn->errno ." - <span lang=\"en\">". $conn->error ."</span>]", 3); //code=3 --> connection open
	}
	else
	{
		//Se tutto è andato bene, posso procedere con la creazione del contenuto della pagina
		
		$correctOrder = orderResultMysqli($result, "Nome");
		$total = $result->num_rows; //equivalente a: "$total = count($correctOrder);"
		
		$tempGenre="<ul class=\"navList\">\n";
		for($i=0; $i < $total; $i++)
		{
			$pos = $correctOrder[$i];
			$result->data_seek($pos);
			$row = $result->fetch_array(MYSQLI_ASSOC);
			
			$paramLink = "showGenreResults.php?genere=". $row["IDGenere"];
			$tempGenre = $tempGenre ."\t\t<li><a href=\"". $paramLink ."\">". $row["Nome"] ."</a></li>\n";
		}
		$tempGenre = $tempGenre ."\t</ul>";
		
		$result->free();
	}
	
	$conn->close();
	
	//Inserisco il content
	$page = str_replace("_CONTENT_", $tempGenre, $page);
	
	//Completo e stampo la pagina
	$title = "Ricerca per genere - Prism Game Reviews";
	$breadcrumb = "<li>Genere</li>";
	$description = "Seleziona il genere per il quale desideri visualizzare i videogiochi recensiti su Prism Game Reviews.";
	$keywords = "genere,categoria,azione,avventura,sport,strategia,recensioni,videogiochi,Prism,Prism Game Reviews";
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