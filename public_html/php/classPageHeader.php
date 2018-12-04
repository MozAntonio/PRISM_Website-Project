<?php

//Definizione della classe 'PageHeader'
class PageHeader
{
	private $_TITLE_="";
	private $_BREADCRUMB_="";
	private $_DESCRIPTION_="";
	private $_KEYWORDS_="";
	private $_ROBOTS_="";
	
	public function __construct($title, $breadcrumb, $description, $keywords, $robotsIndex=true, $robotsFollow=true)
	{
		$this->_TITLE_ = $title;
		$this->_BREADCRUMB_ = $breadcrumb;
		$this->_DESCRIPTION_ = $description;
		$this->_KEYWORDS_ = $keywords;
		
		//robots
		if(!$robotsIndex && !$robotsFollow) //no index, no follow (none)
			$this->_ROBOTS_ = "noindex, nofollow";
		elseif(!$robotsIndex) //no index, si follow
			$this->_ROBOTS_ = "noindex";
		elseif(!$robotsFollow) //si index, no follow
			$this->_ROBOTS_ = "nofollow";
		else //si index, si follow (all)
			$this->_ROBOTS_ = "all";	
	}
	
	public function printer(&$page)
	{
		//opero le sostituzioni
		$page=str_replace("_TITLE_", $this->_TITLE_, $page);
		$page=str_replace("_BREADCRUMB_", $this->_BREADCRUMB_, $page);
		$page=str_replace("_DESCRIPTION_", $this->_DESCRIPTION_, $page);
		$page=str_replace("_KEYWORDS_", $this->_KEYWORDS_, $page);
		$page=str_replace("_ROBOTS_", $this->_ROBOTS_, $page);
		
		//stampo la pagina
		echo($page);
	}
}



//Definizione della classe 'PageHeaderSuccess'
class PageHeaderSuccess extends PageHeader
{
	public function __construct($title, $breadcrumb, $description="", $keywords="", $robotsIndex=false, $robotsFollow=false)
	{
		if($description=="")
			$description = "L'operazione è avvenuta con SUCCESSO, procedi con la navigazione.";
		if($keywords=="")
			$keywords = "successo,ok,procedi,complimenti,avanti,noindex,nofollow,Prism Game Reviews";
		
		parent::__construct($title, $breadcrumb, $description, $keywords, $robotsIndex, $robotsFollow);
	}
}



//Definizione della classe 'PageHeaderError'
class PageHeaderError extends PageHeader
{
	public function __construct($title, $breadcrumb, $description="", $keywords="", $robotsIndex=false, $robotsFollow=false)
	{
		if($description=="")
			$description = "Questa è una pagina di ERRORE, la risorsa richiesta o cercata non è disponibile.";
		if($keywords=="")
			$keywords = "errore,non trovato,404,non disponibile,error,not found,noindex,nofollow,Prism Game Reviews";
		
		parent::__construct($title, $breadcrumb, $description, $keywords, $robotsIndex, $robotsFollow);
	}
}



//Definizione della classe 'PageHeaderException'
class PageHeaderException extends PageHeaderError
{
	public function __construct($breadcrumb="")
	{
		if(($breadcrumb != "") && (substr($breadcrumb, -1) != "\t"))
		{
			$breadcrumb = substr($breadcrumb, 0, -5);	//elimino la chiusura dell'ultimo tag: </li>
			$breadcrumb = $breadcrumb ." - ";
		}
		else //breadcrumb vuoto o che finisce con un link
		{
			$breadcrumb = $breadcrumb ."<li>";
		}
		
		$breadcrumb = $breadcrumb ."Errore: risorsa non disponibile</li>";
		
		parent::__construct("Errore - Prism Game Reviews", $breadcrumb);
	}
}

?>