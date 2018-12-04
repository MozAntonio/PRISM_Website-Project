-- Database (STRUTTURA) per il progetto Prism Game Reviews (Tecnlogie Web 2016-2017)
-- Questo file contiene la struttura e la popolazione di test così come previsti dalla documentazione
-- Per Database (POPOLAZIONE) si usi il file popolazione.sql
-- Per i parametri di connessione si vedano le prime righe di connection.php


DROP DATABASE IF EXISTS DB_PRISM;
CREATE DATABASE DB_PRISM DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE DB_PRISM;



-- CREATE TABLE:

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS UTENTE;
DROP TABLE IF EXISTS RECENSIONE;
DROP TABLE IF EXISTS GIOCO;
DROP TABLE IF EXISTS GENERE;
DROP TABLE IF EXISTS PIATTAFORMA;
DROP TABLE IF EXISTS APPARTENENZA;
DROP TABLE IF EXISTS ESECUZIONE;
DROP TABLE IF EXISTS COMMENTO;


-- TABELLE DERIVANTI DALLE ENTITA':
CREATE TABLE UTENTE(
	Email VARCHAR(128) NOT NULL,
	Username VARCHAR(32) NOT NULL,
	DataIscrizione DATE NOT NULL,   -- Espresso nel formato "AAAA-MM-GG"
	HashPassword VARCHAR(255) NOT NULL,
	Administrator BOOLEAN DEFAULT false,   -- TRUE se e' un ADMIN, e FALSE se e' un utente STANDARD!
	Eliminato BOOLEAN DEFAULT false,   -- TRUE se e' un utente eliminato/non piu' registrato, e FALSE se e' un utente ancora attivo/registrato!
	
	UNIQUE (Email),
	
	PRIMARY KEY (Username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE RECENSIONE(
	ID INT(5) NOT NULL AUTO_INCREMENT,   -- Se si dovesse raggiungere la quota di 100.000 recensioni: "INT(5)" costituirebbe un problema!
	Titolo VARCHAR(255) NOT NULL,
	Contenuto MEDIUMTEXT NOT NULL,
	TempoLettura INT(3) NOT NULL,   -- Dato inserito automaticamente in base al numero di caratteri del testo!
	Keywords TEXT NOT NULL,
	DescrizioneHTML VARCHAR(150) NOT NULL,
	Autore VARCHAR(32) NOT NULL,
	AutoreModifica VARCHAR(32) DEFAULT NULL,   -- Dato opzionale, puo' essere anche NULL, cio' accade se non e' ancora stata apportata alcuna modifica alla recensione!
	DataPubblicazione DATE NOT NULL,   -- Espresso nel formato "AAAA-MM-GG"
	DataModifica DATE DEFAULT NULL,   -- Espresso nel formato "AAAA-MM-GG" +++ Dato opzionale, puo' essere anche NULL, cio' accade se non e' ancora stata apportata alcuna modifica alla recensione!
	
	PRIMARY KEY (ID),
	
	FOREIGN KEY (Autore) REFERENCES UTENTE(Username)
		ON DELETE RESTRICT ON UPDATE CASCADE,   -- Impedisce l'eliminazione di UTENTE se ci sono RECENSIONI, e se UTENTE viene aggiornato si aggiornano anche le RECENSIONI.
	FOREIGN KEY (AutoreModifica) REFERENCES UTENTE(Username)
		ON DELETE RESTRICT ON UPDATE CASCADE   -- Impedisce l'eliminazione di UTENTE se ci sono RECENSIONI, e se UTENTE viene aggiornato si aggiornano anche le RECENSIONI.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE GIOCO(
	IDGioco INT(5) NOT NULL,
	Titolo VARCHAR(255) NOT NULL,
	TitoloOrdinamento VARCHAR(255) NOT NULL,
	CoverExt CHAR(3) NOT NULL,
	CoverDescr VARCHAR(255) NOT NULL,
	-- Inizio Dati Tecnici:
	Sviluppatore VARCHAR(255) NOT NULL,
	AnnoUscita SMALLINT(4) NOT NULL,
	SitoUfficiale VARCHAR(128) DEFAULT "ND",   -- Dato opzionale, puo' essere anche NULL!
	Pegi ENUM('3+', '7+', '12+', '16+', '18+') NOT NULL,
	-- Fine Dati Tecnici!
	
	UNIQUE (Titolo, AnnoUscita),
	PRIMARY KEY (IDGioco),
	
	FOREIGN KEY (IDGioco) REFERENCES RECENSIONE(ID)
		ON DELETE CASCADE   -- Se si elimina una RECENSIONE, tale azione verra' propagata anche a GIOCO.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE GENERE(
	IDGenere TINYINT(2) NOT NULL AUTO_INCREMENT,
	Nome VARCHAR(128) NOT NULL,
	
	UNIQUE (Nome),
	PRIMARY KEY (IDGenere)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE PIATTAFORMA(
	IDPiattaforma TINYINT(2) NOT NULL AUTO_INCREMENT,
	Nome VARCHAR(64) NOT NULL,
	Versione VARCHAR(64) NOT NULL,
	AnnoRilascio SMALLINT(4) NOT NULL,
	
	UNIQUE (Nome, Versione),
	PRIMARY KEY (IDPiattaforma)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- TABELLE DERIVANTI DA RELAZIONI N-N:
CREATE TABLE APPARTENENZA(
	IDGioco INT(5) NOT NULL,
	IDGenere TINYINT(2) NOT NULL,
	
	PRIMARY KEY (IDGioco, IDGenere),
	
	FOREIGN KEY (IDGioco) REFERENCES GIOCO(IDGioco)
		ON DELETE CASCADE,   -- Se si elimina un GIOCO, tale azione verra' propagata anche ad APPARTENENZA.
	FOREIGN KEY (IDGenere) REFERENCES GENERE(IDGenere)
		ON DELETE RESTRICT   -- Impedisce l'eliminazione di GENERE se ci sono APPARTENENZE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ESECUZIONE(
	IDGioco INT(5) NOT NULL,
	IDPiattaforma TINYINT(2) NOT NULL,
	
	PRIMARY KEY (IDGioco, IDPiattaforma),
	
	FOREIGN KEY (IDGioco) REFERENCES GIOCO(IDGioco)
		ON DELETE CASCADE,   -- Se si elimina un GIOCO, tale azione verra' propagata anche ad ESECUZIONE.
	FOREIGN KEY (IDPiattaforma) REFERENCES PIATTAFORMA(IDPiattaforma)
		ON DELETE RESTRICT   -- Impedisce l'eliminazione di PIATTAFORMA se ci sono ESECUZIONI
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE COMMENTO(
	IDRecensione INT(5) NOT NULL,
	Utente VARCHAR(32) NOT NULL,
	Contenuto TEXT NOT NULL,
	DataCommento DATE NOT NULL,   -- Espresso nel formato "AAAA-MM-GG"
	
	PRIMARY KEY (IDRecensione, Utente),
	
	FOREIGN KEY (IDRecensione) REFERENCES RECENSIONE(ID)
		ON DELETE CASCADE,   -- Se si elimina una RECENSIONE, tale azione verra' propagata anche a COMMENTO.
	FOREIGN KEY (Utente) REFERENCES UTENTE(Username)
		ON DELETE CASCADE ON UPDATE CASCADE   -- Se si elimina o aggiorna un UTENTE, tale azione verra' propagata anche a COMMENTO.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS=1;



-- PROCEDURE:
DROP PROCEDURE IF EXISTS DELETE_USER;
DROP PROCEDURE IF EXISTS PROMOTE_USER;


-- Procedura per eliminare un utente:
	-- se Amministratore elimina tutti i suoi eventuali commenti, e setta il campo Eliminato a true
	-- altrimenti elimina il record da UTENTE (e per cascade si eliminano i suoi eventuali commenti)
DELIMITER |
CREATE PROCEDURE DB_PRISM.DELETE_USER(IN UsernameToDelete VARCHAR(32))
BEGIN
	DECLARE isAdmin BOOLEAN;
	SELECT Administrator INTO isAdmin FROM UTENTE WHERE Username=UsernameToDelete;
	
	IF (isAdmin) THEN
		UPDATE UTENTE SET Eliminato=true WHERE Username=UsernameToDelete;
		DELETE FROM COMMENTO WHERE Utente=UsernameToDelete;
	ELSE
		DELETE FROM UTENTE WHERE Username=UsernameToDelete;
	END IF;
END |
DELIMITER ;


-- Procedura per promuovere un utente ad amministratore
DELIMITER |
CREATE PROCEDURE DB_PRISM.PROMOTE_USER(IN UsernameToPromote VARCHAR(32))
BEGIN
	UPDATE UTENTE SET Administrator=true WHERE Username=UsernameToPromote;
END |
DELIMITER ;



-- TRIGGER:

DROP TRIGGER IF EXISTS NotDeleteGioco; /* oppure: DROP TRIGGER IF EXISTS DeleteRecensione; */
DROP TRIGGER IF EXISTS PreventIllegalInsertIntoRecensione;
DROP TRIGGER IF EXISTS PreventIllegalUpdateOnRecensione;


-- Trigger che impedisce la cancellazione di un GIOCO se ad esso vi e' collegata una RECENSIONE
-- [Ovvero, nel concreto, impedisce sempre l'eliminazione di un GIOCO!] --> Assumendo cio' il corpo del trigger si puo' ridurre alla sola INSERT (e alla SIGNAL)
DELIMITER |
CREATE TRIGGER NotDeleteGioco
BEFORE DELETE ON GIOCO
FOR EACH ROW
BEGIN
	DECLARE x INT;
	
	SELECT COUNT(*) INTO x FROM RECENSIONE R WHERE R.ID=OLD.IDGioco;
	
	IF (x > 0) THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Cancellazione del gioco fallita: impossibile elimiare il gioco.';
		-- ALTERNATIVE a SIGNAL per MySQL < 5.5
			-- INSERT INTO GIOCO SELECT * FROM GIOCO LIMIT 1; -- non informativo
			-- INSERT INTO GIOCO (AttrNotNULL, Attr2, Attr...) VALUES (NULL, value2, value...) -- non informativo
			-- CALL `Cancellazione del gioco fallita: impossibile elimiare il gioco.`; -- informativo
			-- UPDATE `Cancellazione del gioco fallita: impossibile elimiare il gioco` SET dummy=1; -- informativo
			-- INSERT INTO `Cancellazione del gioco fallita: impossibile elimiare il gioco` VALUES (1); -- informativo
			-- UPDATE `ERRORI` SET Errore='Cancellazione del gioco fallita: impossibile elimiare il gioco.' WHERE dummy=1; -- semi informativo
		--
	END IF;
END |
DELIMITER ;
/* oppure:
-- Trigger che se un GIOCO viene eliminato allora elimina anche la RECENSIONE ad esso associata
CREATE TRIGGER DeleteRecensione
AFTER DELETE ON GIOCO
FOR EACH ROW
DELETE FROM RECENSIONE WHERE ID=OLD.IDGioco;
*/


-- Trigger che impedisce inserimenti illegittimi nella tabella RECENSIONE, ovvero se:
	-- l'attributo Autore non è un Amministratore
	-- l'attributo Autore è un Amministratore ma viene settato anche l'attributo AutoreModifica e/o l'attributo DataModifica
DELIMITER |
CREATE TRIGGER PreventIllegalInsertIntoRecensione
BEFORE INSERT ON RECENSIONE
FOR EACH ROW
BEGIN
	DECLARE isAdmin BOOLEAN;
	SELECT Administrator INTO isAdmin FROM UTENTE WHERE Username=NEW.Autore;

	IF (isAdmin = false) THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Inserimento della recensione fallito: solo gli utenti amministratori possono inserire recensioni.';
		-- vedi ALTERNATIVE a SIGNAL per MySQL < 5.5 nel trigger NotDeleteGioco
	ELSEIF ((NEW.AutoreModifica IS NOT NULL) OR (NEW.DataModifica IS NOT NULL)) THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Inserimento della recensione fallito: non e'' possibile specificare AutoreModifica e/o DataModifica diversi da NULL.';
		-- vedi ALTERNATIVE a SIGNAL per MySQL < 5.5 nel trigger NotDeleteGioco
	END IF;
END |
DELIMITER ;


-- Trigger che impedisce modifiche illegittime alla tabella RECENSIONE, ovvero se:
	-- gli attributi AutoreModifica e/o DataModifica sono NULL
	-- l'attributo AutoreModifica non è NULL e non è un Amministratore
	-- l'attributo AutoreModifica non è NULL, è un Amministratore ma viene tentata la modifica degli attributi Autore e/o DataPubblicazione
DELIMITER |
CREATE TRIGGER PreventIllegalUpdateOnRecensione
BEFORE UPDATE ON RECENSIONE
FOR EACH ROW
BEGIN
	DECLARE isAdmin BOOLEAN;
	
	IF ((NEW.AutoreModifica IS NULL) OR (NEW.DataModifica IS NULL)) THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Modifica della recensione fallita: i campi AutoreModifica e/o DataModifica non possono essere NULL.';
		-- vedi ALTERNATIVE a SIGNAL per MySQL < 5.5 nel trigger NotDeleteGioco
	END IF;
	
	SELECT Administrator INTO isAdmin FROM UTENTE WHERE Username=NEW.AutoreModifica;

	IF (isAdmin = false) THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Modifica della recensione fallita: solo gli utenti amministratori possono modificare recensioni.';
		-- vedi ALTERNATIVE a SIGNAL per MySQL < 5.5 nel trigger NotDeleteGioco
	ELSEIF ((OLD.Autore <> NEW.Autore) OR (OLD.DataPubblicazione <> NEW.DataPubblicazione)) THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Modifica della recensione fallita: non e'' possibile modificare i campi Autore e/o DataPubblicazione.';
		-- vedi ALTERNATIVE a SIGNAL per MySQL < 5.5 nel trigger NotDeleteGioco
	END IF;
END |
DELIMITER ;



-- POPULATION:

/*
SET FOREIGN_KEY_CHECKS=0;

TRUNCATE TABLE UTENTE;
TRUNCATE TABLE RECENSIONE;
TRUNCATE TABLE GIOCO;
TRUNCATE TABLE GENERE;
TRUNCATE TABLE PIATTAFORMA;
TRUNCATE TABLE APPARTENENZA;
TRUNCATE TABLE ESECUZIONE;
TRUNCATE TABLE COMMENTO;

SET FOREIGN_KEY_CHECKS=1;
*/


INSERT INTO UTENTE (Email, Username, DataIscrizione, HashPassword, Administrator, Eliminato) VALUES 
('admin@example.com', 'admin', '2015-01-01', '$2y$10$XMaYjRk4u6DrDXM7fITYVO4QNn7.BZDG8VCHbAQr2QhXPdOmVp6SO', True, False),
('user@example.com', 'user', '2015-01-01', '$2y$10$G2YjJX5.XMJCLyDV7sF.nOiSMQjJVsSOMflW3zyg9cB8I/UjvR2E6', False, False),
('admin2@example.it', 'admin2', '2015-02-01', 'admin2', True, False),
('admin3@example.it', 'admin3', '2015-03-01', 'admin3', True, False),
('admin4@example.it', 'admin4', '2015-04-01', 'admin4', True, False),
('user2@example.it', 'user2', '2016-02-01', 'user2', False, False),
('user3@example.it', 'user3', '2016-03-01', 'user3', False, False),
('newAdmin@example.it', 'newAdmin', '2017-04-23', 'newAdmin', False, False);


INSERT INTO GENERE (Nome) VALUES 
('Azione'),
('Avventura'),
('Sportivo'),
('FPS'),
('Strategia');


INSERT INTO PIATTAFORMA (Nome, Versione, AnnoRilascio) VALUES 
('Xbox', 'Original', 2002),
('Xbox', '360', 2005),
('Xbox', 'One', 2013),
('PlayStation', 'Original', 1995),
('PlayStation', '2', 2000),
('PlayStation', '4', 2013),
('PlayStation', '3', 2006),
('Windows', '10', 2015);


INSERT INTO RECENSIONE (Titolo, Contenuto, TempoLettura, Keywords, DescrizioneHTML, Autore, DataPubblicazione) VALUES 
('TestRec_1', 'TestContRec_1', 4, 'test,key,words,_1', 'TestDescrHTML_1', 'admin', '2016-01-01'),
('TestRec_2', 'TestContRec_2', 13, 'test,key,words,_2', 'TestDescrHTML_2', 'admin2', '2016-01-02'),
('TestRec_3', 'TestContRec_3', 7, 'test,key,words,_3', 'TestDescrHTML_3', 'admin', '2016-01-03');

UPDATE RECENSIONE SET Titolo='Edit_TestRec_1', AutoreModifica='admin', DataModifica='2017-12-01' WHERE ID=1;
UPDATE RECENSIONE SET Titolo='Edit_TestRec_2', AutoreModifica='admin3', DataModifica='2017-12-02' WHERE ID=2;


INSERT INTO GIOCO (IDGioco, Titolo, TitoloOrdinamento, CoverExt, CoverDescr, Sviluppatore, AnnoUscita, SitoUfficiale, Pegi) VALUES 
(1, 'TestGioco_1', 'TestGioco_1', 'jpg', 'TestCoverDescrGioco_1', 'TestSvilupGioco_1', 2012, 'http://wwww.testSitoGioco_1.it', '3+'),
(2, 'TestGioco_2', 'TestGioco_2', 'jpg', 'TestCoverDescrGioco_2', 'TestSvilupGioco_2', 1998, NULL, '18+'),
(3, 'TestGioco_3', 'TestGioco_3', 'png', 'TestCoverDescrGioco_3', 'TestSvilupGioco_3', 2003, 'ND', '7+');


INSERT INTO APPARTENENZA (IDGioco, IDGenere) VALUES 
(1, 1),
(1, 2),
(1, 4),
(2, 4),
(3, 1),
(3, 5);


INSERT INTO ESECUZIONE (IDGioco, IDPiattaforma) VALUES 
(1, 2),
(1, 3),
(1, 7),
(1, 6),
(1, 8),
(2, 1),
(2, 6),
(2, 8),
(3, 3),
(3, 6),
(3, 8);


INSERT INTO COMMENTO (IDRecensione, Utente, Contenuto, DataCommento) VALUES 
(1, 'user', 'TestCommRecID1_1', '2017-01-01'),
(1, 'user2', 'TestCommRecID1_2', '2017-01-02'),
(1, 'admin', 'TestCommRecID1_3', '2017-01-03'),
(1, 'admin3', 'TestCommRecID1_4', '2017-01-04'),
(2, 'admin', 'TestCommRecID2_1', '2017-02-01'),
(2, 'newAdmin', 'TestCommRecID2_2', '2017-02-02'),
(2, 'admin3', 'TestCommRecID2_3', '2017-02-03'),
(2, 'user', 'TestCommRecID2_4', '2017-02-04'),
(3, 'newAdmin', 'TestCommRecID3_1', '2017-03-01'),
(3, 'user2', 'TestCommRecID3_2', '2017-03-02'),
(3, 'admin4', 'TestCommRecID3_3', '2017-03-03');



-- END