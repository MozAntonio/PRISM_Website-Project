<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *																									 *
 *	CONTENUTO DEL FILE																				 *
 *	- (string) checkReviewForm(string $modo [, int &$id [, mysqli $conn = NULL]]) ..... line: 14	 *
 *																									 *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */





/*	Controlla i dati iviati tramite le form d'INSERIMENTO e di MODIFICA recensione.
 *
 *	Richiede:
 *		$modo una stringa indica alla funzione se i dati arrivano dalla form d'inserimento o da quella di modifica
 *			valori permessi (case sensitive): "inserisci" o "modifica"
 *		$id intero identificatore del gioco e della recensione che si vogliono modificare (per riferimento)
 *			- se modo=inserisci allora $id è una variabile vuota, e:
 *				-- in caso di successo (return == "") $id è usato per restituire al chiamante (via side-effect) l'id della nuova recensione
 *				-- in caso di errore (eccezione o return != "") nulla si può dire sul suo stato
 *			- se modo=modifica allora $id è usato in sola lettura (no side-effect, neanche in caso di errore o eccezione)
 *		$conn (opzionale) una connessione se il chiamante ne ha già una aperta da darmi
 *
 *	Comportamento atteso:
 *		controlla i dati provenienti dalle form di recensione, e ritorna stringa vuota.
 *
 *	Errori:
 *		Variabili & Risorse:
 *			la connessione viene chiusa (se locale, inalterata altrimenti)
 *		Eccezioni:
 *			se una qualche query va male o se non trova i file di cui necessita
 *		Standard:
 *			ritorna una stringa con l'elenco degli errori
 */
