import { AlertService } from './../_services/alert.service';
import { PartecipantiService } from './../_services/partecipanti.service';
import { Router } from '@angular/router';
import { MatTableDataSource } from '@angular/material/table';
import { MatPaginator } from '@angular/material/paginator';
import { Partecipante } from './../_models/partecipanti';
import { Component, OnInit, ViewChild } from '@angular/core';
import { Matricola } from '../_models';

@Component({
  selector: 'app-partecipanti-globali',
  templateUrl: './partecipanti-globali.component.html',
  styleUrls: ['./partecipanti-globali.component.css']
})

export class PartecipantiGlobaliComponent implements OnInit {

  displayedColumns: string[] = ['id', 'pctUtilizzo', 'mansione', 'costo', 'actions'];
  dataSource = new MatTableDataSource<Partecipante>();

  @ViewChild(MatPaginator, { static: true }) paginator!: MatPaginator;
  allPartecipanti: Array<any> = [];
  allMatricole: Matricola[] = [];

  constructor(
    private router: Router,
    private partecipanteService: PartecipantiService,
    private alertService: AlertService) {
  }

  ngOnInit(): void {

    this.getMatricole();
  }

  getAll() {

    this.partecipanteService.getAll().subscribe(response => {
      this.allPartecipanti = response.data;
      this.dataSource = new MatTableDataSource<Partecipante>(response.data);
    },
      error => {
      });
  }

  getRecord(par: Partecipante) {

    par.isEditable = true;
  }

  nuovoPartecipante() {

    let newPartecipante: any;
    newPartecipante = { ID_DIPENDENTE: null, PCT_UTILIZZO: 100, MANSIONE: "", COSTO: "", isEditable: true };

    const data = this.dataSource.data;
    data.push(newPartecipante);

    this.dataSource.data = data;
  }

  deleteChange(par: Partecipante) {

    this.partecipanteService.delete(par.ID_DIPENDENTE)
      .subscribe(response => {
        this.getAll();
        this.dataSource = new MatTableDataSource<Partecipante>(this.allPartecipanti);
      },
        error => {
          this.alertService.error("Impossibile eliminare questo partecipante. "
            + "Forse è già stata utilizzato all'interno di un Progetto?");
        });
  }

  saveChange(par: Partecipante): any {

    par.isEditable = false;

    if (par.ID_DIPENDENTE == null) {
      this.partecipanteService.insert(par)
        .subscribe(response => {
          console.log(this.dataSource.data);
          console.log(response.value);
          this.alertService.success("Partecipante modificato");
          this.dataSource.data.splice(-1, 1);
          this.dataSource.data.push(response.value);
          console.log(this.dataSource.data);
          this.dataSource.data = this.dataSource.data;
        },
          error => {
            this.alertService.error(error);
          });
    } else {
      this.partecipanteService.update(par)
        .subscribe(response => {
          this.alertService.success("Partecipante aggiunto");
        },
          error => {
            this.alertService.error(error);
          });
    }
  }

  undoChange(par: Partecipante) {

    par.isEditable = false;

    if (par.ID_DIPENDENTE == null) {
      this.dataSource.data.splice(-1, 1);
      this.dataSource.data = this.dataSource.data;
    }
  }

  getMatricole(): void {

    this.partecipanteService.getAllMatricole().subscribe(response => {
      this.allMatricole = response.data;
    },
      error => {
        this.alertService.error(error);
      });
  }

  getNomeMatricola(idDipendente: string): string {

    if (this.allMatricole == null) {
      return '(unknown)';
    }

    const pantheraObj = this.allMatricole.find(x => x.ID_DIPENDENTE == idDipendente);

    return pantheraObj != null && pantheraObj.DENOMINAZIONE != null ? pantheraObj.DENOMINAZIONE : '(unknown)';
  }

}