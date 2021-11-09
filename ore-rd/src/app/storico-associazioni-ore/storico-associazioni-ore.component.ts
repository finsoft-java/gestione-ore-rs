import { Component, OnInit, ViewChild } from '@angular/core';
import { MatPaginator, PageEvent } from '@angular/material/paginator';
import { MatTableDataSource } from '@angular/material/table';
import { Caricamento, Esecuzione } from '../_models';
import { AlertService } from '../_services/alert.service';
import { CaricamentiService } from '../_services/caricamenti.service';
import { EsecuzioniService } from '../_services/esecuzioni.service';

@Component({
  selector: 'app-storico-associazioni-ore',
  templateUrl: './storico-associazioni-ore.component.html',
  styleUrls: ['./storico-associazioni-ore.component.css']
})
export class StoricoAssociazioniOreComponent implements OnInit {

  length? = 0;
  pageSize = 10;
  pageIndex = 0;
  pageSizeOptions = [5, 10, 25];
  showFirstLastButtons = true;
  
  dataSource = new MatTableDataSource<Esecuzione>();
  displayedColumns: string[] = ['idEsecuzione', 'idProgetto', 'utente', 'tmsEsecuzione', 'totOre', 'applied', 'actions'];

  dataSource2 = new MatTableDataSource<Caricamento>();
  displayedColumns2: string[] = ['idCaricamento', 'utente', 'tmsEsecuzione', 'actions'];

  constructor(private esecuzioniService: EsecuzioniService,
    private caricamentiService: CaricamentiService,
    private alertService: AlertService) { }

  ngOnInit(): void {
    this.getLast();
  }

  getLast() {
    this.esecuzioniService.getLast()
        .subscribe(response => {
          this.length = response.count;
          this.dataSource = new MatTableDataSource<Esecuzione>(response.data);
        },
        error => {
          this.alertService.error(error);
        });
    this.caricamentiService.getLast()
        .subscribe(response => {
          this.length = response.count;
          this.dataSource2 = new MatTableDataSource<Caricamento>(response.data);
        },
        error => {
          this.alertService.error(error);
        });
  }

  elimina(e: Esecuzione) {
    this.esecuzioniService.delete(e.ID_ESECUZIONE).subscribe(
      response => { this.getLast(); },
      error => { this.alertService.error(error); }
    );
  }

  eliminaCaricamento(e: Caricamento) {
    this.caricamentiService.delete(e.ID_CARICAMENTO).subscribe(
      response => { this.getLast(); },
      error => { this.alertService.error(error); }
    );
  }

}
