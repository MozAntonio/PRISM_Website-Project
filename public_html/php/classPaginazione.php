<?php

//Creazione oggetto Paginazione
class Paginazione
{
	private $currentPage=0; //Pagina corrente
	private $previousPages=2; //Numero di link a pagine da stampare prima della pagina corrente
	private $nextPages=2; //Numero di link a pagine da stampare dopo della pagina corrente
	
	private $totalRows=0; //Numero totale di righe da paginare
	private $offsetValue=0; //Posizione dalla quale stampare i tot risultati della query (valore di OFFSET nella query)
	private $numPerPage=0; //Numero di risultati della query da stampare (al massimo) per pagina
	
	private $linkTo=""; //I link delle pagine reindirizzeranno a questo link
	
	
	//Definizione del costruttore
	public function __construct($currentPage, $totalRows, $numPerPage, $linkTo)
	{
		$this->totalRows = max($totalRows, 0);
		$this->numPerPage = max($numPerPage, 1);
		$this->currentPage = max($currentPage, 1); //Se il valore di $currentPage < 1, allora lo impongo a 1
		$this->currentPage = min($this->currentPage, $this->getTotalPages()); //Se il valore di $currentPage > "totale pagine", allora lo impongo a "totale pagine"
		$this->linkTo = $linkTo;
	}
	
	
	//Restituisce il numero totale di pagine che verranno create per ospitare il risultato della query
	public function getTotalPages()
	{
		return ceil($this->totalRows / $this->numPerPage);
	}
	
	//Restituisce la corretta posizione dalla quale stampare i risultati della query (= valore di OFFSET nella query)
	public function getOffsetValue()
	{
		if($this->currentPage == 1)
			$correctValue = $this->offsetValue;
		else
			$correctValue = ($this->currentPage - 1) * $this->numPerPage;
		
		return $correctValue;
	}
	
	
	//Ritorna il link alla prima pagina
	private function getFirstPage()
	{
		if($this->currentPage != 1)
			return "<li id=\"firstPage\"><a href=\"". $this->linkTo ."&p=1\" title=\"Prima pagina\">Inizio</a></li>";
		else
			return "";
			//return "<li id=\"firstPage\">Inizio</li>";
	}
	
	//Ritorna il link all'ultima pagina
	private function getLastPage()
	{
		if($this->currentPage != $this->getTotalPages())
			return "<li id=\"lastPage\"><a href=\"". $this->linkTo ."&p=". $this->getTotalPages() ."\" title=\"Ultima pagina\">Fine(". $this->getTotalPages() .")</a></li>";
		else
			return "";
			//return "<li id=\"lastPage\">Fine(". $this->getTotalPages() .")</li>";
	}
	
	//Ritorna il link alla pagina precedente a quella attuale
	private function getPreviousPage()
	{
		if($this->currentPage-1 > 0)
			return "<li id=\"previousPage\"><a href=\"". $this->linkTo ."&p=". ($this->currentPage-1) ."\" title=\"Pagina precedente\"><abbr title=\"Precedente\">Prec</abbr></a></li>";
		else
			return "";
			//return "<li id=\"previousPage\"><abbr title=\"Precedente\">Prec</abbr></li>";
	}
	
