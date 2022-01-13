export class Matricola {
  constructor(
    public MATRICOLA: number|null = null,
    public ID_DIPENDENTE: string|null = null,
    public DENOMINAZIONE: string|null = null
    ) {}    
}
export class DataFirma {
  constructor(
    public ID_PROGETTO: number|null = null,
    public TITOLO: string|null = null,
    public ID_SUPERVISOR: string|null = null,
    public ID_DIPENDENTE: string|null = null,
    public DATA_FIRMA: string = '',
    public isEditable: boolean = false
    ) {}    
}
