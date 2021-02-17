-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Feb 16, 2021 alle 16:50
-- Versione del server: 10.4.6-MariaDB
-- Versione PHP: 7.3.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
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
-- Struttura della tabella `ore_consuntivate`
--

CREATE TABLE `ore_consuntivate` (
  `ID_PROGETTO` int(11) NOT NULL,
  `ID_WP` int(11) NOT NULL,
  `MATRICOLA_DIPENDENTE` varchar(50) NOT NULL,
  `DATA` date NOT NULL,
  `ORE_LAVORATE` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struttura della tabella `ore_presenza_lul`
--

CREATE TABLE `ore_presenza_lul` (
  `MATRICOLA_DIPENDENTE` varchar(50) NOT NULL,
  `DATA` date NOT NULL,
  `ORE_PRESENZA_ORDINARIE` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
  `MATRICOLA_SUPERVISOR` varchar(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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

-- --------------------------------------------------------

--
-- Struttura della tabella `progetti_wp_risorse`
--

CREATE TABLE `progetti_wp_risorse` (
  `ID_PROGETTO` int(11) NOT NULL,
  `ID_WP` int(11) NOT NULL,
  `MATRICOLA_DIPENDENTE` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struttura della tabella `tipologie_spesa`
--

CREATE TABLE `tipologie_spesa` (
  `ID_TIPOLOGIA` int(11) NOT NULL,
  `DESCRIZIONE` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indici per le tabelle scaricate
--

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
  ADD PRIMARY KEY (`ID_WP`,`ID_PROGETTO`,`MATRICOLA_DIPENDENTE`) USING BTREE;

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
  MODIFY `ID_PROGETTO` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `progetti_spese`
--
ALTER TABLE `progetti_spese`
  MODIFY `ID_SPESA` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `progetti_wp`
--
ALTER TABLE `progetti_wp`
  MODIFY `ID_WP` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `tipologie_spesa`
--
ALTER TABLE `tipologie_spesa`
  MODIFY `ID_TIPOLOGIA` int(11) NOT NULL AUTO_INCREMENT;

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

--aggiunto a mano
ALTER TABLE `ore_consuntivate` ADD FOREIGN KEY (`ID_PROGETTO`, `ID_WP`, `MATRICOLA_DIPENDENTE`) REFERENCES `progetti_wp_risorse`(`ID_PROGETTO`, `ID_WP`, `MATRICOLA_DIPENDENTE`) ON DELETE RESTRICT ON UPDATE RESTRICT;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
