<?php

//Se c'è una sessione attiva e i suoi campi corrispondono a quelli salvati nel DB restituisce 'true', altrimenti restituisce 'false'
function isAuthenticated(mysqli $localConn):bool
{
	if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["admin"]))
	{
		$query = "select * from UTENTE where Username='". $_SESSION["username"] ."';";
		if (!($result = $localConn->query($query)))
		{
			throw new Exception ("eseguendo la <span lang=\"en\">query</span>: <code>". $query ."</code> recuperando i dei dati dell'utente dal <span lang=\"en\">database</span> per garantirne l'identità prima di effettuare inserimenti o modifiche [#". $localConn->errno ." - <span lang=\"en\">". $localConn->error ."</span>]");
		}
		else
		{
			if($result->num_rows > 0)
			{
				$row = $result->fetch_array(MYSQLI_ASSOC);
				
				if(password_verify($_SESSION["password"], $row["HashPassword"]) && ($_SESSION["admin"] == $row["Administrator"]))
				{
					$result->free();
					return true;
				}
			}
			
			$result->free();
		}
	}
	
	return false;
}

//NOTE:
/*
-->	Funzione: string strip_tags (string $str [, string $list_allowable_tags])
	Utilizzare questa funzione per: Rimuovere tutti i tag HTML e i commenti al codice
	N.B.: Non sempre è una buona idea, per esempio nel caso di tag parziali o rotti non è garantita la loro rimozione
-->	Funzione: string htmlspecialchars (string $str [, ENT_QUOTES] [, "UTF-8"])
	- Parametro $str: Testo sul quale convertire i vari caratteri speciali con le relative entità
	- Parametro ENT_QUOTES: Parametro opzionale che forza la conversione in entità sia delle doppie virgolette: ", sia del singolo apice: '
	- Parametro stringa "UTF-8": Setta il set di caratteri (charset) su cui basare le conversioni
	N.B.: La funzione converte l'apice singolo: "'", nell'entità: "&#039;"
	Quindi per uniformarlo con le altre entità si consiglia l'utilizzo dell'istruzione: $text = str_replace("&#039;", "&apos;", $text);
*/

//Funzione che converte tutti i caratteri speciali nelle loro rispettive entità HTML
function fixInput(string $text):string
{
	//Converto i caratteri speciali in entità HTML
	$text = htmlspecialchars($text, ENT_QUOTES | ENT_XHTML, "UTF-8");
	//Per coerenza con le altre entità sostituisco il codice numerico decimale dell'apice singolo con la sua versione in codice nominale
	//$text = str_replace("&#039;", "&apos;", $text); //non necessario con htmlspecialchars
	//Converto eventuali backslah con un doppio backslah
	//$text = str_replace("\\", "\\\\", $text);
	
	return $text;
}

