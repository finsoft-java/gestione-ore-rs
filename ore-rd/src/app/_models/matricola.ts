export class Matricola {
  constructor(
    public MATRICOLA: number|null = null,
    public NOME: string|null = null
    ) {}    
}
export class DataFirma {
  constructor(
    public ID_PROGETTO: number|null = null,
    public TITOLO: string|null = null,
    public MATRICOLA_SUPERVISOR: string|null = null,
    public MATRICOLA_DIPENDENTE: string|null = null,
    public DATA_FIRMA: string = '',
    public isEditable: boolean = false
    ) {}    
}
