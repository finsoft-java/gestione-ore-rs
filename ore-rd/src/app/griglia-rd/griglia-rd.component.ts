import { ProgettiRDService } from './../_services/progettiRD.service';
import { LulSpecchietto } from '../_models/lul';
import { Component, OnInit, ViewChild } from '@angular/core';
import { FormControl } from '@angular/forms';
import { ColumnDefinition } from '../mat-edit-table';
import { Lul, Matricola, Periodo, ProgettoRD } from '../_models';
import { LulService } from '../_services/lul.service';
import {MomentDateAdapter, MAT_MOMENT_DATE_ADAPTER_OPTIONS} from '@angular/material-moment-adapter';
import {DateAdapter, MAT_DATE_FORMATS, MAT_DATE_LOCALE} from '@angular/material/core';
import {MatDatepicker} from '@angular/material/datepicker';
import {Moment} from 'moment';

// Depending on whether rollup is used, moment needs to be imported differently.
// Since Moment.js doesn't have a default export, we normally need to import using the `* as`
// syntax. However, rollup creates a synthetic default module and we thus need to import it using
// the `default as` syntax.
import * as _moment from 'moment';
import { formatDate } from '@angular/common';
import { ProgettiService } from '../_services/progetti.service';
import { AlertService } from '../_services/alert.service';
import { PeriodiService } from '../_services/periodi.service';
import { MatTableDataSource } from '@angular/material/table';
import { MatPaginator, PageEvent } from '@angular/material/paginator';

const moment = _moment;
export const MY_FORMATS = {
  parse: {
    dateInput: 'YYYY-MM',
  },
  display: {
    dateInput: 'YYYY-MM',
    monthYearLabel: 'YYYY MMM',
    dateA11yLabel: 'LL',
    monthYearA11yLabel: 'YYYY MMMM',
  },
};

@Component({
  selector: 'app-griglia-rd',
  templateUrl: './griglia-rd.component.html',
  styleUrls: ['./griglia-rd.component.css'],
  providers: [
    {
      provide: DateAdapter,
      useClass: MomentDateAdapter,
      deps: [MAT_DATE_LOCALE, MAT_MOMENT_DATE_ADAPTER_OPTIONS]
    },
    {
      provide: MAT_DATE_FORMATS, useValue: MY_FORMATS
    }
  ],
})
export class GrigliaRDComponent implements OnInit {

  filter: any = {};
  myControl = new FormControl();
  columns: ColumnDefinition<ProgettoRD>[] = [
    {
      title: 'Progetto',
      data: 'PROGETTO'
    },{
      title: 'Matricola',
      data: 'MATRICOLA_DIPENDENTE'
    },
    {
      title: 'Data',
      data: 'DATA'
    },
    {
      title: 'Ore presenza ordinarie',
      data: 'ORE_PRESENZA_ORDINARIE'
    }
  ];
  
  displayedColumns: string[] = ['matricola', 'mese', 'ore'];
  service!: ProgettiRDService;
  date = new FormControl();
  allMatricole: Matricola[] = [];
  allPeriodi: Periodo[] = [];
  allProgetti: string[] = [];
  filtroProgetti: string =  "";
  filtroPeriodo?: Periodo;
  searchProgetto: string = "";
  pageSizeOptions = [5, 10, 25];
  showFirstLastButtons = true;
  
  @ViewChild(MatPaginator, { static: true }) paginator!: MatPaginator;
  length?= 0;
  pageSize = 10;
  pageIndex = 0;
  isAttivo = false;
  specchietto = new MatTableDataSource<LulSpecchietto>();
  constructor(private rdService: ProgettiRDService,
    private progettiService: ProgettiService,
    private alertService: AlertService,
    private periodiService: PeriodiService){
    this.service = rdService;
  }

  ngOnInit(): void {
    this.getMatricole();
    this.getAllPeriodi();
    this.getAllProgettiRD();
  }

  getAllPeriodi() {
    this.periodiService.getAll().subscribe(response => {
      this.allPeriodi = response.data;
    })
  }

  getAllProgettiRD() {
    this.filter.progetto = "Y";
    console.log(this.filter);
    this.rdService.getAll(this.filter)
      .subscribe(response => {
        response.data.forEach(element => {
          this.allProgetti.push(element["PROGETTO"]);
        });
        console.log(this.allProgetti);
        delete this.filter.progetto;
      },
      error => {
        this.alertService.error(error);
      });
  }

  filterRow(editTableComponent: any): void {
    console.log("this",this);
    if (this.filter.matricola) {
      this.filter.matricola = this.filter.matricola.trim();
    } else {
      delete this.filter.matricola;
    }
    if(this.date.value != null) {
      this.filter.month = formatDate(this.date.value,"YYYY-MM","en-GB");
    } else {
      delete this.filter.month;
    }

    if (this.filtroPeriodo) {
      this.filter.dataInizio = this.filtroPeriodo.DATA_INIZIO;
      this.filter.dataFine = this.filtroPeriodo.DATA_FINE;
    }
    if(this.searchProgetto){
      this.filter.searchProgetto = this.searchProgetto;
    }    
    editTableComponent.filter(this.filter);
  }

  // carica l'elenco di tutte le matricole da Panthera
  getMatricole(): void {
    this.progettiService.getAllMatricole()
      .subscribe(response => {
        this.allMatricole = response.data;
      },
      error => {
        this.alertService.error(error);
      });
  }

  resetFilter(editTableComponent: any): void {
    delete this.filter.matricola;
    delete this.filter.month;
    delete this.filter.data;
    delete this.filter.dataInizio;
    delete this.filter.dataFine;
    delete this.filtroPeriodo;
    this.date.setValue(null);
    editTableComponent.filter(this.filter);
  }

  chosenYearHandler(normalizedYear: Moment) {
    if (this.date.value == null) {
      this.date.setValue(moment());
    }
    const ctrlValue = this.date.value;
    ctrlValue.year(normalizedYear.year());
    this.date.setValue(ctrlValue);
  }

  chosenMonthHandler(normalizedMonth: Moment, datepicker: MatDatepicker<Moment>) {
    const ctrlValue = this.date.value;
    ctrlValue.month(normalizedMonth.month());
    this.date.setValue(ctrlValue);
    datepicker.close();
  }

  handlePageEvent(event: PageEvent) {
    console.log(event);
    this.length = event.length;
    this.pageSize = event.pageSize;
    this.pageIndex = event.pageIndex;
  }
}