//Funzione che converte tutti i caratteri speciali nelle loro rispettive entità HTML, lasciando però inalterati i tag <span>, <acronym>, <abbr>
function fixInputSAA(string $text):string
{
	//Sistemzione iniziale: Escape di tutti i caratteri speciali
	$text = fixInput($text);
	
	//Reinserisco i tag <span> per permettere all'utente l'indicazione della lingua (scelta fra: italiano, inglese, tedesco, francese e/o spagnolo)
	//Italiano: <span lang="it">
	$text = str_replace('&lt;span lang=&quot;it&quot;&gt;', "<span lang=\"it\">", $text);
	//Inglese: <span lang="en">
	$text = str_replace('&lt;span lang=&quot;en&quot;&gt;', "<span lang=\"en\">", $text);
	//Tedesco: <span lang="de">
	$text = str_replace('&lt;span lang=&quot;de&quot;&gt;', "<span lang=\"de\">", $text);
	//Francese: <span lang="fr">
	$text = str_replace('&lt;span lang=&quot;fr&quot;&gt;', "<span lang=\"fr\">", $text);
	//Spagnolo: <span lang="es">
	$text = str_replace('&lt;span lang=&quot;es&quot;&gt;', "<span lang=\"es\">", $text);
	//Tag di chiusura: </span>
	$text = str_replace("&lt;/span&gt;", "</span>", $text);
	
	/*
	Funzione: int preg_match_all (string $pattern, string $subject [, array &$matches] [, int $flags = PREG_PATTERN_ORDER])
	--> $pattern: Regex;
	--> $subject: Testo in cui cercare i match;
	--> $matches: Array multidimensionale passato per riferimento in cui verranno messe le sottostringhe che hanno matchato;
	--> $flags: Valore di default "PREG_PATTERN_ORDER" (mi va bene). --> Così in $matches[0] vi sarà un array che contiene tutti e soli i full match.
	
	--> Ritorna un intero che corrisponde al numero di full match trovati! (Può essere: >0, =0, =false (in caso di errori))
	*/
	
	//Reinserisco anche il possibile utilizzo del tag <acronym>
	$matchRegex=array();
	$numMatch=preg_match_all("/&lt;acronym title=&quot;((\w)+[ ]?)+&quot;&gt;/", $text, $matchRegex);
	
	if(($numMatch != false) && ($numMatch > 0))
	{
		//Tag <acronym> (apertura)
		for($i=0; $i <= $numMatch-1; $i++)
		{
			$title=explode("&quot;", $matchRegex[0][$i]);
			$text = str_replace("&lt;acronym title=&quot;". $title[1] ."&quot;&gt;", "<acronym title=\"". $title[1] ."\">", $text);
		}
		
		//Tag <acronym> (chiusura)
		$text = str_replace("&lt;/acronym&gt;", "</acronym>", $text);
	}
	
	//Reinserisco anche il possibile utilizzo del tag <abbr>
	$matchRegex=array();
	$numMatch=preg_match_all("/&lt;abbr title=&quot;((\w)+[ ]?)+&quot;&gt;/", $text, $matchRegex);
	
	if(($numMatch != false) && ($numMatch > 0))
	{
		//Tag <abbr> (apertura)
		for($i=0; $i <= $numMatch-1; $i++)
		{
			$title=explode("&quot;", $matchRegex[0][$i]);
			$text = str_replace("&lt;abbr title=&quot;". $title[1] ."&quot;&gt;", "<abbr title=\"". $title[1] ."\">", $text);
		}
		
		//Tag <abbr> (chiusura)
		$text = str_replace("&lt;/abbr&gt;", "</abbr>", $text);
	}
	
	return $text;
}

//Funzione che converte tutti i caratteri speciali nelle loro rispettive entità HTML, lasciando però inalterati i tag: <span>, <acronym>, <abbr>, <strong>, <em>
//Nota: Funzione predefinita per i commenti degli utenti
function fixInputComment(string $text):string
{
	//Sistemzione iniziale: Escape di tutti i caratteri speciali, e permesso dell'utilizzo del tag <span>, <acronym>, <abbr>
	$text = fixInputSAA($text);
	
	//Reinserisco anche il possibile utilizzo dei tag <strong> ed <em>
	//Tag <strong> (apertura e chiusura)
	$text = str_replace("&lt;strong&gt;", "<strong>", $text);
	$text = str_replace("&lt;/strong&gt;", "</strong>", $text);
	//Tag <em> (apertura e chiusura)
	$text = str_replace("&lt;em&gt;", "<em>", $text);
	$text = str_replace("&lt;/em&gt;", "</em>", $text);
	
	return $text;
}

//Funzione che converte tutti i caratteri speciali nelle loro rispettive entità HTML, lasciando però inalterati i tag: <span>, <acronym>, <abbr>, <strong>, <em>, <p>, <ul>, <ol>, <li>
//Nota: Funzione predefinita per i contenuti delle recensioni
function fixInputReview(string $text):string
{
	//Sistemzione iniziale: Escape di tutti i caratteri speciali, e permesso dell'utilizzo dei tag: <span>, <acronym>, <abbr>, <strong>, <em>
	$text = fixInputComment($text);
	
	//Reinserisco anche il possibile utilizzo dei tag <p>, <ul>, <ol> ed <li>
	//Tag <p> (apertura e chiusura)
	$text = str_replace("&lt;p&gt;", "<p>", $text);
	$text = str_replace("&lt;/p&gt;", "</p>", $text);
	//Tag <ul> (apertura e chiusura)
	$text = str_replace("&lt;ul&gt;", "<ul>", $text);
	$text = str_replace("&lt;/ul&gt;", "</ul>", $text);
	//Tag <ol> (apertura e chiusura)
	$text = str_replace("&lt;ol&gt;", "<ol>", $text);
	$text = str_replace("&lt;/ol&gt;", "</ol>", $text);
	//Tag <li> (apertura e chiusura)
	$text = str_replace("&lt;li&gt;", "<li>", $text);
	$text = str_replace("&lt;/li&gt;", "</li>", $text);
	
	return $text;
}
?>