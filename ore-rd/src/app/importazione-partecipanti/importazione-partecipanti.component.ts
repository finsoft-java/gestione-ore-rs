//TODO import upload service here
import { HttpResponse, HttpEventType } from '@angular/common/http';
import { AlertService } from './../_services/alert.service';
import { Component, ElementRef, OnInit, ViewChild } from '@angular/core';

@Component({
  selector: 'app-importazione-partecipanti',
  templateUrl: './importazione-partecipanti.component.html',
  styleUrls: ['./importazione-partecipanti.component.css'],
})
export class ImportazionePartecipantiComponent implements OnInit {
  selectedFile?: File;
  progressInfos = { value: 0, fileName: 'Caricamento' };
  message_success = '';
  message_error = '';
  eventoClick?: any;
  // nomeFile: string = '';
  loading = false;
  @ViewChild('fileInput') inputFile?: ElementRef;

  //TODO insert upload service in constructor
  constructor(private alertService: AlertService) {}

  ngOnInit(): void {}

  reset() {
    this.eventoClick.srcElement.value = null;
    this.selectedFile = undefined;
    //this.nomeFile = '';
    this.progressInfos = { value: 0, fileName: 'Caricamento' };
    this.message_error = '';
    this.message_success = '';
  }

  selectFile(event: any) {
    this.eventoClick = event;
    this.selectedFile = event.target.file;
  }

  resetAlertSuccess() {
    this.message_success = '';
  }

  resetAlertDanger() {
    this.message_error = '';
  }

  uploadFiles() {
    if (this.selectedFile) {
      this.upload(this.selectedFile);
    }
  }

  upload(file: File) {
    this.progressInfos.value = 0;
    this.loading = true;
    this.message_error = '';
    this.message_success = 'Loading...';

    //TODO insert upload service
    // this.uploadService.upload(file).subscribe(
    //   event => {
    //   console.log("EVENT=", event);
    //     if (event.type === HttpEventType.UploadProgress) {
    //       if (event.total) {
    //         this.progressInfos.value = Math.round(100 * event.loaded / event.total);
    //       }
    //     } else if (event instanceof HttpResponse) {
    //       this.message_error = event.body.value.error;
    //       this.message_success = event.body.value.success;
    //       this.loading = false;
    //     }
    //   },
    //   err => {
    //   console.log("ERRORE=", err);
    //     if (err && err.error && err.error.error)
    //         this.message_error = err.error.error.message;
    //     else if (err && err.error)
    //         this.message_error = err.error;
    //     else
    //         this.message_error = err;
    //         this.loading = false;
    //   });
  }
}
