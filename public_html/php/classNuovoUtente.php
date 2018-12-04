<?php

if (file_exists("./classUtente.php"))
	require_once("./classUtente.php");
else
	throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (classUtente.php)");

//Creazione oggetto 'NuovoUtente'
class NuovoUtente extends Utente
{
	private $email="";
	private $checkPassword="";
	private $dataIscrizione="";
	private $errore="";
	
	//Definizione del costruttore
	public function __construct($email, $username, $password, $checkPassword)
	{
		$errorEmail = $this->setEmail($email);
		
		parent::__construct($username, $password);
		$errorUser = $this->getErrore();
		
		if($errorUser != "")
		{
			$errorUser = substr($errorUser, 4);	//elimino l'apertura del primo tag: <ul>
			$errorUser = substr($errorUser, 0, -5);	//elimino la chiusura dell'ultimo tag: </ul>
		}
		
		$errorCheckPassword = $this->setCheckPassword($password, $checkPassword);
		
		$this->setDataIscrizione();
		
		$this->errore = $errorEmail . $errorUser . $errorCheckPassword;
		
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
	private function setEmail($value)
	{
		$errore="";
		
		if(strlen($value) <= 128)
		{
			//Opzione 1:
			//if(preg_match("/^([a-z]|[A-Z]|[0-9]|\+|\_|\-|\.)+\@([a-z]|[A-Z]|[0-9]|\+|\_|\-|\.)+\.([a-z]|[A-Z])+$/", $value))
			//Opzione 2:
			if(filter_var($value, FILTER_VALIDATE_EMAIL))
				//Se sono arrivato qui: la lunghezza massima e il formato sono validi
				$this->email = $value;
			else
				//Lunghezza massima rispettata, ma formato non corretto
				$errore="<li>Formato <span lang=\"en\">email</span> non valido. Esempio: esempio@dominio.it</li>";
		}
		else
		{
			$errore="<li><span lang=\"en\">Email</span> troppo lunga, massimo 128 caratteri.</li>";
		}
		
		return $errore;
	}
	
	private function setCheckPassword($valuePassword, $valueCheckPassword)
	{
		$errore="";
		
		if($valueCheckPassword === $valuePassword)
			//Se sono arrivato qui: il campo 'password' ed il campo 'checkPassword' sono equivalenti
			$this->checkPassword = $valueCheckPassword;
		else
			//Il campo 'password' ed il campo 'checkPassword' non corrispondono
			$errore="<li>Conferma <span lang=\"en\">password</span> non corrisponde alla <span lang=\"en\">password</span>.</li>";
		
		return $errore;
	}
	
	private function setDataIscrizione()
	{
		$this->dataIscrizione = date("Y-m-d");
	}
	
	//Metodi GET: Si occupano della lettura dei valori
	public function getEmail()
		{return $this->email;}
	
	public function getCheckPassword()
		{return $this->checkPassword;}
	
	public function getDataIscrizione()
		{return $this->dataIscrizione;}
	
	
	//Controllo l'univocità nel DB della primary key: Username
	private function checkUnique_Username($localConn)
	{
		$errore="";
		
		$query = "select * from UTENTE where Username='". $this->getUsername() ."';";
		if (!($result = $localConn->query($query)))
		{
			throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> per la verifica di disponibilità del nome utente nel <span lang=\"en\">database</span> [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]");
		}
		else
		{
			//Se sono arrivato qui la query sullo Username è stata eseguita con successo, ora controlliamo il risultato
			if($result->num_rows > 0)
			{
				//Errore: Il campo Username inserito esiste già nel DB
				$errore = "<li>Il nome utente inserito è già presente, inserirne un'altro.</li>";
			}
			
			//Liberazione delle risorse occupate da: $result
			$result->free();
		}
		
		return $errore;
	}
	
