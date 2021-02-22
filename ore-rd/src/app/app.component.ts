import { User } from './_models';
import { AuthenticationService } from './_services/authentication.service';
import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.css']
})
export class AppComponent implements OnInit {
  title = 'ore-rd';
  router_frontend: Router;
  showFiller = false;
  isLogged: boolean = false;
  currentUserSubject: User = new User;

  constructor(private route: ActivatedRoute, private router: Router,private authenticationService: AuthenticationService) {
    this.router_frontend = router;
  }

  ngOnInit(): void {
    let url = window.location.href.endsWith('login');
    let token = localStorage.getItem('currentUser');
    if(token == null){
      token = '';
    }
    this.currentUserSubject.username = token;
    if(!url) {
      console.log(this.currentUserSubject.username);
      this.isLogged = true;
    }else{
      this.isLogged = false;
    }
  }

  logout(){
    this.authenticationService.logout();
  }

  changeIcon(){

  }
}