function checkReviewForm($modo, &$id, $conn = NULL) {
	
	//Se non mi hanno dato una connessione me ne apro e gestisco una
	if($conn == NULL)
	{
		if (file_exists("./connection.php"))
			require_once("./connection.php");
		else
			throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (connection.php)");
		
		$localConn=dbConnect();
		$isLocalConn=true;
	}
	else //altrimenti uso quella passatami
	{
		$localConn=$conn;
		$isLocalConn=false;
	}
	
	try {
	
	//Preparo la variabile per gli errori
	$errore="";
	
	//Check di tutti i campi (tranne piattaforme, generi e cover), solo il campo sito è opzionale
	if (empty($_POST["titoloGioco"]) || empty($_POST["coverDescr"]) || empty($_POST["sviluppatore"]) || empty($_POST["annoUscita"]) || empty($_POST["pegi"]) || empty($_POST["titoloRecensione"]) || empty($_POST["descrRecensione"]) || empty($_POST["recensione"]) || !(isset($_POST["sito"])))
	{
		$errore = "<li>I seguenti campi sono obbligatori:<ul><li>Titolo del gioco</li><li>Descrizione copertina</li><li>Sviluppatore</li><li>Anno di pubblicazione</li><li><acronym title=\"Pan European Game Information\">PEGI</acronym></li><li>Titolo della recensione</li><li>Breve descrizione della recensione</li><li>Recensione</li></ul></li>";
	}
	else
	{
		//Check delle piattaforme, almeno una è obbligatoria
		$listPlatform="";
		if(empty($_POST["jsPiattaforma"]))
		{
			$query = "select * from PIATTAFORMA;";
			
			if (!($result = $localConn->query($query)))
			{
				throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> recuperando le piattaforme nel <span lang=\"en\">database</span> per controllare che ne sia stata selezionata almeno una [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]");
			}
			else
			{
				$correctOrder = orderResultMysqli($result, "Nome", "AnnoRilascio");
				$total = $result->num_rows; //equivalente a: "$total = count($correctOrder);"
				
				for($i=0; $i < $total; $i++)
				{
					$pos = $correctOrder[$i];
					$result->data_seek($pos);
					$row = $result->fetch_array(MYSQLI_ASSOC);
					
					if(!(empty($_POST["platform". $row["IDPiattaforma"]])) && ($_POST["platform". $row["IDPiattaforma"]]=="on"))
					{
						if($listPlatform == "") //prima trovata
							$listPlatform = $row["IDPiattaforma"];
						else //le eventuali altre
							$listPlatform = $listPlatform .",". $row["IDPiattaforma"];
					}
				}
				
				$result->free();
			}
		}
		else
		{
			/*	NOTA:
				Nel caso che venga implementata la gestione, tramite JavaScript, del dropdown menu che compone una textarea, allora:
					- la lista di "platform . IDPiattaforma" non è garantito che sia già ordinata in base alla coppia: "Nome-AnnoRilascio";
					- o si ordina tramite JavaScript, oppure si ordina qui, prima dell'istruzione: $listPlatform=$_POST["jsPiattaforma"].
			*/
			$listPlatform=$_POST["jsPiattaforma"];
		}
		
		if($listPlatform == "")
			$errore = $errore ."<li>Il campo piattaforme è obbligatorio: selezionare almeno una piattaforma.</li>";
		
		//Check dei generi, almeno uno è obbligatorio
		$listGenre="";
		if(empty($_POST["jsGenere"]))
		{
			$query = "select * from GENERE;";
			
			if (!($result = $localConn->query($query)))
			{
				throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> recuperando i generi nel <span lang=\"en\">database</span> per controllare che ne sia stato selezionato almeno uno [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]");
			}
			else
			{
				$correctOrder = orderResultMysqli($result, "Nome");
				$total = $result->num_rows; //equivalente a: "$total = count($correctOrder);"
				
				for($i=0; $i < $total; $i++)
				{
					$pos = $correctOrder[$i];
					$result->data_seek($pos);
					$row = $result->fetch_array(MYSQLI_ASSOC);
					
					if(!(empty($_POST["genre". $row["IDGenere"]])) && ($_POST["genre". $row["IDGenere"]]=="on"))
					{
						if($listGenre == "") //primo trovato
							$listGenre = $row["IDGenere"];
						else //gli eventuali altri
							$listGenre = $listGenre .",". $row["IDGenere"];
					}
				}
				
				$result->free();
			}
		}
		else
		{
			/*	NOTA:
				Nel caso che venga implementata la gestione, tramite JavaScript, del dropdown menu che compone una textarea, allora:
					- la lista di "genre . IDGenere" non è garantito che sia già ordinata in base a: "Nome";
					- o si ordina tramite JavaScript, oppure si ordina qui, prima dell'istruzione: $listGenre=$_POST["jsGenere"].
			*/
			$listGenre=$_POST["jsGenere"];
		}
		
		if($listGenre == "")
			$errore = $errore ."<li>Il campo generi è obbligatorio: selezionare almeno un genere.</li>";
		
		//Check della cover (se modo=inserisci deve aver selezionato un file, se modo=modifica è opzionale)
		if($_FILES["coverImg"]["name"] == "")
		{
			if($modo != "modifica")
				$errore = $errore ."<li>Il campo immagine di copertina è obbligatorio: selezionare un <span lang=\"en\">file</span>.</li>";
			else
				$existsCoverImage = false;
		}
		else
		{
				$existsCoverImage = true;
		}
	}
	
	//Se qualche campo obbligatorio non è stato compilato ritorno l'errore 
	if($errore != "")
	{
		$errore = "<ul>". $errore ."</ul>";
		
		if($isLocalConn)
			$localConn->close();
		
		return $errore;
	}
	else //Tutti i campi obbligatori sono stati compilati
	{
		//Ciclo che esegue per ogni campo una trim sugli eventuali spazi che precedono o terminano la stringa
		foreach ($_POST as $key => &$value)
			$value=trim($value);
		
		 //Se devo inserire una nuova recensione
		if($modo != "modifica")
		{
			//Recupero dal DB l'id dell'ultima recensione inserita
			$query = "select ID from RECENSIONE order by ID desc limit 1;";
			
			if (!($result = $localConn->query($query)))
			{
				throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> per recuperare l'<abbr title=\"identificatore\">ID</abbr> dell'ultima recensione inserita [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]");
			}
			else
			{
				if($result->num_rows > 0)
					//Prendo l'ultimo id e sommo 1
					$id = $result->fetch_array(MYSQLI_ASSOC)["ID"] + 1;
				else
					//Prima recensione inserita
					$id = 1;
				
				$result->free();
			}
		}
		//Altrimenti $id mi viene passato come parametro
		//Sarebbe buona cosa fare un controllo, senza controlli se $id è NULL o non c'è nel DB la modifica procede e si conclude in un nulla di fatto senza errori
		
		
		if (file_exists("./classGioco.php"))
			require_once("./classGioco.php"); //per poter usare un oggetto 'Gioco'
		else
			throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (classGioco.php)");
		
		if (file_exists("./classRecensione.php"))
			require_once("./classRecensione.php"); //per poter usare un oggetto 'Recensione'
		else
			throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (classRecensione.php)");
		
		//Creo un nuovo oggetto 'Gioco'
		$game = new Gioco($_POST["titoloGioco"], $_POST["coverDescr"], $_POST["sviluppatore"], $_POST["annoUscita"], $listPlatform, $listGenre, $_POST["sito"], $_POST["pegi"], $existsCoverImage, $localConn);
		
		//Preparo l'array di keywords, sia quelle parametriche, sia quelle statiche (in totale: 9)
		//Nota: Il numero di keywords (consigliato) è: <= 10
		$listKeywords = array(strtolower(htmlspecialchars(strip_tags($_POST["titoloGioco"]), ENT_QUOTES | ENT_XHTML, "UTF-8")), "recensione", strtolower(htmlspecialchars(strip_tags($_POST["sviluppatore"]), ENT_QUOTES | ENT_XHTML, "UTF-8")), $_POST["annoUscita"], "videogioco", "review", "game", "Prism");		
		
		//Creo un nuovo oggetto 'Recensione' passandogli anche l'oggetto 'Gioco'
		$review = new Recensione($game, $_POST["titoloRecensione"], $_POST["descrRecensione"], $_POST["recensione"], $listKeywords);
		
		//Se non si sono verificati errori (nella recensione e nel gioco annesso)
		if($review == "")
		{
			if($modo == "modifica")
				$errore = $review->updateInDB($id); //modifica nel DB dei dati della nuova recensione (e del gioco annesso)
			else
				$errore = $review->insertInDB($id); //inserimento nel DB dei dati della nuova recensione (e del gioco annesso)
		}
		else
		{
			$errore = "<p>Si è verificato un problema con i dati inseriti: </p>". $review;
		}
		
		if($isLocalConn)
			$localConn->close();
		
		/* $errore è:
			o vuoto se non ci sono stati errori
			o contiene gli errori di costruzione degli oggetti
			o contiene gli errori generati durante l'inserimento (o l'aggiornamento)
		*/
		return $errore;
	}
	} //chiude il try
	catch (Exception $e)
	{
		//mi occupo di chudere la connessione se mia nei casi di lancio eccezioni
		if($isLocalConn)
			$localConn->close();
		
		throw $e; //rilancio l'eccezione
	}
} //chiude la funzione
?>