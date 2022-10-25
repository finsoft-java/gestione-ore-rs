import { Tipologia } from ".";

export class Progetto {
  constructor(
    public ID_PROGETTO: number|null = null,
    public ACRONIMO: string|null = null,
    public TITOLO: string|null = null,
    public GRANT_NUMBER:string|null = null,
    public ABSTRACT:string|null = null,
    public MONTE_ORE_TOT: number = 0,
    public OBIETTIVO_BUDGET_ORE: number = 0,
    public DATA_INIZIO: string|null = null,
    public DATA_FINE: string|null = null,
    public COSTO_MEDIO_UOMO: number|null = null,
    public COD_TIPO_COSTO_PANTHERA: string|null = null,
    public ID_SUPERVISOR: string|null = null,
    public ORE_GIA_ASSEGNATE: number|null = null,
    public DATA_ULTIMO_REPORT: string|null = null,
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

export class ProgettoPersona {
  constructor(
    public ID_PROGETTO: number|null = null,
    public ID_DIPENDENTE: string|null = null,
    public PCT_IMPIEGO: number = 0,
    public isEditable: boolean = false,
    public isInsert: boolean = false
    ) {}   
}

export class ProgettoCommessa {
  constructor(
    public ID_PROGETTO: number|null = null,
    public COD_COMMESSA: string|null = null,
    public PCT_COMPATIBILITA: number = 0,
    public NOTE: string|null,
    public HAS_GIUSTIFICATIVO: string = 'N',
    public GIUSTIFICATIVO_FILENAME: string|null = null,
    public ORE_PREVISTE: number | null = null,
    public ACRONIMO: string = '',
    public isEditable: boolean = false,
    public isInsert: boolean = false
    ) {}
}