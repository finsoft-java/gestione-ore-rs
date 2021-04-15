import { Tipologia } from ".";

export class Progetto {
  constructor(
    public ID_PROGETTO: number|null = null,
    public ACRONIMO: string|null = null,
    public TITOLO: string|null = null,
    public GRANT_NUMBER:string|null = null,
    public ABSTRACT:string|null = null,
    public MONTE_ORE_TOT: number = 0,
    public DATA_INIZIO: string|null = null,
    public DATA_FINE: string|null = null,
    public COSTO_MEDIO_UOMO: number|null = null,
    public COD_TIPO_COSTO_PANTHERA: string|null = null,
    public MATRICOLA_SUPERVISOR: string|null = null    
    ) {}   
}
export class ProgettoSpesa {
  constructor(
    public ID_PROGETTO: number|null = null,
    public ID_SPESA: number|null = null,
    public DESCRIZIONE: string|null = null,
    public IMPORTO: number|null = null,
    public TIPOLOGIA: Tipologia|null = null,
    public isEditable: boolean = false,
    public isInsert: boolean = false
    ) {}   
}

export class ProgettoWp {
  constructor(
    public ID_PROGETTO: number|null = null,
    public ID_WP: number|null = null,
    public TITOLO: string|null = null,
    public DESCRIZIONE: string|null = null,
    public DATA_INIZIO: string|null = null,
    public DATA_FINE: string|null = null,
    public MONTE_ORE: number = 0,
    public RISORSE: any[] = [], // FIXME any 
    public isEditable: boolean= false,
    public isInsert: boolean= false
    ) {}   
}