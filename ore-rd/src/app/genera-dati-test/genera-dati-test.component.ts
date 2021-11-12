import { DatitestService } from './../_services/datitest.service';
import { Component, OnInit } from '@angular/core';
import {FormControl} from '@angular/forms';
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
import { AlertService } from '../_services/alert.service';
import { ActivatedRoute, Router } from '@angular/router';
import { ProgettiService } from '../_services/progetti.service';
import { Progetto } from '../_models';
// tslint:disable-next-line:no-duplicate-imports

const moment = _moment;
export const MY_FORMATS = {
  parse: {
      dateInput: 'LL'
  },
  display: {
      dateInput: 'DD-MM-YYYY',
      monthYearLabel: 'YYYY',
      dateA11yLabel: 'LL',
      monthYearA11yLabel: 'YYYY'
  }
};

@Component({
  selector: 'app-genera-dati-test',
  templateUrl: './genera-dati-test.component.html',
  styleUrls: ['./genera-dati-test.component.css'],
  providers: [{
    provide: DateAdapter,
    useClass: MomentDateAdapter,
    deps: [MAT_DATE_LOCALE, MAT_MOMENT_DATE_ADAPTER_OPTIONS]
  },
  {provide: MAT_DATE_FORMATS, useValue: MY_FORMATS}]
})
export class GeneraDatiTestComponent implements OnInit {

    date: Date = new Date();

    message_success = '';
    message_error = '';
    idProgetto: number = -1;
    progetto?: Progetto;
    
    constructor(private datitestService: DatitestService,
      private progettiService: ProgettiService,
      private alertService: AlertService,
      private route: ActivatedRoute,
      private router: Router) { }
    
    ngOnInit(): void {
      this.route.params.subscribe(params => {
        this.idProgetto = +params['id_progetto'];
        this.progettiService.getById(this.idProgetto).subscribe(
          response => {
            this.progetto = response.value;
          },
          error => {
            this.alertService.error(error);
          });
      },
        error => {
        this.alertService.error(error);
      });
    }

    resetAlertSuccess() {    
      this.message_success = '';
    }
    
    resetAlertDanger() {
      this.message_error = '';
    }

    run() {
      this.resetAlertSuccess();
      this.resetAlertDanger();

      this.datitestService.run(this.idProgetto, formatDate(this.date,"YYYY-MM-dd","en-GB")).subscribe(response => {
        this.message_success = response.value.success;
        this.message_error = response.value.error;
      },
      error => {
        this.message_error = error;
      });
    }

    back() {
      this.router.navigate(['/progetto', this.idProgetto])
    }
}
