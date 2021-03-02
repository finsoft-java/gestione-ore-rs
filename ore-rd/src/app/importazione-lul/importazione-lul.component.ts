import { AlertService } from './../_services/alert.service';
import { HttpEventType, HttpResponse } from '@angular/common/http';
import { Observable } from 'rxjs';
import { UploadFilesService } from './../_services/upload.service';
import { Component, ElementRef, OnInit, ViewChild } from '@angular/core';

@Component({
  selector: 'app-importazione-lul',
  templateUrl: './importazione-lul.component.html',
  styleUrls: ['./importazione-lul.component.css']
})
export class ImportazioneLulComponent implements OnInit {

  selectedFiles?: FileList;
  progressInfos = { value: 0, fileName: 'Caricamento' };
  message_success = '';
  message_error = '';

  constructor(private uploadService: UploadFilesService, private alertService: AlertService) { }

  ngOnInit(){
  }

  selectFiles(event: any) {
    this.selectedFiles = event.target.files;
  }

  resetAlertSuccess() {    
    this.message_success = '';
  }
  
  resetAlertDanger() {
    this.message_error = '';
  }

  uploadFiles() {
    if(this.selectedFiles) {
      this.upload(this.selectedFiles);
    }
  }

  upload(files: FileList) {
    this.progressInfos.value = 0;
  
    this.uploadService.upload(files).subscribe(
      event => {
        if (event.type === HttpEventType.UploadProgress) {
          if(event.total){
            this.progressInfos.value = Math.round(100 * event.loaded / event.total);
          }
        } else if (event instanceof HttpResponse) {
          this.message_error = event.body.value;
        }
      },
      err => {
        if (err && err.error && err.error.error)
            this.message_error = err.error.error.message;
        else if (err && err.error)
            this.message_error = err.error;
        else
            this.message_error = err;
      });
  }

}
