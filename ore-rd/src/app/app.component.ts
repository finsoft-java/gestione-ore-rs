import { User } from './_models';
import { AuthenticationService } from './_services/authentication.service';
import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router, Event, RouterEvent, NavigationEnd } from '@angular/router';
import { Subscription } from 'rxjs';
import { filter } from 'rxjs/operators';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.css']
})
export class AppComponent implements OnInit {
  title = 'ore-rd';
  showFiller = false;
  isLogged: boolean = false;
  _subscription: Subscription = new Subscription;
  currentUserSubject: User = new User;
  menuDisabled = true;

  constructor(private route: ActivatedRoute, private router: Router, private authenticationService: AuthenticationService) {
  }

  ngOnInit(): void {
    let url = window.location.href.endsWith('login');
    let token = localStorage.getItem('currentUser');

    this._subscription = this.authenticationService.nameChange.subscribe((value) => {
      this.currentUserSubject.username = value;
    });
    if(token == null){
      token = '';
    }
    this.currentUserSubject.username = token;
    if (!url) {
      this.isLogged = true;
    } else {
      this.isLogged = false;
    }
    
    this.router.events.pipe(filter((evt: Event) => evt instanceof NavigationEnd)).subscribe((evt: Event) => {
        this.menuDisabled = ((<NavigationEnd>evt).url == '/login');
    });
  }

  logout(){
    this.authenticationService.logout();
  }

  changeIcon(){

  }
}

