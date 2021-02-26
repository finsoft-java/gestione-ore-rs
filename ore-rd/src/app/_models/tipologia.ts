export class Tipologia {
    id?: number | null;
    descrizione?: string | null;
    isEditable?: boolean = false;
}


export let ELEMENT_DATA: Tipologia[] = [
  { id: 1, descrizione: 'Hydrogen'},
  { id: 2, descrizione: 'Helium'},
  { id: 3, descrizione: 'Lithium'},
  { id: 4, descrizione: 'Beryllium'},
  { id: 5, descrizione: 'Boron'},
  { id: 6, descrizione: 'Carbon' },
  { id: 7, descrizione: 'Nitrogen'},
  { id: 8, descrizione: 'Oxygen' },
  { id: 9, descrizione: 'Fluorine' },
  { id: 10, descrizione: 'Neon'},
];
