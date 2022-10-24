import { AssociazioneOreService } from '../_services/associazione.ore';
import { Component, OnInit } from '@angular/core';
import { MomentDateAdapter, MAT_MOMENT_DATE_ADAPTER_OPTIONS } from '@angular/material-moment-adapter';
import { DateAdapter, MAT_DATE_FORMATS, MAT_DATE_LOCALE } from '@angular/material/core';

// Depending on whether rollup is used, moment needs to be imported differently.
// Since Moment.js doesn't have a default export, we normally need to import using the `* as`
// syntax. However, rollup creates a synthetic default module and we thus need to import it using
// the `default as` syntax.
import * as _moment from 'moment';
import { formatDate } from '@angular/common';
import { AlertService } from '../_services/alert.service';
import { ActivatedRoute, Router } from '@angular/router';
import { ProgettiService } from '../_services/progetti.service';
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
  selector: 'app-associazione-ore',
  templateUrl: './associazione-ore.component.html',
  styleUrls: ['./associazione-ore.component.css'],
  providers: [{
    provide: DateAdapter,
    useClass: MomentDateAdapter,
    deps: [MAT_DATE_LOCALE, MAT_MOMENT_DATE_ADAPTER_OPTIONS]
  },
  {provide: MAT_DATE_FORMATS, useValue: MY_FORMATS}]
})
export class AssociazioneOreComponent implements OnInit {

  date: Date = new Date();

  message_success = '';
  message_error = '';
  running = false;

  constructor(private associazioneOreService: AssociazioneOreService,
    private progettiService: ProgettiService,
    private alertService: AlertService,
    private route: ActivatedRoute,
    private router: Router) { }

  ngOnInit(): void { }

  resetAlertSuccess() {
    this.message_success = '';
  }

  resetAlertDanger() {
    this.message_error = '';
  }

  run() {
    this.resetAlertSuccess();
    this.resetAlertDanger();
    this.running = true;

    this.associazioneOreService.run(formatDate(this.date, 'YYYY-MM-dd', 'en-GB')).subscribe(response => {
      this.message_success = 'Elaborazione terminata. I dettagli verranno visualizzati in una nuova finestra.';
      this.message_error = response.value.error;
      this.running = false;
      const tab = window.open('about:blank', '_blank');
      if (tab != null) {
        tab.document.write('<html><body>' + response.value.success + '<br/>' + response.value.error + '</body></html>');
        tab.document.close(); // to finish loading the page
      }
    },
    error => {
      this.message_error = error;
      this.running = false;
    });
  }
}
