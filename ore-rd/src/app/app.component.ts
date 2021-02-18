import { Component } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.css']
})
export class AppComponent {
  title = 'ore-rd';
  constructor(private route: ActivatedRoute, private router: Router) {
  }
  navigateMenu(page: string) {
    if(page === 'progetti'){
      this.router.navigate(['/progetti']);
    }else if(page === 'tipologia'){
      this.router.navigate(['/tipologie-spesa']);
    }
  }
}

