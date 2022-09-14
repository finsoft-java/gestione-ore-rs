export class Matricola {
  constructor(
    public MATRICOLA: number|null = null,
    public ID_DIPENDENTE: string|null = null,
    public DENOMINAZIONE: string|null = null
    ) {}    
}
export interface DataFirma {
    ID_PROGETTO: number,
    TITOLO: string,
    ID_SUPERVISOR: string,
    ID_DIPENDENTE: string,
    NOME_DIPENDENTE: string,
    NOME_SUPERVISOR: string,
    ULTIMA_PRESENZA_DIP: string,
    ULTIMA_PRESENZA_SUP: string,
    PRIMA_PRESENZA_SUCC_DIP: string,
    PRIMA_PRESENZA_SUCC_SUP: string,
    DATA_FIRMA: string,
    isEditable: boolean
}
