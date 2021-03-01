import { UploadRapportiniService } from './../_services/upload.rapportini.service';
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
  selector: 'app-esportazione-rapportini',
  templateUrl: './esportazione-rapportini.component.html',
  styleUrls: ['./esportazione-rapportini.component.css'],
  providers: [{
    provide: DateAdapter,
    useClass: MomentDateAdapter,
    deps: [MAT_DATE_LOCALE, MAT_MOMENT_DATE_ADAPTER_OPTIONS]
  },
  {provide: MAT_DATE_FORMATS, useValue: MY_FORMATS}],
})
export class EsportazioneRapportiniComponent implements OnInit {
    date = new FormControl(moment());
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
    constructor(private uploadRapportiniService: UploadRapportiniService) { }

    ngOnInit(): void {
    }

    download() {
        this.uploadRapportiniService.download(formatDate(this.date.value,"YYYY-MM","en-GB")).subscribe(response => {
            this.downloadFile(response);
        },
        error => {
            // TODO
        });
    }
  
    downloadFile(data: any) {
        const blob = new Blob([data], { type: 'applicazion/zip' });
        const url = window.URL.createObjectURL(blob);
        var anchor = document.createElement("a");
        anchor.download = "Esportazione.zip";
        anchor.href = url;
        anchor.click();
    }

}
