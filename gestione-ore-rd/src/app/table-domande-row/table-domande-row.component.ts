import {Component, Input, OnInit, EventEmitter, Output} from '@angular/core';
import { User, UserRole, Questionario, ProgettoQuestionari, Progetto, Domanda, Sezione, RispostaAmmessa, RispostaQuestionarioCompilato } from '@/_models';
import { AuthenticationService, UserService, AlertService, ProgettiService, QuestionariService } from '@/_services';
import { Subscription } from 'rxjs';
import { DomandeService } from '@/_services/domande.service';
import { Router } from '@angular/router';
import { DomSanitizer, SafeHtml } from '@angular/platform-browser';
import { CopyDomandeService } from '@/_services/copy.domande.service';
import { ResourceLoader } from '@angular/compiler';

@Component({
  selector: '[table-domande-row]',
  templateUrl: './table-domande-row.component.html',
  styleUrls: ['./table-domande-row.component.css']
})
export class TableDomandeRowComponent implements OnInit {

  @Input() public questionario: Questionario; 
  @Input() public domanda: Domanda;
  @Input() public indexDomanda: number;
  @Input() public esisteDomandaEditing: boolean;
  @Output() public mostraRisposte = new EventEmitter<Domanda>();
  @Output() public itemRemoved = new EventEmitter<number>();
  @Output() public domandaDupl = new EventEmitter<void>();
  @Output() public changeEditMode =  new EventEmitter<boolean>();

  currentUser: User;
  currentUserSubscription: Subscription;
  domanda_in_modifica: Domanda;
  elenco_questionari: Questionario;
  questionatioSelezionato: Questionario;
  utenti: User;
  controlloRisposte:boolean;
  risposta_nuova: RispostaAmmessa;
  html_type_array = ["text","number"];
  tipiRisposte = ["Risposta Aperta","Risposta Chiusa"];
  questionari_loaded = false;
  guardaRisposte = false;
  setDisableText = false;
  risposta_aperta = false;
  setDisableNumber = false;
  tipo_risposta: number;
  descrizione_truncate: SafeHtml;
  
  constructor(private authenticationService: AuthenticationService,
              private progettiService: ProgettiService,
              private domandeService: DomandeService,
              private copydomandeService: CopyDomandeService,
              private questionariService: QuestionariService,              
              private userService: UserService,       
              private router: Router,       
              private alertService: AlertService,
              private sanitizer:DomSanitizer) {
                
              this.currentUserSubscription = this.authenticationService.currentUser.subscribe(user => {
                  this.currentUser = user;
              });
  }
 
  ngOnInit() {
    this.getQuestionari();    
    this.questionatioSelezionato = this.questionario;    
    this.domanda_in_modifica = this.simpleClone(this.domanda);
    this.descrizione_truncate = this.htmlToPlaintext(this.domanda.descrizione);
    if (this.domanda.creating) 
      this.goToEdit();
    
  }
  htmlToPlaintext(text) {
    return text ? String(text).replace(/<[^>]+>/gm, '') : '';
  }
  setValidRisposte(){
    if(this.tipo_risposta == 0){      
      if(this.domanda_in_modifica.risposte != null && this.domanda_in_modifica.risposte.length > 0){
        if(confirm("Se avevi inserito delle risposte ed hai modificato il tipo di risposta da chiusa ad aperta, le risposte verranno eliminate. Sei sicuro?")) {
          this.risposta_aperta = true;
          this.domanda_in_modifica.risposte = [];
        }
      }else{
        this.risposta_aperta = true;
        this.domanda_in_modifica.risposte = [];
      }
    }else{
      this.risposta_aperta = false;
    }
  }

  duplicaDomanda(){
    this.copydomandeService.copiaDomandaConRisposte(this.domanda.id_questionario, this.domanda.progressivo_sezione, this.domanda.progressivo_domanda).subscribe(resp => {
      this.alertService.success("Domanda duplicata!");
      this.domandaDupl.emit();
    },
    error => {
      this.alertService.error(error);
    });
  }
  /**
   * Attiva tutti i campi INPUT sulla riga corrente
   */
  goToEdit() {
    this.domanda.editing = true;
    this.domanda_in_modifica = this.simpleClone(this.domanda);
    if(this.domanda_in_modifica.risposte == null){
      this.risposta_aperta = true;
      this.tipo_risposta = 0; 
    }else{
      if(this.domanda_in_modifica.risposte.length > 0){
        this.risposta_aperta = false;
        this.tipo_risposta = 1; 
      }else{
        this.risposta_aperta = true;
        this.tipo_risposta = 0; 
      }
    }
    this.changeEditMode.emit(true);
    this.setValidInput();
  }
  setValidInput(){
    if(this.domanda_in_modifica.html_type == "0"){
      this.setDisableText = true; 
      this.setDisableNumber = false; 
    }else if(this.domanda_in_modifica.html_type == "1"){
      this.setDisableText = false; 
      this.setDisableNumber = true; 
    }
  }
  removeItem(i: number) {
    this.domanda_in_modifica.risposte.splice(i, 1);
  }
  creaRisposta(){
    let risposta_nuova = new RispostaAmmessa();
    let progressivo_risposta = 1;
    if(this.domanda_in_modifica.risposte != null){
      
      this.domanda_in_modifica.risposte.forEach( r => {
          if (r.progressivo_risposta >= progressivo_risposta) {
              progressivo_risposta = 1 + parseInt(<any>r.progressivo_risposta);
          }
      });
      
    }else{
      this.domanda_in_modifica.risposte = [];
      progressivo_risposta = 1;
    }
    risposta_nuova.id_questionario = this.questionario.id_questionario;
    risposta_nuova.descrizione = '';
    risposta_nuova.progressivo_domanda= this.domanda_in_modifica.progressivo_domanda;
    risposta_nuova.progressivo_risposta = progressivo_risposta;
    risposta_nuova.progressivo_sezione= this.domanda_in_modifica.progressivo_sezione;
    risposta_nuova.valore = 1;
    risposta_nuova.creating = true;
    this.domanda_in_modifica.risposte.push(risposta_nuova);
}
  /**
   * Disattiva tutti i campi INPUT sulla riga corrente, annulla tutte le modifiche effettuate
   */
  returnFromEdit() { 
    if(this.domanda_in_modifica.risposte != null){
      if(this.domanda_in_modifica.risposte.length > 0){
        for(var i =0; i < this.domanda_in_modifica.risposte.length; i++){
          if(this.domanda_in_modifica.risposte[i].editing == true){
            this.alertService.error("Hai una risposta aperta, salva la tua modifica prima di chiudere la domanda!");
            return false;
          }
        }
      }
    }
    if(this.domanda.creating == true) {
      this.itemRemoved.emit(this.indexDomanda);
    } else { 
      this.domanda.creating = false;
      this.domanda.editing = false;    
      this.domanda_in_modifica = null;
      this.changeEditMode.emit(false);
      this.descrizione_truncate = this.htmlToPlaintext(this.domanda.descrizione);
    }
    this.changeEditMode.emit(false);
  }

