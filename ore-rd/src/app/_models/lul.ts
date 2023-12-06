export class Lul {
  constructor(
    public MATRICOLA_DIPENDENTE: string,
    public ID_DIPENDENTE: string,
    public DATA: Date,
    public ORE_PRESENZA_ORDINARIE: number,
    public DENOMINAZIONE: string
    ) {}    
}
export class LulSpecchietto {
  constructor(
    public MATRICOLA_DIPENDENTE: string,
    public MESE: string,
    public ORE_LAVORATE: number
    ) {}    
}