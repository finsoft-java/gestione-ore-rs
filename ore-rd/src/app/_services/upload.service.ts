import { Injectable } from '@angular/core';
import { HttpClient, HttpRequest, HttpEvent } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from './../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class UploadFilesService {

  private baseUrl = environment.wsUrl;

  constructor(private http: HttpClient) { }

  upload(file: FileList): Observable<HttpEvent<any>> {
    const formData: FormData = new FormData();

    for (let i = 0; i < file.length; i++) {
      formData.append('file[]', file[i]);
    }

    const req = new HttpRequest('POST', `${this.baseUrl}/ImportExcelLul.php`, formData, {
      reportProgress: true,
      responseType: 'json'
    });

    return this.http.request(req);
  }
  
}