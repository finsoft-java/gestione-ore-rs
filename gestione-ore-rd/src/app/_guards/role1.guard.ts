import { Injectable } from '@angular/core';
import { Router, CanActivate, ActivatedRouteSnapshot, RouterStateSnapshot } from '@angular/router';

import { AuthenticationService } from '@/_services';
import { AuthGuard } from './auth.guard';

/**
 * Questo 'guard' verifica che l'utente sia loggato e che abbia almeno ruolo '1'
 */
@Injectable({ providedIn: 'root' })
export class Role1Guard extends AuthGuard implements CanActivate {
    constructor(
        router: Router,
        authenticationService: AuthenticationService
    ) {
        super(router, authenticationService);
    }

    canActivate(route: ActivatedRouteSnapshot, state: RouterStateSnapshot) {

        let success: boolean = super.canActivate(route, state);
        if (success) {
            const currentUser = this.authenticationService.currentUserValue;
            success = (currentUser.ruolo >= '1');
        }
        return success;
    }
}