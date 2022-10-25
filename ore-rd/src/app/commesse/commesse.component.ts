import { Component, OnInit, ViewChild } from '@angular/core';
import { MatPaginator } from '@angular/material/paginator';
import { MatTableDataSource } from '@angular/material/table';
import { Commessa } from '../_models';
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

  @ViewChild(MatPaginator, { static: true }) paginator!: MatPaginator;
  allCommesse: Array<any> = [];

  constructor(
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

}