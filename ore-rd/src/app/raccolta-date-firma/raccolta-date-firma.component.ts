import { DataFirma } from './../_models/matricola';
import { Router } from '@angular/router';
import { MatPaginator } from '@angular/material/paginator';
import { MatTableDataSource } from '@angular/material/table';
import { DatitestService } from './../_services/datitest.service';
import { Component, OnInit, ViewChild } from '@angular/core';
import {FormControl} from '@angular/forms';
import {MomentDateAdapter, MAT_MOMENT_DATE_ADAPTER_OPTIONS} from '@angular/material-moment-adapter';
import {DateAdapter, MAT_DATE_FORMATS, MAT_DATE_LOCALE} from '@angular/material/core';
import {MatDatepicker} from '@angular/material/datepicker';
import {Moment} from 'moment';
import * as _moment from 'moment';
import { formatDate } from '@angular/common';
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
  selector: 'app-raccolta-date-firma',
  templateUrl: './raccolta-date-firma.component.html',
  styleUrls: ['./raccolta-date-firma.component.css'],
  providers: [{
    provide: DateAdapter,
    useClass: MomentDateAdapter,
    deps: [MAT_DATE_LOCALE, MAT_MOMENT_DATE_ADAPTER_OPTIONS]
  },
  {provide: MAT_DATE_FORMATS, useValue: MY_FORMATS}]
})
export class RaccoltaDateFirmaComponent implements OnInit {

  date = new FormControl(moment());
  step2=false;
  displayedColumns: string[] = ['titolo','matr_supervisor', 'matr_dipendente','dataFirma', 'actions'];
  dataSource = new MatTableDataSource<DataFirma[]>();
  @ViewChild(MatPaginator, { static: true }) paginator!: MatPaginator;
  router_frontend?: Router;

  chosenYearHandler(normalizedYear: Moment) {
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

  constructor(private datitestService: DatitestService) { }

  ngOnInit(): void {
    this.dataSource.paginator = this.paginator;
  }

  run() {
      this.datitestService.runDateFirma(formatDate(this.date.value,"YYYY-MM","en-GB")).subscribe(response => {
        this.dataSource = new MatTableDataSource<DataFirma[]>(response["data"]);
        this.step2 = true;
      },
      error => {
      });
  }
  getRecord(a:any){
  }
}
