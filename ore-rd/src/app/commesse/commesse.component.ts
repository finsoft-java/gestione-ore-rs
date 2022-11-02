import { Component, OnInit } from '@angular/core';
import { MatTableDataSource } from '@angular/material/table';
import { Commessa, Periodo, ProgettoCommessa } from '../_models';
import { AlertService } from '../_services/alert.service';
import { CommesseService } from '../_services/commesse.service';
import { PeriodiService } from '../_services/periodi.service';

@Component({
  selector: 'app-commesse',
  templateUrl: './commesse.component.html',
  styleUrls: ['./commesse.component.css']
})
export class CommesseComponent implements OnInit {

  dataSource = new MatTableDataSource<Commessa>();

  displayedColumns: string[] = ['codCommessa', 'totOrePreviste', 'pctCompatibilita', 'totOreRdPreviste',
    'tipologia', 'giustificativo'];

  allCommesse: Commessa[] = [];
  allProgetti: string[] = [];
  allPeriodi: Periodo[] = [];
  isLoading: Boolean = true;
  dataInizio: string = '';
  dataFine: string = '';
  filtroPeriodo?: Periodo;

  constructor(
    private alertService: AlertService,
    private commesseService: CommesseService, 
    private periodiService: PeriodiService ) {
  }

  ngOnInit(): void {
    this.getAllPeriodi();
  }

  getAllCommesseFiltrate(dataInizio :string, dataFine :string) {
    this.commesseService.getAll(dataInizio, dataFine).subscribe(response => {
      this.allCommesse = response.data;
      this.dataSource = new MatTableDataSource<Commessa>(response.data);
      this.isLoading = false;
      this.allCommesse.forEach(x => {
        x.PROGETTI.forEach(y => {
          if (!this.allProgetti.includes(y.ACRONIMO)) { this.allProgetti.push(y.ACRONIMO); }
        })
      });
      this.displayedColumns = this.displayedColumns.concat(this.allProgetti);
    });
  }

  getAllPeriodi() {
    this.periodiService.getAll().subscribe(response => {
      this.allPeriodi = response.data;
    })
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

  getOrePreviste(codCommessa: string, acronimo: string): number | null {

    let comm = this.allCommesse.find(x => x.COD_COMMESSA == codCommessa);
    let progettoComm = comm?.PROGETTI.find(x => x.ACRONIMO == acronimo);

    return (progettoComm && progettoComm.ORE_PREVISTE != null && progettoComm.ORE_PREVISTE > 0) ? progettoComm.ORE_PREVISTE : null;
  }

  filtraPeriodo() {
    this.allCommesse = [];
    this.allProgetti = [];
    this.displayedColumns = ['codCommessa', 'totOrePreviste', 'pctCompatibilita', 'totOreRdPreviste',
    'tipologia', 'giustificativo'];
      this.getAllCommesseFiltrate(this.dataInizio, this.dataFine);
  }
}