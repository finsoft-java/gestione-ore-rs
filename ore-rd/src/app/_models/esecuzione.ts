export class Esecuzione {
  constructor(
    public ID_ESECUZIONE: number,
    public UTENTE: string,
    public TOT_ASSEGNATE: number,
    public IS_ASSEGNATE: 1|0,
    public TMS_ESECUZIONE: Date
    ) {}    
}
