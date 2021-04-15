import { Progetto } from './../_models/progetto';
import { ProgettiService } from './../_services/progetti.service';
import { Router } from '@angular/router';
import { MatTableDataSource } from '@angular/material/table';
import { MatPaginator, PageEvent } from '@angular/material/paginator';
import { Component, OnInit, ViewChild } from '@angular/core';


@Component({
  selector: 'app-progetti',
  templateUrl: './progetti.component.html',
  styleUrls: ['./progetti.component.css']
})
export class ProgettiComponent implements OnInit {

  length? = 0;
  pageSize = 10;
  pageIndex = 0;
  pageSizeOptions = [5, 10, 25];
  showFirstLastButtons = true;
  displayedColumns: string[] = ['titolo','acronimo', 'dataInizio','dataFine', 'actions'];
  dataSource = new MatTableDataSource<Progetto>();
  @ViewChild(MatPaginator, { static: true }) paginator!: MatPaginator;
  router_frontend?: Router;

  constructor(private router: Router, private progettiService: ProgettiService){
    this.router_frontend = router;
  }

  ngOnInit() {
    this.dataSource.paginator = this.paginator;
    this.getAll(0, 10);
  }

  getAll(top: number, skip: number) {
    this.progettiService.getAll(top, skip)
        .subscribe(response => {
          this.length = response.count;
          this.dataSource = new MatTableDataSource<Progetto>(response.data);
        },
        error => {
        });
  }

  getRecord(a: any) {
    this.router.navigate(['/progetto/'+a.ID_PROGETTO]);
  }

  nuovoProgetto() {
    this.router.navigate(['/progetto/nuovo']);
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
