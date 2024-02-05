import { LulSpecchietto } from './../_models/lul';
import { Component, OnInit, ViewChild } from '@angular/core';
import { FormControl } from '@angular/forms';
import { ColumnDefinition } from '../mat-edit-table';
import { Lul, Matricola, Periodo } from '../_models';
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
  selector: 'app-griglia-lul',
  templateUrl: './griglia-lul.component.html',
  styleUrls: ['./griglia-lul.component.css'],
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
export class GrigliaLulComponent implements OnInit {

  filter: any = {};
  myControl = new FormControl();
  columns: ColumnDefinition<Lul>[] = [
    {
      title: 'Denominazione',
      data: 'DENOMINAZIONE'
    },{
      title: 'Matricola',
      data: 'MATRICOLA_DIPENDENTE'
    },
    {
      title: 'Id.',
      data: 'ID_DIPENDENTE'
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
  
  displayedColumns: string[] = [];
  service!: LulService;
  date = new FormControl();
  allMatricole: Matricola[] = [];
  allPeriodi: Periodo[] = [];
  mesiRicerca:any[] = [];
  matricola:string="";
  oreTabella:any[] = [];
  filtroPeriodo?: Periodo;
  pageSizeOptions = [5, 10, 25];
  showFirstLastButtons = true;
  
  monthArray: any[] = ["Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno", "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre"];  
  @ViewChild(MatPaginator, { static: true }) paginator!: MatPaginator;
  length?= 0;
  pageSize = 10;
  pageSizeSpecchietto = 10;
  pageIndex = 0;
  isAttivo = false;
  mesiArray = [];
  specchietto: LulSpecchietto[] = [];
  constructor(private lulService: LulService,
    private progettiService: ProgettiService,
    private alertService: AlertService,
    private periodiService: PeriodiService){
    this.service = lulService;
  }

  ngOnInit(): void {
    this.getMatricole();
    this.getAllPeriodi();
  }

  getAllPeriodi() {
    this.periodiService.getAll().subscribe(response => {
      this.allPeriodi = response.data;
    })
  }

  filterRow(editTableComponent: any): void {
    this.displayedColumns = new Array();
    this.mesiRicerca = new Array();
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
      let dInizio = new Date(this.filter.dataInizio);  
      let dFine = new Date(this.filter.dataFine);
      const monthDiff = dFine.getMonth() - dInizio.getMonth();
      this.displayedColumns = ['matricola'];
      this.pageSizeSpecchietto = monthDiff;
      for(let i=0; i <= monthDiff; i++) {
        this.mesiRicerca.push(this.monthArray[i]);
        this.displayedColumns.push(this.monthArray[i]);
      }
    }
    
    this.getSpecchietto(0, this.pageSizeSpecchietto+1, this.filter);
    
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

  getSpecchietto(top: number, skip: number, filter:any): void {
    if(this.filter.dataInizio != null  && this.filter.dataFine != null && this.filter.matricola != null) {
      this.lulService.getSpecchietto(top, skip, filter)
      .subscribe(response => {
        this.oreTabella = new Array();
        this.specchietto = response.data;
        this.oreTabella = Array(this.mesiRicerca.length).fill(0);
        for (const month of this.mesiRicerca) {
          const dataMonth = response.data.find(item => item.MESE === month);
        
          console.log(`Month: ${month}, Data: ${JSON.stringify(dataMonth)}`);
        
          if (dataMonth && dataMonth.ORE_LAVORATE !== undefined) {
            this.oreTabella[this.mesiRicerca.indexOf(month)] = dataMonth.ORE_LAVORATE != null ? dataMonth.ORE_LAVORATE : 0;
          } else {
            // Se dataMonth è undefined o ORE_LAVORATE è undefined, assegna 0 alla posizione corrispondente in this.oreTabella
            this.oreTabella[this.mesiRicerca.indexOf(month)] = 0;
          }
        }
        /*
        for (let i = 0; i < this.monthArray.length; i++) {
          const indiceMese = response.data.findIndex(item => item.MESE === this.monthArray[i]);
          console.log(" indiceMese "+indiceMese);
          console.log(" indiceMese "+response.data[indiceMese]);
          // Verifica se l'indiceMese è valido e l'elemento è definito prima di accedere a ORE_LAVORATE
          if (indiceMese !== -1) {
            console.log(typeof response.data[indiceMese].ORE_LAVORATE);
            // Verifica se ORE_LAVORATE è un numero, altrimenti consideralo come 0
            const oreLavorate = typeof response.data[indiceMese].ORE_LAVORATE === 'number' ? response.data[indiceMese].ORE_LAVORATE : 0;
        
            // Aggiorna oreTabella
            this.oreTabella[i] = oreLavorate;
          }
          console.log(this.oreTabella);
          // Se l'elemento non è definito o ORE_LAVORATE è undefined, l'elemento rimarrà 0 per impostazione predefinita.
        }
        */
        this.matricola = response.data[0].MATRICOLA_DIPENDENTE;
        this.isAttivo = true;
      },
      error => {
        this.alertService.error(error);
      }); 
    }
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
    this.length = event.length;
    this.pageSize = event.pageSize;
    this.pageIndex = event.pageIndex;
    if (this.pageIndex > 0) {
      this.getSpecchietto((this.pageIndex) * this.pageSize, this.pageSize, this.filter);
    } else {
      this.getSpecchietto(0, this.pageSize, this.filter);
    }
  }
}
