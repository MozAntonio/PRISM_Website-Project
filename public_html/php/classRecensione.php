<?php

//Creazione oggetto Recensione
class Recensione
{
	private $gioco = NULL; //oggetto 'Gioco'
	private $titoloRecensione="";
	private $descrizioneHTML="";
	private $keywords="";
	private $contenuto="";
	private $tempoLettura="";
	private $autore="";
	private $dataPubblicazione="";
	private $errore = "";
	
	//Definizione del costruttore
	public function __construct($gioco, $titoloRecensione, $descrizioneHTML, $contenuto, $keywords)
	{
		if (file_exists("./classUtility.php"))
			require_once("./classUtility.php");
		else
			throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (classUtility.php)");
		
		$this->gioco = $gioco;
		
		$errorTitoloRecensione = $this->setTitoloRecensione($titoloRecensione);
		$errorDescrizioneHTML = $this->setDescrizioneHTML($descrizioneHTML);
		$errorContenuto = $this->setContenuto($contenuto);
		
		//"$errorKeywords", per il sito 'PRISM Game Reviews', sarà sempre uguale a stringa vuota
		//La scelta di aggiungere tale errore è per aumentare la mantenibilità e il possibile riutilizzo della classe 'Recensione'
		$errorKeywords = $this->setKeywords($keywords);
		
		$this->setTempoLettura();
		$this->setAutore();
		$this->setDataPubblicazione();
		
		$this->errore = $errorTitoloRecensione . $errorDescrizioneHTML . $errorContenuto . $errorKeywords;
		
		if($this->errore)
			$this->errore = "<ul>" . $this->errore . "</ul>";
		else
			$this->errore = "";
		
		//Antepongo gli eventuali errori di gioco
		$this->errore = $this->gioco . $this->errore;
	}
	
	public function __toString()
	{
        return $this->errore;
	}
	
	//Metodi SET: Si occupano dei check e della correttezza/validità dei valori
	private function setTitoloRecensione($value)
	{
		$errore="";
		$value = fixInputSAA($value);
		$textLength = strlen($value);
		
		if($textLength > 0 && $textLength <= 255)
			//Lunghezza massima rispettata
			$this->titoloRecensione = $value;
		else
			//Lunghezza massima non valida
			$errore="<li>Titolo della recensione di lunghezza errata, minimo 1 e massimo 255 caratteri. Si noti che:<ul><li>Eventuali caratteri speciali non previsti verranno sostituiti con le rispettive entità <abbr title=\"HyperText Markup Language\">HTML</abbr>.</li><li>Eventuali aperture o chiusure di tag <abbr title=\"HyperText Markup Language\">HTML</abbr> non aperti o chiusi correttamente, saranno trattati come caratteri speciali.</li><li>Tali accorgimenti possono aumentare la lunghezza rispetto a quanto inserito, per ulteriori dettagli si veda il <a href=\"../adminmanual.html#reviewTitle\" target=\"_blank\" title=\"vai a titolo recensione nel manuale in una nuova finestra\">manuale amministrativo</a>.</li></ul></li>";
		
		return $errore;
	}
	
	private function setDescrizioneHTML($value)
	{
		$errore="";
		$value = fixInput($value);
		$textLength = strlen($value);
		
		if($textLength > 0 && $textLength <= 150)
			//Lunghezza massima rispettata
			$this->descrizioneHTML = $value;
		else
			//Lunghezza massima non valida
			$errore="<li>Descrizione della recensione di lunghezza errata, minimo 1 e massimo 150 caratteri. Si noti che:<ul><li>Eventuali caratteri speciali verranno sostituiti con le rispettive entità <abbr title=\"HyperText Markup Language\">HTML</abbr>.</li><li>Tale accorgimento può aumentare la lunghezza rispetto a quanto inserito, per ulteriori dettagli si veda il <a href=\"../adminmanual.html#shortDescription\" target=\"_blank\" title=\"vai a breve descrizione nel manuale in una nuova finestra\">manuale amministrativo</a>.</li></ul></li>";
		
		return $errore;
	}
	
