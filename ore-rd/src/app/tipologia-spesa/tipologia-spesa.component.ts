import { TipologiaService } from './../_services/tipologia.service';
import { Tipologia } from './../_models/tipologia';
import { Component, OnInit } from '@angular/core';
import { FormControl, FormArray, FormGroup, Validators } from '@angular/forms';
import { newArray } from '@angular/compiler/src/util';



@Component({
  selector: 'app-tipologia-spesa',
  templateUrl: './tipologia-spesa.component.html',
  styleUrls: ['./tipologia-spesa.component.css']
})
export class TipologiaSpesaComponent implements OnInit {

  displayedColumns: string[] = ['Id', 'Descrizione',];
  dataSource = this.tipologiaService.list$;
  controls!: FormArray;

  constructor(private tipologiaService: TipologiaService) {}

  ngOnInit(): void {
    console.log("qui", this.tipologiaService.list$)

    const toGroups = this.tipologiaService.list$.value.map(entity => {
      return new FormGroup({
        idTipologia:  new FormControl(entity.idTipologia, Validators.required),
        descrizione: new FormControl(entity.descrizione, Validators.required)
      },{updateOn: "blur"});
    });

    this.controls = new FormArray(toGroups);


  }

  updateField(index:number, field:string) {
    const control = this.getControl(index, field);
    if (control.valid) {
      this.tipologiaService.update(index,field,control.value);
    }

   }

  getControl(index:number, fieldName:string) {
    const a  = this.controls.at(index).get(fieldName) as FormControl;
    return this.controls.at(index).get(fieldName) as FormControl;
  }

}
