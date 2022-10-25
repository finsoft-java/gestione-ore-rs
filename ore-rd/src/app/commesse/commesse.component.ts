import { Component, OnInit, ViewChild } from '@angular/core';
import { MatTableDataSource } from '@angular/material/table';
import { Commessa } from '../_models';
import { AlertService } from '../_services/alert.service';

import { CommesseService } from '../_services/commesse.service';

@Component({
  selector: 'app-commesse',
  templateUrl: './commesse.component.html',
  styleUrls: ['./commesse.component.css']
})
export class CommesseComponent implements OnInit {

  dataSource = new MatTableDataSource<Commessa>();
  displayedColumns: string[] = ['codCommessa', 'pctCompatibilita', 'totOrePreviste', 'totOreRdPreviste',
    'tipologia', 'giustificativo'];

  allCommesse: Array<any> = [];

  constructor(private alertService: AlertService,
    private commesseService: CommesseService) {
  }

  ngOnInit(): void {

    this.getAll();
  }

  getAll() {

    this.commesseService.getAll().subscribe(response => {
      this.allCommesse = response.data;
      this.dataSource = new MatTableDataSource<Commessa>(response.data);
    },
      error => {
      });
  }

  uploadGiustificativo(p: Commessa, event: any) {
    console.log(event);
    let file = event.target.files && event.target.files[0];
    console.log('Going to upload:', file);
    if (file) {
      console.log(file);
      this.commesseService.uploadGiustificativo(p.COD_COMMESSA!, file).subscribe(response => {
        p.HAS_GIUSTIFICATIVO = 'Y';
        p.GIUSTIFICATIVO_FILENAME = file.name;
        this.alertService.success('Giustificativo caricato con successo');
      },
        error => {
          this.alertService.error(error);
        });
    }
  }

  deleteGiustificativo(p: Commessa) {
    // TODO Some warning?
    this.commesseService.deleteGiustificativo(p.COD_COMMESSA!).subscribe(response => {
      p.HAS_GIUSTIFICATIVO = 'N';
      p.GIUSTIFICATIVO_FILENAME = null;
      this.alertService.success('Giustificativo eliminato con successo');
    },
      error => {
        this.alertService.error(error);
      });
  }

  downloadGiustificativo(p: Commessa) {
    this.commesseService.downloadGiustificativo(p.COD_COMMESSA!).subscribe(response => {
      this.downloadFile(response, p.GIUSTIFICATIVO_FILENAME!);
    },
      error => {
        this.alertService.error(error);
      });
  }

  downloadFile(data: any, filename: string) {
    const blob = new Blob([data] /* , { type: 'applicazion/zip' } */);
    const url = window.URL.createObjectURL(blob);
    var anchor = document.createElement("a");
    anchor.download = filename;
    anchor.href = url;
    anchor.click();
  }

}