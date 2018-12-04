<?php

//Creazione oggetto 'Utente'
class Utente
{
	private $username="";
	private $password = "";
	private $errore = "";
	
	//Definizione del costruttore
	public function __construct($username, $password)
	{
		/* ### NOTA: Questa è una backdoor per l'utilizzo delle coppie usr-psw "user-user" e "admin-admin" ### */
		if(((strtolower($username) == "admin") && ($password == "admin")) || ((strtolower($username) == "user") && ($password == "user")))
		{
			$this->errore = $this->setUsername($username);
			$this->password = $password;
		}
		else
		{
			$errorUsername = $this->setUsername($username);
			$errorPassword = $this->setPassword($password);
			
			$this->errore = $errorUsername . $errorPassword;
		}
		
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
	private function setUsername($value)
	{
		$errore="";
		
		if((strlen($value) >= 4) && (strlen($value) <= 32))
		{
			if(preg_match("/^([a-z]|[A-Z]|[0-9]|\_|\-|\.)*$/", $value))
			{
				if(!(preg_match("/_eliminato$/i", $value)))
					//Se sono arrivato qui: la lunghezza minima e massima, ed il formato sono validi
					//Inoltre lo username non termina con la stringa "_eliminato" (qualsiasi capitalizzazione abbia)
					$this->username = $value;
				else
					//Se sono arrivato qui: la lunghezza minima e massima, ed il formato sono validi
					//Ma lo username termina con la stringa "_eliminato" (qualsiasi capitalizzazione abbia)
					$errore="<li>Il nome utente non può terminare con la stringa \"_eliminato\" (o sue varianti con le maiuscole).</li>";
			}
			else
			{
				//Lunghezza minima e massima rispettate, ma formato non corretto
				$errore="<li>Formato nome utente non valido. Sono accettati solamente:<ul><li>Lettere maiuscole e minuscole</li><li>Numeri</li><li>I simboli: . (punto), - (trattino) e _ (trattino basso)</li></ul></li>";
			}
		}
		else
		{
			$errore="<li>Nome utente di dimensione errata, minimo 4 e massimo 32 caratteri.</li>";
		}
		return $errore;
	}
	
	private function setPassword($value)
	{
		$errore="";
		
		if((strlen($value) >= 8) && (strlen($value) <= 255))
		{
			if((preg_match("/[a-z]+/", $value)) && (preg_match("/[A-Z]+/", $value)) && (preg_match("/[0-9]+/", $value)))
				//Se sono arrivato qui: la lunghezza minima e massima, ed il formato sono validi
				$this->password = $value;
			else
				//Lunghezza minima e massima rispettate, ma formato non corretto
				$errore="<li>Formato <span lang=\"en\">password</span> non valido. Usare almeno:<ul><li>una lettera maiuscola,</li><li>una lettera minuscola,</li><li>e un numero.</li></ul></li>";
		}
		else
		{
			$errore="<li><span lang=\"en\">Password</span> di dimensione errata, minimo 8 e massimo 255 caratteri.</li>";
		}
		return $errore;
	}
	
	//Metodi GET: Si occupano della lettura dei valori	
	public function getUsername()
		{return $this->username;}
	
	public function getPassword()
		{return $this->password;}
	
	protected function getErrore()
		{return $this->errore;}
	
	
	/* NOTA:
		il login è permesso con una qualunque capitalizzazione dello username, verrà sempre pescato e usato quello inserito in fase di registrazione.
		esempio: registrato come "Mario" posso loggarmi come mario, MARIO, mAriO e verrò sempre autenticato come "Mario"
	*/
	public function authenticate($conn = NULL)
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
			$errore="";
			
			//Interrogo il DB per ottenere le informazioni dell'utente
			$query = "select * from UTENTE where Username='". $_POST["username"] ."';";
			if (!($result = $localConn->query($query)))
			{
				throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> per il recupero dei dati sull'utente dal <span lang=\"en\">database</span> [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]");
			}
			else
			{
				if($result->num_rows > 0)
				{
					$row=$result->fetch_array(MYSQLI_ASSOC);
					
					//Controllo che la password inserita sia corretta
					/*Funzione utilizzata: "boolean password_verify(string $password, string $hash)"
					--> Parametro $password: Password in chiaro
					--> Parametro $hash: Hash salvato nel DB corrispondente allo username indicato*/
					if(!(password_verify($_POST["password"], $row["HashPassword"])))
					{
						$errore="<p>La <span lang=\"en\">password</span> inserita non è corretta.</p>";
					}
					else
					{
						//Controllo che l'utente che sta effettuando la login non risulti 'Eliminato'
						if($row["Eliminato"] == true)
						{
							$errore="<p>Il profilo (amministrativo) associato al nome utente inserito non è più utilizzabile in quanto risulta eliminato.</p>";
						}
						else
						{
							//Memorizzo in sessione i dati dell'utente che sta effettuando la login
							$_SESSION["username"]=$row["Username"];
							$_SESSION["password"]=$_POST["password"];
							$_SESSION["admin"]=$row["Administrator"];
						}
					}
				}
				else
				{
					$errore="<p>Non è stato trovato un profilo associato al nome utente inserito.</p>";
				}
				//Liberazione delle risorse occupate da: $result
				$result->free();
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
	
	//Cancellazione account
	public function deleteUser($conn = NULL)
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
				//Cancellazione dei dati dalla tabella UTENTE tramite apposita procedura
				$del = "CALL DELETE_USER('". $_SESSION["username"] ."');";
				
				if(!($localConn->query($del)))
				{
					throw new Exception ("eseguendo la procedura: <code>". $del ."</code> per eliminare l'utente (". $_SESSION["username"] .") e tutti i suoi commenti dal <span lang=\"en\">database</span> [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]");
				}
				else
				{
					//Logout
					session_unset();
					session_destroy();
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