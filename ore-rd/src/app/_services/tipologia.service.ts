import { Tipologia } from './../_models/tipologia';
import { Injectable } from "@angular/core";
import { BehaviorSubject } from "rxjs";


@Injectable({
  providedIn: "root"
})
export class TipologiaService {
  list: Tipologia[] = [
    { idTipologia: 1, descrizione: "Hydrogen"},
    { idTipologia: 2, descrizione: "Helium"},
    { idTipologia: 3, descrizione: "Lithium" },
    { idTipologia: 4, descrizione: "Beryllium" },
    { idTipologia: 5, descrizione: "Boron" },
    { idTipologia: 6, descrizione: "Carbon" },
    { idTipologia: 7, descrizione: "Nitrogen" },
    { idTipologia: 8, descrizione: "Oxygen" },
    { idTipologia: 9, descrizione: "Fluorine" },
    { idTipologia: 10, descrizione: "Neon" }
  ];
  list$: BehaviorSubject<Tipologia[]> = new BehaviorSubject(this.list);

  constructor() {}

 update(index:number, field:string, value:string) { 
    console.log(index, field, value);
    this.list = this.list.map((e, i) => {
      if (index === i) {
        return {
          ...e,
          [field]: value
        };
      }
      return e;
    });
    console.log(this.list);
    this.list$.next(this.list);
  } 

   getControl(index:number, fieldName:string) { console.log ("pippo")};
}