	//Controllo l'univocità nel DB dell'Email
	private function checkUnique_Email($localConn)
	{
		$errore="";
		
		$query = "select * from UTENTE where Email='". $this->email ."';";
		if (!($result = $localConn->query($query)))
		{
			throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> per la verifica di unicità del'<span lang=\"en\">email</span> nel <span lang=\"en\">database</span> [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]");
		}
		else
		{
			//Se sono arrivato qui la query sull'Email è stata eseguita con successo, ora controlliamo il risultato
			if($result->num_rows > 0)
			{
				//Errore: Il campo Email scelto esiste già nel DB
				$errore = "<li>L'<span lang=\"en\">email</span> inserita è già presente, inserirne un'altra.</li>";
			}
			
			//Liberazione delle risorse occupate da: $result
			$result->free();
		}
		
		return $errore;
	}
	
	
	//Metodo che si occupa dell'inserimento dei dati nel database
	public function insertInDB($conn = NULL)
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
			$errore = $this->checkUnique_Username($localConn) . $this->checkUnique_Email($localConn);
			
			if($errore == "")
			{
				//Nel DB viene salvato un hash della password
				/*Funzione utilizzata: - "string password_hash(string $password, PASSWORD_DEFAULT)"
				--> Parametro $password: Password in chiaro
				--> Parametro 'PASSWORD_DEFAULT': Seleziona l'algoritmo di hashing di default in base alla versione di PHP,
					si consiglia di salvarlo nel DB in una variabile testuale di 255 caratteri*/
				$hashPassword = password_hash($this->getPassword(), PASSWORD_DEFAULT);
				
				$ins = "insert into UTENTE(Email, Username, HashPassword, DataIscrizione) VALUES('". $this->email ."', '". $this->getUsername() ."', '". $hashPassword ."', '". $this->dataIscrizione ."');";
				if (!($localConn->query($ins)))
				{
					throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $ins ."</code> per l'inserimento dei dati del nuovo utente nel <span lang=\"en\">database</span> [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]");
				}
				else
				{
					//Setto i valori dell'array globale: _SESSION
					$_SESSION["username"]=$this->getUsername();
					$_SESSION["password"]=$this->getPassword();
					$_SESSION["admin"]=false;
				}
			}
			else
			{
				$errore = "<ul>". $errore ."</ul>";
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
	
	//Metodo che si occupa dell'aggiornamento della Password nel database
	public function updatePassword($conn = NULL)
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
			if (file_exists("./classUtility.php"))
				require_once("./classUtility.php");
			else
				throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (classUtility.php)");
			
			if(isAuthenticated($localConn))
			{
				$hashPassword = password_hash($this->getPassword(), PASSWORD_DEFAULT);
				
				//Aggiornamento del campo HashPassword della tabella UTENTE nel DB
				$upd = "update UTENTE set HashPassword='". $hashPassword ."' where Username='". $this->getUsername() ."';";
				
				if(!($localConn->query($upd)))
				{
					throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $upd ."</code> per modificare l'<span lang=\"en\">hash</span> della <span lang=\"en\">password</span> nel <span lang=\"en\">database</span> [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]");
				}
				else
				{
					$_SESSION["password"]=$this->getPassword();
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
	
	//Metodo che si occupa dell'aggiornamento dell'Email nel database
	public function updateEmail($conn = NULL)
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
			//Salvo l'eventuale errore sul vincolo di unique nel DB del campo Email
			$errore = $this->checkUnique_Email($localConn);
			
			//Se non ci sono stati errori procedo con l'aggiornamento
			if($errore == "")
			{
				if (file_exists("./classUtility.php"))
					require_once("./classUtility.php");
				else
					throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (classUtility.php)");
				
				if(isAuthenticated($localConn))
				{
					//Aggiornamento del campo Email della tabella UTENTE nel DB
					$upd = "update UTENTE set Email='". $this->email ."' where Username='". $this->getUsername() ."';";
					
					if(!($localConn->query($upd)))
					{
						throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $upd ."</code> per modificare l'<span lang=\"en\">email</span> nel <span lang=\"en\">database</span> [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]");
					}
				}
				else
				{
					throw new Exception("Si è verificato un problema durante la verifica delle credenziali");
				}
			}
			else
			{
				$errore = "<ul>". $errore ."</ul>";
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
}
?>