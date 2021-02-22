export class Progetto {
    idProgetto?: number | null;
    acronimo ?: string;
    titolo ?: string;
    grantNumber ?: string;
    abstract ?: string;
    monteOreTot ?: string;
    dataInizio ?: string;
    dataFine ?: string;
    costoMedioUomo ?: string;
    codTipoCostoPanthera ?: string;
    matricolaSupervisor ?: string;    
}


export let ELEMENT_DATA_PROGETTO: Progetto[] = [
  { idProgetto:1, titolo: 'Progetto1 ', dataInizio: '22/02/2021' },
  { idProgetto:2, titolo: 'Progetto2 ', dataInizio: '23/02/2021' },
  { idProgetto:3, titolo: 'Progetto3 ', dataInizio: '23/02/2021' },
  { idProgetto:4, titolo: 'Progetto4 ', dataInizio: '24/02/2021' },
  { idProgetto:5, titolo: 'Progetto5 ', dataInizio: '24/02/2021' },
  { idProgetto:6, titolo: 'Progetto6 ', dataInizio: '25/02/2021' },
  { idProgetto:7, titolo: 'Progetto7 ', dataInizio: '26/02/2021' },
  { idProgetto:8, titolo: 'Progetto8 ', dataInizio: '27/02/2021' },
  { idProgetto:9, titolo: 'Progetto9 ', dataInizio: '28/02/2021' },
  { idProgetto:10, titolo: 'Progetto10 ', dataInizio: '1/02/2021' },
  { idProgetto:11, titolo: 'Progetto11 ', dataInizio: '2/02/2021' },
  { idProgetto:12, titolo: 'Progetto12 ', dataInizio: '3/02/2021' }  
];
