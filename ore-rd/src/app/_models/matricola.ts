export class Matricola {
  constructor(
    public MATRICOLA?:number,
    public NOME?:string 
    ) {}    
}
export class DataFirma {
  constructor(
    public TITOLO?:number,
    public MATRICOLA_SUPERVISOR?:string,
    public MATRICOLA_DIPENDENTE?:string,
    public DATA_FIRMA?:string 
    ) {}    
}
