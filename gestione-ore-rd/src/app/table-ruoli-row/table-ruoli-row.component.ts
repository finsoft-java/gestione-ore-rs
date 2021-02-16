import {Component, Input, OnInit, EventEmitter, Output} from '@angular/core';
import { User, UserRole, UserRuoli, Progetto } from '@/_models';
import { AuthenticationService, UserService, AlertService } from '@/_services';
import { Subscription } from 'rxjs';
import { Router } from '@angular/router';

@Component({
  selector: '[table-ruoli-row]',
  templateUrl: './table-ruoli-row.component.html',
  styleUrls: ['./table-ruoli-row.component.css']
})
export class TableRuoliRowComponent implements OnInit {

  progettoUtenti: UserRuoli;
  @Input() public utente: UserRuoli; 
  @Input() public progetto: Progetto;
  
  currentUser: User;
  currentUserSubscription: Subscription;

  constructor(private authenticationService: AuthenticationService) {
                
              this.currentUserSubscription = this.authenticationService.currentUser.subscribe(user => {
                  this.currentUser = user;
              });
  }
 
  ngOnInit() {
  }

}
