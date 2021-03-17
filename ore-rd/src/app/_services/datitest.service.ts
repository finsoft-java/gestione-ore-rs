import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from './../../environments/environment';

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
  
}
