import { UploadRapportiniService } from './../_services/upload.rapportini.service';
import { HttpResponse, HttpEventType } from '@angular/common/http';
import { AlertService } from './../_services/alert.service';
import { Observable } from 'rxjs';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-importazione-rapportini',
  templateUrl: './importazione-rapportini.component.html',
  styleUrls: ['./importazione-rapportini.component.css']
})
export class ImportazioneRapportiniComponent implements OnInit {

  selectedFiles?: FileList;
  progressInfos: Array<any> = [];
  message_success = '';
  message_error = '';
  fileInfos: Observable<any> = new Observable;

  constructor(private uploadService: UploadRapportiniService, private alertService: AlertService) { }
  ngOnInit(){
    //this.fileInfos = this.uploadService.getFiles();
  }
  selectFiles(event: any) {
    this.progressInfos = [];
    this.selectedFiles = event.target.files;
  }

  resetAlertSuccess() {    
    this.message_success = '';
  }
  
  resetAlertDanger() {
    this.message_error = '';
  }

  uploadFiles() {
    if(this.selectedFiles)
    for (let i = 0; i < this.selectedFiles.length; i++) {
      this.upload(i, this.selectedFiles[i]);
    }
  }

  upload(idx:any, file:any) {
    this.progressInfos[idx] = { value: 0, fileName: file.name };
  
    this.uploadService.upload(file).subscribe(
      event => {
        console.log('event', event);
        if (event.type === HttpEventType.UploadProgress) {
          if(event.total)
          this.progressInfos[idx].value = Math.round(100 * event.loaded / event.total);
          this.message_success = 'Tutto ok';
        } else if (event instanceof HttpResponse) {
          console.log('event success?? -> ',event);
        }
      },
      err => {
        console.log('err', err);
        this.progressInfos[idx].value = 0;        
        this.message_error = 'Un errore';
      });
  }

}
