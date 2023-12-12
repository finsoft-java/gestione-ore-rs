import { AlertService } from './../_services/alert.service';
import { PartecipantiService } from './../_services/partecipanti.service';
import { MatTableDataSource } from '@angular/material/table';
import { Partecipante } from './../_models/partecipanti';
import { Component, OnInit, ViewChild, AfterViewInit } from '@angular/core';
import { Matricola, Periodo } from '../_models';
import { PageEvent } from '@angular/material/paginator';
import { ColumnDefinition } from '../mat-edit-table';
import {LiveAnnouncer} from '@angular/cdk/a11y';
import {MatSort, Sort, MatSortModule} from '@angular/material/sort';
import { PeriodiService } from '../_services/periodi.service';

@Component({
  selector: 'app-partecipanti-globali',
  templateUrl: './partecipanti-globali.component.html',
  styleUrls: ['./partecipanti-globali.component.css']
})

export class PartecipantiGlobaliComponent implements OnInit {

  filter: any = {
    denominazione : "",
    matricola : "",
    prcUtilizzo : "",
    mansione : "",
    dataInizio: "",
    dataFine: ""
  };
  dataSource = new MatTableDataSource<Partecipante>();
  pageSize = 500;
  pageIndex = 0;
  dataInizio: string = '';
  dataFine: string = '';
  pageSizeOptions = [50, 100, 250, 500];
  showFirstLastButtons = true;
  displayedColumns: string[] = ['DENOMINAZIONE', 'MATRICOLA', 'PCT_UTILIZZO', 'MANSIONE', 'COSTO', 'DATA', 'actions'];
  
  service!: PartecipantiService;
  allPartecipanti: Partecipante[] = [];
  maxDate: String = '';
  allNomeMatricole: Matricola[] = [];
  isLoading: Boolean = true;
  length?= 0;
  filtroPeriodo?: Periodo;
  primoPeriodo?:Periodo;
  allPeriodi: Periodo[] = [];
  

  constructor(
    private partecipanteService: PartecipantiService,
    private alertService: AlertService,
    private periodiService: PeriodiService,
    private _liveAnnouncer: LiveAnnouncer) {
  }
  @ViewChild('empTbSort') empTbSort = new MatSort();

  ngOnInit(): void {

    this.getAll(0, this.pageSize);
    this.getMatricole();
    this.getAllPeriodi();
  }

  getAllPeriodi() {
    this.periodiService.getAll().subscribe(response => {
      this.allPeriodi = response.data;
      this.dataFine = this.allPeriodi[0].DATA_FINE;
      this.dataInizio = this.allPeriodi[0].DATA_INIZIO;
      this.filtroPeriodo = new Periodo(this.dataInizio,this.dataFine);
      this.primoPeriodo = new Periodo(this.dataInizio,this.dataFine);
    })
  }

  getAllWithFilter(top: number, skip: number, filter: any) {
    this.partecipanteService.getAllWithFilter(top,skip,filter.denominazione,filter.matricola,filter.prcUtilizzo,filter.mansione,filter.dataInizio, filter.dataFine).subscribe(response => {
      this.allPartecipanti = response.data;
      this.length = response.count;
      this.isLoading = false;
      if(filter.dataFine != null && filter.dataInizio != null){
        this.dataFine = filter.dataFine;
        this.dataInizio = filter.dataInizio;
      }
      this.getMatricole();      
    },
      error => {
        this.alertService.error(error);
        this.isLoading = false;
    });
  }

  getAll(top: number, skip: number) {

    this.partecipanteService.getAll(top,skip).subscribe(response => {
      
      this.allPartecipanti = response.data;
      this.length = response.count;
      this.isLoading = false;
      this.getMatricole();
    },
      error => {
        this.alertService.error(error);
        this.isLoading = false;
      });
  }

  getRecord(row: Partecipante) {

    if (this.dataSource.data) {
      this.dataSource.data.forEach(x => x.isEditable = false);
    }

    row.isEditable = true;
  }

  getMatricole(): void {

    this.partecipanteService.getAllMatricole().subscribe(response => {
      this.allNomeMatricole = response.data;

      this.allPartecipanti.forEach(x => {
        x.DENOMINAZIONE = this.getNomeMatricola(x.ID_DIPENDENTE!);
      });

      this.allPartecipanti.sort((a, b) => a.DENOMINAZIONE!.localeCompare(b.DENOMINAZIONE!));
      this.dataSource = new MatTableDataSource<Partecipante>(this.allPartecipanti);
      this.empTbSort.disableClear = true;
      this.dataSource.sort = this.empTbSort;
    },
      error => {
        this.alertService.error(error);
      });
  }

