import { Router } from '@angular/router';
import { MatTableDataSource } from '@angular/material/table';
import { MatPaginator } from '@angular/material/paginator';
import { ELEMENT_DATA_PROGETTO, Progetto } from './../_models/progetto';
import { Component, OnInit, ViewChild } from '@angular/core';


@Component({
  selector: 'app-progetti',
  templateUrl: './progetti.component.html',
  styleUrls: ['./progetti.component.css']
})
export class ProgettiComponent implements OnInit {

  displayedColumns: string[] = ['titolo', 'dataInizio', 'actions'];
  dataSource = new MatTableDataSource<Progetto>(ELEMENT_DATA_PROGETTO);
  @ViewChild(MatPaginator, { static: true }) paginator!: MatPaginator;
  router_frontend?: Router;
  constructor(private router: Router){
    this.router_frontend = router;
  }
  ngOnInit() {
    this.dataSource.paginator = this.paginator;
  }
  getRecord(a:any){
    console.log(a);
    this.router.navigate(['/progetto/'+a.idProgetto]);
  }

}
