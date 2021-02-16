import {Component, Input, OnInit, EventEmitter, Output} from '@angular/core';
import { User, UserRole, Questionario, ProgettoQuestionari, Progetto, Domanda, Sezione, RispostaAmmessa, RispostaQuestionarioCompilato } from '@/_models';
import { AuthenticationService, UserService, AlertService, ProgettiService, QuestionariService } from '@/_services';
import { Subscription } from 'rxjs';
import { DomandeService } from '@/_services/domande.service';

@Component({
  selector: '[table-risposte-row]',
  templateUrl: './table-risposte-row.component.html',
  styleUrls: ['./table-risposte-row.component.css']
})
export class TableRisposteRowComponent implements OnInit {

  @Input() public rispostaCorrente: RispostaAmmessa;
  @Input() public questionario: Questionario; 
  @Input() public domanda: Domanda;
  @Input() public indexRisposta: number;
  @Output() public rispostaCreata: EventEmitter<boolean> = new EventEmitter<boolean>();
  @Output() public itemRemoved = new EventEmitter<number>();

  currentUser: User;
  currentUserSubscription: Subscription;
  risposta_in_modifica: RispostaAmmessa;
  elenco_questionari: Questionario;
  utenti: User;
  risposta_nuova: RispostaAmmessa;
  questionari_loaded = false;
  guardaRisposte = false;
  constructor(private authenticationService: AuthenticationService,
              private progettiService: ProgettiService,
              private domandeService: DomandeService,
              private questionariService: QuestionariService,              
              private userService: UserService,              
              private alertService: AlertService) {
                
              this.currentUserSubscription = this.authenticationService.currentUser.subscribe(user => {
                  this.currentUser = user;
              });
  }
 
  ngOnInit() {
    this.getQuestionari();
    if (this.rispostaCorrente.creating) 
      this.goToEdit();
  }

  /**
   * Attiva tutti i campi INPUT sulla riga corrente
   */
  goToEdit() {
    this.rispostaCorrente.editing = true;
    this.risposta_in_modifica = this.simpleClone(this.rispostaCorrente);
  }

  /**
   * Disattiva tutti i campi INPUT sulla riga corrente, annulla tutte le modifiche effettuate
   */
  returnFromEdit() {
    if(this.rispostaCorrente.creating == true) {
      this.itemRemoved.emit(this.indexRisposta);
    } else {
      this.rispostaCorrente.creating = false;
      this.rispostaCorrente.editing = false;    
      this.risposta_in_modifica = null;
    }
  }
  

  /**
   * Richiamato dopo che l'utente ha premuto il tasto Elimina
   */
  delete() {
    
    this.domanda.risposte.splice(this.indexRisposta,1);
    this.domandeService.updateDomandaConRisposte(this.domanda).subscribe(resp => {
      if (this.authenticationService.currentUserValue) {
        this.risposta_in_modifica = null;
        Object.assign(this.domanda, resp["value"]); // meglio evitare this.utente = ...
        this.rispostaCorrente.editing = false;
      }
    },
    error => {
      this.alertService.error(error);
    });
  }

  /**
   * Richiamato dopo che l'utente ha premuto il tasto Salva
   */
 
  save() {

        this.domandeService.updateDomandaConRisposte(this.domanda).subscribe(resp => {
          if (this.authenticationService.currentUserValue) {
            this.risposta_in_modifica = null;
            Object.assign(this.domanda, resp["value"]); // meglio evitare this.utente = ...
            this.rispostaCorrente.editing = false;
            this.rispostaCorrente.creating = false;
          }
        });

    }   

  controlloDatiImmessi(){
    
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

  truncate(value: string, limit = 25, completeWords = false, ellipsis = '...') {

    if (value.length < limit)
      return `${value.substr(0, limit)}`;
 
    if (completeWords) {
      limit = value.substr(0, limit).lastIndexOf(' ');
    }
    return `${value.substr(0, limit)}${ellipsis}`;
 }

  getQuestionari(): void {
  this.questionari_loaded = false;
  this.questionariService.getAll()
    .subscribe(response => {
        this.elenco_questionari = response["data"];
        this.questionari_loaded = true;
    },
    error => {
      this.alertService.error(error);
      this.questionari_loaded = true;
    });
  }

  getUsers(): void {
    this.userService.getAll().subscribe(
      response => {
        this.utenti = response["data"];
      }
    );
  }

  simpleClone(obj: any) {
    return Object.assign({}, obj);
  }
}
