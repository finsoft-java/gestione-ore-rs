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

  dataSource = new MatTableDataSource<Partecipante>();
  displayedColumns: string[] = ['nome', 'id', 'pctUtilizzo', 'mansione', 'costo', 'actions'];

  @ViewChild(MatPaginator, { static: true }) paginator!: MatPaginator;
  allPartecipanti: Array<any> = [];
  allMatricole: Matricola[] = [];

  constructor(
    private router: Router,
    private partecipanteService: PartecipantiService,
    private alertService: AlertService) {
  }

  ngOnInit(): void {

    this.getAll();
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

  getRecord(row: Partecipante) {

    if (this.dataSource.data) {
      this.dataSource.data.forEach(x => x.isEditable = false);
    }

    row.isEditable = true;
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

  addPartecipante() {

    let newPartecipante: any;
    newPartecipante = { ID_DIPENDENTE: null, PCT_UTILIZZO: 100, MANSIONE: "", COSTO: "", isEditable: true, isInsert: true };

    let data: any[] = [];
    if (this.dataSource.data != null) {
      data = this.dataSource.data;
    }
    data.push(newPartecipante);

    this.dataSource.data = data;
  }

  annullaEdit(row: Partecipante) {

    this.getAll();
  }

  saveEdit(row: Partecipante) {

    if (row.isInsert) {

      this.partecipanteService.insert(row)
        .subscribe(response => {
          this.alertService.success("Partecipante inserito con successo");
          this.dataSource.data.splice(-1, 1);
          this.dataSource.data.push(response.value);
          this.dataSource.data = this.dataSource.data;
          row.isEditable = false;
        },
          error => {
            this.alertService.error(error);
          });
    } else {

      this.partecipanteService.update(row)
        .subscribe(response => {
          this.alertService.success("Partecipante aggiornato con successo");
          row.isEditable = false;
        },
          error => {
            this.alertService.error(error);
          });
    }
  }

  deletePartecipante(row: Partecipante) {

    if (row.ID_DIPENDENTE != null) {

      this.partecipanteService.delete(row.ID_DIPENDENTE)
        .subscribe(response => {
          this.getAll();
        },
          error => {
            this.alertService.error("Impossibile eliminare questo partecipante. "
              + "Forse è già stata utilizzato all'interno di un Progetto?");
          });
    }
  }

}