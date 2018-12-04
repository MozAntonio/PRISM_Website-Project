<?php

//Creazione oggetto 'Commento'
class Commento
{
	//private $user = NULL; //oggetto 'Utente'
	private $autore = "";
	private $testo = "";
	private $data = "";
	private $errore = "";
	
	//Definizione del costruttore
	public function __construct($testo)
	{
		if (file_exists("./classUtility.php"))
			require_once("./classUtility.php"); //per le funzioni di fixInput che servono ai setter
		else
			throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (classUtility.php)");
		
		$errorTesto = $this->setTesto($testo);
		
		$this->setAutore();
		$this->setData();
		
		$this->errore = $errorTesto;
		
		if($this->errore)
			$this->errore = "<ul>" . $this->errore . "</ul>";
		else
			$this->errore = "";
	}
	
	public function __toString()
	{
        return $this->errore;
	}
	
	//Metodi SET: Si occupano dei check e della correttezza/validità dei valori
	private function setTesto($value)
	{
		$errore="";
		$value = fixInputComment($value);
		$textLength = strlen($value);
		
		if($textLength > 0 && $textLength <= 1000)
			//Lunghezza massima rispettata
			$this->testo = $value;
		else
			//Lunghezza massima non valida
			$errore="<li>Commento di lunghezza errata, minimo 1 e massimo 1000 caratteri. Si noti che:<ul><li>Eventuali caratteri speciali non previsti verranno sostituiti con le rispettive entità <abbr title=\"HyperText Markup Language\">HTML</abbr>.</li><li>Eventuali aperture o chiusure di tag <abbr title=\"HyperText Markup Language\">HTML</abbr> non aperti o chiusi correttamente, saranno trattati come caratteri speciali.</li><li>Tali accorgimenti possono aumentare la lunghezza rispetto a quanto inserito.</li></ul></li>";
		
		return $errore;
	}
	
	private function setAutore()
	{
		if(!(isset($_SESSION["username"])))
			throw new Exception ("controllando il nome utente: <code> isset({$_SESSION["username"]})==". isset($_SESSION["username"]) ." </code> per impostare il campo Autore del commento");
		else
			$this->autore = $_SESSION["username"];
	}
	
	private function setData()
	{
		$this->data = date("Y-m-d");
	}
	
	//Metodi GET: Si occupano della lettura dei valori	
	public function getTesto()
		{return $this->testo;}
	
	public function getAutore()
		{return $this->autore;}
		
	public function getData()
		{return $this->data;}
	
	
	//Controllo l'univocità nel DB della coppia di dati: IDRecensione-AutoreCommento
	public function checkUnique_IDRecensioneUtente($id, $autoreCommento, $localConn)
	{
		$errore="";
		
		$query = "select IDRecensione, Utente from COMMENTO where IDRecensione=". $id ." and Utente='". $autoreCommento ."';";
		if (!($result = $localConn->query($query)))
		{
			throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> recuperando i dati per la verifica di unicità della coppia IDRecensione-AutoreCommento per i commenti dal <span lang=\"en\">database</span> [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]");
		}
		else
		{
			if($result->num_rows > 0)
			{
				$errore = "<p>Risulta già un commento con questo nome utente a questa recensione e pertanto l'operazione è stata annullata. Si ricorda che è possibile commentare una sola volta ogni recensione.</p>";
			}
			$result->free();
		}
		
		return $errore;
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
			$errore = $this->checkUnique_IDRecensioneUtente($id, $this->autore, $localConn);
			
			//Se non ci sono stati errori procedo all'inserimento
			if($errore == "")
			{
				if (file_exists("./classUtility.php"))
					require_once("./classUtility.php"); //per la funzione isAuthenticated
				else
					throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (classUtility.php)");
				
				if(isAuthenticated($localConn))
				{
					//Inserimento dei dati della tabella COMMENTO nel DB
					$ins = "insert into COMMENTO(IDRecensione, Utente, Contenuto, DataCommento) VALUES(". $id .", '". $this->autore ."', '". $this->testo ."', '". $this->data ."');";
					
					if (!($localConn->query($ins)))
					{
						throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $ins ."</code> per inserire il commento nel <span lang=\"en\">database</span> [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]");
					}
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
			if(isAuthenticated($localConn))
			{
				//Aggiornamento dei dati della tabella COMMENTO nel DB
				$upd = "update COMMENTO set Contenuto='". $this->testo ."', DataCommento='". $this->data ."' where IDRecensione=". $id ." and Utente='". $this->autore ."';";
				
				if (!($localConn->query($upd)))
				{
					throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $upd ."</code> per modificare il commento nel <span lang=\"en\">database</span> [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]");
				}
			}
			else
			{
				throw new Exception("Si è verificato un problema durante la verifica delle credenziali");
			}
			
			if($isLocalConn)
				$localConn->close();
			
			return;
		}
		catch(Exception $e)
		{			
			if($isLocalConn)
				$localConn->close();
			
			throw $e;
		}
	}
}
?>