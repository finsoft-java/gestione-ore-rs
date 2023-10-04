import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from './../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class ReportService {

  private baseUrl = environment.wsUrl;

  constructor(private http: HttpClient) { }

  downloadReportBudget(id_progetto: number, periodo: string, isCompleto: boolean, dataInizio: string, dataFine: string) {
    let periodoUrl = '';
    let rangeUrl = '';
    if (periodo != '') {
      periodoUrl = `&periodo=${periodo}`;
    }
    if (dataInizio != '' && dataFine != '') {
      rangeUrl = `&dataInizio=${dataInizio}&dataFine=${dataFine}`;
    }
    return this.http.get(`${this.baseUrl}/ReportBudget.php?id_progetto=${id_progetto}${periodoUrl}${rangeUrl}&completo=${isCompleto}`, {
      responseType: 'arraybuffer'
    });
  }

}