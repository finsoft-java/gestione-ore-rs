-- seconda versione del software

drop table ore_consuntivate;
drop table progetti_wp_risorse;
drop table progetti_wp;

CREATE TABLE `ore-rd`.`progetti_persone` ( `ID_PROGETTO` INT NOT NULL , `MATRICOLA_DIPENDENTE` VARCHAR(50) NOT NULL , `PCT_IMPIEGO` FLOAT NOT NULL , PRIMARY KEY (`ID_PROGETTO`, `MATRICOLA_DIPENDENTE`)) ENGINE = InnoDB;
ALTER TABLE `progetti_persone` ADD FOREIGN KEY (`ID_PROGETTO`) REFERENCES `progetti`(`ID_PROGETTO`) ON DELETE RESTRICT ON UPDATE RESTRICT;

CREATE TABLE `ore-rd`.`progetti_commesse` ( `ID_PROGETTO` INT NOT NULL , `COD_COMMESSA` VARCHAR(255) NOT NULL , `PCT_COMPATIBILITA` FLOAT NOT NULL , `NOTE` TEXT NULL , `GIUSTIFICATIVO` LONGBLOB NULL , PRIMARY KEY (`ID_PROGETTO`, `COD_COMMESSA`)) ENGINE = InnoDB;
ALTER TABLE `progetti_commesse` ADD FOREIGN KEY (`ID_PROGETTO`) REFERENCES `progetti`(`ID_PROGETTO`) ON DELETE RESTRICT ON UPDATE RESTRICT;

CREATE TABLE `ore-rd`.`ore_consuntivate_commesse` ( `COD_COMMESSA` VARCHAR(255) NOT NULL , `MATRICOLA_DIPENDENTE` VARCHAR(255) NOT NULL , `DATA` DATE NOT NULL , `RIF_DOC` VARCHAR(255) NOT NULL , `RIF_RIGA_DOC` INT NOT NULL , `ORE_LAVORATE` INT NOT NULL , `TMS_CARICAMENTO` TIMESTAMP NOT NULL , PRIMARY KEY (`COD_COMMESSA`, `MATRICOLA_DIPENDENTE`, `DATA`, `RIF_DOC`, `RIF_RIGA_DOC`)) ENGINE = InnoDB;

CREATE TABLE `ore-rd`.`ore_consuntivate_progetti` ( `ID_PROGETTO` INT NOT NULL , `MATRICOLA_DIPENDENTE` VARCHAR(255) NOT NULL , `DATA` DATE NOT NULL , `ORE_LAVORATE` INT NOT NULL , `COSTO_ORARIO` DECIMAL(16,2) NULL, COSTO DECIMAL(19,2) NULL, PRIMARY KEY (`ID_PROGETTO`, `MATRICOLA_DIPENDENTE`, `DATA`)) ENGINE = InnoDB;
ALTER TABLE `ore_consuntivate_progetti` ADD FOREIGN KEY (`ID_PROGETTO`) REFERENCES `progetti`(`ID_PROGETTO`) ON DELETE RESTRICT ON UPDATE RESTRICT;

CREATE TABLE `ore-rd`.`storico_assegnazioni` ( `ID_ESECUZIONE` INT NOT NULL , `ID_PROGETTO` INT NOT NULL , `MATRICOLA_DIPENDENTE` VARCHAR(255) NOT NULL , `DATA` DATE NOT NULL , `ORE_LAVORATE` INT NOT NULL , `COD_COMMESSA` VARCHAR(255) , PRIMARY KEY (`ID_ESECUZIONE`, `ID_PROGETTO`, `MATRICOLA_DIPENDENTE`, `DATA`)) ENGINE = InnoDB;

ALTER TABLE `progetti` ADD `OBIETTIVO_BUDGET_ORE` INT NULL AFTER `MONTE_ORE_TOT`;
ALTER TABLE `progetti` ADD `DATA_ULTIMO_REPORT` DATE NULL AFTER `MATRICOLA_SUPERVISOR`, ADD `ORE_GIA_ASSEGNATE` INT NULL AFTER `DATA_ULTIMO_REPORT`;

