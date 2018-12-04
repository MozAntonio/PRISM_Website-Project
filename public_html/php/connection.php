<?php
define("HOST", "localhost");
define("USER", "root");
define("PASSWORD", "");
define("DB", "DB_PRISM");

//Istanzia una connessione al DB, e ne verifica l'avvenuto successo; in caso di fallimento viene sollevato l'errore
function dbConnect():mysqli
{
	$conn = new mysqli(HOST, USER, PASSWORD, DB);
	
	if ($conn->connect_errno)
	{
		throw new Exception ("Connessione al <span lang=\"en\">database</span> fallita (". $conn->connect_errno . "): ". $conn->connect_error);
	}
	
	return $conn;
}



/*
Scopo della funzione:
	- Questa funzione si prefigge l'onere di restituire un array numerico monodimensionale che contiene la lista corretta di indici richiesti,
	- l'array di output è necessario per la stampa di un ordinamento ascendente del contenuto del parametro "$result",
	- i criteri di tale ordinamento devono essere specificati grazie agli altri due parametri messi a disposizione.
I parametri in input richiesti sono: un oggetto mysqli_result per riferimento, una stringa obbligatoria, ed una stringa opzionale.
	- "$result" (oggetto mysqli_result): Tale parametro non subirà alcuna modifica (nessun side-effect), e l'iteratore verrà resettato a 0;
	- $columnOne: Tale parametro identifica il nome della (prima) colonna sulla quale effettuare l'ordinamento [asc];
	- $columnTwo: Tale parametro, se passato, identifica il nome della seconda colonna sulla quale effettuare un ulteriore ordinamento [asc].
Utilizzo del risultato della funzione:
	- (Assumendo "$temp = orderResultMysqli(...)", ora "$temp" conterrà l'array con le indicazioni per ottenere "$result" ordinato)
	- Grazie all'array "$temp" sarà possibile eseguire un ciclo esattamente lungo quanto il valore: "$result->num_rows" (o "count($temp)"),
	- ad ogni iterazione "$i" del ciclo è possibile lo spostamento dell'iteratore: "$result->data_seek($pos);" (dove "$pos" è il valore di "$temp[$i]"),
	- così facendo ad ogni loop del ciclo, con l'iteratore nella corretta posizione, avremo la possibilità di ottenere sempre il campo necessario,
	- tramite: "$result->fetch_array(MYSQLI_ASSOC)["nome_campo"]" otteniamo il valore di qualsiasi campo noi desideriamo.
*/
function orderResultMysqli(&$result, $columnOne, $columnTwo = "")
{
	$orderResult=array();
	$numRow=0;
	
	if($columnTwo != "")
	{
		while($row = $result->fetch_array(MYSQLI_ASSOC))
		{
			$orderResult[$numRow] = strtolower(strip_tags($row[$columnOne])) ." ". strtolower(strip_tags($row[$columnTwo]));
			$numRow++;
		}
	}
	else
	{
		while($row = $result->fetch_array(MYSQLI_ASSOC))
		{
			$orderResult[$numRow] = strtolower(strip_tags($row[$columnOne]));
			$numRow++;
		}
	}
	
	$result->data_seek(0);
	asort($orderResult);
	
	return array_keys($orderResult);
}
?>