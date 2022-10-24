import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from './../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class ReportService {

  private baseUrl = environment.wsUrl;

  constructor(private http: HttpClient) { }

  downloadReportBudget(id_progetto: number, periodo: string, isCompleto: boolean) {
    let periodoUrl = '';
    if (periodo != '') {
      periodoUrl = `&periodo=${periodo}`;
    }
    return this.http.get(`${this.baseUrl}/ReportBudget.php?id_progetto=${id_progetto}${periodoUrl}&completo=${isCompleto}`, {
      responseType: 'arraybuffer'
    });
  }

}