	//Ritorna il link alla pagina successiva a quella attuale
	private function getNextPage()
	{
		if($this->currentPage+1 <= $this->getTotalPages())
			return "<li id=\"nextPage\"><a href=\"". $this->linkTo ."&p=". ($this->currentPage+1) ."\" title=\"Pagina successiva\"><abbr title=\"Successiva\">Succ</abbr></a></li>";
		else
			return "";
			//return "<li id=\"nextPage\"><abbr title=\"Successiva\">Succ</abbr></li>";
	}
	
	
	//Ritorna l'intera paginazione
	public function printPagination()
	{
		$current = $this->currentPage;
		$prev = $this->previousPages;
		$next = $this->nextPages;
		$total = $this->getTotalPages();
		$toReturn = array();
		
		$resultPagination = "<nav class=\"skipSection\"><a href=\"#menufooter\">salta paginazione e vai al <span lang=\"en\">footer</span></a></nav>";
		$resultPagination = $resultPagination ."<nav id=\"menupagination\"><ul>";
		$resultPagination = $resultPagination . $this->getFirstPage() . $this->getPreviousPage();
		
		//N.B.: Nei commenti di questa funzione si assume che: "$maxPage = $prev + $next + 1"
		//Alla fine degli "if" l'array $toReturn conterrà una sequenza contigua di $maxPage elementi, tale sequenza non inizierà necessariamente dalla posizione zero dell'array
		
		//se il totale delle pagine è minore del numero massimo di quelle che voglio visualizzare: ho finito!
		if($total < ($prev + $next + 1))
		{
			//metto a zero il valore di $prev e $next per evitare problemi
			$prev=0;
			$next=0;
			
			for($i = $total; $i > 0; $i--) //aggiungo all'array l'elenco delle pagine da visualizzare (tutte, dove: "tutte < $maxPage")
			{
				$toReturn[$i] = $i;
			}
		}
		else
		{
			if($current == $total) //se sono sull'ultima pagina: ho finito!
			{
				if($current - ($prev + $next + 1) >= 0)	//controllo non necessario, ma effettuato per evitare problemi
				{
					for($i = $total; $i > ($total - ($prev + $next + 1)); $i--) //aggiungo solo dall'ultima fino a "ultima - $maxPage + 1"
					{
						$toReturn[$i] = $i;
					}
				}
			}
			elseif($current < $total) //altrimenti non sono sull'ultima pagina, ma controllo (anche se non necessario) di non essere oltre il limite massimo di pagine
			{
				if($current - $prev > 0) //se prima della pagina corrente ci sono almeno un numero di pagine >= $prev, allora procedo come segue
				{
					if($current + $next <= $total) //se dopo la pagina corrente ci sono almeno un numero di pagine >= $next: ho finito!
					{
						for($i = $current - $prev; $i < ($current + $next + 1); $i++) //aggiungo solo dalla "pagina corrente - $prev, fino a pagina corrente + $next"
						{
							$toReturn[$i] = $i;
						}
					}
					elseif($current + $next > $total) //altrimenti la pagina corrente è compresa tra "$next" e "$total", il controllo non è necessario ma viene effettuato per evitare problemi
					{
						for($i = $total; $i > ($total - ($prev + $next + 1)); $i--) //aggiungo solo dalla "ultima pagina fino a ultima - $maxPage + 1"
						{
							$toReturn[$i] = $i;
						}
					}
				}
				elseif($current - $prev <= 0) //altrimenti la pagina corrente è compresa tra "1" e "$prev", il controllo non è necessario ma viene effettuato per evitare problemi
				{
					for($i = 1; $i <= ($prev + $next + 1); $i++) //aggiungo solo dalla "prima pagina fino a $maxPage"
					{
						$toReturn[$i] = $i;
					}
				}
			}
		}
		
		//se l'array con l'elenco delle pagine da visualizzare non è vuoto (test non necessario, ma effettuato per sicurezza)
		if(!(empty($toReturn)))
		{
			sort($toReturn); //mantengo solo la porzione di array definita (ora l'array partirà da "0")
		}
		
		//scorro ogni elemento dell'array per ottenere i rispettivi numeri di pagina da dover stampare, e preparo il menu di paginazione
		for($i = 0; $i < count($toReturn); $i++)
		{
			if($toReturn[$i] != $current)
				$resultPagination = $resultPagination ."<li class=\"numberPage\"><a href=\"". $this->linkTo ."&p=". $toReturn[$i] ."\" title=\"Pagina ". $toReturn[$i] ."\">". $toReturn[$i] ."</a></li>";
			else
				$resultPagination = $resultPagination ."<li id=\"currentPage\">". $toReturn[$i] ."</li>";
		}
		
		$resultPagination = $resultPagination . $this->getNextPage() . $this->getLastPage();
		$resultPagination = $resultPagination ."</ul></nav>";
		
		return $resultPagination;
	}
}
?>