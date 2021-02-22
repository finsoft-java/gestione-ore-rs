import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { BehaviorSubject, Observable, Subject } from 'rxjs';
import { map } from 'rxjs/operators';
import { User } from '../_models/index';

//import { User } from '@/_models';

@Injectable({ providedIn: 'root' })
export class AuthenticationService {
    public currentUserSubject: User;
    nameChange: Subject<string> = new Subject<string>();

    constructor (private http: HttpClient) {
      this.currentUserSubject = new User;
    }

    changeUsername(username: string) {
      this.currentUserSubject.username = username;
      this.nameChange.next(username);
    }

    login(username: string, password: string) {
        const url = `http://localhost/ore-rd/ws/login.php`;
        const body = JSON.stringify({username: username,
                                     password: password});

        //return this.http.post<any>(url, body).pipe(map(response => {
        //    if (response) {
        localStorage.setItem('currentUser', JSON.stringify(username));
        this.changeUsername(username);
        //    }
        //    return response;
        // }));
    }
    public isAuthenticated(): boolean {
      const token = localStorage.getItem('currentUser');
      if(token == null && token == undefined){
        return false;
      }else{
        this.currentUserSubject.username = token;
        return true;
      }
    }

    logout() {
        // remove user from local storage to log user out
        localStorage.removeItem('currentUser');
        window.location.reload()
        //this.currentUserSubject.next();
    }
}
