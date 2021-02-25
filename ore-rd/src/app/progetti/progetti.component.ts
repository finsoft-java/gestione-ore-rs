import { Progetto } from './../_models/progetto';
import { ProgettiService } from './../_services/progetti.service';
import { Router } from '@angular/router';
import { MatTableDataSource } from '@angular/material/table';
import { MatPaginator } from '@angular/material/paginator';
import { Component, OnInit, ViewChild } from '@angular/core';


@Component({
  selector: 'app-progetti',
  templateUrl: './progetti.component.html',
  styleUrls: ['./progetti.component.css']
})
export class ProgettiComponent implements OnInit {

  displayedColumns: string[] = ['titolo', 'dataInizio', 'actions'];
  dataSource = new MatTableDataSource<Progetto>();
  @ViewChild(MatPaginator, { static: true }) paginator!: MatPaginator;
  router_frontend?: Router;
  constructor(private router: Router, private progettiService: ProgettiService){
    this.router_frontend = router;
  }
  ngOnInit() {
    this.dataSource.paginator = this.paginator;
    this.getAll();
  }
  getAll() {
    this.progettiService.getAll()
        .subscribe(response => {
          console.log(response);
          this.dataSource = new MatTableDataSource<Progetto>(response);
          console.log(this.dataSource);
        },
        error => {
        });
  }
  getRecord(a:any){
    console.log(a);
    this.router.navigate(['/progetto/'+a.idProgetto]);
  }

}
