import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { BehaviorSubject, Observable } from 'rxjs';
import { map } from 'rxjs/operators';

import { User } from '@/_models';

@Injectable({ providedIn: 'root' })
export class AuthenticationService {
    private currentUserSubject: BehaviorSubject<User>;
    public currentUser: Observable<User>;

    constructor(private http: HttpClient) {
        this.currentUserSubject = new BehaviorSubject<User>(JSON.parse(localStorage.getItem('currentUser')));
        this.currentUser = this.currentUserSubject.asObservable();
    }

    public get currentUserValue(): User {        
        return this.currentUserSubject.value;
    }

    login(username: string, password: string) {
        const url = `${config.apiUrl}/login.php`;
        const body = JSON.stringify({username: username,
                                     password: password});

        return this.http.post<any>(url, body).pipe(map(response => {
            if (response) {
                localStorage.setItem('currentUser', JSON.stringify(response['value']));
                this.currentUserSubject.next(response['value']);
            }
            return response;
        }));



       
    }

    logout() {
        // remove user from local storage to log user out
        localStorage.removeItem('currentUser');
        this.currentUserSubject.next(null);
    }

    controlloUtenza(username,pass){

    }
}