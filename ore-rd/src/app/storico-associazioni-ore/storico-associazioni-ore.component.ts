import { Component, OnInit } from '@angular/core';
import { MatTableDataSource } from '@angular/material/table';
import { Caricamento, Esecuzione, Periodo } from '../_models';
import { AlertService } from '../_services/alert.service';
import { CaricamentiService } from '../_services/caricamenti.service';
import { EsecuzioniService } from '../_services/esecuzioni.service';
import { PeriodiService } from '../_services/periodi.service';

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
  isCaricamentoEliminabile = false;
  
  dataSource = new MatTableDataSource<Esecuzione>();
  displayedColumns: string[] = ['idEsecuzione', 'utente', 'tmsEsecuzione', 'totOre', 'applied', 'actions'];

  dataSource2 = new MatTableDataSource<Caricamento>();
  displayedColumns2: string[] = ['idCaricamento', 'utente', 'tmsEsecuzione', 'actions'];
  
  allPeriodiCommesse: Periodo[] = [];
  periodoCommessa?: Periodo;

  constructor(private esecuzioniService: EsecuzioniService,
    private caricamentiService: CaricamentiService,
    private periodiService: PeriodiService,
    private alertService: AlertService) { }

  ngOnInit(): void {
    this.getLast();
    this.loadPeriodi();
  }

  getLast() {
    this.esecuzioniService.getLast()
        .subscribe(response => {
          this.length = response.count;
          this.dataSource = new MatTableDataSource<Esecuzione>(response.data);
          this.checkIsCaricamentoEliminabile();
        },
        error => {
          this.alertService.error(error);
          this.checkIsCaricamentoEliminabile();
        });
    this.caricamentiService.getLast()
        .subscribe(response => {
          this.length = response.count;
          this.dataSource2 = new MatTableDataSource<Caricamento>(response.data);
          this.checkIsCaricamentoEliminabile();
        },
        error => {
          this.alertService.error(error);
          this.checkIsCaricamentoEliminabile();
        });
  }

  checkIsCaricamentoEliminabile() {
    const lastCar = this.dataSource2.data && this.dataSource2.data.length > 0 ? this.dataSource2.data[0] : null;
    const lastEsec = this.dataSource.data && this.dataSource.data.length > 0 ? this.dataSource.data[0] : null;
    this.isCaricamentoEliminabile = lastEsec == null || (lastCar != null && lastEsec.TMS_ESECUZIONE < lastCar.TMS_ESECUZIONE);
  }

  elimina(e: Esecuzione) {
    if (confirm('Stai per eliminare l\'ultima associazione ore, sei sicuro?')) {
      this.esecuzioniService.delete(e.ID_ESECUZIONE).subscribe(
        response => { this.getLast(); },
        error => { this.alertService.error(error); }
      );
    }
  }

  eliminaCaricamento(e: Caricamento) {
    if (confirm('Stai per eliminare l\'ultimo **caricamento** ore, sei sicuro?')) {
      this.caricamentiService.delete(e.ID_CARICAMENTO).subscribe(
        response => { this.getLast(); },
        error => { this.alertService.error(error); }
      );
    }
  }

  eliminaPeriodo(p: Periodo) {
    if (confirm(`Stai per eliminare il periodo ${p.DATA_INIZIO} - ${p.DATA_FINE}, sei sicuro?`)) {
      this.periodiService.eliminaPeriodo(p.DATA_INIZIO, p.DATA_FINE).subscribe(
        response => { this.loadPeriodi(); },
        error => { this.alertService.error(error); }
      );
    }
  }

  loadPeriodi() {
    this.periodiService.getAll().subscribe(response => {
      this.allPeriodiCommesse = response.data;
    });
  }

}
