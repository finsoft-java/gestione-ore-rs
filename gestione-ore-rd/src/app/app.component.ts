import { Component } from '@angular/core';
import { Router } from '@angular/router';

import { AuthenticationService } from './_services';
import { User } from './_models';

import {VERSION} from '@angular/material';

@Component({ selector: 'app', templateUrl: 'app.component.html',
styleUrls: ['./app.component.css']})
export class AppComponent {
    version = VERSION;
    currentUser: User;
    sidenavWidth = 4;
    ngStyle: string;
    constructor(
        private router: Router,
        private authenticationService: AuthenticationService
    ) {
        this.authenticationService.currentUser.subscribe(x => this.currentUser = x);
    }
    increase() {
        this.sidenavWidth = 15;
        console.log('increase sidenav width');
      }
      decrease() {
        this.sidenavWidth = 4;
        console.log('decrease sidenav width');
      }

    ngOnInit() {
    }
    logout() {
        this.authenticationService.logout();
        this.router.navigate(['/login']);
    }
}