import { FormControl } from '@angular/forms';
import { MatTableDataSource } from '@angular/material/table';
import { DataFirma } from './../_models/matricola';
import { Component, Input, OnInit, Output, EventEmitter } from '@angular/core';
import {MomentDateAdapter, MAT_MOMENT_DATE_ADAPTER_OPTIONS} from '@angular/material-moment-adapter';
import {DateAdapter, MAT_DATE_FORMATS, MAT_DATE_LOCALE} from '@angular/material/core';
import {MatDatepicker} from '@angular/material/datepicker';
import {Moment} from 'moment';
import * as _moment from 'moment';
import { formatDate } from '@angular/common';


const moment = _moment;
export const MY_FORMATS = {
  parse: {
    dateInput: 'YYYY-MM-DD',
  },
  display: {
    dateInput: 'YYYY-MM-DD',
    monthYearLabel: 'YYYY MMM',
    dateA11yLabel: 'LL',
    monthYearA11yLabel: 'YYYY MMMM',
  },
};
@Component({
  selector: 'app-tabella-date-firma',
  templateUrl: './tabella-date-firma.component.html',
  styleUrls: ['./tabella-date-firma.component.css'],
  providers: [{
    provide: DateAdapter,
    useClass: MomentDateAdapter,
    deps: [MAT_DATE_LOCALE, MAT_MOMENT_DATE_ADAPTER_OPTIONS]
  },
  {provide: MAT_DATE_FORMATS, useValue: MY_FORMATS}]
})
export class TabellaDateFirmaComponent implements OnInit {
  @Input() dataSourceFiglio: any;

  displayedColumns: string[] = ['titolo','matr_supervisor', 'matr_dipendente','dataFirma'];
  constructor() { }

  ngOnInit(): void {
  }

  salvaDateFirma(){
    console.log(this.dataSourceFiglio);
  }
}