	private function setContenuto($value)
	{
		$errore="";
		$value = fixInputReview($value);
		$textLength = strlen($value);
		
		if($textLength > 0 && $textLength <= 65535)
			//Lunghezza massima rispettata
			$this->contenuto = $value;
		else
			//Lunghezza massima non valida --> Max: 2^16 - 1 = 65535
			$errore="<li>Recensione di lunghezza errata, minimo 1 e massimo 65535 caratteri. Si noti che:<ul><li>Eventuali caratteri speciali non previsti verranno sostituiti con le rispettive entità <abbr title=\"HyperText Markup Language\">HTML</abbr>.</li><li>Eventuali aperture o chiusure di tag <abbr title=\"HyperText Markup Language\">HTML</abbr> non aperti o chiusi correttamente, saranno trattati come caratteri speciali.</li><li>Tali accorgimenti possono aumentare la lunghezza rispetto a quanto inserito, per ulteriori dettagli si veda il <a href=\"../adminmanual.html#reviewContent\" target=\"_blank\" title=\"vai a recensione nel manuale in una nuova finestra\">manuale amministrativo</a>.</li></ul></li>";
		
		return $errore;
	}
	
	private function setKeywords($arrayValues)
	{
		$errore="";
		$listKeywords = implode(",", $arrayValues);
		
		/*Nota:
			La lunghezza di "$listKeywords", per il sito 'PRISM Game Reviews', sarà sempre rispettata e valida, perchè:
				max teorico: 255 titoloGioco + 255 sviluppatore + 4 annoPub + 7 virgole + 35 keywords aziendali = 556
				considerando che i tag validi vengono qui eliminati si recuperano caratteri
			La scelta di aggiungere tale controllo è per aumentare la mantenibilità e il possibile riutilizzo della classe 'Recensione'
			Si sconsiglia comunque l'uso di più di una decina di keywords e si ricorda di elencarle in ordine decrescente per importanza */
		if(strlen($listKeywords) <= 1000)
			//Lunghezza massima rispettata
			$this->keywords = $listKeywords;
		else
			//Lunghezza massima non valida
			$errore="<li>Lista delle <span lang=\"en\">keywords</span> troppo lunga, massimo 1000 caratteri. Si ricorda che dopo ogni <span lang=\"en\">keyword</span> inserita (ad eccezione dell'ultima) viene aggiunta una virgola.</li>";
		
		return $errore;
	}
	
	private function setTempoLettura()
	{
		$numWords = str_word_count($this->contenuto);
		$this->tempoLettura = ceil($numWords / 180); //In media, considerando tutti i fattori, è stimata la lettura di 180 parole al minuto
	}
	
	private function setAutore()
	{
		if(!(isset($_SESSION["username"])))
			throw new Exception ("controllando il nome utente: <code> isset({$_SESSION["username"]})==". isset($_SESSION["username"]) ." </code> per impostare il campo Autore (o AutoreModifica) della recensione");
		else
			$this->autore = $_SESSION["username"];
	}
	
	private function setDataPubblicazione()
	{
		$this->dataPubblicazione = date("Y-m-d");
	}
	
	//Metodi GET: Si occupano della lettura dei valori
	public function getGioco()
		{return $this->gioco;}
	
	public function getTitoloRecensione()
		{return $this->titoloRecensione;}
	
	public function getDescrizioneHTML()
		{return $this->descrizioneHTML;}
	
	public function getContenuto()
		{return $this->contenuto;}
	
	public function getKeywords()
		{return $this->keywords;}
	
	public function getTempoLettura()
		{return $this->tempoLettura;}
	
	public function getAutore()
		{return $this->autore;}
	
