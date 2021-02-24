import { User } from './../_models/user';
import { AuthenticationService } from './../_services/authentication.service';
import { Injectable } from '@angular/core';
import { HttpRequest, HttpHandler, HttpEvent, HttpInterceptor } from '@angular/common/http';
import { Observable, BehaviorSubject } from 'rxjs';

@Injectable()
export class JwtInterceptor implements HttpInterceptor {
    constructor(private authenticationService: AuthenticationService) {}

    intercept(request: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
        // add authorization header with jwt token if available
        let currentUser = this.authenticationService.currentUserSubject;
        if (currentUser && currentUser.username) {
            request = request.clone({
                setHeaders: { 
                    Authorization: `Bearer ${currentUser.username}`
                }
            });
        }
        return next.handle(request);
    }
}