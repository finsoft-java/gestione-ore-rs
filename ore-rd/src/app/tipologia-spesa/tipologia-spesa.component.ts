import { TipoSpesaService } from './../_services/tipospesa.service';
import { Router } from '@angular/router';
import { MatTableDataSource } from '@angular/material/table';
import { MatPaginator } from '@angular/material/paginator';
import { ELEMENT_DATA, Tipologia } from './../_models/tipologia';
import { Component, OnInit, ViewChild } from '@angular/core';

@Component({
  selector: 'app-tipologia-spesa',
  templateUrl: './tipologia-spesa.component.html',
  styleUrls: ['./tipologia-spesa.component.css']
})
export class TipologiaSpesaComponent /*implements OnInit*/ {
  displayedColumns: string[] = ['id', 'descrizione', 'actions'];
  dataSource = new MatTableDataSource<Tipologia[]>();
  @ViewChild(MatPaginator, { static: true }) paginator!: MatPaginator;
  allTipologie: Array<any> = [];
  ngOnInit() {
    this.dataSource.paginator = this.paginator;
    this.getAll();
  }
  constructor(private router: Router, private tipoSpesaService: TipoSpesaService){
  }
  getAll() {
    this.tipoSpesaService.getAll()
        .subscribe(response => {
          console.log(response["data"]);
          this.allTipologie = response["data"];
          this.dataSource = new MatTableDataSource<Tipologia[]>(response["data"]);
        },
        error => {
        });
  }

  getRecord(a:Tipologia){
    console.log("get",a);
    a.isEditable=true;
  }

  saveChange(a:Tipologia){
    console.log("save",a);
    a.isEditable=false;
  }
  nuovaTipologia() {  
    let tipologia_nuova:any;
    tipologia_nuova = {ID_TIPOLOGIA:0,DESCRIZIONE:""};
    const data = this.dataSource.data;
    data.push(tipologia_nuova[0]);
    this.dataSource.data = data;
  }
  undoChange(a:Tipologia){
    console.log("undo",a);
    a.isEditable=false;
  }
}

