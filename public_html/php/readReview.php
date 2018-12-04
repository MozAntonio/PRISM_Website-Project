<?php

/*	Questo script viene invocato per mostrare la recensione di un determinato gioco, e gli eventuali commenti ad essa relativi
 *	Se in GET non è stato passato alcun valore per l'id, oppure viene passato un valore errato, mostra un messaggio di errore
 *	Se in GET non sono stati passati i valori di "modo" e "ricerca", oppure vengono passati valori errati, allora verrà impostato il breadcrumb di default
 *	Prende tutti i dati riguardanti la recensione (dati del gioco e dati della recensione) e li stampa a video
 *		se non ci sono stati errori
 *			e l'utente è loggato: verrà mostrato anche il suo commento (o la form di inserimento), e i commenti degli altri utenti (paginati ogni 24);
 *			e l'utente è loggato come amministratore: vale lo stesso degli utenti standard ed in più c'è il pulsante di modifica recensione;
 *			e l'utente non è loggato: al posto dei commenti c'è un pulsante che invita l'utente a loggarsi per poter leggere i commenti;
 *		se ci sono stati errori critici lo comunica al posto del contenuto della pagina;
 *		se ci sono stati errori fatali stampa la pagina di avviso.
 */

try {
	//Controllo di avere tutti i file di cui necessito
	if (file_exists("../templates/pages/readReview_page.txt") && file_exists("./utility.php"))
	{
		$page = file_get_contents("../templates/pages/readReview_page.txt"); //carico il template
		require_once("./utility.php"); //le funzioni di stampa e isLogged
	}
	else
	{
		throw new Exception("readReview_page.txt e utility.php", 1); //code=1 eccezione fatale
	}
	
	$errore="";
	$breadcrumb="";
	
	if(!(empty($_GET["id"])) && ctype_digit($_GET["id"]))
	{
		if (file_exists("./connection.php"))
			require_once("./connection.php");
		else
			throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (connection.php)");
		
		
		$conn=dbConnect();
		
		//Dati della recensione:
		$query = "select * from RECENSIONE where ID=". $_GET["id"] .";";
		
		if(!($result = $conn->query($query)))
		{
			throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> per il recupero dei dati della recensione (". $_GET["id"] .") da stampare dal <span lang=\"en\">database</span> [#". $conn->errno ." - <span lang=\"en\">". $conn->error ."</span>]", 3); //code=3 --> connection open
		}
		
		if($result->num_rows > 0)
		{
			$rowReview = $result->fetch_array(MYSQLI_ASSOC);
			$result->free();
			
			//Dati del gioco:
			$query = "select * from GIOCO where IDGioco=". $_GET["id"] .";";
			
			if(!($result = $conn->query($query)))
			{
				throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> per il recupero dei dati del gioco (". $_GET["id"] .") da stampare dal <span lang=\"en\">database</span> [#". $conn->errno ." - <span lang=\"en\">". $conn->error ."</span>]", 3); //code=3 --> connection open
			}
			
			$rowGame = $result->fetch_array(MYSQLI_ASSOC);
			$result->free();
			
			//Dati sulle piattaforme:
			$query = "select * from ESECUZIONE E join PIATTAFORMA P on (E.IDPiattaforma=P.IDPiattaforma) where E.IDGioco=". $_GET["id"] .";";
			
			if(!($result = $conn->query($query)))
			{
				throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> per il recupero delle piattaforme del gioco (". $_GET["id"] .") da stampare dal <span lang=\"en\">database</span> [#". $conn->errno ." - <span lang=\"en\">". $conn->error ."</span>]", 3); //code=3 --> connection open
			}
			else
			{
				$correctOrder = orderResultMysqli($result, "Nome", "AnnoRilascio");
				$total = $result->num_rows;
				
				$tempPlatform="";
				for($i=0; $i < $total; $i++)
				{
					$pos = $correctOrder[$i];
					$result->data_seek($pos);
					$row = $result->fetch_array(MYSQLI_ASSOC);
					
					if($i < ($total-1))
						$tempPlatform = $tempPlatform . $row["Nome"] ."-". $row["Versione"] .", ";
					else
						$tempPlatform = $tempPlatform . $row["Nome"] ."-". $row["Versione"];
				}
				
				$result->free();
			}
			
			//Dati sui generi:
			$query = "select * from APPARTENENZA A join GENERE G on (A.IDGenere=G.IDGenere) where A.IDGioco=". $_GET["id"] .";";
			
			if(!($result = $conn->query($query)))
			{
				throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> per il recupero dei generi del gioco (". $_GET["id"] .") da stampare dal <span lang=\"en\">database</span> [#". $conn->errno ." - <span lang=\"en\">". $conn->error ."</span>]", 3); //code=3 --> connection open
			}
			else
			{
				$correctOrder = orderResultMysqli($result, "Nome");
				$total = $result->num_rows;
				
				$tempGenre="";
				for($i=0; $i < $total; $i++)
				{
					$pos = $correctOrder[$i];
					$result->data_seek($pos);
					$row = $result->fetch_array(MYSQLI_ASSOC);
					
					if($i < ($total-1))
						$tempGenre = $tempGenre . $row["Nome"] .", ";
					else
						$tempGenre = $tempGenre . $row["Nome"];
				}
				
				$result->free();
			}
			
			
			//Controllo se l'account dell'admin creatore della recensione è ancora attivo
			$query = "select Eliminato from UTENTE where Username='". $rowReview["Autore"] ."';";
			
			if(!($result = $conn->query($query)))
			{
				throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> recuperando lo stato dell'autore della recensione (". $_GET["id"] .") da stampare dal <span lang=\"en\">database</span> [#". $conn->errno ." - <span lang=\"en\">". $conn->error ."</span>]", 3); //code=3 --> connection open
			}
			
			if($result->fetch_array(MYSQLI_ASSOC)["Eliminato"] == true)
				$rowReview["Autore"] = $rowReview["Autore"] . "_eliminato";
			
			$result->free();
			
			//Se la recensione è stata modificata almeno una volta, devo aggiungere l'autore e la data dell'ultima modifica
			//In tal caso controllo se l'account dell'admin della modifica della recensione è ancora attivo
			if(($rowReview["AutoreModifica"] != NULL) && ($rowReview["DataModifica"] != NULL))
			{
				$query = "select Eliminato from UTENTE where Username='". $rowReview["AutoreModifica"] ."';";
				
				if(!($result = $conn->query($query)))
				{
					throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> recuperando lo stato dell'autore della modifica alla recensione (". $_GET["id"] .") da stampare dal <span lang=\"en\">database</span> [#". $conn->errno ." - <span lang=\"en\">". $conn->error ."</span>]", 3); //code=3 --> connection open
				}
				
				if($result->fetch_array(MYSQLI_ASSOC)["Eliminato"] == true)
					$rowReview["AutoreModifica"] = $rowReview["AutoreModifica"] . "_eliminato";
				
				$result->free();
				
				$authorModifier = "<dl class=\"publisher\">\n\t\t\t\t\t<dt>Ultima modifica:</dt>\n\t\t\t\t\t\t<dd><time datetime=\"". $rowReview["DataModifica"] ."\">". date_format(date_create($rowReview["DataModifica"]), "d-m-Y") ."</time></dd>\n\t\t\t\t\t<dt>di:</dt>\n\t\t\t\t\t\t<dd>". $rowReview["AutoreModifica"] ."</dd>\n\t\t\t\t</dl>";
			}
			else
			{
				$authorModifier = "";
			}
			
			
			//Controllo che l'immagine di copertina ci sia nel server
			if(file_exists("../images/covers/". $_GET["id"] .".". $rowGame["CoverExt"]))
				$coverDim = getimagesize("../images/covers/". $_GET["id"] .".". $rowGame["CoverExt"])[3];
			else
				$coverDim = "";
			
			//Valore di 'SitoUfficiale'
			if($rowGame["SitoUfficiale"] == "ND")
				$officialSite = "<abbr title=\"non definito\">ND</abbr>";
			else
				$officialSite = "<a href=\"". $rowGame["SitoUfficiale"] ."\" rel=\"external nofollow\" target=\"_blank\" title=\"apri il sito ufficiale in una nuova finestra\">". explode("://", $rowGame["SitoUfficiale"])[1] ."</a>";
			
			//Valore di 'DataPubblicazione'
			$issueDate = "<time datetime=\"". $rowReview["DataPubblicazione"] ."\">". date_format(date_create($rowReview["DataPubblicazione"]), "d-m-Y") ."</time>";
			
			//Valore di 'TempoLettura'
			if($rowReview["TempoLettura"] == 1)
				$endMinuteWord = "o";
			else
				$endMinuteWord = "i";
			
			$readingTime = "<time datetime=\"PT". $rowReview["TempoLettura"] ."M\">". $rowReview["TempoLettura"] ." minut". $endMinuteWord ."</time>";
			
			
			//Prepariamo il 'breadcrumb'
			$isDefault = true;
			
			if(!(empty($_GET["modo"])) && ctype_digit($_GET["modo"]) && !(empty($_GET["ricerca"])))
			{
				switch ($_GET["modo"])
				{
					//Caso 1: Si è effettuata una ricerca tramite 'Piattaforma'
					case 1:
						
						$platform = explode("-", $_GET["ricerca"]);
						
						if(!(empty($platform[0])) && ctype_digit($platform[0]) && !(empty($platform[1])) && ((strtolower($platform[1]) == "all") || (ctype_digit($platform[1]))))
						{
							//NOTA: Se il parametro $platform[1] è diverso da "all", allora il parametro $platform[0] non viene mai utilizzato
							
							if(strtolower($platform[1]) == "all")
							{
								//Ottengo dal database il record che corrisponde alla famiglia/categoria di piattaforme con ID: $_GET["ricerca"]
								$query = "select Nome from PIATTAFORMA where IDPiattaforma=". $platform[0] ." group by Nome;";
							}
							else
							{
								//Ottengo dal database il record che corrisponde alla piattaforma (con versione annessa) con ID: $_GET["ricerca"]
								$query = "select * from PIATTAFORMA where IDPiattaforma=". $platform[1] .";";
							}
							if(!($result = $conn->query($query)))
							{
								throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> recuperando dal <span lang=\"en\">database</span> la piattaforma (con l'eventuale versione) selezionata per costruire il <span lang=\"en\">breadcrumb</span> [#". $conn->errno ." - <span lang=\"en\">". $conn->error ."</span>]", 3); //code=3 --> connection open
							}
							
							if($result->num_rows > 0)
							{
								$row = $result->fetch_array(MYSQLI_ASSOC);
								$result->free();
								
								$validPlatform = explode(", ", $tempPlatform);
								$success = false;
								
								for($i=0; (!($success)) && ($i < count($validPlatform)); $i++)
								{
									$temp = explode("-", $validPlatform[$i]);
									
									if(strtolower($platform[1]) == "all")
									{
										if($row["Nome"] == $temp[0])
											$success = true;
									}
									else
									{
										if(($row["Nome"] == $temp[0]) && ($row["Versione"] == $temp[1]))
											$success = true;
									}
								}
								
								if($success)
								{
									if(strtolower($platform[1]) == "all")
										$breadcrumb = "<li><a href=\"searchByPlatform.php\">Piattaforma</a></li>\n\t\t<li><a href=\"showPlatformResults.php?piattaforma=". $platform[0] ."&versione=". strtolower($platform[1]) ."\">". $row["Nome"] ."</a></li>\n\t\t<li class=\"lastChildBreadcrumb\">". $rowGame["Titolo"] ."</li>";
									else
										$breadcrumb = "<li><a href=\"searchByPlatform.php\">Piattaforma</a></li>\n\t\t<li><a href=\"showPlatformResults.php?piattaforma=". $platform[0] ."&versione=". $platform[1] ."\">". $row["Nome"] ." ". $row["Versione"] ."</a></li>\n\t\t<li class=\"lastChildBreadcrumb\">". $rowGame["Titolo"] ."</li>";
									
									$getModo = $_GET["modo"];
									$getRicerca = strtolower($_GET["ricerca"]);
									$isDefault = false;
								}
							}
						}
						
						break;
					
					//Caso 2: Si è effettuata una ricerca tramite 'Genere'
					case 2:
						
						if(ctype_digit($_GET["ricerca"]))
						{
						
							//Ottengo dal database il record che corrisponde al genere con ID: $_GET["ricerca"]
							$query = "select * from GENERE where IDGenere=". $_GET["ricerca"] .";";
							
							if(!($result = $conn->query($query)))
							{
								throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> recuperando dal <span lang=\"en\">database</span> il genere selezionato per costruire il <span lang=\"en\">breadcrumb</span> [#". $conn->errno ." - <span lang=\"en\">". $conn->error ."</span>]", 3); //code=3 --> connection open
							}
							
							if($result->num_rows > 0)
							{
								$row = $result->fetch_array(MYSQLI_ASSOC);
								$result->free();
								
								$validGenre = explode(", ", $tempGenre);
								$success = false;
								
								for($i=0; (!($success)) && ($i < count($validGenre)); $i++)
								{
									if($row["Nome"] == $validGenre[$i])
										$success = true;
								}
								
								if($success)
								{
									$breadcrumb = "<li><a href=\"searchByGenre.php\">Genere</a></li>\n\t\t<li><a href=\"showGenreResults.php?genere=". $_GET["ricerca"] ."\">". $row["Nome"] ."</a></li>\n\t\t<li class=\"lastChildBreadcrumb\">". $rowGame["Titolo"] ."</li>";
									
									$getModo = $_GET["modo"];
									$getRicerca = $_GET["ricerca"];
									$isDefault = false;
								}
							}
						}
						
						break;
				}
			}
			
			//Caso 3: Si è effettuata una ricerca tramite 'A-Z' ($_GET["modo"] == 3)
			//Oppure i parametri passati tramite GET non ci sono, o non sono validi
			if($isDefault)
			{
				if(!(empty($_GET["modo"])) && ($_GET["modo"] == 3) && !(empty($_GET["ricerca"])) && (strtolower($_GET["ricerca"]) == "all"))
				{
					$firstGameTitleChar = "all";
					$caseSensChar = "Mostra Tutto";
				}
				else
				{
					$firstGameTitleChar = strtolower(substr($rowGame["TitoloOrdinamento"], 0, 1));
					
					if(!(ctype_alpha($firstGameTitleChar)))
					{
						$firstGameTitleChar = "numeri%20e%20simboli";
						$caseSensChar = "Numeri e Simboli";
					}
					else
					{
						$caseSensChar = "Lettera ". strtoupper($firstGameTitleChar);
					}
				}
				
				$breadcrumb = "<li><a href=\"../searchByAZ.html\"><abbr title=\"Lettera dell'Alfabeto\">A-Z</abbr></a></li>\n\t\t<li><a href=\"showAZResults.php?carattere=". $firstGameTitleChar ."\">". $caseSensChar ."</a></li>\n\t\t<li class=\"lastChildBreadcrumb\">". $rowGame["Titolo"] ."</li>";
				
				$getModo = 3;
				$getRicerca = $firstGameTitleChar;
			}
			
			
			//Prepariamo l'header
			$pageH = new PageHeader($rowGame["TitoloOrdinamento"] ." - Recensione Prism", $breadcrumb, $rowReview["DescrizioneHTML"], $rowReview["Keywords"], true, true);
			
			
			//Prepariamo il content
			$page = str_replace("_TITOLOGIOCO_", $rowGame["Titolo"], $page);
			$page = str_replace("_COVERFILE_", $_GET["id"] .".". $rowGame["CoverExt"], $page);
			$page = str_replace("_COVERTITOLOGIOCO_", $rowGame["TitoloOrdinamento"], $page);
			$page = str_replace("_COVERDIM_", $coverDim, $page);
			$page = str_replace("_COVERDESCR_", $rowGame["CoverDescr"], $page);
			$page = str_replace("_SVILUPPATORE_", $rowGame["Sviluppatore"], $page);
			$page = str_replace("_ANNOUSCITA_", $rowGame["AnnoUscita"], $page);
			
			$tempPlatform = str_replace("-", " ", $tempPlatform);
			$page = str_replace("_PLATFORM_", $tempPlatform, $page);
			
			$page = str_replace("_GENRE_", $tempGenre, $page);
			$page = str_replace("_SITO_", $officialSite, $page);
			$page = str_replace("_PEGI_", $rowGame["Pegi"], $page);
			
			$page = str_replace("_TITOLORECENSIONE_", $rowReview["Titolo"], $page);
			$page = str_replace("_DATAPUBB_", $issueDate, $page);
			$page = str_replace("_AUTORE_", $rowReview["Autore"], $page);
			$page = str_replace("_MODIFICA_", $authorModifier, $page);
			$page = str_replace("_TEMPOLETTURA_", $readingTime, $page);
			$page = str_replace("_RECENSIONE_", $rowReview["Contenuto"], $page);
			
			
			
			session_start(); //Avvia la sessione
			
			if(!(isLogged()))
			{
				$page = str_replace("_PULSANTEMODIFICA_", "", $page);
				$page = str_replace("_COMMENTI_", "<nav class=\"navButtons\"><a href=\"login.php\">Accedi per mostrare i commenti</a></nav>", $page);
			}
			else
			{
				if($_SESSION["admin"] == true)
					$page = str_replace("_PULSANTEMODIFICA_", "<form action=\"editReview.php\" method=\"post\" class=\"onlyButton\"><fieldset>\n\t\t\t<input type=\"hidden\" name=\"id\" value=\"". $_GET["id"] ."\"/>\n\t\t\t<input type=\"submit\" name=\"editReviewButton\" value=\"Modifica recensione\" id=\"editReviewButton\"/>\n\t\t</fieldset></form>", $page);
				else
					$page = str_replace("_PULSANTEMODIFICA_", "", $page);
				
				
				//Prepariamo il contenuto della sezione 'Commenti'
				
				//Ogni utente può commentare al più una volta ogni recensione, quindi:
				//- Se l'utente loggato ha già commentato la recensione, il suo commento sarà pinnato in alto (primo commento della sezione 'Commenti')
				//- Se l'utente loggato non ha ancora commentato la recensione, prima di tutto verrà visualizzata la form per l'inserimento di un nuovo commento
				$query = "select * from COMMENTO where IDRecensione=". $_GET["id"] ." and Utente='". $_SESSION["username"] ."';";
				
				if(!($result = $conn->query($query)))
				{
					throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> recuperando l'eventuale commento dell'utente dal <span lang=\"en\">database</span> [#". $conn->errno ." - <span lang=\"en\">". $conn->error ."</span>]", 3); //code=3 --> connection open
				}
				else
				{
					$existsCommentUser = false;
					$commentUser = "";
					if($result->num_rows > 0)
					{
						$row = $result->fetch_array(MYSQLI_ASSOC);
						$existsCommentUser = true;
						$commentUser = "<article>\n\t\t\t\t<header>\n\t\t\t\t\t<h2 class=\"username\">Commento di: ". $row["Utente"] ."</h2>\n\t\t\t\t\t<time datetime=\"". $row["DataCommento"] ."\">". date_format(date_create($row["DataCommento"]), "d-m-Y"). "</time>\n\t\t\t\t</header>\n<p class=\"userComment\">". $row["Contenuto"] ."</p>\n\t\t\t</article>\n\t\t\t";
					}
					else
					{
						if (file_exists("../templates/newComment.txt"))
							$commentUser = file_get_contents("../templates/newComment.txt"); //carico la form d'inserimento commenti
						else
							throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (newComment.txt)");
						
						if(empty($_GET["p"]) || !(ctype_digit($_GET["p"])))
							$commentUser = str_replace("readReview.php?check", "readReview.php?check&modo=". $getModo ."&ricerca=". $getRicerca ."&id=". $_GET["id"], $commentUser);
						else
							$commentUser = str_replace("readReview.php?check", "readReview.php?check&modo=". $getModo ."&ricerca=". $getRicerca ."&id=". $_GET["id"] ."&p=". $_GET["p"], $commentUser);
					}
					
					$result->free();
				}
				
				
				//Query per ottenere il totale dei record che dovranno essere paginati
				$query = "select count(*) from COMMENTO where IDRecensione=". $_GET["id"] ." and Utente<>'". $_SESSION["username"] ."';";
				
				if(!($result = $conn->query($query)))
				{
					throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> contando il numero totale degli altri commenti alla recensione (". $_GET["id"] .") per paginare i risultati [#". $conn->errno ." - <span lang=\"en\">". $conn->error ."</span>]", 3); //code=3 --> connection open
				}
				
				$totalRows = $result->fetch_row()[0];
				$result->free();
				
				$otherComments = "";
				
				//Se c'è almeno un record nella tabella commento (escluso l'eventuale commento a questa recensione dell'utente attualmente loggato)
				//Allora pagino i risultati, altrimenti non opero nessuna paginazione
				if($totalRows > 0)
				{
					//Decido il numero massimo di risultati per pagina
					$numPerPage = 24;
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
						if(isset($_GET["check"]))
							$linkTo = "readReview.php?check&modo=". $getModo ."&ricerca=". $getRicerca ."&id=". $_GET["id"];
						else
							$linkTo = "readReview.php?modo=". $getModo ."&ricerca=". $getRicerca ."&id=". $_GET["id"];
						
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
					//Recupero i dati dei commenti degli altri utenti
					$query = "select * from COMMENTO where IDRecensione=". $_GET["id"] ." and Utente<>'". $_SESSION["username"] ."' order by DataCommento desc limit ". $numPerPage ." offset ". $offsetValue .";";
					
					if(!($result = $conn->query($query)))
					{
						throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> prelevando dal <span lang=\"en\">database</span>, a partire dal <span lang=\"en\">record</span> numero ". $offsetValue .", i successivi ". $numPerPage. " altri commenti alla recensione (". $_GET["id"] .") da paginare [#". $conn->errno ." - <span lang=\"en\">". $conn->error ."</span>]", 3); //code=3 --> connection open
					}
					else
					{
						while($row = $result->fetch_array(MYSQLI_ASSOC))
						{
							$otherComments = $otherComments ."<article>\n\t\t\t\t<header>\n\t\t\t\t\t<h2 class=\"username\">Commento di: ". $row["Utente"] ."</h2>\n\t\t\t\t\t<time datetime=\"". $row["DataCommento"] ."\">". date_format(date_create($row["DataCommento"]), "d-m-Y"). "</time>\n\t\t\t\t</header>\n<p>". $row["Contenuto"] ."</p>\n\t\t\t</article>\n\t\t\t";
						}
						
						$result->free();
					}
					
					//Aggiungo in fondo ai risultati l'elenco della paginazione solo se i risultati superano il valore "$numPerPage"
					if($showMenuPagination)
					{
						$otherComments = $otherComments . $pager->printPagination();
					}
				}
				
				//Controllo se sono arrivato tramite il submit della form per il nuovo commento
				if(isset($_GET["check"]) && !($existsCommentUser))
				{
					$erroreCommento="";
					
					if(empty($_POST["commento"]))
					{
						$erroreCommento = "<p>Nessun commento inserito.</p>";
					}
					else
					{
						$_POST["commento"] = trim($_POST["commento"]);
						
						if (file_exists("./classCommento.php"))
							require_once("./classCommento.php");
						else
							throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (classCommento.php)");
						
						$comment = new Commento($_POST["commento"]);
						
						if($comment == "")
							$erroreCommento = $comment->insertInDB($_GET["id"], $conn);
						else
							$erroreCommento = $comment;
					}
					
					if($erroreCommento != "")
						insertFeedback($commentUser, $erroreCommento, "append"); //inserisco l'elenco degli errori sotto la form
					else
					{
						if(empty($_GET["p"]) || !(ctype_digit($_GET["p"])))
							$destURL ="readReview.php?modo=". $getModo ."&ricerca=". $getRicerca ."&id=". $_GET["id"];
						else
							$destURL = "readReview.php?modo=". $getModo ."&ricerca=". $getRicerca ."&id=". $_GET["id"] ."&p=". $_GET["p"];
						
						pleaseRedirect($page, $destURL);
					}
				}
				
				//Se l'utente loggato non ha commentato e non ci sono altri commenti
				if(!($existsCommentUser) && ($otherComments==""))
					$page = str_replace("_COMMENTI_", "<p>Questa recensione non è ancora stata commentata. Commenta per primo!</p>". $commentUser, $page);
				else //Altrimenti stampo all'utente la form per il suo commento (o il suo commento) seguita dagli eventuali altri commenti
					$page = str_replace("_COMMENTI_", $commentUser . $otherComments, $page);
			}
			
			$conn->close();
			
			//Tutto OK, devo stampare la pagina preparata
		}
		else
		{
			$conn->close();
			$errore="recensione non valida";
		}
	}
	else
	{
		$errore="recensione non valida";
	}
	
	if($errore != "")
	{
		//Prepariamo l'header della pagina di errore
		$title = "Errore: ". $errore ." - Prism Game Reviews";
		$breadcrumb = "<li>Recensione non valida</li>";
		$pageH = new PageHeaderError($title, $breadcrumb);
		
		//Inserisco l'avviso
		insertFeedback($page, "<h1>Oops ...</h1>\n<p>La recensione selezionata non è valida. Si prega di riprovare.</p>", "replace");
	}
	
	//Stampo la pagina (o con l'errore o con la recensione, eventuali commenti e/o form di commento)
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