	public function getDataPubblicazione()
		{return $this->dataPubblicazione;}
	
	
	//Metodo che si occupa dell'inserimento dei dati nel database
	public function insertInDB($id, $conn = NULL)
	{
		if (file_exists("./classUtility.php"))
			require_once("./classUtility.php");
		else
			throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (classUtility.php)");
		
		if (file_exists("./classGioco.php"))
			require_once("./classGioco.php");
		else
			throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (classGioco.php)");
		
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
		
		$errore="";
		
		try {
			if(isAuthenticated($localConn))
			{
				//Apro una transazione mysqli
				$localConn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
				
				//Inserimento dei dati della tabella RECENSIONE nel DB
				$ins = "insert into RECENSIONE(ID, Titolo, Contenuto, TempoLettura, Keywords, DescrizioneHTML, Autore, DataPubblicazione) VALUES(". $id .", '". $this->titoloRecensione ."', '". $this->contenuto ."', ". $this->tempoLettura .", '". $this->keywords ."', '". $this->descrizioneHTML ."', '". $this->autore ."', '". $this->dataPubblicazione ."');";
				
				if (!($localConn->query($ins)))
				{
					throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $ins ."</code> per inserire la recensione nel <span lang=\"en\">database</span> (è stata eseguita una <span lang=\"en\">rollback</span>) [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]", 2); //code=2 --> transaction
				}
				
				$errore = ($this->gioco)->insertInDB($id, $localConn);
				
				if($errore != "")
				{
					//L'inserimento è andato male, ripristino lo stato di consistenza del DB
					$localConn->rollback();
				}
				else
				{
					//Confermo la transaction
					$localConn->commit();
				}
			}
			else
			{
				throw new Exception("Si è verificato un problema durante la verifica delle credenziali");
			}
			
			if($isLocalConn)
				$localConn->close();
			
			return $errore;
		}
		catch(Exception $e)
		{
			if($e->getCode() == 2) //code=2 eccezione sollevata durante una 'transaction'
				$localConn->rollback(); //L'inserimento è andato male, ripristino lo stato di consistenza del DB
			
			if($isLocalConn)
				$localConn->close();
			
			throw $e;
		}
	}
	
	//Metodo che si occupa dell'aggiornamento dei dati nel database
	public function updateInDB($id, $conn = NULL)
	{
		if (file_exists("./classUtility.php"))
			require_once("./classUtility.php");
		else
			throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (classUtility.php)");
		
		if (file_exists("./classGioco.php"))
			require_once("./classGioco.php");
		else
			throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (classGioco.php)");
		
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
		
		$errore="";
		
		try {
			if(isAuthenticated($localConn))
			{
				//Apro una transazione mysqli
				$localConn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
				
				//Aggiornamento dei dati della tabella RECENSIONE nel DB
				$upd = "update RECENSIONE set Titolo='". $this->titoloRecensione ."', Contenuto='". $this->contenuto ."', TempoLettura=". $this->tempoLettura .", Keywords='". $this->keywords ."', DescrizioneHTML='". $this->descrizioneHTML ."', AutoreModifica='". $this->autore ."', DataModifica='". $this->dataPubblicazione ."' where ID=". $id .";";
				
				if (!($localConn->query($upd)))
				{
					throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $upd ."</code> per apportare le modifiche richieste alla recensione nel <span lang=\"en\">database</span> (è stata eseguita una <span lang=\"en\">rollback</span>) [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]", 2); //code=2 --> transaction
				}
				
				$errore = ($this->gioco)->updateInDB($id, $localConn);
				
				if($errore != "")
				{
					//La modifica è andata male, ripristino lo stato di consistenza del DB
					$localConn->rollback();
				}
				else
				{
					//Confermo la transaction
					$localConn->commit();
				}
			}
			else
			{
				throw new Exception("Si è verificato un problema durante la verifica delle credenziali");
			}
			
			if($isLocalConn)
				$localConn->close();
			
			return $errore;
		}
		catch(Exception $e)
		{
			if($e->getCode() == 2) //code=2 eccezione sollevata durante una 'transaction'
				$localConn->rollback(); //La modifica è andata male, ripristino lo stato di consistenza del DB
			
			if($isLocalConn)
				$localConn->close();
			
			throw $e;
		}
	}
}
?>