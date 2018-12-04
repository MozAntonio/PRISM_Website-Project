<?php

/*	Questo script viene invocato per MODIFICARE i dati di un utente o eliminare l'account.
 *	Al primo giro carica e stampa all'utente la form corretta:
 *		- quella relativa ai dati da modificare (email o password);
 *		- o quella per l'eliminazione (unsubscribe);
 * 	Al secondo giro (con ?check="dato" in $_GET) controlla se quanto inserito è valido:
 *		se non ci sono stati errori effettua le modifiche nel DB e comunica il successo;
 *		se ci sono stati errori risolvibili dall'utente glielo comunica sotto la form;
 *		se ci sono stati errori critici nel processo di controllo lo comunica al posto della form;
 *		se ci sono stati errori fatali stampa la pagina di avviso;
 *	Se un utente passa parametri non validi in $_GET stampa un errore al posto della form.
 */

session_start(); //Avvia la sessione

try {
	if (file_exists("../templates/pages/editProfile_page.txt") && file_exists("./utility.php"))
	{
		$page = file_get_contents("../templates/pages/editProfile_page.txt"); //carico il template
		require_once("./utility.php"); //le funzioni di stampa e isLogged
	}
	else
	{
		throw new Exception("editProfile_page.txt e utility.php", 1); //code=1 eccezione fatale
	}
	
	//Preparo i campi per l'header
	$title = "Modifica dati - Prism Game Reviews";
	$breadcrumb = "<li><a href=\"profile.php\">Profilo</a></li>\n\t\t";
	$description = "Pagina preposta alla modifica dei dati personali sul sito di Prism Game Reviews.";
	$keywords = "modifica,aggiorna,email,password,account,dati personali,gioco,videogiochi,Prism Game Reviews";
	
	if(!(isLogged()))
	{
		$text="<h1>Sezione riservata</h1>\n<p>Non risulti autenticato.</p>\n<nav>\n\t<ul>\n\t\t<li><a href=\"login.php\">Accedi</a></li>\n\t\t<li><a href=\"register.php\">Registrati</a></li>\n\t</ul>\n</nav>";
	
		//Preparo l'header della pagina di errore
		$pageH = new PageHeaderError("Sezione riservata - Prism Game Reviews", $breadcrumb ."<li>Sezione riservata</li>");
		
		//Inserisco l'avviso
		insertFeedback($page, $text, "replace");
	}
	elseif(isset($_GET["success"])) //Se sono arrivato qui dopo una modifica andata a buon fine (via PGR)
	{
		//Preparo l'header della pagina di successo
		$pageH = new PageHeaderSuccess("Modifica completata - Prism Game Reviews", $breadcrumb ."<li>Modifica completata</li>");
		
		$text = "<h1>Complimenti</h1>\n<p>La modifica è avvenuta con successo!</p>\n<nav id=\"menusuccess\">\n\t <a href=\"profile.php\">Torna al profilo</a>\n</nav>";
		//Inserisco l'avviso: tutto OK, modifica riuscita
		insertFeedback($page, $text, "replace");
	}
	else //o check o prima volta
	{
		//Se è stata inviata una modifica la controllo
		if(!(empty($_GET["check"])))
		{
			//Preparo la variabile per gli errori di modifica
			$errore="";
			
			switch ($_GET["check"])
			{
				//Caso email: richiesto il controllo su una modifica di email
				case "email":
					//Carico il template in caso di errori
					if (file_exists("../templates/editEmail.txt"))
						$form = file_get_contents("../templates/editEmail.txt"); //carico il template
					else
						throw new Exception("editEmail.txt");
						
					//Controllo che email e password non siano vuoti
					if (!(empty($_POST["email"])) && !(empty($_POST["checkPassword"])))
					{
						//Eseguo un trim sull'email
						$_POST["email"]=trim($_POST["email"]);
						
						if (file_exists("./classNuovoUtente.php"))
							require_once("./classNuovoUtente.php");
						else
							throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (classNuovoUtente.php)");
						
						//Creo un nuovo oggetto 'NuovoUtente'
						$user = new NuovoUtente($_POST["email"], $_SESSION["username"], $_SESSION["password"], $_POST["checkPassword"]);
						
						//Se non si sono verificati errori durante la creazione, procedo con l'aggiornamento nel DB dell'email
						if($user=="")
						{
							$errore = $user->updateEmail();
							
							if($errore == "") //Se non ci sono stati errori nell'aggiornamento, successo
							{
								//Direttiva di reindirizzamento (PRG)
								pleaseRedirect($page, "editProfile.php?success"); //usare con cautela
							}
						}
						else //Ci sono stati errori
						{
							$errore="<p>Si sono verificati i seguenti errori: </p>". $user;
						}
					}
					else //I campi sono vuoti
					{
						$errore="<p>Compilare tutti i campi, nessun campo è opzionale.</p>";
					}
				break;
				
				//Caso password: richiesto il controllo su una modifica di password
				case "password":
					//Carico il template in caso di errori
					if (file_exists("../templates/editPassword.txt"))
						$form = file_get_contents("../templates/editPassword.txt"); //carico il template
					else
						throw new Exception("editPassword.txt");
					
					//Controllo che non ci siano campi vuoti
					if (!(empty($_POST["oldPassword"])) && !(empty($_POST["newPassword"])) && !(empty($_POST["checkPassword"])))
					{
						if($_POST["oldPassword"] == $_SESSION["password"])
						{
							if (file_exists("./classNuovoUtente.php"))
								require_once("./classNuovoUtente.php");
							else
								throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (classNuovoUtente.php)");
							
							//Creo un nuovo oggetto 'NuovoUtente'
							$user = new NuovoUtente("esempio@dominio.it", $_SESSION["username"], $_POST["newPassword"], $_POST["checkPassword"]);
							
							//Se non si sono verificati errori durante la creazione, procedo con l'aggiornamento nel DB della password
							if($user=="")
							{
								$user->updatePassword();
								
								//Direttiva di reindirizzamento (PRG)
								pleaseRedirect($page, "editProfile.php?success"); //usare con cautela
							}
							else
							{
								//Errore in uno dei campi dati (NewPassword o CheckPassword)
								$errore="<p>Si sono verificati i seguenti errori: </p>". $user;
							}
						}
						else
						{
							$errore="<p>La vecchia <span lang=\"en\">password</span> inserita non è corretta.</p>";
						}
					}
					else
					{
						$errore="<p>Compilare tutti i campi, nessun campo è opzionale.</p>";
					}
				break;
				
				//Caso unsubscribe: richiesto il controllo sulla conferma di eliminazione profilo
				case "unsubscribe":
					//Carico il template in caso di errori
					if (file_exists("../templates/unsubscribe.txt"))
						$form = file_get_contents("../templates/unsubscribe.txt"); //carico il template
					else
						throw new Exception("unsubscribe.txt");
					
					//Se è amministratore
					if($_SESSION["admin"]==true)
						$disclaimer="<ul>\n\t\t\t<li>L'eliminazione dal nostro sistema di tutti i dati personali;</li>\n\t\t\t<li>L'eliminazione di tutti gli eventuali commenti dell'utente;</li>\n\t\t\t<li>Il nome utente in uso, in quanto amministratore, non tornerà disponibile;</li>\n\t\t\t<li>Le eventuali recensioni scritte o modificate non verranno eliminate;</li>\n\t\t\t<li>Tali recensioni riporteranno come Autore e/o Autore Modifica il nome utente in uso seguito da &quot;_eliminato&quot;.</li>\n\t\t</ul>";
					else //Altrimenti utente standard
						$disclaimer="<ul>\n\t\t\t<li>L'eliminazione dal nostro sistema di tutti i dati personali;</li>\n\t\t\t<li>L'eliminazione di tutti gli eventuali commenti dell'utente;</li>\n\t\t\t<li>Il nome utente in uso tornerà disponibile.</li>\n\t\t</ul>";
					
					$form = str_replace("_AVVISO_", $disclaimer, $form);
					
					//Controllo che non ci siano campi vuoti
					if (!(empty($_POST["ack"])) && ($_POST["ack"] == "on"))
					{
						if (file_exists("./classUtente.php"))
							require_once("./classUtente.php");
						else
							throw new Exception("<span lang=\"en\">File</span> necessario per l'esecuzione non trovato (classUtente.php)");
						
						//Creo un nuovo oggetto 'Utente'
						$user = new Utente($_SESSION["username"], $_SESSION["password"]);
						
						//Non si verificano errori durante la creazione, perchè i param sono in $_session
						$user->deleteUser();
						
						//Direttiva di reindirizzamento (PRG)
						pleaseRedirect($page, "login.php"); //usare con cautela
					}
					else
					{
						$errore="<p>Per procedere conferma di aver letto e compreso il <span lang=\"en\">disclaimer</span>.</p>";
					}
				break;
				
				//tentativo di check su un dato non valido
				default:
					//Preparo l'header della pagina di errore
					$pageH = new PageHeaderError("Errore modifica - Prism Game Reviews", $breadcrumb ."<li>Errore modifica</li>");
					
					//Inserisco l'avviso
					insertFeedback($page, "<h1>Oops ...</h1>\n<p>Impossibile verificare la modifica al dato inviata. Si prega di riprovare.</p>", "replace");
			}
			
			if($errore != "")
			{
				//Preparo l'header della pagina di errore
				$pageH = new PageHeaderError("Errore modifica - Prism Game Reviews", $breadcrumb ."<li>Errore modifica</li>");
				
				//inserisco la form
				$page = str_replace("_FORM_", $form, $page);
				
				//Inserisco l'elenco degli errori sotto la form
				insertFeedback($page, $errore, "append");
			}
			
		}
		else //non è stata inviata una modifica
		{
			//Preparo la variabile per gli errori sulla richiesta inviata
			$erroreDato="";
			
			//Se è la prima volta che arrivo su questa pagina carico la form di modifica appropriata
			if(!(empty($_GET["dato"])))
			{
				switch ($_GET["dato"])
				{
					//Caso email: richiesta modifica email
					case "email":
						if (file_exists("../templates/editEmail.txt"))
							$form = file_get_contents("../templates/editEmail.txt"); //carico il template
						else
							throw new Exception("editEmail.txt");
					break;
					
					//Caso password: richiesta modifica password
					case "password":
						if (file_exists("../templates/editPassword.txt"))
							$form = file_get_contents("../templates/editPassword.txt"); //carico il template
						else
							throw new Exception("editPassword.txt");
					break;
					
					//Caso unsubscribe: richiesta di eliminazione account
					case "unsubscribe":
						if (file_exists("../templates/unsubscribe.txt"))
							$form = file_get_contents("../templates/unsubscribe.txt"); //carico il template
						else
							throw new Exception("unsubscribe.txt");
						
						//Se è amministratore
						if($_SESSION["admin"]==true)
							$disclaimer="<ul>\n\t\t\t<li>L'eliminazione dal nostro sistema di tutti i dati personali;</li>\n\t\t\t<li>L'eliminazione di tutti gli eventuali commenti dell'utente;</li>\n\t\t\t<li>Il nome utente in uso, in quanto amministratore, non tornerà disponibile;</li>\n\t\t\t<li>Le eventuali recensioni scritte o modificate non verranno eliminate;</li>\n\t\t\t<li>Tali recensioni riporteranno come Autore e/o Autore Modifica il nome utente in uso seguito da &quot;_eliminato&quot;.</li>\n\t\t</ul>";
						else //Altrimenti utente standard
							$disclaimer="<ul>\n\t\t\t<li>L'eliminazione dal nostro sistema di tutti i dati personali;</li>\n\t\t\t<li>L'eliminazione di tutti gli eventuali commenti dell'utente;</li>\n\t\t\t<li>Il nome utente in uso tornerà disponibile.</li>\n\t\t</ul>";
						
						$form = str_replace("_AVVISO_", $disclaimer, $form);
					break;
					
					//tentativo di modifica su un dato non valido
					default:
						$erroreDato="<h1>Errore</h1><p>Dato (". htmlspecialchars($_GET["dato"], ENT_QUOTES | ENT_XHTML, "UTF-8") .") non valido o non modificabile.</p>";
				}
				
				if($erroreDato != "")
				{
					//Preparo l'header della pagina di errore
					$pageH = new PageHeaderError("Errore modifica - Prism Game Reviews", $breadcrumb ."<li>Errore modifica</li>");
					
					//Inserisco l'avviso
					insertFeedback($page, $erroreDato, "replace");
				}
				else //non ci sono stati errori
				{
					//inserisco la form da compilare
					$page = str_replace("_FORM_", $form, $page);
					
					//Preparo l'header della pagina
					if($_GET["dato"] == "unsubscribe")
						$breadcrumb = $breadcrumb ."<li>Eliminazione <span lang=\"en\">account</span></li>";
					else
						$breadcrumb = $breadcrumb ."<li>Modifica <span lang=\"en\">". $_GET["dato"] ."</span></li>";
					
					$pageH = new PageHeader($title, $breadcrumb, $description, $keywords, false, false);
				}
			}
			else //tentativo di modifica non valido
			{
				//Preparo l'header della pagina di errore
				$pageH = new PageHeaderError("Errore modifica - Prism Game Reviews", $breadcrumb ."<li>Errore modifica</li>");
				
				//Inserisco l'avviso
				insertFeedback($page, "<h1>Oops ...</h1>\n<p>Richiesta di modifica non valida. Si prega di riprovare.</p>", "replace");
			}
		}
	} //chiude isSuccess
	
	//Stampo la pagina (o con l'avviso o con la form di modifica)
	$pageH->printer($page);
	
} //chiude try

catch(Exception $e)
{
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