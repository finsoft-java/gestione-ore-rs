import { Component, Input, OnInit } from '@angular/core';
import { MatTableDataSource } from '@angular/material/table';
import { ProgettoCommessa } from '../_models';
import { AlertService } from '../_services/alert.service';
import { ProgettiCommesseService } from '../_services/progetti.commesse.service';

@Component({
  selector: 'app-progetto-commesse',
  templateUrl: './progetto-commesse.component.html',
  styleUrls: ['./progetto-commesse.component.css']
})
export class ProgettoCommesseComponent implements OnInit {

  displayedColumnsCommesseP: string[] = ['codCommessa', 'note'];
  displayedColumnsCommesseC: string[] = ['codCommessa', 'pctCompatibilita', 'note'];
  dataSourceCommesseDiProgetto = new MatTableDataSource<ProgettoCommessa>();
  dataSourceCommesseCompatibili = new MatTableDataSource<ProgettoCommessa>();

  @Input()
  idProgetto!: number | null;

  constructor(private alertService: AlertService,
    private progettiCommesseService: ProgettiCommesseService,) { }

  ngOnInit(): void {
    this.getProgettoCommesse();
  }


  getProgettoCommesse(): void {
    this.progettiCommesseService.getById(this.idProgetto!)
      .subscribe(response => {
        let progettoCommesseCompatibili: ProgettoCommessa[] = [];
        let progettoCommesseDiProgetto: ProgettoCommessa[] = [];
        if (response.data != null) {
          progettoCommesseDiProgetto = response.data.filter(x => x.PCT_COMPATIBILITA == 100);
          progettoCommesseCompatibili = response.data.filter(x => x.PCT_COMPATIBILITA < 100);
        }
        this.dataSourceCommesseCompatibili = new MatTableDataSource(progettoCommesseCompatibili);
        this.dataSourceCommesseDiProgetto = new MatTableDataSource(progettoCommesseDiProgetto);
      },
        error => {
          this.dataSourceCommesseCompatibili = new MatTableDataSource();
          this.dataSourceCommesseDiProgetto = new MatTableDataSource();
        });
  }

  nuovoProgettoCommessa(compat: boolean = false) {
    let nuovo: ProgettoCommessa;
    nuovo = {
      ID_PROGETTO: this.idProgetto,
      COD_COMMESSA: null,
      PCT_COMPATIBILITA: compat ? 50 : 100,
      NOTE: null,
      ORE_PREVISTE: null,
      ACRONIMO: '',
      isEditable: true,
      isInsert: true
    };
    if (compat) {
      const array = this.dataSourceCommesseCompatibili.data;
      array.push(nuovo);
      this.dataSourceCommesseCompatibili.data = array;
    } else {
      const array = this.dataSourceCommesseDiProgetto.data;
      array.push(nuovo);
      this.dataSourceCommesseDiProgetto.data = array;
    }
  }

}
