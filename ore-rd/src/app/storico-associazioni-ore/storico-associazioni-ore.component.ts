import { Component, OnInit, ViewChild } from '@angular/core';
import { MatPaginator, PageEvent } from '@angular/material/paginator';
import { MatTableDataSource } from '@angular/material/table';
import { Esecuzione } from '../_models';
import { AlertService } from '../_services/alert.service';
import { EsecuzioniService } from '../_services/esecuzioni.service';

@Component({
  selector: 'app-storico-associazioni-ore',
  templateUrl: './storico-associazioni-ore.component.html',
  styleUrls: ['./storico-associazioni-ore.component.css']
})
export class StoricoAssociazioniOreComponent implements OnInit {

  length? = 0;
  pageSize = 10;
  pageIndex = 0;
  pageSizeOptions = [5, 10, 25];
  showFirstLastButtons = true;
  displayedColumns: string[] = ['idEsecuzione', 'utente', 'tmsEsecuzione', 'totOre', 'applied', 'actions'];
  dataSource = new MatTableDataSource<Esecuzione>();
  @ViewChild(MatPaginator, { static: true }) paginator!: MatPaginator;

  constructor(private esecuzioniService: EsecuzioniService, private alertService: AlertService) { }

  ngOnInit(): void {
    this.dataSource.paginator = this.paginator;
    this.getAll(0, this.pageSize);
  }

  getAll(top: number, skip: number) {
    this.esecuzioniService.getAll(top, skip)
        .subscribe(response => {
          this.length = response.count;
          this.dataSource = new MatTableDataSource<Esecuzione>(response.data);
        },
        error => {
          this.alertService.error(error);
        });
  }

  apply(e: Esecuzione) {
    // e.IS_ASSEGNATE == 0
      this.esecuzioniService.apply(e.ID_ESECUZIONE).subscribe(
        response => { e.IS_ASSEGNATE = response.value.IS_ASSEGNATE; },
        error => { this.alertService.error(error); }
      );
  }

  unapply(e: Esecuzione) {
    // e.IS_ASSEGNATE == 1
    this.esecuzioniService.unapply(e.ID_ESECUZIONE).subscribe(
      response => { e.IS_ASSEGNATE = response.value.IS_ASSEGNATE; },
      error => { this.alertService.error(error); }
    );
  }

  elimina(e: Esecuzione) {
    // e.IS_ASSEGNATE == 0
    this.esecuzioniService.delete(e.ID_ESECUZIONE).subscribe(
      response => { this.getAll(0, this.pageSize); },
      error => { this.alertService.error(error); }
    );
  }
  
  handlePageEvent(event: PageEvent) {
    console.log(event);
    this.length = event.length;
    this.pageSize = event.pageSize;
    this.pageIndex = event.pageIndex;
    if (this.pageIndex > 0) {
      this.getAll((this.pageIndex)*this.pageSize, this.pageSize);
    } else {
      this.getAll(0, this.pageSize);
    }
  }

}
