import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { ReportService } from './../_services/report.service';
import { AlertService } from './../_services/alert.service';
import { FormControl } from '@angular/forms';
import { MomentDateAdapter, MAT_MOMENT_DATE_ADAPTER_OPTIONS } from '@angular/material-moment-adapter';
import { DateAdapter, MAT_DATE_FORMATS, MAT_DATE_LOCALE } from '@angular/material/core';
import { MatDatepicker } from '@angular/material/datepicker';
import { Moment } from 'moment';

// Depending on whether rollup is used, moment needs to be imported differently.
// Since Moment.js doesn't have a default export, we normally need to import using the `* as`
// syntax. However, rollup creates a synthetic default module and we thus need to import it using
// the `default as` syntax.
import * as _moment from 'moment';
import { formatDate } from '@angular/common';
// tslint:disable-next-line:no-duplicate-imports

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
  selector: 'app-report-completo',
  templateUrl: './report-completo.component.html',
  styleUrls: ['./report-completo.component.css'],
  providers: [{
    provide: DateAdapter,
    useClass: MomentDateAdapter,
    deps: [MAT_DATE_LOCALE, MAT_MOMENT_DATE_ADAPTER_OPTIONS]
  },
  {provide: MAT_DATE_FORMATS, useValue: MY_FORMATS}],
})
export class ReportCompletoComponent implements OnInit {
    
    isCompleto = true;
    date = new FormControl(moment());
    idProgetto: number = -1;

    constructor(private reportService: ReportService,
      private alertService: AlertService,
      private route: ActivatedRoute) { }

    chosenYearHandler(normalizedYear: Moment) {
      console.log('1');
      console.log(this.date.value);
      console.log(normalizedYear.year());
      let ctrlValue;
      if(this.date.value == null){
        ctrlValue = moment();
      }else{
        ctrlValue = this.date.value;
      }
      ctrlValue.year(normalizedYear.year());
      this.date.setValue(ctrlValue);
    }

    chosenMonthHandler(normalizedMonth: Moment, datepicker: MatDatepicker<Moment>) {
      const ctrlValue = this.date.value;
      ctrlValue.month(normalizedMonth.month());
      this.date.setValue(ctrlValue);
      datepicker.close();
    }

    ngOnInit(): void {
      this.route.params.subscribe(params => {
        this.idProgetto = +params['id_progetto']; 
      },
        error => {
        this.alertService.error(error);
      });
    }

    download() {

      let dateRapportini = '';
      if(this.date.value != null){
        dateRapportini = formatDate(this.date.value,"YYYY-MM","en-GB");
      }   
      this.reportService.downloadReportBudget(this.idProgetto, dateRapportini, this.isCompleto).subscribe(response => {
          this.openHtmlPage(response);
      },
      error => {
          // TODO
      });
    }
  
    openHtmlPage(data: any) {
        const blob = new Blob([data], { type: 'text/html' });
        const url = window.URL.createObjectURL(blob);
        window.open(url);
    }

}
