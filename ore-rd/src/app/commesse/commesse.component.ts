import { Component, OnInit } from '@angular/core';
import { MatTableDataSource } from '@angular/material/table';
import { Commessa, ProgettoCommessa } from '../_models';
import { AlertService } from '../_services/alert.service';

import { CommesseService } from '../_services/commesse.service';

@Component({
  selector: 'app-commesse',
  templateUrl: './commesse.component.html',
  styleUrls: ['./commesse.component.css']
})
export class CommesseComponent implements OnInit {

  dataSource = new MatTableDataSource<Commessa>();

  displayedColumns: string[] = ['codCommessa', 'totOrePreviste', 'pctCompatibilita', 'totOreRdPreviste',
    'tipologia'];

  allCommesse: Commessa[] = [];
  allProgetti: string[] = [];
  isLoading: Boolean = true;
  // filtroDataInizio?: Date;
  // filtroDataFine?: Date;

  constructor(
    private alertService: AlertService,
    private commesseService: CommesseService) {
  }

  ngOnInit(): void {

    this.getAllCommesse();
  }

  getAllCommesse() {

    this.commesseService.getAll().subscribe(response => {
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

  getOrePreviste(codCommessa: string, acronimo: string): number | null {

    let comm = this.allCommesse.find(x => x.COD_COMMESSA == codCommessa);
    let progettoComm = comm?.PROGETTI.find(x => x.ACRONIMO == acronimo);

    return (progettoComm && progettoComm.ORE_PREVISTE != null && progettoComm.ORE_PREVISTE > 0) ? progettoComm.ORE_PREVISTE : null;
  }

}