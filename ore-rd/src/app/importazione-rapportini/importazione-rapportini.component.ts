import { UploadRapportiniService } from './../_services/upload.rapportini.service';
import { HttpResponse, HttpEventType } from '@angular/common/http';
import { AlertService } from './../_services/alert.service';
import { Component, ElementRef, OnInit, ViewChild } from '@angular/core';

@Component({
  selector: 'app-importazione-rapportini',
  templateUrl: './importazione-rapportini.component.html',
  styleUrls: ['./importazione-rapportini.component.css']
})
export class ImportazioneRapportiniComponent implements OnInit {

  displayedColumns: string[] = ['serieDoc', 'nrDoc', 'data', 'altri', 'idDipendente', 'rifAtv', 'codCommessa', 'sottoCommessa', 'more'];

  selectedFiles?: FileList;
  progressInfos = { value: 0, fileName: 'Caricamento' };
  message_success = '';
  message_error = '';
  eventoClick?: any;
  nomiFile: string[] = [];
  loading = false;
  @ViewChild('fileInput') inputFile?: ElementRef;

  constructor(private uploadService: UploadRapportiniService, private alertService: AlertService) { }

  ngOnInit() {
  }

  reset() {
    this.eventoClick.srcElement.value = null;
    this.selectedFiles = undefined;
    this.nomiFile = [];
    this.progressInfos = { value: 0, fileName: 'Caricamento' };
    this.message_error = '';
    this.message_success = '';
  }

  selectFiles(event: any) {
    this.eventoClick = event;
    this.selectedFiles = event.target.files;
    if (this.selectedFiles) {
      for (let i = 0; i < this.selectedFiles.length; i++) {
        this.nomiFile.push(this.selectedFiles[i].name);
      }
    }
  }

  resetAlertSuccess() {
    this.message_success = '';
  }

  resetAlertDanger() {
    this.message_error = '';
  }

  uploadFiles() {
    if (this.selectedFiles) {
      this.upload(this.selectedFiles);
    }
  }

  upload(files: FileList) {
    this.progressInfos.value = 0;
    this.loading = true;
    this.message_error = '';
    this.message_success = 'Loading...';

    this.uploadService.upload(files).subscribe(
      event => {
        // console.log("EVENT=", event);
        if (event.type === HttpEventType.UploadProgress) {
          if (event.total) {
            this.progressInfos.value = Math.round(100 * event.loaded / event.total);
          }
        } else if (event instanceof HttpResponse) {
          if (event.body && event.body.value) {
            this.message_error = event.body.value.error;
            this.message_success = event.body.value.success;
          } else {
            this.message_error = 'Errore in fase di upload, verificare i log di Apache';
            this.message_success = '';
          }
          this.loading = false;
        }
      },
      err => {
        console.error("ERRORE=", err);
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