  getNomeMatricola(idDipendente: string): string {

    if (this.allNomeMatricole == null) {
      return '(unknown)';
    }

    const pantheraObj = this.allNomeMatricole.find(x => x.ID_DIPENDENTE == idDipendente);

    return pantheraObj != null && pantheraObj.DENOMINAZIONE != null ? pantheraObj.DENOMINAZIONE : '(unknown)';
  }

  addPartecipante() {

    let newPartecipante: Partecipante;
    newPartecipante = {
      ID_DIPENDENTE: null, MATRICOLA: null, PCT_UTILIZZO: 100, MANSIONE: null, COSTO: null, DENOMINAZIONE: null, isEditable: true, isInsert: true
    };

    let data: any[] = [newPartecipante];
    if (this.dataSource.data != null) {
      data = data.concat(this.dataSource.data);
    }

    this.dataSource.data = data;
  }

  annullaEdit(row: Partecipante) {

    this.getAll(0, this.pageSize);
  }

  saveEdit(row: Partecipante, matricola: number) {

    if (row.isInsert) {

      let idDip = this.allNomeMatricole.find(x => x.MATRICOLA == matricola);
      row.ID_DIPENDENTE = idDip?.ID_DIPENDENTE!;

      this.partecipanteService.insert(row)
        .subscribe(response => {
          this.alertService.success("Partecipante inserito con successo");
          this.dataSource.data.splice(-1, 1);
          this.dataSource.data.push(response.value);
          this.dataSource.data = this.dataSource.data;
          row.isEditable = false;
          row.isInsert = false;
        },
          error => {
            this.alertService.error(error);
          });
    } else {

      this.partecipanteService.update(row)
        .subscribe(response => {
          this.alertService.success("Partecipante aggiornato con successo");
          row.isEditable = false;
        },
          error => {
            this.alertService.error(error);
          });
    }
  }

  deletePartecipante(row: Partecipante) {

    if (row.ID_DIPENDENTE != null) {

      this.partecipanteService.delete(row.ID_DIPENDENTE)
        .subscribe(response => {
          this.getAll(0, this.pageSize);
        },
          error => {
            this.alertService.error("Impossibile eliminare questo partecipante. "
              + "Forse è già stata utilizzato all'interno di un Progetto?");
          });
    }
  }
  handlePageEvent(event: PageEvent) {
    this.length = event.length;
    this.pageSize = event.pageSize;
    this.pageIndex = event.pageIndex;
    if (this.pageIndex > 0) {
      this.getAllWithFilter((this.pageIndex) * this.pageSize,this.pageSize,this.filter);
    } else {
      this.getAllWithFilter(0,this.pageSize,this.filter);
    }
  }
  
  resetFilter(): void {
    delete this.filter.matricola;
    delete this.filter.denominazione;
    delete this.filter.prcUtilizzo;
    delete this.filter.mansione;  
    this.filtroPeriodo = this.primoPeriodo;
    this.getAll(0, this.pageSize);
  }

  filterRow(): void {
    if (this.filter.matricola) {
      this.filter.matricola = this.filter.matricola.trim();
    } else {
      this.filter.matricola = "";
    }
    if (this.filter.denominazione) {
      this.filter.denominazione = this.filter.denominazione.trim();
    } else {
      this.filter.denominazione = "";
    }
    if (this.filter.prcUtilizzo) {
      this.filter.prcUtilizzo = this.filter.prcUtilizzo.trim();
    } else {
      this.filter.prcUtilizzo = "";
    }
    if (this.filter.mansione) {
      this.filter.mansione = this.filter.mansione.trim();
    } else {
      this.filter.mansione = "";
    }
    if (this.filtroPeriodo) {
      this.filter.dataInizio = this.filtroPeriodo.DATA_INIZIO;
      this.filter.dataFine = this.filtroPeriodo.DATA_FINE;
    }
    this.getAllWithFilter(0,500,this.filter);
  }
  
  filtraPeriodo() {
    if (this.filtroPeriodo) {
      this.filter.dataInizio = this.filtroPeriodo.DATA_INIZIO;
      this.filter.dataFine = this.filtroPeriodo.DATA_FINE;
    }
  }

}