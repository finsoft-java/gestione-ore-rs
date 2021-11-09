-- seconda versione del software

drop table ore_consuntivate;
drop table progetti_wp_risorse;
drop table progetti_wp;
drop view progetti_wp_residuo;

-- nuove tabelle

CREATE TABLE `ore-rd`.`progetti_persone` (
  `ID_PROGETTO` INT NOT NULL ,
  `MATRICOLA_DIPENDENTE` VARCHAR(50) NOT NULL ,
  `PCT_IMPIEGO` FLOAT NOT NULL ,
  PRIMARY KEY (`ID_PROGETTO`, `MATRICOLA_DIPENDENTE`)
) ENGINE = InnoDB;
ALTER TABLE `progetti_persone` ADD FOREIGN KEY (`ID_PROGETTO`) REFERENCES `progetti`(`ID_PROGETTO`) ON DELETE RESTRICT ON UPDATE RESTRICT;

CREATE TABLE `ore-rd`.`progetti_commesse` (
  `ID_PROGETTO` INT NOT NULL ,
  `COD_COMMESSA` VARCHAR(50) NOT NULL ,
  `PCT_COMPATIBILITA` FLOAT NOT NULL ,
  `NOTE` TEXT NULL ,
  `GIUSTIFICATIVO` LONGBLOB NULL ,
  `GIUSTIFICATIVO_FILENAME` VARCHAR(255) NULL,
  PRIMARY KEY (`ID_PROGETTO`, `COD_COMMESSA`)
) ENGINE = InnoDB;
ALTER TABLE `progetti_commesse` ADD FOREIGN KEY (`ID_PROGETTO`) REFERENCES `progetti`(`ID_PROGETTO`) ON DELETE RESTRICT ON UPDATE RESTRICT;

CREATE TABLE `ore-rd`.`ore_consuntivate_commesse` (
  `COD_COMMESSA` VARCHAR(50) NOT NULL ,       
  `MATRICOLA_DIPENDENTE` VARCHAR(50) NOT NULL , 
  `DATA` DATE NOT NULL ,
  `RIF_SERIE_DOC` VARCHAR(10) NOT NULL ,
  `RIF_NUMERO_DOC` VARCHAR(50) NOT NULL ,
  `RIF_ATV` VARCHAR(10) NOT NULL , 
  `RIF_SOTTO_COMMESSA` VARCHAR(50) NOT NULL ,
  `NUM_ORE_LAVORATE` FLOAT NOT NULL ,
  `TMS_CARICAMENTO` TIMESTAMP NOT NULL ,
  PRIMARY KEY (`COD_COMMESSA`, `MATRICOLA_DIPENDENTE`, `DATA`, `RIF_SERIE_DOC`, `RIF_NUMERO_DOC`, `RIF_ATV`, `RIF_SOTTO_COMMESSA`)
) ENGINE = InnoDB;

CREATE TABLE `ore-rd`.`ore_consuntivate_progetti` (
  ID INT NOT NULL AUTO_INCREMENT,
  `ID_PROGETTO` INT NOT NULL ,
  `MATRICOLA_DIPENDENTE` VARCHAR(50) NOT NULL ,
  `DATA` DATE NOT NULL ,
  `NUM_ORE_LAVORATE` FLOAT NOT NULL ,
  `COSTO_ORARIO` DECIMAL(16,2) NULL,
  COSTO DECIMAL(19,2) NULL,
  `ID_ESECUZIONE` INT NULL ,
  PRIMARY KEY (`ID`)
) ENGINE = InnoDB;
ALTER TABLE `ore_consuntivate_progetti` ADD FOREIGN KEY (`ID_PROGETTO`) REFERENCES `progetti`(`ID_PROGETTO`) ON DELETE RESTRICT ON UPDATE RESTRICT;

