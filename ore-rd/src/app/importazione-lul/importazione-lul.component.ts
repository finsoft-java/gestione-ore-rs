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
  progressInfos: any;
  message_success = '';
  message_error = '';
  fileInfos: Observable<any> = new Observable;

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
    this.progressInfos = { value: 0, fileName: 'Caricamento' };
  
    this.uploadService.upload(files).subscribe(
      event => {
        if (event.type === HttpEventType.UploadProgress) {
          console.log(event);
          if(event.total){
            this.progressInfos.value = Math.round(100 * event.loaded / event.total);
            console.log(this.progressInfos);
          }
        } else if (event instanceof HttpResponse) {
          console.log('success -> ',event.body.value);
          this.message_error = event.body.value;
          console.log('success -> ',this.message_error);
        }
      },
      err => {
        console.log('err', err);
        this.progressInfos.value = 0;        
        this.message_error = err.error.error.message;
      });
  }

}
