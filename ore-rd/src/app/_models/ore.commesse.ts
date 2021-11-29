export class OreCommesse {
  constructor(
    public MATRICOLA_DIPENDENTE: string,
    public DATA: Date,
    public COD_COMMESSA: string,
    public RIF_SERIE_DOC: string,
    public RIF_NUMERO_DOC: string,
    public RIF_ATV : string,
    public RIF_SOTTO_COMMESSA  : string,
    public NUM_ORE_LAVORATE : string,
    public ID_CARICAMENTO  : number
    ) {}
}
