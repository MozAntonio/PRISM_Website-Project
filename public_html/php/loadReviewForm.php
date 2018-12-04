<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *																									 *
 *	CONTENUTO DEL FILE																				 *
 *	- (void) loadNewReviewForm(string &$page [, mysqli $conn = NULL]) ................. line: 15	 *
 *	- (string) loadEditReviewForm(string &$page, int $id [, mysqli $conn = NULL]) ..... line: 125	 *
 *																									 *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */





/*	Genera la parte dinamica della form d'INSERIMENTO recensione.
 *
 *	Richiede:
 *		$page stringa per riferimento che contenga le sub stringhe: _PLATFORM_ e _GENRE_
 *		$conn (opzionale) una connessione se il chiamante ne ha già una aperta da darmi
 *
 *	Comportamento atteso:
 *		tramite side-effect su $page, sostituisce _PLATFORM_ e _GENRE_ con quanto recuperato dal DB
 *
 *	Errori:
 *		Variabili & Risorse:
 *			$page risulta inalterata
 *			la connessione viene chiusa (se locale, inalterata altrimenti)
 *		Eccezioni:
 *			se una qualche query va male o se non trova i file di cui necessita
 *		Standard:
 *			non emette errori standard, ritorna sempre void
 */
function loadNewReviewForm(&$page, $conn = NULL) {

	if($conn == NULL)
	{
		if (file_exists("./connection.php"))
			require_once("./connection.php");
		else
			throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (connection.php)");
		
		$localConn=dbConnect();
		$isLocalConn=true;
	}
	else
	{
		$localConn=$conn;
		$isLocalConn=false;
	}
	
	try {
	
	$query = "select * from PIATTAFORMA;";
	
	if(!($result = $localConn->query($query)))
	{
		throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> per il recupero dal <span lang=\"en\">database</span> delle piattaforme per generare le <span lang=\"en\">checkbox</span> della <span lang=\"en\">form</span> Nuova recensione [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]");
	}
	else
	{
		$correctOrder = orderResultMysqli($result, "Nome", "AnnoRilascio");
		$total = $result->num_rows; //equivalente a: "$total = count($correctOrder);"
		
		$tempPlatform="";
		for($i=0; $i < $total; $i++)
		{
			$pos = $correctOrder[$i];
			$result->data_seek($pos);
			$row = $result->fetch_array(MYSQLI_ASSOC);
			
			$tempPlatform = $tempPlatform ."<div><input type=\"checkbox\" name=\"platform". $row["IDPiattaforma"] ."\" id=\"platform". $row["IDPiattaforma"] ."\" value=\"on\"/><label for=\"platform". $row["IDPiattaforma"] ."\">". $row["Nome"] ." ". $row["Versione"] ."</label></div>";
		}
		
		$result->free();
	}
	
	$query = "select * from GENERE;";
	
	if(!($result = $localConn->query($query)))
	{
		throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> per il recupero dal <span lang=\"en\">database</span> dei generi per generare le <span lang=\"en\">checkbox</span> della <span lang=\"en\">form</span> Nuova recensione [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]");
	}
	else
	{
		$correctOrder = orderResultMysqli($result, "Nome");
		$total = $result->num_rows; //equivalente a: "$total = count($correctOrder);"
		
		$tempGenre="";
		for($i=0; $i < $total; $i++)
		{
			$pos = $correctOrder[$i];
			$result->data_seek($pos);
			$row = $result->fetch_array(MYSQLI_ASSOC);
			
			$tempGenre = $tempGenre ."<div><input type=\"checkbox\" name=\"genre". $row["IDGenere"] ."\" id=\"genre". $row["IDGenere"] ."\" value=\"on\"/><label for=\"genre". $row["IDGenere"] ."\">". $row["Nome"] ."</label></div>";
		}
		
		$result->free();
	}
	
	if($isLocalConn)
		$localConn->close();
	
	//Procedo con le sostituzioni
	$page = str_replace("_PLATFORM_", $tempPlatform, $page);
	$page = str_replace("_GENRE_", $tempGenre, $page);
	
	return;
	
	} //chiude il try
	catch (Exception $e)
	{
		//mi occupo di chudere la connessione se mia nei casi di lancio eccezioni
		if($isLocalConn)
			$localConn->close();
		
		throw $e; //rilancio l'eccezione
	}
} //chiude la funzione loadNewReviewForm





