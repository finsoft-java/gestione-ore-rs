DROP TABLE IF EXISTS `ore_consuntivate_residuo`;

CREATE VIEW ore_consuntivate_residuo as
SELECT COD_COMMESSA,ID_DIPENDENTE,DATA,RIF_SERIE_DOC,RIF_NUMERO_DOC,RIF_ATV,RIF_SOTTO_COMMESSA,NUM_ORE_LAVORATE-(
    SELECT NVL(SUM(ad.NUM_ORE_PRELEVATE),0)
    FROM assegnazioni_dettaglio ad
    JOIN assegnazioni a ON a.ID_ESECUZIONE=ad.ID_ESECUZIONE
    WHERE ad.COD_COMMESSA=x.COD_COMMESSA AND
        ad.RIF_SERIE_DOC=x.RIF_SERIE_DOC AND
        ad.RIF_NUMERO_DOC=x.RIF_NUMERO_DOC AND
        ad.RIF_ATV=x.RIF_ATV AND
        ad.RIF_SOTTO_COMMESSA=x.RIF_SOTTO_COMMESSA AND
        ad.NUM_ORE_PRELEVATE>0 AND
        a.IS_ASSEGNATE=1
    ) as NUM_ORE_RESIDUE
FROM ore_consuntivate_commesse x;
