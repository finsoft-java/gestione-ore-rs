export class Partecipante {

  constructor(
    public ID_DIPENDENTE: string|null,
    public MATRICOLA: string|null,
    public PCT_UTILIZZO: number|null,
    public MANSIONE: string|null,
    public COSTO: number|null,
    public DENOMINAZIONE: string|null,
    public isEditable: boolean = false,
    public isInsert: boolean = false
  ) { }

}