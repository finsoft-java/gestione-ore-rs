-- phpMyAdmin SQL Dump
-- version 5.0.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Mar 04, 2021 alle 09:15
-- Versione del server: 10.4.17-MariaDB
-- Versione PHP: 7.3.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ore-rd`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `date_firma`
--

CREATE TABLE `date_firma` (
  `MATRICOLA_DIPENDENTE` varchar(50) NOT NULL,
  `ANNO_MESE` varchar(7) NOT NULL,
  `ID_PROGETTO` int(11) NOT NULL,
  `DATA_FIRMA` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struttura della tabella `ore_consuntivate`
--

CREATE TABLE `ore_consuntivate` (
  `ID_PROGETTO` int(11) NOT NULL,
  `ID_WP` int(11) NOT NULL,
  `MATRICOLA_DIPENDENTE` varchar(50) NOT NULL,
  `DATA` date NOT NULL,
  `ORE_LAVORATE` int(11) NOT NULL,
  `COSTO_ORARIO` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dump dei dati per la tabella `ore_consuntivate`
--

INSERT INTO `ore_consuntivate` (`ID_PROGETTO`, `ID_WP`, `MATRICOLA_DIPENDENTE`, `DATA`, `ORE_LAVORATE`, `COSTO_ORARIO`) VALUES
(1, 1, 'PIPPO', '2021-02-02', 3, NULL),
(1, 1, 'PIPPO', '2021-02-03', 3, NULL),
(1, 1, 'PIPPO', '2021-02-04', 0, NULL),
(1, 1, 'PIPPO', '2021-02-05', 0, NULL),
(1, 1, 'PIPPO', '2021-02-09', 4, NULL),
(1, 1, 'PIPPO', '2021-02-10', 5, NULL),
(1, 1, 'PIPPO', '2021-02-11', 5, NULL),
(1, 1, 'PIPPO', '2021-02-14', 6, NULL),
(1, 2, 'PIPPO', '2021-02-02', 4, NULL),
(1, 2, 'PIPPO', '2021-02-03', 4, NULL),
(1, 2, 'PIPPO', '2021-02-04', 0, NULL),
(1, 2, 'PIPPO', '2021-02-05', 0, NULL),
(1, 2, 'PIPPO', '2021-02-12', 6, NULL),
(1, 2, 'PIPPO', '2021-02-14', 2, NULL);

-- --------------------------------------------------------

--
-- Struttura della tabella `ore_presenza_lul`
--

CREATE TABLE `ore_presenza_lul` (
  `MATRICOLA_DIPENDENTE` varchar(50) NOT NULL,
  `DATA` date NOT NULL,
  `ORE_PRESENZA_ORDINARIE` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dump dei dati per la tabella `ore_presenza_lul`
--

INSERT INTO `ore_presenza_lul` (`MATRICOLA_DIPENDENTE`, `DATA`, `ORE_PRESENZA_ORDINARIE`) VALUES
('PIPPO', '2021-02-02', 8),
('PIPPO', '2021-02-03', 7),
('PIPPO', '2021-02-04', 8),
('PIPPO', '2021-02-05', 7);

-- --------------------------------------------------------

--
-- Struttura della tabella `progetti`
--

CREATE TABLE `progetti` (
  `ID_PROGETTO` int(11) NOT NULL,
  `ACRONIMO` varchar(255) NOT NULL,
  `TITOLO` varchar(255) NOT NULL,
  `GRANT_NUMBER` varchar(255) NOT NULL,
  `ABSTRACT` varchar(255) DEFAULT NULL,
  `MONTE_ORE_TOT` int(11) NOT NULL,
  `DATA_INIZIO` date NOT NULL,
  `DATA_FINE` date NOT NULL,
  `COSTO_MEDIO_UOMO` decimal(19,2) NOT NULL,
  `COD_TIPO_COSTO_PANTHERA` varchar(20) NOT NULL,
  `MATRICOLA_SUPERVISOR` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dump dei dati per la tabella `progetti`
--

INSERT INTO `progetti` (`ID_PROGETTO`, `ACRONIMO`, `TITOLO`, `GRANT_NUMBER`, `ABSTRACT`, `MONTE_ORE_TOT`, `DATA_INIZIO`, `DATA_FINE`, `COSTO_MEDIO_UOMO`, `COD_TIPO_COSTO_PANTHERA`, `MATRICOLA_SUPERVISOR`) VALUES
(1, 'xxx', 'prova', 'yyy', 'hello world', 100, '2021-02-01', '2021-02-24', '20.00', 'A02', '1234');

-- --------------------------------------------------------

--
-- Struttura della tabella `progetti_spese`
--

CREATE TABLE `progetti_spese` (
  `ID_PROGETTO` int(11) NOT NULL,
  `ID_SPESA` int(11) NOT NULL,
  `DESCRIZIONE` varchar(255) NOT NULL,
  `IMPORTO` decimal(19,2) NOT NULL,
  `ID_TIPOLOGIA` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dump dei dati per la tabella `progetti_spese`
--

INSERT INTO `progetti_spese` (`ID_PROGETTO`, `ID_SPESA`, `DESCRIZIONE`, `IMPORTO`, `ID_TIPOLOGIA`) VALUES
(1, 1, '13213', '0.00', 1),
(1, 2, 'dfsadsf', '222.00', 1);

-- --------------------------------------------------------

--
-- Struttura della tabella `progetti_wp`
--

CREATE TABLE `progetti_wp` (
  `ID_PROGETTO` int(11) NOT NULL,
  `ID_WP` int(11) NOT NULL,
  `TITOLO` varchar(255) NOT NULL,
  `DESCRIZIONE` varchar(255) DEFAULT NULL,
  `DATA_INIZIO` date NOT NULL,
  `DATA_FINE` date NOT NULL,
  `MONTE_ORE` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dump dei dati per la tabella `progetti_wp`
--

INSERT INTO `progetti_wp` (`ID_PROGETTO`, `ID_WP`, `TITOLO`, `DESCRIZIONE`, `DATA_INIZIO`, `DATA_FINE`, `MONTE_ORE`) VALUES
(1, 1, 'WP1', 'regeavefav', '2021-02-01', '2021-02-17', 40),
(1, 2, 'WP2', 'vsvfdavfav', '2021-02-10', '2021-02-28', 70);

-- --------------------------------------------------------

--
-- Struttura stand-in per le viste `progetti_wp_residuo`
-- (Vedi sotto per la vista effettiva)
--
CREATE TABLE `progetti_wp_residuo` (
`ID_PROGETTO` int(11)
,`ID_WP` int(11)
,`DATA_INIZIO` date
,`DATA_FINE` date
,`MONTE_ORE_RESIDUO` decimal(33,0)
);

-- --------------------------------------------------------

--
-- Struttura della tabella `progetti_wp_risorse`
--

CREATE TABLE `progetti_wp_risorse` (
  `ID_PROGETTO` int(11) NOT NULL,
  `ID_WP` int(11) NOT NULL,
  `MATRICOLA_DIPENDENTE` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dump dei dati per la tabella `progetti_wp_risorse`
--

INSERT INTO `progetti_wp_risorse` (`ID_PROGETTO`, `ID_WP`, `MATRICOLA_DIPENDENTE`) VALUES
(1, 1, 'PIPPO'),
(1, 1, 'PLUTO'),
(1, 2, 'PAPERINO'),
(1, 2, 'PIPPO');

-- --------------------------------------------------------

--
-- Struttura della tabella `tipologie_spesa`
--

CREATE TABLE `tipologie_spesa` (
  `ID_TIPOLOGIA` int(11) NOT NULL,
  `DESCRIZIONE` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dump dei dati per la tabella `tipologie_spesa`
--

INSERT INTO `tipologie_spesa` (`ID_TIPOLOGIA`, `DESCRIZIONE`) VALUES
(1, 'pasti'),
(2, 'trasferte'),
(3, 'fuffa');

-- --------------------------------------------------------

--
-- Struttura per vista `progetti_wp_residuo`
--
DROP TABLE IF EXISTS `progetti_wp_residuo`;

create view progetti_wp_residuo
AS
SELECT wp.ID_PROGETTO, wp.ID_WP, wp.DATA_INIZIO, wp.DATA_FINE, wp.MONTE_ORE-NVL(SUM(oc.ORE_LAVORATE),0) AS MONTE_ORE_RESIDUO FROM progetti_wp wp
LEFT JOIN ore_consuntivate oc ON oc.ID_PROGETTO=wp.ID_PROGETTO AND oc.ID_WP=wp.ID_WP
GROUP BY wp.ID_PROGETTO, wp.ID_WP, wp.DATA_INIZIO, wp.DATA_FINE, wp.MONTE_ORE;


--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `date_firma`
--
ALTER TABLE `date_firma`
  ADD PRIMARY KEY (`MATRICOLA_DIPENDENTE`,`ANNO_MESE`,`ID_PROGETTO`);

--
-- Indici per le tabelle `ore_consuntivate`
--
ALTER TABLE `ore_consuntivate`
  ADD PRIMARY KEY (`ID_PROGETTO`,`ID_WP`,`MATRICOLA_DIPENDENTE`,`DATA`);

--
-- Indici per le tabelle `ore_presenza_lul`
--
ALTER TABLE `ore_presenza_lul`
  ADD PRIMARY KEY (`MATRICOLA_DIPENDENTE`,`DATA`);

--
-- Indici per le tabelle `progetti`
--
ALTER TABLE `progetti`
  ADD PRIMARY KEY (`ID_PROGETTO`) USING BTREE;

--
-- Indici per le tabelle `progetti_spese`
--
ALTER TABLE `progetti_spese`
  ADD PRIMARY KEY (`ID_PROGETTO`,`ID_SPESA`) USING BTREE,
  ADD KEY `ID_SPESA` (`ID_SPESA`) USING BTREE,
  ADD KEY `ID_TIPOLOGIA` (`ID_TIPOLOGIA`);

--
-- Indici per le tabelle `progetti_wp`
--
ALTER TABLE `progetti_wp`
  ADD PRIMARY KEY (`ID_WP`,`ID_PROGETTO`) USING BTREE,
  ADD KEY `ID_PROGETTO` (`ID_PROGETTO`);

--
-- Indici per le tabelle `progetti_wp_risorse`
--
ALTER TABLE `progetti_wp_risorse`
  ADD PRIMARY KEY (`ID_WP`,`ID_PROGETTO`,`MATRICOLA_DIPENDENTE`) USING BTREE,
  ADD KEY `progetti_wp_risorse_ibfk_1` (`ID_PROGETTO`,`ID_WP`);

--
-- Indici per le tabelle `tipologie_spesa`
--
ALTER TABLE `tipologie_spesa`
  ADD PRIMARY KEY (`ID_TIPOLOGIA`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `progetti`
--
ALTER TABLE `progetti`
  MODIFY `ID_PROGETTO` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `tipologie_spesa`
--
ALTER TABLE `tipologie_spesa`
  MODIFY `ID_TIPOLOGIA` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `ore_consuntivate`
--
ALTER TABLE `ore_consuntivate`
  ADD CONSTRAINT `ore_consuntivate_ibfk_1` FOREIGN KEY (`ID_PROGETTO`,`ID_WP`,`MATRICOLA_DIPENDENTE`) REFERENCES `progetti_wp_risorse` (`ID_PROGETTO`, `ID_WP`, `MATRICOLA_DIPENDENTE`);

--
-- Limiti per la tabella `progetti_spese`
--
ALTER TABLE `progetti_spese`
  ADD CONSTRAINT `progetti_spese_ibfk_1` FOREIGN KEY (`ID_PROGETTO`) REFERENCES `progetti` (`ID_PROGETTO`),
  ADD CONSTRAINT `progetti_spese_ibfk_2` FOREIGN KEY (`ID_TIPOLOGIA`) REFERENCES `tipologie_spesa` (`ID_TIPOLOGIA`);

--
-- Limiti per la tabella `progetti_wp`
--
ALTER TABLE `progetti_wp`
  ADD CONSTRAINT `progetti_wp_ibfk_1` FOREIGN KEY (`ID_PROGETTO`) REFERENCES `progetti` (`ID_PROGETTO`);

--
-- Limiti per la tabella `progetti_wp_risorse`
--
ALTER TABLE `progetti_wp_risorse`
  ADD CONSTRAINT `progetti_wp_risorse_ibfk_1` FOREIGN KEY (`ID_PROGETTO`,`ID_WP`) REFERENCES `progetti_wp` (`ID_PROGETTO`, `ID_WP`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

ALTER TABLE `ore_presenza_lul` CHANGE `ORE_PRESENZA_ORDINARIE` `ORE_PRESENZA_ORDINARIE` FLOAT(11) NOT NULL; 
