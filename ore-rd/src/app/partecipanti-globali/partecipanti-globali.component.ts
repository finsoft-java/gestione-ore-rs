import { AlertService } from './../_services/alert.service';
import { PartecipantiService } from './../_services/partecipanti.service';
import { MatTableDataSource } from '@angular/material/table';
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
  displayedColumns: string[] = ['nome', 'matricola', 'pctUtilizzo', 'mansione', 'costo', 'actions'];

  allPartecipanti: Partecipante[] = [];
  allNomeMatricole: Matricola[] = [];
  isLoading: Boolean = true;

  constructor(
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
      console.log(this.allPartecipanti);
      this.isLoading = false;
      this.getMatricole();
      
    },
      error => {
        this.alertService.error(error);
        this.isLoading = false;
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
      this.allNomeMatricole = response.data;

      this.allPartecipanti.forEach(x => {
        x.DENOMINAZIONE = this.getNomeMatricola(x.ID_DIPENDENTE!);
      });

      this.allPartecipanti.sort((a, b) => a.DENOMINAZIONE!.localeCompare(b.DENOMINAZIONE!));
      this.dataSource = new MatTableDataSource<Partecipante>(this.allPartecipanti);
    },
      error => {
        this.alertService.error(error);
      });
  }

  getNomeMatricola(idDipendente: string): string {

    if (this.allNomeMatricole == null) {
      return '(unknown)';
    }

    const pantheraObj = this.allNomeMatricole.find(x => x.ID_DIPENDENTE == idDipendente);

    return pantheraObj != null && pantheraObj.DENOMINAZIONE != null ? pantheraObj.DENOMINAZIONE : '(unknown)';
  }

  addPartecipante() {

    let newPartecipante: Partecipante;
    newPartecipante = {
      ID_DIPENDENTE: null, MATRICOLA: null, PCT_UTILIZZO: 100, MANSIONE: null, COSTO: null, DENOMINAZIONE: null, isEditable: true, isInsert: true
    };

    let data: any[] = [newPartecipante];
    if (this.dataSource.data != null) {
      data = data.concat(this.dataSource.data);
    }

    this.dataSource.data = data;
  }

  annullaEdit(row: Partecipante) {

    this.getAll();
  }

  saveEdit(row: Partecipante, matricola: number) {

    if (row.isInsert) {

      let idDip = this.allNomeMatricole.find(x => x.MATRICOLA == matricola);

      console.log(idDip?.ID_DIPENDENTE);

      row.ID_DIPENDENTE = idDip?.ID_DIPENDENTE!;

      this.partecipanteService.insert(row)
        .subscribe(response => {
          this.alertService.success("Partecipante inserito con successo");
          this.dataSource.data.splice(-1, 1);
          this.dataSource.data.push(response.value);
          this.dataSource.data = this.dataSource.data;
          row.isEditable = false;
          row.isInsert = false;
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