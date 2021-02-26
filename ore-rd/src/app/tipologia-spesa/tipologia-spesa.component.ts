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
  dataSource = new MatTableDataSource<Tipologia>(ELEMENT_DATA);
  @ViewChild(MatPaginator, { static: true }) paginator!: MatPaginator;
  ngOnInit() {
    this.dataSource.paginator = this.paginator;
  }
  getRecord(a:Tipologia){
    console.log("get",a);
    a.isEditable=true;
  }

  saveChange(a:Tipologia){
    console.log("save",a);
    a.isEditable=false;
  }
  undoChange(a:Tipologia){
    console.log("undo",a);
    a.isEditable=false;
  }
}

