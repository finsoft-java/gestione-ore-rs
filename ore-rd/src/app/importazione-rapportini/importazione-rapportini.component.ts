import { UploadRapportiniService } from './../_services/upload.rapportini.service';
import { HttpResponse, HttpEventType } from '@angular/common/http';
import { AlertService } from './../_services/alert.service';
import { Observable } from 'rxjs';
import { Component, ElementRef, OnInit, ViewChild } from '@angular/core';

@Component({
  selector: 'app-importazione-rapportini',
  templateUrl: './importazione-rapportini.component.html',
  styleUrls: ['./importazione-rapportini.component.css']
})
export class ImportazioneRapportiniComponent implements OnInit {

  selectedFiles?: FileList;
  progressInfos = { value: 0, fileName: 'Caricamento' };
  message_success = '';
  message_error = '';
  nomiFile:string[] = [];
  @ViewChild('fileInput') inputFile?: ElementRef;

  constructor(private uploadService: UploadRapportiniService, private alertService: AlertService) { }

  ngOnInit(){
  }

  reset() {
    this.selectedFiles = undefined;
    this.nomiFile = [];
    this.progressInfos = { value: 0, fileName: 'Caricamento' };
    this.message_error = '';
    this.message_success = '';
  }
  selectFiles(event: any) {
    //far apparire i file caricati in un box
    this.selectedFiles = event.target.files;
    if(this.selectedFiles){
      for(let i = 0; i < this.selectedFiles.length; i++){
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
