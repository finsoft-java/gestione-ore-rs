import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';

@Injectable({ providedIn: 'root' })
export class StatisticsService {
    constructor(private http: HttpClient) { }
    
    get() {
        return this.http.get(`${config.apiUrl}/Statistics.php`);
    }

}