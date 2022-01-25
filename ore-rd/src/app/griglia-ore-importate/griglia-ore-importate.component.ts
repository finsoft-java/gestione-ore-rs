import { Component, OnInit } from '@angular/core';
import { FormControl } from '@angular/forms';
import { ColumnDefinition } from '../mat-edit-table';
import { Matricola, OreCommesse } from '../_models';
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
import { OreCommesseService } from '../_services/ore.commesse.service';
import { ProgettiService } from '../_services/progetti.service';
import { AlertService } from '../_services/alert.service';

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
  selector: 'app-griglia-ore-importate',
  templateUrl: './griglia-ore-importate.component.html',
  styleUrls: ['./griglia-ore-importate.component.css'],
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
export class GrigliaOreImportateComponent implements OnInit {

  filter: any = {};
  myControl = new FormControl();
  columns: ColumnDefinition<OreCommesse>[] = [
    {
      title: 'Dipendente',
      data: 'ID_DIPENDENTE'
    },
    {
      title: 'Data',
      data: 'DATA'
    },
    {
      title: 'Nr.Doc',
      data: 'fuffa',
      //render: (x) => x.RIF_SERIE_DOC + ' ' + x.RIF_NUMERO_DOC
    },
    {
      title: 'Commessa',
      data: 'COD_COMMESSA'
    },
    {
      title: 'SottoCommessa',
      data: 'RIF_SOTTO_COMMESSA'
    },
    {
      title: 'Atv.',
      data: 'RIF_ATV'
    },
    {
      title: 'Ore lavorate',
      data: 'NUM_ORE_LAVORATE'
    }
  ];
  service!: OreCommesseService;
  date = new FormControl();
  allMatricole: Matricola[] = [];
  
  constructor(private svc: OreCommesseService,
    private progettiService: ProgettiService,
    private alertService: AlertService){
    this.service = svc;
  }

  ngOnInit(): void {
    this.getMatricole();
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

  filterRow(editTableComponent: any): void {
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

    editTableComponent.filter(this.filter);
  }

  resetFilter(editTableComponent: any): void {
    delete this.filter.matricola;
    delete this.filter.month;
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

}
