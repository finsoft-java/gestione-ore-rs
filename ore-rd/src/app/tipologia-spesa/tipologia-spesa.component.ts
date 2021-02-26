import { AlertService } from './../_services/alert.service';
import { TipoSpesaService } from './../_services/tipospesa.service';
import { Router } from '@angular/router';
import { MatTableDataSource } from '@angular/material/table';
import { MatPaginator } from '@angular/material/paginator';
import { Tipologia } from './../_models/tipologia';
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
  constructor(private router: Router, private tipoSpesaService: TipoSpesaService, private alertService: AlertService){
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
    a.isEditable=true;
  }

  saveChange(a:Tipologia){
    a.isEditable=false;


    if(a.ID_TIPOLOGIA == null){
      this.tipoSpesaService.insert(a)
      .subscribe(response => {
        this.alertService.success("Tipologia inserita con successo");
      },
      error => {
        this.alertService.error(error);
      });
    } else {
      this.tipoSpesaService.update(a)
      .subscribe(response => {
        this.alertService.success("Tipologia modificata con successo");
      },
      error => {
        this.alertService.error(error);
      });
    }
    
  }


  nuovaTipologia() {  
    let tipologia_nuova:any;
    tipologia_nuova = {ID_TIPOLOGIA:null,DESCRIZIONE:"", isEditable:true};
    const data = this.dataSource.data;
    console.log(tipologia_nuova.ID_TIPOLOGIA);
    data.push(tipologia_nuova);
    console.log(this.dataSource.data);
    this.dataSource.data = data;
  }
  
  undoChange(a:Tipologia){
    a.isEditable=false;
  }
}

