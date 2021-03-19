import { DataFirma } from './../_models/matricola';
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from './../../environments/environment';
import { formatDate } from '@angular/common';

@Injectable({
  providedIn: 'root'
})
export class DatitestService {

  private baseUrl = environment.wsUrl;

  constructor(private http: HttpClient) { }
  
  run(periodo: string) {
    return this.http.post(`${this.baseUrl}/GeneraDatiTest.php`, {
      periodo: periodo
    });
  }

  runDateFirma(periodo: string) {
    return this.http.post<any>(`${this.baseUrl}/GeneraDateFirma.php`, {
      periodo: periodo
    });
  }

  salvaDataFirma(dataFirma: DataFirma[]) {
    for(let i = 0; i < dataFirma.length - 1; i++){
      if(dataFirma[i].DATA_FIRMA != null){
        dataFirma[i].DATA_FIRMA = formatDate(dataFirma[i].DATA_FIRMA,"YYYY-MM-dd","en-GB");
      }
    }
    return this.http.put<any>(`${this.baseUrl}/GeneraDateFirma.php`, {
      data_firma: dataFirma
    });
  }
  
}
