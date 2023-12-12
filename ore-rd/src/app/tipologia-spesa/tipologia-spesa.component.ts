import { AlertService } from './../_services/alert.service';
import { TipologiaSpesaService } from './../_services/tipospesa.service';
import { Router } from '@angular/router';
import { MatTableDataSource } from '@angular/material/table';
import { Tipologia } from './../_models/tipologia';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-tipologia-spesa',
  templateUrl: './tipologia-spesa.component.html',
  styleUrls: ['./tipologia-spesa.component.css']
})
export class TipologiaSpesaComponent /*implements OnInit*/ {
  displayedColumns: string[] = ['id', 'descrizione', 'actions'];
  dataSource = new MatTableDataSource<Tipologia>();
  allTipologie: Array<any> = [];
  isLoading: Boolean = true;

  ngOnInit() {
    //this.dataSource.paginator = this.paginator;
    this.getAll();
  }

  constructor(private router: Router, private tipologiaSpesaService: TipologiaSpesaService, private alertService: AlertService) {
  }

  getAll() {
    this.tipologiaSpesaService.getAll()
      .subscribe(response => {
        this.allTipologie = response.data;
        this.dataSource = new MatTableDataSource<Tipologia>(response.data);
        this.isLoading = false;
      },
        error => {
          this.isLoading = false;
        });
  }

  getRecord(a: Tipologia) {
    a.isEditable = true;
  }

  nuovaTipologia() {
    let tipologia_nuova: any;
    tipologia_nuova = { ID_TIPOLOGIA: null, DESCRIZIONE: "", isEditable: true };
    const data = this.dataSource.data;
    data.push(tipologia_nuova);
    this.dataSource.data = data;
  }

  saveChange(a: Tipologia): any {
    a.isEditable = false;
    if (a.ID_TIPOLOGIA == null) {
      this.tipologiaSpesaService.insert(a)
        .subscribe(response => {
          this.alertService.success("Tipologia inserita");
          this.dataSource.data.splice(-1, 1);
          this.dataSource.data.push(response.value);
          this.dataSource.data = this.dataSource.data;
        },
          error => {
            this.alertService.error(error);
          });
    } else {
      this.tipologiaSpesaService.update(a)
        .subscribe(response => {
          this.alertService.success("Tipologia modificata");
        },
          error => {
            this.alertService.error(error);
          });
    }
  }

  undoChange(a: Tipologia) {
    a.isEditable = false;
    if (a.ID_TIPOLOGIA == null) {
      this.dataSource.data.splice(-1, 1);
      this.dataSource.data = this.dataSource.data;
    }
  }

  deleteChange(a: Tipologia) {
    this.tipologiaSpesaService.delete(a.ID_TIPOLOGIA)
      .subscribe(response => {
        this.getAll();
        this.dataSource = new MatTableDataSource<Tipologia>(this.allTipologie);
      },
        error => {
          this.alertService.error("Impossibile eliminare questa tipologia. Forse è già stata utilizzata all'interno di un Progetto?");
        });
  }

}

