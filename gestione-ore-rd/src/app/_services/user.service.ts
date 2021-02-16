import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';

import { User, UserRuoli } from '@/_models';

@Injectable({ providedIn: 'root' })
export class UserService {
    constructor(private http: HttpClient) { }
    
    getAll() {
        return this.http.get<User[]>(`${config.apiUrl}/User.php`);
    }
    getAllFiltered(top:number, skip:number,search : string, orderBy : string) {
        return this.http.get<User[]>(`${config.apiUrl}/User.php?top=${top}&skip=${skip}&search=${search}&orderby=${orderBy}`);
    }
    getById(username: string) {
        return this.http.get(`${config.apiUrl}/User.php?username=${username}`);
    }
    insert(user: User) {
        return this.http.put(`${config.apiUrl}/User.php`, user);
    }
    insertPassword(username:string, pwd:string) {
        return this.http.post(`${config.apiUrl}/UserPassword.php`, {
            username : username,
            pwd : pwd
        });
    }
    update(user: User) {
        return this.http.post(`${config.apiUrl}/User.php`, user);
    }
    delete(username: string) {
        return this.http.delete(`${config.apiUrl}/User.php?username=${username}`);
    }
    sync() {
        return this.http.post(`${config.apiUrl}/SyncLDAP.php`, '');
    }
}