CREATE TABLE `assegnazioni` (
  `ID_ESECUZIONE` INT NOT NULL ,
  `ID_PROGETTO` INT NOT NULL ,
  `UTENTE` varchar(255) NOT NULL,
  `TOT_ASSEGNATE` FLOAT NULL,
  `IS_ASSEGNATE` BOOLEAN NOT NULL DEFAULT FALSE,
  `TMS_ESECUZIONE` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID_ESECUZIONE`)
) ENGINE=InnoDB;

CREATE TABLE `assegnazioni_dettaglio` (
  `ID_ESECUZIONE` INT NOT NULL ,
  `ID_PROGETTO` INT NOT NULL ,
  `COD_COMMESSA` varchar(50) NOT NULL,
  `PCT_COMPATIBILITA` FLOAT NOT NULL,
  `MATRICOLA_DIPENDENTE` varchar(50) NOT NULL,
  `PCT_IMPIEGO` FLOAT NOT NULL,
  `DATA` date NOT NULL,
  `RIF_SERIE_DOC` VARCHAR(10) NOT NULL ,
  `RIF_NUMERO_DOC` VARCHAR(50) NOT NULL ,
  `RIF_ATV` VARCHAR(10) NOT NULL , 
  `RIF_SOTTO_COMMESSA` VARCHAR(50) NOT NULL ,
  `NUM_ORE_RESIDUE` FLOAT NOT NULL,
  `NUM_ORE_COMPATIBILI` FLOAT NOT NULL,
  `NUM_ORE_LUL` FLOAT NULL,
  `NUM_ORE_UTILIZZABILI_LUL` FLOAT NULL,
  `NUM_ORE_COMPATIBILI_LUL` FLOAT NULL,
  PRIMARY KEY (`ID_ESECUZIONE`, `ID_PROGETTO`, `COD_COMMESSA`,`MATRICOLA_DIPENDENTE`,`DATA`,`RIF_SERIE_DOC`,`RIF_NUMERO_DOC`,`RIF_ATV`,`RIF_SOTTO_COMMESSA`)
) ENGINE=InnoDB;

ALTER TABLE `assegnazioni_dettaglio` ADD FOREIGN KEY (`ID_ESECUZIONE`) REFERENCES `assegnazioni`(`ID_ESECUZIONE`) ON DELETE RESTRICT ON UPDATE RESTRICT;

CREATE VIEW ore_consuntivate_residuo as
SELECT COD_COMMESSA,MATRICOLA_DIPENDENTE,DATA,RIF_SERIE_DOC,RIF_NUMERO_DOC,RIF_ATV,RIF_SOTTO_COMMESSA,NUM_ORE_LAVORATE-(
    SELECT NVL(SUM(ad.NUM_ORE_COMPATIBILI_LUL),0)
    FROM assegnazioni_dettaglio ad
    JOIN assegnazioni a ON a.ID_ESECUZIONE=ad.ID_ESECUZIONE
    WHERE ad.COD_COMMESSA=x.COD_COMMESSA AND
        ad.RIF_SERIE_DOC=x.RIF_SERIE_DOC AND
        ad.RIF_NUMERO_DOC=x.RIF_NUMERO_DOC AND
        ad.RIF_ATV=x.RIF_ATV AND
        ad.RIF_SOTTO_COMMESSA=x.RIF_SOTTO_COMMESSA AND
        ad.NUM_ORE_COMPATIBILI_LUL>0 AND
        a.IS_ASSEGNATE=1
    ) as NUM_ORE_RESIDUE
FROM ore_consuntivate_commesse x;


-- qualche nuovo campo

ALTER TABLE `progetti` ADD `OBIETTIVO_BUDGET_ORE` INT NULL AFTER `MONTE_ORE_TOT`;
ALTER TABLE `progetti` ADD `DATA_ULTIMO_REPORT` DATE NULL AFTER `MATRICOLA_SUPERVISOR`, ADD `ORE_GIA_ASSEGNATE` INT NULL AFTER `DATA_ULTIMO_REPORT`;

