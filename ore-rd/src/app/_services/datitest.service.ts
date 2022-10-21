import { DataFirma } from './../_models/matricola';
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from './../../environments/environment';
import { formatDate } from '@angular/common';
import { ListBean } from '../_models';

@Injectable({
  providedIn: 'root'
})
export class DatitestService {

  private baseUrl = environment.wsUrl;

  constructor(private http: HttpClient) { }
  
  run(periodo: string) {
    return this.http.post<any>(`${this.baseUrl}/AssegnaOreProgetti.php`, {
      periodo: periodo
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
