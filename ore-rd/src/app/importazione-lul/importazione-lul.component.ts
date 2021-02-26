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
  progressInfos: Array<any> = [];
  message = '';

  fileInfos: Observable<any> = new Observable;

  constructor(private uploadService: UploadFilesService, private alertService: AlertService) { }
  ngOnInit(){
    //this.fileInfos = this.uploadService.getFiles();
  }
  selectFiles(event: any) {
    this.progressInfos = [];
    this.selectedFiles = event.target.files;
  }
  uploadFiles() {
    this.message = '';
    if(this.selectedFiles)
    for (let i = 0; i < this.selectedFiles.length; i++) {
      this.upload(i, this.selectedFiles[i]);
    }
  }
  upload(idx:any, file:any) {
    this.progressInfos[idx] = { value: 0, fileName: file.name };
  
    this.uploadService.upload(file).subscribe(
      event => {
        console.log(event);
        if (event.type === HttpEventType.UploadProgress) {
          if(event.total)
          this.progressInfos[idx].value = Math.round(100 * event.loaded / event.total);
        } else if (event instanceof HttpResponse) {
          console.log('event -> ',event);
          //this.fileInfos = this.uploadService.getFiles();
        }
      },
      err => {
        console.log(err);
        this.progressInfos[idx].value = 0;
        this.alertService.error("Errore nel WS controllare i log");
      });
  }
}
