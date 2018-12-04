<?php

//Creazione oggetto Gioco
class Gioco
{
	private $titoloGioco="";
	private $coverExt="";
	private $coverTempName="";
	private $coverDescr="";
	private $sviluppatore="";
	private $annoUscita=0;
	private $piattaforma=array();
	private $genere=array();
	private $sito=""; //campo opzionale
	private $pegi="";
	private $errore = "";
	
	//Definizione del costruttore
	public function __construct($titoloGioco, $coverDescr, $sviluppatore, $annoUscita, $piattaforma, $genere, $sito, $pegi, $existsCoverImage, $conn = NULL)
	{
		if (file_exists("./classUtility.php"))
			require_once("./classUtility.php");
		else
			throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (classUtility.php)");
		
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
			$errorTitoloGioco = $this->setTitoloGioco($titoloGioco);
			$errorCoverDescr = $this->setCoverDescr($coverDescr);
			$errorSviluppatore = $this->setSviluppatore($sviluppatore);
			$errorAnnoUscita = $this->setAnnoUscita($annoUscita);
			$errorPiattaforma = $this->setPiattaforma($piattaforma, $localConn);
			$errorGenere = $this->setGenere($genere, $localConn);
			$errorSito = $this->setSito($sito);
			$errorPegi = $this->setPegi($pegi);
			
			if($existsCoverImage)
				$errorCoverImage = $this->setCoverImage();
			else
				$errorCoverImage="";
			
			$this->errore = $errorTitoloGioco . $errorCoverImage . $errorCoverDescr . $errorSviluppatore . $errorAnnoUscita . $errorPiattaforma . $errorGenere . $errorSito . $errorPegi;
			
			if($this->errore)
				$this->errore = "<ul>" . $this->errore . "</ul>";
			else
				$this->errore = "";
			
			if($isLocalConn)
				$localConn->close();
		}
		catch(Exception $e)
		{
			if($isLocalConn)
				$localConn->close();
			
			throw $e;
		}
	}
	
	public function __toString()
	{
        return $this->errore;
	}
	
	//Metodi SET: Si occupano dei check e della correttezza/validità dei valori
	private function setTitoloGioco($value)
	{
		$errore="";
		$value = fixInputSAA($value);
		$textLength = strlen($value);
		
		if($textLength > 0 && $textLength <= 255)
			//Lunghezza massima rispettata
			$this->titoloGioco = $value;
		else
			//Lunghezza massima non valida
			$errore="<li>Titolo del gioco di lunghezza errata, minimo 1 e massimo 255 caratteri. Si noti che:<ul><li>Eventuali caratteri speciali non previsti verranno sostituiti con le rispettive entità <abbr title=\"HyperText Markup Language\">HTML</abbr>.</li><li>Eventuali aperture o chiusure di tag <abbr title=\"HyperText Markup Language\">HTML</abbr> non aperti o chiusi correttamente, saranno trattati come caratteri speciali.</li><li>Tali accorgimenti possono aumentare la lunghezza rispetto a quanto inserito, per ulteriori dettagli si veda il <a href=\"../adminmanual.html#gameTitle\" target=\"_blank\" title=\"vai a titolo gioco nel manuale in una nuova finestra\">manuale amministrativo</a>.</li></ul></li>";
		
		return $errore;
	}
	
	private function setCoverImage()
	{
		//IMPORTANTE: Nel file "php.ini" la direttiva di "file_uploads" deve risultare attiva --> "file_uploads = On"
		
		$errore="";
		
		//Verifico che il file sia stato caricato senza errori nella directory temporanea del server
		if(!(is_uploaded_file($_FILES["coverImg"]["tmp_name"])) || $_FILES["coverImg"]["error"] > 0)
		{
			$errore = "<li>Si è verificato un errore nel caricamento dell'imagine di copertina.</li>";
		}
		else
		{
			//Controllo che il file non superi i 512kB di dimensione
			//512 kB = 512 * 1024 = 524288 B
			if ($_FILES["coverImg"]["size"] > 524288)
				$errore="<li>Dimensione del <span lang=\"en\">file</span> caricato come immagine di copertina non valida. Dimensione massima permessa 512<abbr title=\"kilobyte\">kB</abbr>.</li>";
			
			//Ottengo alcune informazioni dal file caricato per eseguire i vari controlli
			$check = getimagesize($_FILES["coverImg"]["tmp_name"]);
			
			//Controllo che il file caricato sia un'immagine
			if($check != false) //NB: non è il controllo più giusto da fare ma non ho la libreria "Fileinfo"
			{
				//Controllo che l'immagine caricata rispetti un formato supportato
				if(($check["mime"] != "image/png") && ($check["mime"] != "image/jpeg"))
				{
					$errore = $errore ."<li>Formato dell'immagine di copertina non supportato. Formati supportati: .png, .jpg e .jpeg</li>";

				}
				else
				{
					//Controllo che l'immagine rispetti i parametri di larghezza e altezza massimi
					//$check[0] --> Larghezza + $check[1] --> Altezza
					if(($check[0] > 512) || ($check[1] > 720))
						$errore = $errore ."<li>L'immagine di copertina caricata ha larghezza e/o altezza non valide.<ul><li>La larghezza non deve superare i 512 <span lang=\"en\">pixel</span></li><li>L'altezza non deve superare i 720 <span lang=\"en\">pixel</span></li></ul></li>";
					else
						//L'immagine va bene
						$this->coverTempName = basename($_FILES["coverImg"]["tmp_name"]); //contiene il nome completo: coverTempName="nome.tmp"
				}
			}
			else
			{
				$errore = $errore ."<li>Il <span lang=\"en\">file</span> caricato non è un'immagine.</li>";
			}
		}
		
		if($errore != "")
			$errore = "<li>Il <span lang=\"en\">file</span> selezionato come immagine di copertina presenta i seguenti problemi:<ul>". $errore ."<li>Per ulteriori dettagli si veda il <a href=\"../adminmanual.html#coverImage\" target=\"_blank\" title=\"vai a immagine copertina nel manuale in una nuova finestra\">manuale amministrativo</a></li></ul></li>";
		
		return $errore;
	}
	
	private function setCoverDescr($value)
	{
		$errore="";
		$value = fixInputSAA($value);
		$textLength = strlen($value);
		
		if($textLength > 0 && $textLength <= 255)
			//Lunghezza massima rispettata
			$this->coverDescr = $value;
		else
			//Lunghezza massima non valida
			$errore="<li>Descrizione copertina di lunghezza errata, minimo 1 e massimo 255 caratteri. Si noti che:<ul><li>Eventuali caratteri speciali non previsti verranno sostituiti con le rispettive entità <abbr title=\"HyperText Markup Language\">HTML</abbr>.</li><li>Eventuali aperture o chiusure di tag <abbr title=\"HyperText Markup Language\">HTML</abbr> non aperti o chiusi correttamente, saranno trattati come caratteri speciali.</li><li>Tali accorgimenti possono aumentare la lunghezza rispetto a quanto inserito, per ulteriori dettagli si veda il <a href=\"../adminmanual.html#coverDescription\" target=\"_blank\" title=\"vai a descrizione copertina nel manuale in una nuova finestra\">manuale amministrativo</a>.</li></ul></li>";
		
		return $errore;
	}
	
	private function setSviluppatore($value)
	{
		$errore="";
		$value = fixInputSAA($value);
		$textLength = strlen($value);
		
		if($textLength > 0 && $textLength <= 255)
			//Lunghezza massima rispettata
			$this->sviluppatore = $value;
		else
			//Lunghezza massima non valida
			$errore="<li>Sviluppatore di lunghezza errata, minimo 1 e massimo 255 caratteri. Si noti che:<ul><li>Eventuali caratteri speciali non previsti verranno sostituiti con le rispettive entità <abbr title=\"HyperText Markup Language\">HTML</abbr>.</li><li>Eventuali aperture o chiusure di tag <abbr title=\"HyperText Markup Language\">HTML</abbr> non aperti o chiusi correttamente, saranno trattati come caratteri speciali.</li><li>Tali accorgimenti possono aumentare la lunghezza rispetto a quanto inserito, per ulteriori dettagli si veda il <a href=\"../adminmanual.html#developer\" target=\"_blank\" title=\"vai a sviluppatore nel manuale in una nuova finestra\">manuale amministrativo</a>.</li></ul></li>";
		
		return $errore;
	}
	
	private function setAnnoUscita($value)
	{
		$errore="";
		
		if((ctype_digit($value)) && ($value >= 1970) && ($value <= 2099))
			//Anno pubblicazione valido
			$this->annoUscita = $value;
		else
			//Anno pubblicazione inferiore al 1970 o superiore a 2099
			$errore="<li>Anno pubblicazione non può essere precedente al <time datetime=\"1970\">1970</time> o successivo al <time datetime=\"2099\">2099</time></li>";
		
		return $errore;
	}
	
	private function setPiattaforma($value, $localConn)
	{
		$errore="";
		
		$platform = explode(",", $value);
		$numRows = count($platform);
		
		$query = "select * from PIATTAFORMA;";
		
		if(!($result = $localConn->query($query)))
		{
			throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> recuperando la piattaforma e la sua versione dal <span lang=\"en\">database</span> per il controllo con quelle inserite dall'utente [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]");
		}
		else
		{
			$stop=false;
			for($i=0; $i < $numRows && !($stop); $i++)
			{
				$result->data_seek(0); //riazzero l'iteratore di $result
				$success=false;
				while(!($success) && ($row = $result->fetch_array(MYSQLI_ASSOC)))
				{
					if($row["IDPiattaforma"] == $platform[$i])
						$success=true;
				}
				
				if($success)
					$this->piattaforma[$i] = $platform[$i];
				else
					//Se trova una piattaforma che non corrisponde a quelle presenti nel DB ferma tutto perchè c'è un errore
					$stop=true;
			}
			$result->free();
		}
		
		if($stop)
			//Lista di piattaforme non valida
			$errore="<li>Una o più coppie piattaforma-versione inserite non sono valide. Sono permesse solo le coppie selezionabili</li>";
		
		return $errore;
	}
	
	private function setGenere($value, $localConn)
	{
		$errore="";
		
		$genre = explode(",", $value);
		$numRows = count($genre);
		
		$query = "select * from GENERE;";
		
		if(!($result = $localConn->query($query)))
		{
			throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> recuperando i generi dal <span lang=\"en\">database</span> per il controllo con quelli inseriti dall'utente [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]");
		}
		else
		{
			$stop=false;
			for($i=0; $i < $numRows && !($stop); $i++)
			{
				$result->data_seek(0); //riazzero l'iteratore di $result
				$success=false;
				while(!($success) && ($row = $result->fetch_array(MYSQLI_ASSOC)))
				{
					if($row["IDGenere"] == $genre[$i])
						$success=true;
				}
				
				if($success)
					$this->genere[$i] = $genre[$i];
				else
					//Se trova un genere che non corrisponde a quelli presenti nel DB ferma tutto perchè c'è un errore
					$stop=true;
			}
			$result->free();
		}
		
		if($stop)
			//Lista di generi non valida
			$errore="<li>Uno o più generi inseriti non sono validi. Sono permessi solo i generi selezionabili</li>";
		
		return $errore;
	}
	
	private function setSito($value)
	{
		$errore="";
		$value = fixInput($value);
		
		if(strlen($value) <= 128)
		{
			//Lunghezza massima valida
			if($value == "")
				$this->sito = "ND"; //se è vuoto gli metto il default
			else
			{
				if(filter_var($value, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED))
					//Lunghezza massima valida, formato valido
					$this->sito = $value;
				else
					//Lunghezza massima valida, ma formato non corretto
					$errore="<li>Formato di Sito ufficiale non valido.<ul><li>Usare il formato: <code>protocollo://indirizzo</code></li><li>Per ulteriori dettagli si veda il <a href=\"../adminmanual.html#officialSite\" target=\"_blank\" title=\"vai a sito ufficiale nel manuale in una nuova finestra\">manuale amministrativo</a></li></ul></li>";
			}
		}
		else
		{
			//Lunghezza massima non valida
			$errore="<li>Sito ufficiale troppo lungo, massimo 128 caratteri. Si noti che:<ul><li>Eventuali caratteri speciali verranno sostituiti con le rispettive entità <abbr title=\"HyperText Markup Language\">HTML</abbr>.</li><li>Tale accorgimento può aumentare la lunghezza rispetto a quanto inserito, per ulteriori dettagli si veda il <a href=\"../adminmanual.html#officialSite\" target=\"_blank\" title=\"vai a sito ufficiale nel manuale in una nuova finestra\">manuale amministrativo</a>.</li></ul></li>";
		}
		
		return $errore;
	}
	
	private function setPegi($value)
	{
		$errore="";
		$validPegi = array("3+", "7+", "12+", "16+", "18+");
		$numRows = count($validPegi);
		$success=false;
		
		while(!($success) && ($numRows > 0))
		{
			if($value == $validPegi[$numRows-1])
				$success=true;
			else
				$numRows--;
		}
		
		if(!($success))
			//Valore del campo PEGI non valido
			$errore="<li><acronym title=\"Pan European Game Information\">PEGI</acronym> inserito non valido. Scegliere tra: 3+, 7+, 12+, 16+ e 18+</li>";
		else
			$this->pegi = $value;
			
		return $errore;
	}
	
	//Metodi GET: Si occupano della lettura dei valori
	public function getTitoloGioco()
		{return $this->titoloGioco;}
	
	public function getCoverExt()
		{return $this->coverExt;}
	
	public function getCoverTempName()
		{return $this->coverTempName;}
	
	public function getCoverDescr()
		{return $this->coverDescr;}
	
	public function getSviluppatore()
		{return $this->sviluppatore;}
	
	public function getAnnoUscita()
		{return $this->annoUscita;}
	
	public function getPiattaforma()
		{return $this->piattaforma;}
	
	public function getGenere()
		{return $this->genere;}
	
	public function getSito()
		{return $this->sito;}
	
	public function getPegi()
		{return $this->pegi;}
	
	
	//Sposto ("inserisco") la cover nella cartella apposita
	private function insertCover($id)
	{
		$targetPath="../images/covers/";
		
		//Testo se riesco a spostarla con successo
		if(!(move_uploaded_file($_FILES["coverImg"]["tmp_name"], $targetPath . $this->coverTempName)))
		{
			throw new Exception ("si è verificato un errore durante il salvataggio dell'immagine di copertina nei nostri <span lang=\"en\">server</span>");
		}
		else
		{
			//Recupero l'estensione dell'immagine caricata dal suo tipo mime
			$imageExt = explode("/", (getimagesize($targetPath . $this->coverTempName)["mime"]))[1];
			
			if($imageExt=="jpeg")
				$imageExt="jpg"; //non salvo mai .jpeg
			
			$this->coverExt = $imageExt;
			
			//Testo se la ridenominazione ha successo
			if(!(rename($targetPath . $this->coverTempName, $targetPath . $id .".". $this->coverExt)))
			{
				throw new Exception ("si è verificato un errore durante la ridenominazione dell'immagine di copertina");
			}
		}
		
		return;
	}
	
	//Controllo l'univocità nel DB della coppia di dati: TitoloGioco-AnnoUscita
	private function checkUnique_TitleYear($id, $localConn)
	{
		$errore="";
		
		$query = "select Titolo, AnnoUscita from GIOCO where IDGioco<>". $id .";";
		if (!($result = $localConn->query($query)))
		{
			throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> recuperando i dati per la verifica di unicità della coppia TitoloGioco-AnnoUscita dal <span lang=\"en\">database</span> [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]");
		}
		else
		{
			if($result->num_rows > 0)
			{
				$gameTitle=strip_tags($this->titoloGioco);
				$stop=false;
				
				while(!($stop) && ($row = $result->fetch_array(MYSQLI_ASSOC)))
				{
					if(($gameTitle == strip_tags($row["Titolo"])) && ($this->annoUscita == $row["AnnoUscita"]))
						$stop=true;
				}
				
				if($stop)
					$errore = "<p>La coppia di campi Titolo del gioco e Anno di pubblicazione inseriti sono già presenti nel <span lang=\"en\">database</span> e pertanto l'operazione è stata annullata (<span lang=\"en\">rollback</span>). Si ricorda che è possibile la stesura di una sola recensione per ogni videogioco.</p>";
			}
			$result->free();
		}
		
		return $errore;
	}
	
	//Inserimento delle piattaforme nel DB
	private function insertPlatform($id, $localConn)
	{
		//Inserimento dei dati nella tabella ESECUZIONE
		$numElement = count($this->piattaforma);
		
		for ($i=0; $i <= $numElement-1; $i++)
		{
			$ins = "insert into ESECUZIONE(IDGioco, IDPiattaforma) VALUES(". $id .", '". $this->piattaforma[$i] ."');";
			
			if(!($localConn->query($ins)))
			{
				throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $ins ."</code> per inserire (o modificare) la piattaforma e la sua versione per il gioco (". $id .") nel <span lang=\"en\">database</span> (è stata eseguita una <span lang=\"en\">rollback</span>) [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]", 2); //code=2 --> transaction
			}
		}
	}
	
	//Inserimento dei generi nel DB
	private function insertGenre($id, $localConn)
	{
		//Inserimento dei dati nella tabella APPARTENENZA
		$numElement = count($this->genere);
		
		for ($i=0; $i <= $numElement-1; $i++)
		{
			$ins = "insert into APPARTENENZA(IDGioco, IDGenere) VALUES(". $id .", '". $this->genere[$i] ."');";
			
			if(!($localConn->query($ins)))
			{
				throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $ins ."</code> per inserire (o modificare) il genere del gioco (". $id .") nel <span lang=\"en\">database</span> (è stata eseguita una <span lang=\"en\">rollback</span>) [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]", 2); //code=2 --> transaction
			}
		}
	}
	
	//Cancellazione delle piattaforme nel DB
	private function deletePlatform($id, $localConn)
	{
		//Cancellazione dei dati dalla tabella ESECUZIONE
		$del = "delete from ESECUZIONE where IDGioco=". $id .";";
		
		if(!($localConn->query($del)))
		{
			throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $del ."</code> per modificare la piattaforma e la sua versione per il gioco (". $id .") nel <span lang=\"en\">database</span> (è stata eseguita una <span lang=\"en\">rollback</span>) [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]", 2); //code=2 --> transaction
		}
	}
	
	//Cancellazione dei generi nel DB
	private function deleteGenre($id, $localConn)
	{
		//Cancellazione dei dati dalla tabella APPARTENENZA
		$del = "delete from APPARTENENZA where IDGioco=". $id .";";
		
		if(!($localConn->query($del)))
		{
			throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $del ."</code> per modificare il genere del gioco (". $id .") nel <span lang=\"en\">database</span> (è stata eseguita una <span lang=\"en\">rollback</span>) [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]", 2); //code=2 --> transaction
		}
	}
	
	
	//Metodo che si occupa dell'inserimento dei dati nel database
	public function insertInDB($id, $conn = NULL)
	{
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
			//Salvo l'eventuale errore sul vincolo di unique nel DB della coppia di dati: TitoloGioco-AnnoUscita
			$errore = $this->checkUnique_TitleYear($id, $localConn);
			
			//Se non ci sono stati errori procedo all'inserimento
			if($errore == "")
			{
				if (file_exists("./classUtility.php"))
					require_once("./classUtility.php");
				else
					throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (classUtility.php)");
				
				if(isAuthenticated($localConn))
				{
					//Sposta l'immagine nella cartella apposita, e setta il campo coverExt
					$this->insertCover($id);
					
					//Apro una transazione mysqli
					//Commentata perchè eseguo Gioco::insertInDB() in una transazione aperta in Recensione::insertInDB()
					//$localConn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
					
					//Inserimento dei dati della tabella GIOCO nel DB
					$ins = "insert into GIOCO(IDGioco, Titolo, TitoloOrdinamento, CoverExt, CoverDescr, Sviluppatore, AnnoUscita, SitoUfficiale, Pegi) VALUES(". $id .", '". $this->titoloGioco ."', '". trim(strip_tags($this->titoloGioco)) ."', '". $this->coverExt ."', '". $this->coverDescr ."', '". $this->sviluppatore ."', ". $this->annoUscita .", '". $this->sito ."', '". $this->pegi ."');";
					
					if(!($localConn->query($ins)))
					{
						throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $ins ."</code> per inserire il gioco nel <span lang=\"en\">database</span> (è stata eseguita una <span lang=\"en\">rollback</span>) [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]", 2); //code=2 --> transaction
					}
					
					//Inserimento delle piattaforme
					$this->insertPlatform($id, $localConn);
					
					//Inserimento dei generi
					$this->insertGenre($id, $localConn);
					
					//Confermo la transaction
					//$localConn->commit();
				}
				else
				{
					throw new Exception("Si è verificato un problema durante la verifica delle credenziali");
				}
			}
			
			if($isLocalConn)
				$localConn->close();
			
			return $errore; //stringa vuota se non ci sono stati errori
		}
		catch(Exception $e)
		{
			//if($e->getCode() == 2) //code=2 eccezione sollevata durante una 'transaction'
			//	$localConn->rollback(); //L'inserimento è andato male, ripristino lo stato di consistenza del DB
			
			if($isLocalConn)
				$localConn->close();
			
			throw $e;
		}
	}
	
	//Metodo che si occupa dell'aggiornamento dei dati nel database
	public function updateInDB($id, $conn = NULL)
	{
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
			//Salvo l'eventuale errore sul vincolo di unique nel DB della coppia di dati: TitoloGioco-AnnoUscita
			$errore = $this->checkUnique_TitleYear($id, $localConn);
			
			//Se non ci sono stati errori procedo all'inserimento
			if($errore == "")
			{
				if (file_exists("./classUtility.php"))
					require_once("./classUtility.php");
				else
					throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (classUtility.php)");
				
				if(isAuthenticated($localConn))
				{
					//Se l'immagine di copertina è stata modificata, allora sposto l'immagine nella cartella apposita, e setto il campo coverExt
					if($this->coverTempName != "")
					{
						$this->insertCover($id);
						$coverExtQuery=" CoverExt='". $this->coverExt ."',";
					}
					else
					{
						$coverExtQuery="";
					}
					
					//Apro una transazione mysqli
					//Commentata perchè eseguo Gioco::updateInDB() in una transazione aperta in Recensione::updateInDB()
					//$localConn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
					
					//Aggiornamento dei dati della tabella GIOCO nel DB
					$upd = "update GIOCO set Titolo='". $this->titoloGioco ."', TitoloOrdinamento='". trim(strip_tags($this->titoloGioco)) ."',". $coverExtQuery ." CoverDescr='". $this->coverDescr ."', Sviluppatore='". $this->sviluppatore ."', AnnoUscita=". $this->annoUscita .", SitoUfficiale='". $this->sito ."', Pegi='". $this->pegi ."' where IDGioco=". $id .";";
					
					if(!($localConn->query($upd)))
					{
						throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $upd ."</code> per modificare i dati del gioco nel <span lang=\"en\">database</span> (è stata eseguita una <span lang=\"en\">rollback</span>) [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]", 2); //code=2 --> transaction
					}
					
					//Cancellazione delle piattaforme
					$this->deletePlatform($id, $localConn);
					//Inserimento delle piattaforme
					$this->insertPlatform($id, $localConn);
					
					//Cancellazione dei generi
					$this->deleteGenre($id, $localConn);
					//Inserimento dei generi
					$this->insertGenre($id, $localConn);
					
					//Confermo la transaction
					//$localConn->commit();
				}
				else
				{
					throw new Exception("Si è verificato un problema durante la verifica delle credenziali");
				}
			}
			
			if($isLocalConn)
				$localConn->close();
			
			return $errore;
		}
		catch(Exception $e)
		{
			//if($e->getCode() == 2) //code=2 eccezione sollevata durante una 'transaction'
			//	$localConn->rollback(); //La modifica è andata male, ripristino lo stato di consistenza del DB
			
			if($isLocalConn)
				$localConn->close();
			
			throw $e;
		}
	}
}
?>