//TODO import upload service here
import { HttpResponse, HttpEventType } from '@angular/common/http';
import { AlertService } from './../_services/alert.service';
import { Component, ElementRef, OnInit, ViewChild } from '@angular/core';

@Component({
  selector: 'app-importazione-commesse',
  templateUrl: './importazione-commesse.component.html',
  styleUrls: ['./importazione-commesse.component.css']
})
export class ImportazioneCommesseComponent implements OnInit {
  selectedFiles?: FileList;
  progressInfos = { value: 0, fileName: 'Caricamento' };
  message_success = '';
  message_error = '';
  eventoClick?: any;
  nomiFile: string[] = [];
  loading = false;
  @ViewChild('fileInput') inputFile?: ElementRef;

  //TODO insert upload service in constructor
  constructor(private alertService: AlertService) {}

  ngOnInit(): void {}
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

    //TODO insert upload service
    // this.uploadService.upload(files).subscribe(
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
