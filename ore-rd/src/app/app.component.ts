import { Component } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.css']
})
export class AppComponent {
  title = 'ore-rd';
  router_frontend: Router;
  showFiller = false;
  constructor(private route: ActivatedRoute, private router: Router) {
    this.router_frontend = router;
  }
  changeIcon(){

  }
}

