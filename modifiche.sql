CREATE TABLE `partecipanti_globali` ( `ID_DIPENDENTE` INT NOT NULL, `PCT_UTILIZZO` DECIMAL NOT NULL, `MANSIONE` VARCHAR(20) NULL DEFAULT NULL , `COSTO` DECIMAL NULL DEFAULT NULL , PRIMARY KEY (`ID_DIPENDENTE`)) ENGINE = InnoDB;

CREATE TABLE `commesse` (
  `COD_COMMESSA` VARCHAR(50) NOT NULL ,
  `PCT_COMPATIBILITA` FLOAT NOT NULL DEFAULT '100' ,
  `NOTE` TEXT NULL ,
  `GIUSTIFICATIVO` LONGBLOB NULL ,
  `GIUSTIFICATIVO_FILENAME` VARCHAR(255) NULL ,
  `TOT_ORE_PREVISTE` DECIMAL NOT NULL DEFAULT '0',
  `TOT_ORE_RD_PREVISTE` DECIMAL NOT NULL DEFAULT '0',
  `TIPOLOGIA` VARCHAR(255) NULL ,
  PRIMARY KEY (`COD_COMMESSA`)) ENGINE = InnoDB;

DELETE FROM `progetti_commesse`;

ALTER TABLE `progetti_commesse`
  DROP `PCT_COMPATIBILITA`,
  DROP `NOTE`,
  DROP `GIUSTIFICATIVO`,
  DROP `GIUSTIFICATIVO_FILENAME`;

ALTER TABLE `progetti_commesse` ADD `ORE_PREVISTE` DECIMAL NOT NULL DEFAULT '0' AFTER `COD_COMMESSA`;

ALTER TABLE `progetti_commesse` ADD FOREIGN KEY (`COD_COMMESSA`) REFERENCES `commesse`(`COD_COMMESSA`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE partecipanti_globali MODIFY ID_DIPENDENTE VARCHAR(50) NOT NULL;

ALTER TABLE `commesse` CHANGE `TOT_ORE_PREVISTE` `TOT_ORE_PREVISTE` DECIMAL(10,2) NOT NULL DEFAULT '0'; 
ALTER TABLE `commesse` CHANGE `TOT_ORE_RD_PREVISTE` `TOT_ORE_RD_PREVISTE` DECIMAL(10,2) NOT NULL DEFAULT '0'; 
ALTER TABLE `progetti_commesse` CHANGE `ORE_PREVISTE` `ORE_PREVISTE` DECIMAL(10,2) NOT NULL DEFAULT '0'; 
ALTER TABLE `partecipanti_globali` CHANGE `COSTO` `COSTO` DECIMAL(10,2) NULL DEFAULT NULL;
ALTER TABLE `assegnazioni` DROP `ID_PROGETTO`;
ALTER TABLE `assegnazioni_dettaglio` CHANGE `PCT_IMPIEGO` `PCT_UTILIZZO` FLOAT NOT NULL;
ALTER TABLE `progetti_persone` DROP `PCT_IMPIEGO`;
ALTER TABLE `assegnazioni_dettaglio` CHANGE `NUM_ORE_RESIDUE` `NUM_ORE_RESIDUE` DECIMAL(10,2) NOT NULL;
ALTER TABLE `assegnazioni_dettaglio` CHANGE `NUM_ORE_PRELEVATE` `NUM_ORE_PRELEVATE` DECIMAL(10,2) NOT NULL DEFAULT '0';