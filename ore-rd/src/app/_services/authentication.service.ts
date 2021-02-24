import { Router } from '@angular/router';
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { BehaviorSubject, Observable, Subject } from 'rxjs';
import { map } from 'rxjs/operators';
import { User } from '../_models/index';
import { environment } from './../../environments/environment';

@Injectable({ providedIn: 'root' })
export class AuthenticationService {
    public currentUserSubject: User;
    nameChange: Subject<string> = new Subject<string>();

    constructor (private http: HttpClient,private router: Router) {
      this.currentUserSubject = new User;
    }

    changeUsername(username: string) {
      this.currentUserSubject.username = username;
      this.nameChange.next(username);
    }

    login(username: string, password: string) {
      
        const url = environment.wsUrl+`login.php`;
        const body = JSON.stringify({username: username,
                                     password: password});
        return this.http.post<any>(url, body).pipe(map(data => {
          if (data) {
            localStorage.setItem('currentUser', data["value"].username);
            this.changeUsername(username);
            //if(this.isAuthenticated()){
            //  this.router.navigate(['/progetti']);
            //}
          }else{
            data = 'bho';
          }
          console.log('aaa');
          return data;
        }));
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
