import { Commessa, ListBean, ValueBean } from './../_models';
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';

@Injectable({ providedIn: 'root' })
export class CommesseService {

    constructor(private http: HttpClient) { }

    getAll() {
        return this.http.get<ListBean<Commessa>>(environment.wsUrl + `Commesse.php`);
    }

    downloadGiustificativo(codCommessa: string) {
        return this.http.get(environment.wsUrl + `Giustificativo.php?cod_commessa=${codCommessa}`, {
            responseType: 'arraybuffer'
        });
    }

    deleteGiustificativo(codCommessa: string) {
        return this.http.delete<void>(environment.wsUrl + `Giustificativo.php?cod_commessa=${codCommessa}`);
    }

    uploadGiustificativo(codCommessa: string, file: File) {
        const formData = new FormData();
        formData.append('file', file);
        return this.http.post<void>(environment.wsUrl + `Giustificativo.php?cod_commessa=${codCommessa}`, formData);
    }

}