import {Component, Input, OnInit, EventEmitter, Output} from '@angular/core';
import { User, UserRole } from '@/_models';
import { AuthenticationService, UserService, AlertService } from '@/_services';
import { Subscription } from 'rxjs';
import { Router } from '@angular/router';
declare var $: any;

@Component({
  selector: '[table-utenti-row]',
  templateUrl: './table-utenti-row.component.html',
  styleUrls: ['./table-utenti-row.component.css']
})
export class TableUtentiRowComponent implements OnInit {

  @Input() public utente: User; 
  @Input() public indice_utente: number;
  @Output() public itemRemoved = new EventEmitter<string>(); //emette lo username
  @Output() public itemModified = new EventEmitter<User>();
  @Output() public itemCreated = new EventEmitter<User>();
  closeResult: string;
  currentUser: User;
  utente_username: string;
  currentUserSubscription: Subscription;
  utente_in_modifica: User;
  utente_con_password: User;
  password_utente: string;
  ruoliMap = ["Utente normale", "Gestore progetti", "Amministratore"];

  constructor(private authenticationService: AuthenticationService,
              private userService: UserService,
              private alertService: AlertService) {
                
              this.currentUserSubscription = this.authenticationService.currentUser.subscribe(user => {
                  this.currentUser = user;
              });
  }
 
  ngOnInit() {
    if (this.utente.creating) 
      this.goToEdit();
  }

  /**
   * Attiva tutti i campi INPUT sulla riga corrente
   */
  goToEdit() {
    this.utente.editing = true;
    this.utente_in_modifica = this.simpleClone(this.utente);
  }

  /**
   * Disattiva tutti i campi INPUT sulla riga corrente, annulla tutte le modifiche effettuate
   */
  returnFromEdit() {
    if(this.utente.creating == true) {
      this.itemRemoved.emit("");
    } else {
      this.utente.creating = false;
      this.utente.editing = false;    
      this.utente_in_modifica = null;
    }
  }

  /**
   * Richiamato dopo che l'utente ha premuto il tasto Elimina
   */
  delete(username: string) {
    this.userService.delete(username).subscribe(resp => {
          this.itemRemoved.emit(this.utente.username);
    },
    error => {
      this.alertService.error(error);
    });
  }
  scrollToTop(){
    let scrollToTop = window.setInterval(() => {
        let pos = window.pageYOffset;
        if (pos > 0) {
            window.scrollTo(0, pos - 20); // how far to scroll on each step
        } else {
            window.clearInterval(scrollToTop);
        }
    }, 16);
}
  /**
   * Richiamato dopo che l'utente ha premuto il tasto Salva
   */
  save() {
    //se il flg nuovo utente è settato sarà una insert altrimenti update
    if(this.utente_in_modifica.creating == true) {
      this.userService.insert(this.utente_in_modifica).subscribe(resp => {
        if (this.authenticationService.currentUserValue) { 

          this.utente_in_modifica = null;
          Object.assign(this.utente, resp["value"]); // meglio evitare this.utente = ...
          this.utente.editing = false;
          this.utente.creating = false;
          this.itemCreated.emit(this.utente);
          this.alertService.success("Utente inserito con successo");
          //this.scrollToTop();
        }
      },
      error => {
        this.alertService.error(error);
      });
    } else {
      this.userService.update(this.utente_in_modifica).subscribe(resp => {
        if (this.authenticationService.currentUserValue) {
          this.utente_in_modifica = null;
          Object.assign(this.utente, resp["value"]); // meglio evitare this.utente = ...
          this.utente.editing = false;
          this.itemModified.emit(this.utente);          
          this.alertService.success("Utente modificato con successo");
          //this.scrollToTop();
        }
      });
    }
    
  }

  truncate(value: string, limit = 25, completeWords = false, ellipsis = '...') {

    if (value.length < limit)
      return `${value.substr(0, limit)}`;
 
    if (completeWords) {
      limit = value.substr(0, limit).lastIndexOf(' ');
    }
    return `${value.substr(0, limit)}${ellipsis}`;
 }

  simpleClone(obj: any) {
    return Object.assign({}, obj);
  }

  showModal(utente):void {
    $(".myModal_"+this.indice_utente).modal('show');
  }
  sendModal(username:string): void {
    this.userService.insertPassword(username, this.password_utente).subscribe(resp => {
      this.alertService.success("Password modificata con successo");
      this.hideModal();
    });
  }
  hideModal():void {
    document.getElementById('close-modal_'+this.indice_utente).click();
  }

}
