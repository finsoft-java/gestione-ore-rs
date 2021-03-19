export class Matricola {
  constructor(
    public MATRICOLA?:number,
    public NOME?:string 
    ) {}    
}
export class DataFirma {
  constructor(
    public ID_PROGETTO?:number,
    public TITOLO?:number,
    public MATRICOLA_SUPERVISOR?:string,
    public MATRICOLA_DIPENDENTE?:string,
    public DATA_FIRMA:string = '',
    public isEditable: boolean= false
    ) {}    
}
