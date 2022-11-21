export class Partecipante {

  constructor(
    public ID_DIPENDENTE: string,
    public MATRICOLA: string,
    public PCT_UTILIZZO: number,
    public MANSIONE: string,
    public COSTO: number,
    public DENOMINAZIONE: string,
    public isEditable: boolean = false,
    public isInsert: boolean = false
  ) { }

}