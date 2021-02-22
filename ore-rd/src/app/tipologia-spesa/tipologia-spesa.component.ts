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
  displayedColumns: string[] = ['id', 'descrizione'];
  dataSource = new MatTableDataSource<Tipologia>(ELEMENT_DATA);
  @ViewChild(MatPaginator, { static: true }) paginator!: MatPaginator;
  ngOnInit() {
    this.dataSource.paginator = this.paginator;
  }
  getRecord(a:any){
    console.log(a);
  }
}

