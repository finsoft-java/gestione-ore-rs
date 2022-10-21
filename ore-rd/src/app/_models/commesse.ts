export class Commessa {
  constructor(
    public COD_COMMESSA: string,
    public PCT_COMPATIBILITA: number = 0,
    public NOTE: string|null,
    public HAS_GIUSTIFICATIVO: string = 'N',
    public GIUSTIFICATIVO_FILENAME: string|null = null,
    public isEditable: boolean = false,
    public isInsert: boolean = false
    ) {}    
}