  /**
   * Richiamato dopo che l'utente ha premuto il tasto Elimina
   */
  delete() {
    this.domandeService.eliminaDomandaConRisposte(this.domanda.id_questionario, this.domanda.progressivo_sezione, this.domanda.progressivo_domanda).subscribe(resp => {
          this.itemRemoved.emit(this.indexDomanda);
    },
    error => {
      this.alertService.error(error);
    });
  }

  /**
   * Richiamato dopo che l'utente ha premuto il tasto Salva
   */
  newRisposta(){
    let risposta_nuova = new RispostaAmmessa();
    risposta_nuova.id_questionario = this.questionario.id_questionario;
    risposta_nuova.descrizione = '';
    risposta_nuova.progressivo_domanda= this.domanda.progressivo_domanda;
    risposta_nuova.progressivo_risposta = 0;
    risposta_nuova.valore = 1;
    this.domanda.risposte.push(risposta_nuova);
    this.guardaRisposte = true;
    this.mostraRisposte.emit(this.domanda);
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

  save() {
    if(this.controlloDatiImmessi()){
    //se il flg creating è settato sarà una insert altrimenti update
      if(this.domanda_in_modifica.creating == true) {

        this.domandeService.creaDomandaConRisposte(this.domanda_in_modifica).subscribe(resp => {
          if (this.authenticationService.currentUserValue) {
            this.domanda_in_modifica = null;
            this.domanda = this.simpleClone(resp["value"]);
            this.domanda_in_modifica = this.simpleClone(resp["value"]);
            this.domanda.editing = true;
            this.domanda.creating = false;
            this.changeEditMode.emit(true);
            this.alertService.success("Domanda inserita con successo");
            this.returnFromEdit();
          }
        },
        error => {
          this.alertService.error(error);
        });
      } else {

        if(this.tipo_risposta == 0){
          if(this.domanda.risposte != null){
            if(this.domanda.risposte.length > 0){
              this.controlloRisposte = true;
            }else{
              this.controlloRisposte = false;
            }
          }
          this.domanda_in_modifica.risposte = [];
        }else if(this.tipo_risposta == 1){
          this.domanda_in_modifica.html_max = null;
          this.domanda_in_modifica.html_min = null;
          this.domanda_in_modifica.html_maxlength = null;
          this.domanda_in_modifica.html_pattern = null; 
        }
        
        if(this.controlloRisposte){
            this.domandeService.updateDomandaConRisposte(this.domanda_in_modifica).subscribe(resp => {
              if (this.authenticationService.currentUserValue) {
                this.domanda_in_modifica = null;
                this.domanda = this.simpleClone(resp["value"]);
                this.domanda_in_modifica = this.simpleClone(resp["value"]);
                this.domanda.editing = true;
                this.changeEditMode.emit(true);
                this.alertService.success("Domanda salvata con successo");
                this.router.navigate(["questionari/"+this.questionario.id_questionario]);
              }
            });
        }else{
          this.domandeService.updateDomandaConRisposte(this.domanda_in_modifica).subscribe(resp => {
            if (this.authenticationService.currentUserValue) {
              this.domanda_in_modifica = null;
              this.domanda = this.simpleClone(resp["value"]);
              this.domanda_in_modifica = this.simpleClone(resp["value"]);
              this.domanda.editing = true;
              this.changeEditMode.emit(true);
              this.alertService.success("Domanda salvata con successo");
              this.router.navigate(["questionari/"+this.questionario.id_questionario]);
            }
          });
        }
      } 
    }   
  }

  controlloDatiImmessi(){
    if(this.domanda_in_modifica.html_max == null && this.domanda_in_modifica.html_min != ''){
      return true;
    }else{
      if(this.domanda_in_modifica.html_min != '' &&  (this.domanda_in_modifica.html_min > this.domanda_in_modifica.html_max)){
        this.alertService.error("Il minimo non può superare il massimo");
        //this.scrollToTop();
        return false;
      }
    }
    
    return true;
  }
  truncate(value: string, limit =15, completeWords = false, ellipsis = '...') {

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
  this.userService.getAll()
    .subscribe(response => {
        this.utenti = response["data"];
    });
}
  simpleClone(obj: any) {
    return Object.assign({}, obj);
  }


  getRisposta(){
    this.mostraRisposte.emit(this.domanda);
  }

}