/*	Genera la parte dinamica della form di MODIFICA recensione.
 *
 *	Richiede:
 *		$page stringa per riferimento che contenga le sub stringhe:	_ID_, _TITOLOGIOCO_, _COVERDESCR_,_SVILUPPATORE_, _ANNOUSCITA_, _PLATFORM_, _GENRE_, _SITO_, _TITOLORECENSIONE_, _DESCRRECENSIONE_ e _RECENSIONE_
 *		$id intero identificatore del gioco e della recensione di cui si vogliono recuperare i dati
 *		$conn (opzionale) una connessione se il chiamante ne ha già una aperta da darmi
 *
*	Comportamento atteso:
 *		tramite side-effect su $page, sostituisce le sub stringhe con i dati associati a $id recuperati dal DB, e ritorna stringa vuota.
 *
 *	Errori:
 *		Variabili & Risorse:
 *			$page risulta inalterata
 *			la connessione viene chiusa (se locale, inalterata altrimenti)
 *		Eccezioni:
 *			se una qualche query fallisce o se non trova i file di cui necessita
 *		Standard:
 *			ritorna una stringa d'errore nel caso non venga trovato un gioco e una recensione corrispondeti a $id
 */
function loadEditReviewForm(&$page, $id, $conn = NULL) {
	
	if($conn == NULL)
	{
		if (file_exists("./connection.php"))
			require_once("./connection.php");
		else
			throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (connection.php)");
		
		$localConn=dbConnect();
		$isLocalConn=true;
	}
	else
	{
		$localConn=$conn;
		$isLocalConn=false;
	}
	
	try {
	
	//Prendo i dati del gioco e della recensione dal database, grazie all'ID ricevuto
	
	//Dati della recensione:
	$queryReview = "select * from RECENSIONE where ID=". $id .";";
	
	if(!($result = $localConn->query($queryReview)))
	{
		throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $queryReview ."</code> per il recupero dal <span lang=\"en\">database</span> dei dati della recensione (". $id .") per riempire i <span lang=\"en\">value</span> da modificare nella <span lang=\"en\">form</span> Modifica recensione [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]");
	}
	elseif(!($result->num_rows > 0))
	{
		$result->free();
		
		if($isLocalConn)
			$localConn->close();
		
		$errore="<h1>Errore</h1>\n<p>Non è stata trovata nessuna recensione corrispondente all'identificatore ". $id .", pertanto la modifica non è possibile.</p>";
		//Restituisco al chiamante l'errore
		return $errore;
	}
	
	$rowReview = $result->fetch_array(MYSQLI_ASSOC);
	$result->free();
	
	//Dati del gioco:
	$queryGame = "select * from GIOCO where IDGioco=". $id .";";
	
	if(!($result = $localConn->query($queryGame)))
	{
		throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $queryGame ."</code> per il recupero dal <span lang=\"en\">database</span> dei dati del gioco (". $id .") per riempire i <span lang=\"en\">value</span> da modificare nella <span lang=\"en\">form</span> Modifica recensione [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]");
	}
	elseif(!($result->num_rows > 0))
	{
		$result->free();
		
		if($isLocalConn)
			$localConn->close();
		
		$errore="<h1>Errore</h1>\n<p>Non è stato trovato nessun gioco associato alla recensione con identificatore ". $id .", pertanto la modifica non è possibile.</p>";
		//Restituisco al chiamante l'errore
		return $errore;
	}
	
	$rowGame = $result->fetch_array(MYSQLI_ASSOC);
	$result->free();
	
	//Dati sulle piattaforme:
	
	//Tabella PIATTAFORMA:
	$queryAllPlatform = "select * from PIATTAFORMA;";
	//Tabella ESECUZIONE:
	$querySelectedPlatform = "select * from ESECUZIONE where IDGioco=". $id .";";
	
	if(!($resultAll = $localConn->query($queryAllPlatform)))
	{
		throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $queryAllPlatform ."</code> per il recupero dal <span lang=\"en\">database</span> delle piattaforme per generare le <span lang=\"en\">checkbox</span> della <span lang=\"en\">form</span> Modifica recensione [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]");
	}
	
	if(!($resultSelected = $localConn->query($querySelectedPlatform)))
	{
		throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $querySelectedPlatform ."</code> per rimettere le spunte da modificare nelle <span lang=\"en\">checkbox</span> delle piattaforme della <span lang=\"en\">form</span> Modifica recensione [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]");
	}
	
	$correctOrder = orderResultMysqli($resultAll, "Nome", "AnnoRilascio");
	$total = $resultAll->num_rows; //equivalente a: "$total = count($correctOrder);"
	
	$tempPlatform="";
	for($i=0; $i < $total; $i++)
	{
		$pos = $correctOrder[$i];
		$resultAll->data_seek($pos);
		$rowAll = $resultAll->fetch_array(MYSQLI_ASSOC);
		
		$tempPlatform = $tempPlatform ."<div><input type=\"checkbox\" name=\"platform". $rowAll["IDPiattaforma"] ."\" id=\"platform". $rowAll["IDPiattaforma"] ."\" value=\"on\"";
		
		$resultSelected->data_seek(0); //riazzero l'iteratore di $resultSelected
		
		$stop=false;
		while(!($stop) && ($rowSelected = $resultSelected->fetch_array(MYSQLI_ASSOC)))
		{
			if($rowAll["IDPiattaforma"]==$rowSelected["IDPiattaforma"])
				$stop = true;
		}
		
		if($stop)
			$tempPlatform = $tempPlatform ." checked=\"checked\"";
		
		$tempPlatform = $tempPlatform ."/><label for=\"platform". $rowAll["IDPiattaforma"] ."\">". $rowAll["Nome"] ." ". $rowAll["Versione"] ."</label></div>";
	}
	
	$resultAll->free();
	$resultSelected->free();
	
	//Dati sui generi:
	
	//Tabella GENERE:
	$queryAllGenre = "select * from GENERE;";
	//Tabella APPARTENENZA:
	$querySelectedGenre = "select * from APPARTENENZA where IDGioco=". $id .";";
	
	if(!($resultAll = $localConn->query($queryAllGenre)))
	{
		throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $queryAllGenre ."</code> per il recupero dal <span lang=\"en\">database</span> dei generi per generare le <span lang=\"en\">checkbox</span> della <span lang=\"en\">form</span> Modifica recensione [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]");
	}
	
	if(!($resultSelected = $localConn->query($querySelectedGenre)))
	{
		throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $querySelectedGenre ."</code> per rimettere le spunte da modificare nelle <span lang=\"en\">checkbox</span> dei generi della <span lang=\"en\">form</span> Modifica recensione [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]");
	}
	
	$correctOrder = orderResultMysqli($resultAll, "Nome");
	$total = $resultAll->num_rows; //equivalente a: "$total = count($correctOrder);"
	
	$tempGenre="";
	for($i=0; $i < $total; $i++)
	{
		$pos = $correctOrder[$i];
		$resultAll->data_seek($pos);
		$rowAll = $resultAll->fetch_array(MYSQLI_ASSOC);
		
		$tempGenre = $tempGenre ."<div><input type=\"checkbox\" name=\"genre". $rowAll["IDGenere"] ."\" id=\"genre". $rowAll["IDGenere"] ."\" value=\"on\"";
		
		$resultSelected->data_seek(0); //riazzero l'iteratore di $resultSelected
		
		$stop=false;
		while(!($stop) && ($rowSelected = $resultSelected->fetch_array(MYSQLI_ASSOC)))
		{
			if($rowAll["IDGenere"]==$rowSelected["IDGenere"])
				$stop = true;
		}
		
		if($stop)
			$tempGenre = $tempGenre ." checked=\"checked\"";
		
		$tempGenre = $tempGenre ."/><label for=\"genre". $rowAll["IDGenere"] ."\">". $rowAll["Nome"] ."</label></div>";
	}
	
	$resultAll->free();
	$resultSelected->free();
	
	//Sito Ufficiale: se non era stato inserito lo risetto a stringa vuota
	if($rowGame["SitoUfficiale"]=="ND")
		$rowGame["SitoUfficiale"]="";
	
	//PEGI: Preparo le variabili per impostare il corretto valore
	$toReplace = "<option value=\"". $rowGame["Pegi"] ."\">". $rowGame["Pegi"] ."</option>";
	$replace = "<option value=\"". $rowGame["Pegi"] ."\" selected=\"selected\">". $rowGame["Pegi"] ."</option>";
	
	//Ciclo che prima di caricare la form di modifica fa l'escape delle " per i campi di Recensione
	foreach ($rowReview as $key => &$value)
		$value=str_replace("\"", "&quot;", $value);
	
	//Ciclo che prima di caricare la form di modifica fa l'escape delle " per i campi di Gioco
	foreach ($rowGame as $key => &$value)
		$value=str_replace("\"", "&quot;", $value);
	
	//Sostituisco
	$page = str_replace("_ID_", $id, $page);
	$page = str_replace("_TITOLOGIOCO_", $rowGame["Titolo"], $page);
	$page = str_replace("_COVERDESCR_", $rowGame["CoverDescr"], $page);
	$page = str_replace("_SVILUPPATORE_", $rowGame["Sviluppatore"], $page);
	$page = str_replace("_ANNOUSCITA_", $rowGame["AnnoUscita"], $page);
	$page = str_replace("_PLATFORM_", $tempPlatform, $page);
	$page = str_replace("_GENRE_", $tempGenre, $page);
	$page = str_replace("_SITO_", $rowGame["SitoUfficiale"], $page);
	$page = str_replace($toReplace, $replace, $page); //pegi
	
	$page = str_replace("_TITOLORECENSIONE_", $rowReview["Titolo"], $page);
	$page = str_replace("_DESCRRECENSIONE_", $rowReview["DescrizioneHTML"], $page);
	$page = str_replace("_RECENSIONE_", $rowReview["Contenuto"], $page);
	
	
	if($isLocalConn)
		$localConn->close();

	return ""; //tutto ok, ritorno stringa vuota

	} //chiude il try
	catch (Exception $e)
	{
		//mi occupo di chudere la connessione se mia nei casi di lancio eccezioni
		if($isLocalConn)
			$localConn->close();
		
		throw $e; //rilancio l'eccezione
	}	
} //chiude la funzione loadEditReviewForm
?>