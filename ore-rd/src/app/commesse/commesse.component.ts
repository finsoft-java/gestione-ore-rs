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
  colonneConstants : string[] = ['codCommessa', 'totOrePreviste', 'pctCompatibilita', 'totOreRdPreviste',
  'tipologia']; 
  displayedColumns = this.colonneConstants;

  allCommesse: Commessa[] = [];
  allProgetti: string[] = [];
  allPeriodi: Periodo[] = [];
  isLoading: Boolean = true;
  dataInizio: string = '';
  dataFine: string = '';
  filtroPeriodo?: Periodo;
  checkRiepilogo: Boolean = false;
  cntR:number=0;
  cntC:number=0;
  totaleOre:number=0;
  constructor(
    private alertService: AlertService,
    private commesseService: CommesseService,
    private periodiService: PeriodiService) {
  }

  ngOnInit(): void {
    this.getAllPeriodi();
  }

  getAllCommesseFiltrate(dataInizio: string, dataFine: string) {
    this.commesseService.getAll(dataInizio, dataFine).subscribe(response => {
      console.log("reeeee ", response);
      this.allCommesse = response.data;
      this.dataSource = new MatTableDataSource<Commessa>(response.data);
      this.isLoading = false;
      this.checkRiepilogo = true;
      this.cntR=0;
      this.cntC=0;
      this.totaleOre = 0;
      this.allCommesse.forEach(x => {
        console.log(x.TIPOLOGIA);
        switch (x.TIPOLOGIA?.toUpperCase()) {
          case "COMMESSE \"R\"":
            this.cntR++
            break;
          case "COMMESSE COMPATIBILI":
            this.cntC++
            break;
          default:
            break;
        }
        this.totaleOre = this.totaleOre+Number(x.TOT_ORE_PREVISTE);
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

  getOrePreviste(codCommessa: string, acronimo: string): number | null {

    let comm = this.allCommesse.find(x => x.COD_COMMESSA == codCommessa);
    let progettoComm = comm?.PROGETTI.find(x => x.ACRONIMO == acronimo);

    return (progettoComm && progettoComm.ORE_PREVISTE != null && progettoComm.ORE_PREVISTE > 0) ? progettoComm.ORE_PREVISTE : null;
  }

  filtraPeriodo() {
    if (this.filtroPeriodo) {
      this.dataInizio = this.filtroPeriodo.DATA_INIZIO;
      this.dataFine = this.filtroPeriodo.DATA_FINE;
      this.allCommesse = [];
      this.allProgetti = [];
      this.displayedColumns = this.colonneConstants;
      this.getAllCommesseFiltrate(this.dataInizio, this.dataFine);
    }
  }
}