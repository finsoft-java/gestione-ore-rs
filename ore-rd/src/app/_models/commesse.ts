export class Commessa {

  constructor(
    public COD_COMMESSA: string,
    public PCT_COMPATIBILITA: number = 0,
    public NOTE: string | null,
    public GIUSTIFICATIVO: string | null = null,
    public GIUSTIFICATIVO_FILENAME: string | null = null,
    public TOT_ORE_PREVISTE: number = 0,
    public TOT_ORE_RD_PREVISTE: number = 0,
    public TIPOLOGIA: string | null = null,
    public isEditable: boolean = false,
    public isInsert: boolean = false
  ) { }

}