import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';
import { ListBean, ValueBean, ProgettoCommessa } from '../_models';
import { map } from 'rxjs/operators';

@Injectable({ providedIn: 'root' })
export class ProgettiCommesseService {
    constructor(private http: HttpClient) { }

    getById(idProgetto: number) {
        return this.http.get<ListBean<ProgettoCommessa>>(environment.wsUrl + `ProgettiCommesse.php?id_progetto=${idProgetto}`);
    }

    insert(p: ProgettoCommessa) {
        return this.http.post<ValueBean<ProgettoCommessa>>(environment.wsUrl + `ProgettiCommesse.php`, p);
    }

    update(p: ProgettoCommessa) {
        return this.http.put<ValueBean<ProgettoCommessa>>(environment.wsUrl + `ProgettiCommesse.php`, p);
    }

    delete(idProgetto: number, codCommessa: string) {
        return this.http.delete<void>(environment.wsUrl + `ProgettiCommesse.php?id_progetto=${idProgetto}&cod_commessa=${codCommessa}`);
    }

    downloadGiustificativo(idProgetto: number, codCommessa: string) {
        return this.http.get(environment.wsUrl + `Giustificativo.php?id_progetto=${idProgetto}&cod_commessa=${codCommessa}`, {
            responseType: 'arraybuffer'
        });
    }

    deleteGiustificativo(idProgetto: number, codCommessa: string) {
        return this.http.delete<void>(environment.wsUrl + `Giustificativo.php?id_progetto=${idProgetto}&cod_commessa=${codCommessa}`);
    }

    uploadGiustificativo(idProgetto: number, codCommessa: string, file: File) {
        const formData = new FormData();
        formData.append('file', file);
        return this.http.post<void>(environment.wsUrl + `Giustificativo.php?id_progetto=${idProgetto}&cod_commessa=${codCommessa}`, formData);
    }
}