import { Injectable } from '@angular/core';
import { HttpClient, HttpRequest, HttpEvent } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from './../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class ReportService {

  private baseUrl = environment.wsUrl;

  constructor(private http: HttpClient) { }
  
  downloadReportBudget(id_progetto: number, periodo: string) {
    return this.http.get(`${this.baseUrl}/ReportBudget.php?id_progetto=${id_progetto}&periodo=${periodo}`, {
      responseType: 'arraybuffer'
    });
  }
  
}