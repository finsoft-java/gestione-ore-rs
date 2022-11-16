import { HttpResponse, HttpEventType } from '@angular/common/http';
import { Component, ElementRef, ViewChild } from '@angular/core';
import { AlertService } from './../_services/alert.service';
import { UploadCommesseService } from '../_services/upload.commesse.service ';
import { MomentDateAdapter, MAT_MOMENT_DATE_ADAPTER_OPTIONS } from '@angular/material-moment-adapter';
import { DateAdapter, MAT_DATE_FORMATS, MAT_DATE_LOCALE } from '@angular/material/core';
import * as _moment from 'moment';

export const MY_FORMATS = {
  parse: {
    dateInput: 'LL'
  },
  display: {
    dateInput: 'DD/MM/YYYY',
    monthYearLabel: 'YYYY',
    dateA11yLabel: 'LL',
    monthYearA11yLabel: 'YYYY'
  }
};


@Component({
  selector: 'app-importazione-commesse',
  templateUrl: './importazione-commesse.component.html',
  styleUrls: ['./importazione-commesse.component.css'],
  providers: [{
    provide: DateAdapter,
    useClass: MomentDateAdapter,
    deps: [MAT_DATE_LOCALE, MAT_MOMENT_DATE_ADAPTER_OPTIONS]
  },
  { provide: MAT_DATE_FORMATS, useValue: MY_FORMATS }]
})
export class ImportazioneCommesseComponent {

  displayedColumns: string[] = ['tipologia', 'codCommessa', 'totOrePreviste', 'pctCompatibilita', 'totOreRdPreviste', 'progetto1', 'progetto2', 'altri'];

  selectedFiles?: FileList;
  dataInizio?: _moment.Moment;
  dataFine?: _moment.Moment;
  progressInfos = { value: 0, fileName: 'Caricamento' };
  message_success = '';
  message_error = '';
  eventoClick?: any;
  nomeFile: string = '';
  loading = false;

  @ViewChild('fileInput') inputFile?: ElementRef;

  constructor(private uploadCommesseService: UploadCommesseService, private alertService: AlertService) { }

  reset() {
    this.eventoClick.srcElement.value = null;
    this.selectedFiles = undefined;
    this.nomeFile = '';
    this.progressInfos = { value: 0, fileName: 'Caricamento' };
    this.message_error = '';
    this.message_success = '';
  }

  selectFile(event: any) {
    this.eventoClick = event;
    this.selectedFiles = event.target.files;
    this.nomeFile = event.target.files[0].name;
  }

  resetAlertSuccess() {
    this.message_success = '';
  }

  resetAlertDanger() {
    this.message_error = '';
  }

  uploadFiles() {
    if (this.dataFine && this.dataInizio && this.selectedFiles) {
      const dataInizioString: string = this.dataInizio.format('YYYY-MM-DD');
      const dataFineString: string = this.dataFine.format('YYYY-MM-DD');
      this.upload(this.selectedFiles, dataInizioString, dataFineString);
    }
  }

  upload(file: FileList, dataInizio: string, dataFine: string) {
    this.progressInfos.value = 0;
    this.loading = true;
    this.message_error = '';
    this.message_success = 'Loading...';

    this.uploadCommesseService.upload(file, dataInizio, dataFine).subscribe(
      event => {
        console.log("EVENT=", event);
        if (event.type === HttpEventType.UploadProgress) {
          if (event.total) {
            this.progressInfos.value = Math.round(100 * event.loaded / event.total);
          }
        } else if (event instanceof HttpResponse) {
          this.message_error = event.body.value.error;
          this.message_success = event.body.value.success;
          this.loading = false;
        }
      },
      err => {
        console.log("ERRORE=", err);
        if (err && err.error && err.error.error)
          this.message_error = err.error.error.message;
        else if (err && err.error)
          this.message_error = err.error;
        else
          this.message_error = err;
        this.loading = false;
      });
  }
}
