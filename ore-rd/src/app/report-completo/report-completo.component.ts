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
import { MatCheckboxChange } from '@angular/material/checkbox';
import { ProgettiService } from '../_services/progetti.service';
import { Periodo, Progetto } from '../_models';
import { PeriodiService } from '../_services/periodi.service';
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
    singoloMese = false;
    periodo = false;
    allPeriodi: Periodo[] = [];
    date = new FormControl(moment());
    periodoSelect: string = "";
    idProgetto: number = -1;
    progetto?: Progetto;
    dataInizio: string = '';
    dataFine: string = '';
    filtroPeriodo?: Periodo;

    constructor(private reportService: ReportService,
      private progettiService: ProgettiService,
      private alertService: AlertService,
      private route: ActivatedRoute,
      private router: Router,
      private periodiService: PeriodiService) { }

    ngOnInit(): void {
      this.route.params.subscribe(params => {
        this.idProgetto = +params['id_progetto'];
        this.progettiService.getById(this.idProgetto).subscribe(
          response => {
            this.progetto = response.value;
            this.getAllPeriodi();
          },
          error => {
            this.alertService.error(error);
          });
      },
        error => {
        this.alertService.error(error);
      });

      
    }

    getAllPeriodi() {
      this.periodiService.getAll().subscribe(response => {
        this.allPeriodi = response.data;
      })
    }
    
    chosenYearHandler(normalizedYear: Moment) {
      let ctrlValue;
      if (this.date.value == null) {
        ctrlValue = moment();
      } else {
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

    download() {
      let dateRapportini = '';
      if(this.date.value != null){
        dateRapportini = formatDate(this.date.value,"YYYY-MM","en-GB");
      }   
      if (this.filtroPeriodo) {
        this.dataInizio = this.filtroPeriodo.DATA_INIZIO;
        this.dataFine = this.filtroPeriodo.DATA_FINE;
      }
      console.log(this.filtroPeriodo);
      this.reportService.downloadReportBudget(this.idProgetto, dateRapportini, this.isCompleto, this.dataInizio, this.dataFine).subscribe(response => {
          this.openHtmlPage(response);
      },
      error => {
        // Qui error è una stringa !?!
        this.alertService.error(error);
      });
    }
  
    openHtmlPage(data: any) {
        const blob = new Blob([data], { type: 'text/html' });
        const url = window.URL.createObjectURL(blob);
        window.open(url);
    }

    changeSingoloMese($event: MatCheckboxChange) {
      console.log($event);
      this.singoloMese = $event.checked;
      this.date.setValue(null);
      console.log('HERE', this.date.value)
    }

    changePeriodo($event: MatCheckboxChange) {
      console.log($event);
      this.periodo = $event.checked;
      console.log('HERE', this.date.value)
    }

    back(){
      this.router.navigate(['/progetto', this.idProgetto])
    }
}
