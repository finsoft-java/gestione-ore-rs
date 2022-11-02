import { Injectable } from '@angular/core';
import { HttpClient, HttpRequest, HttpEvent } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from './../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class UploadCommesseService {

  private baseUrl = environment.wsUrl;

  constructor(private http: HttpClient) { }

  upload(file: FileList, dataInizio: string, dataFine: string): Observable<HttpEvent<any>> {
    const formData: FormData = new FormData();
    for (let i = 0; i < file.length; i++) {
      formData.append('file[]', file[i]);
    }
    formData.append("DATA_FINE", dataFine);
    formData.append("DATA_INIZIO", dataInizio);
    const req = new HttpRequest('POST', `${this.baseUrl}/ImportCommesse.php`, formData, {
      reportProgress: true,
      responseType: 'json'
    });

    return this.http.request(req);
  }
  
}