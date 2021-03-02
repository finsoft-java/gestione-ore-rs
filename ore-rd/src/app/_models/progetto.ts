export class Progetto {
  constructor(
    public ID_PROGETTO?:number,
    public ACRONIMO?:string,
    public TITOLO?:string,
    public GRANT_NUMBER?:string,
    public ABSTRACT?:string,
    public MONTE_ORE_TOT?:string,
    public DATA_INIZIO?:string,
    public DATA_FINE?:string,
    public COSTO_MEDIO_UOMO?:string,
    public COD_TIPO_COSTO_PANTHERA?:string,
    public MATRICOLA_SUPERVISOR?:string    
    ) {}   
}
export class ProgettoSpesa {
  constructor(
    public ID_PROGETTO?:number,
    public ID_SPESA?:string,
    public DESCRIZIONE?:string,
    public IMPORTO?:string,
    public ID_TIPOLOGIA?:string,
    public isEditable: boolean= false,
    public isInsert: boolean= false
    ) {}   
}

export class ProgettoWp {
  constructor(
    public ID_PROGETTO?:number,
    public ID_WP?:number,
    public TITOLO?:string,
    public DESCRIZIONE?:string,
    public DATA_INIZIO?:string,
    public DATA_FINE?:string,
    public MONTE_ORE?:number,
    public isEditable: boolean= false,
    public isInsert: boolean= false
    ) {}   
}