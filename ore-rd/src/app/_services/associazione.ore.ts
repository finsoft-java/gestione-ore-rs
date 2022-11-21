import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { formatDate } from '@angular/common';
import { environment } from '../../environments/environment';
import { ListBean, Progetto } from '../_models';
import { DataFirma } from '../_models/matricola';

@Injectable({
  providedIn: 'root'
})
export class AssociazioneOreService {

  private baseUrl = environment.wsUrl;

  constructor(private http: HttpClient) { }
  
  getProgettiAttivi(dataInizio: string, dataFine: string) {
    return this.http.get<ListBean<Progetto>>(`${this.baseUrl}/GetProgettiAttivi.php?dataInizio=${dataInizio}&dataFine=${dataFine}`);
  }
  
  run(dataInizio: string, dataFine: string, idProgetto: number) {
    return this.http.post<any>(`${this.baseUrl}/AssegnaOreProgetti.php`, {
      dataInizio: dataInizio,
      dataFine: dataFine,
      idProgetto: idProgetto
    });
  }

  runDateFirma(periodo: string) {
    return this.http.post<ListBean<DataFirma>>(`${this.baseUrl}/GeneraDateFirma.php`, {
      periodo: periodo
    });
  }

  salvaDataFirma(dataFirma: DataFirma[]) {
    for(let i = 0; i < dataFirma.length - 1; i++){
      if(dataFirma[i].DATA_FIRMA != null){
        dataFirma[i].DATA_FIRMA = formatDate(dataFirma[i].DATA_FIRMA,"YYYY-MM-dd","en-GB");
      }
    }
    return this.http.put<void>(`${this.baseUrl}/GeneraDateFirma.php`, {
      data_firma: dataFirma
    });
  }
  
}
