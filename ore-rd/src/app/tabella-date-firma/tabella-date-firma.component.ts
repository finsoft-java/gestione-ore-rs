import { AlertService } from './../_services/alert.service';
import { AssociazioneOreService } from '../_services/associazione.ore';
import { Component, Input, OnInit } from '@angular/core';
import { MomentDateAdapter, MAT_MOMENT_DATE_ADAPTER_OPTIONS } from '@angular/material-moment-adapter';
import { DateAdapter, MAT_DATE_FORMATS, MAT_DATE_LOCALE } from '@angular/material/core';
import * as _moment from 'moment';


const moment = _moment;
export const MY_FORMATS = {
  parse: {
    dateInput: 'YYYY-MM-DD',
  },
  display: {
    dateInput: 'YYYY-MM-DD',
    monthYearLabel: 'YYYY MMM',
    dateA11yLabel: 'LL',
    monthYearA11yLabel: 'YYYY MMMM',
  },
};
@Component({
  selector: 'app-tabella-date-firma',
  templateUrl: './tabella-date-firma.component.html',
  styleUrls: ['./tabella-date-firma.component.css'],
  providers: [{
    provide: DateAdapter,
    useClass: MomentDateAdapter,
    deps: [MAT_DATE_LOCALE, MAT_MOMENT_DATE_ADAPTER_OPTIONS]
  },
  {provide: MAT_DATE_FORMATS, useValue: MY_FORMATS}]
})
export class TabellaDateFirmaComponent implements OnInit {
  @Input() dataSourceFiglio: any;
  @Input() annoMese: any;

  displayedColumns: string[] = ['titolo',
    'nome_dipendente', 'ult_pres_dipendente', 'prima_pres_dipendente',
    'nome_supervisor', 'ult_pres_supervisor', 'prima_pres_supervisor',
    'dataFirma'];
  constructor(private associazioneOreService: AssociazioneOreService, private alertService: AlertService) { }

  ngOnInit(): void {
  }

  salvaDateFirma() {
    let objAnnoMese = { "ANNO_MESE" : this.annoMese}
    this.dataSourceFiglio.data.push(objAnnoMese);
    this.associazioneOreService.salvaDataFirma(this.dataSourceFiglio.data)
      .subscribe(response => {
        
        this.alertService.success("Caricamento eseguito con successo");
        this.dataSourceFiglio.data = this.dataSourceFiglio.data.slice(0,-1);
      },
      error => {
        this.alertService.error(error);
        this.dataSourceFiglio.data = this.dataSourceFiglio.data.slice(0,-1);
      });
  }
}