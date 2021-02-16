import { Component, OnInit, OnDestroy, Output } from '@angular/core';
import { Subscription, Observable } from 'rxjs';
import { User, QuestionarioCompilato, VistaQuestionariCompilabili, Sezione, RispostaAmmessa, RispostaQuestionarioCompilato, Progetto, Questionario, Domanda } from '@/_models';
import { UserService, AuthenticationService, QuestionariCompilatiService, AlertService, WebsocketService, Message } from '@/_services';
import { ActivatedRoute, Router } from '@angular/router';
import { FormBuilder, FormGroup, Validators, FormControl } from '@angular/forms';

@Component({templateUrl: 'compila_questionario.component.html'})
export class CompilaQuestionarioComponent implements OnInit, OnDestroy {
    
    currentUserSubscription: Subscription;
    questSubscription: Subscription;
    websocketsSubscription: Subscription;
    currentUser: User;
    
    errore_string: string[] = [];
    dom_err : Domanda [] = [];
    questionarioCompilato: QuestionarioCompilato;
    progressivo_quest_comp: number;
    risposteForm: FormGroup;
    utente_valutato_corrente: string;
    indice_sezione_corrente: number;    // l'indice non è per forza uguale al progressivo
    sezione_corrente: Sezione;
    loading = true;
    esiste_sezione_prec = false;
    esiste_sezione_succ = false;
    esiste_utente_prec = false;
    esiste_utente_succ = false;
    esiste_utente_succ_hidd = true;
    esiste_utente_prec_hidd = true;
    title_sez_succ = "Sez. successiva";
    constructor(
        private authenticationService: AuthenticationService,
        private questCompService: QuestionariCompilatiService,
        private alertService: AlertService,
        private formBuilder: FormBuilder,
        private websocketsService: WebsocketService,
        private route: ActivatedRoute,
        private router: Router
    ) {
        this.currentUserSubscription = this.authenticationService.currentUser.subscribe(user => {
            this.currentUser = user;
        });
        this.websocketsSubscription = websocketsService.messages.subscribe(msg => { this.onWebsocketMessage(msg); });
        
    } 

    ngOnInit() {
        this.questSubscription = this.route.params.subscribe(params => {
            this.progressivo_quest_comp = +params['progressivo_quest_comp']; // (+) converts string 'id' to a number
            this.getQuestionarioCompilato();
         });        
    }

    ngOnDestroy() {
        // unsubscribe to ensure no memory leaks
        this.currentUserSubscription.unsubscribe();
        this.questSubscription.unsubscribe();
        this.websocketsSubscription.unsubscribe();
    }
    getQuestionarioCompilato(): void {
      this.loading = true;
      this.questCompService.getById(this.progressivo_quest_comp)
        .subscribe(response => {
            this.questionarioCompilato = response["value"];
            let utente_da_caricare = null;
            let indice_sezione_da_caricare = 0;

            if (this.questionarioCompilato.progressivo_sezione_corrente != null) {
                // Una parte del questionario è già stata compilata, vado all'ultima sezione compilata
                utente_da_caricare = this.questionarioCompilato.utente_valutato_corrente;
                indice_sezione_da_caricare = this.questionarioCompilato.sezioni.findIndex(s =>
                        s.progressivo_sezione == this.questionarioCompilato.progressivo_sezione_corrente);
            } else {
                if(this.questionarioCompilato.utenti_valutati) {
                    // l'utente è null per i questionari generici
                    if(this.questionarioCompilato.utenti_valutati[0]){
                        utente_da_caricare = this.questionarioCompilato.utenti_valutati[0].username;
                    }
                }                
            }
            this.utente_valutato_corrente = utente_da_caricare;
            this.caricaSezione(utente_da_caricare, indice_sezione_da_caricare);
            for(let i=0; i < this.questionarioCompilato.progetto.questionari.length; i++){
                if(this.questionarioCompilato.id_questionario == this.questionarioCompilato.progetto.questionari[i].id_questionario){
                    if(this.questionarioCompilato.progetto.questionari[i].tipo_questionario == "1"){
                        this.esiste_utente_succ_hidd = true;
                        this.esiste_utente_prec_hidd = true;
                    }else{
                        this.esiste_utente_succ_hidd = false;
                        this.esiste_utente_prec_hidd = false;
                    }
                }
            }
        },
        error => {
          this.alertService.error(error);
          this.loading = false;
        });
    }
    convalida() {
        let errore= false;
        if (this.questionarioCompilato.stato == '0') {
            
            if (this.indice_sezione_corrente == null) {
                return;
            } 
            this.errore_string = [];
            if(this.controllaRisposte(this.sezione_corrente.domande)[0] == null){
                let risposte : RispostaQuestionarioCompilato[] = [];
                this.sezione_corrente.domande.forEach(domanda => {
                    risposte.push(domanda.risposta);
                });
                this.loading = true;
                this.questCompService.salvaRisposte(this.progressivo_quest_comp, risposte)
                    .subscribe(response => {
                        this.alertService.success("Salvataggio effettuato.");
                        this.sendMsgQuestComp(this.questionarioCompilato, 'Compilazione salvata');
                        this.loading = false;
                        if (!this.esiste_utente_succ) {
                            // ultima sezione: abilito il bottone 'Convalida'
                            this.questionarioCompilato.is_compilato = '1';
                        }

                        let message = "Si prega di completare le sezioni:<br/> ";
                        this.questCompService.getRisposteUtenti(this.progressivo_quest_comp).subscribe(response => {
                            if(response["value"].length >= 1){
                                for(let i=0; i < response["value"].length; i++){
                                    message += response["value"][i].progressivo_sezione;
                                    if(response["value"][i].nominativo != null && response["value"][i].nominativo.trim() != ""){
                                        message+= " per l'utente <strong>"+response["value"][i].nominativo+"</strong>";
                                    }
                                    message += ";<br/>";
                                    errore = true;
                                    this.alertService.error(message);
                                }
                            }else{
                                this.questCompService.convalida(this.progressivo_quest_comp)
                                .subscribe(response => {
                                    this.sendMsgQuestComp(this.questionarioCompilato, 'Compilazione convalidata');
                                    this.router.navigate(['/questionari_compilati']);
                                },
                                error => {
                                    this.alertService.error(error);
                                });
                            }
                        },
                        error => {
                            this.alertService.error(error);
                        });
                    },
                    error => {
                        this.alertService.error(error);
                        this.loading = false;
                    });
            }else{
                for(let i = 0; i < this.dom_err.length; i++){
                    this.errore_string.push(this.dom_err[i].progressivo_sezione+"."+this.dom_err[i].progressivo_domanda);
                }
                this.alertService.error("Attenzione! sono presenti degli errori nelle domande: <br/>"+this.errore_string.join('<br/>'));
                return false;
            }
        }
        
    }
    caricaSezione(nome_utente_valutato: string, indice: number) {

        if (!this.questionarioCompilato || !this.questionarioCompilato.sezioni || indice >= this.questionarioCompilato.sezioni.length || indice < 0) {
            this.alertService.error(`La sezione ${indice} non esiste`);
            return;
        }
        this.loading = true;
        let progressivo_sezione = this.questionarioCompilato.sezioni[indice].progressivo_sezione;

        this.questCompService.getSezione(this.progressivo_quest_comp, progressivo_sezione, nome_utente_valutato)
            .subscribe(response => {
                this.indice_sezione_corrente = indice;
                this.sezione_corrente = response["value"];
                this.utente_valutato_corrente = nome_utente_valutato;
                this.indice_sezione_corrente = indice;
                this.rimescola();
                this.calc_esiste_prec_succ();
                this.loading = false;
            },
            error => {
                this.alertService.error(error);
                this.loading = false;
            });
    }
    /**
     * Va a cercare tutte le domande che prevedono il rimescolamento, e rimescola le risposte
     */
    rimescola() {
        if (this.sezione_corrente && this.sezione_corrente.domande) {
            this.sezione_corrente.domande.forEach(domanda => {
                if (domanda.rimescola == true && domanda.risposte) {
                    this.shuffle(domanda.risposte);
                }
            });
        }
    }

    /*
    CONTROLLI CAMPI RISPOSTE
    html_type:
    '0' => 'text',
    '1' => 'number',
    '2' => 'date',
    '3' => 'button',
    '4' => 'checkbox',
    '5' => 'color',
    '6' => 'datetime-local',
    '7' => 'month',
    '8' => 'range',
    '9' => 'tel',
    'A' => 'time',
    'B' => 'week'
    */
    controllaRisposte(domande){
        let success = true;
        this.dom_err = [];
        for(let i = 0; i < domande.length; i++){
            let html_type = domande[i].html_type;
            domande[i].is_valid = true;
            if(domande[i].obbligatorieta == true && (domande[i].risposta.risposta_aperta == null || domande[i].risposta.risposta_aperta.trim() == "") 
                                                    && 
                                                    (domande[i].risposta.progressivo_risposta == null ||
                                                    domande[i].risposta.progressivo_risposta.trim() == "")                                  
                ){
                domande[i].is_valid = false;
                success = false;
                this.dom_err.push(domande[i]);
                
            }
            switch (html_type) {
                case "0":
                    let html_pattern = domande[i].html_pattern;
                    if(html_pattern != null){
                        this.risposteForm = new FormGroup({
                            'risposta_aperta': new FormControl(domande[i].risposta.risposta_aperta, [
                                Validators.compose([Validators.pattern(domande[i].html_pattern)])                                                
                                                ])
                        });
                        if (this.risposteForm.invalid) {
                            domande[i].is_valid = false;
                            success = false;
                            this.dom_err.push(domande[i]);
                        }
                    }
                    break;
                case "1":
                    this.risposteForm = new FormGroup({
                        'risposta_aperta': new FormControl(domande[i].risposta.risposta_aperta, [
                                                Validators.compose([Validators.min(domande[i].html_min), Validators.max(domande[i].html_max)])
                        ])
                    });
                    if (this.risposteForm.invalid) {
                        domande[i].is_valid = false;
                        success = false;
                        this.dom_err.push(domande[i]);
                    }
                    break;
            
                default:
                    break;
            }
        }
        return this.dom_err;
    }
    
    salvaSezione() {
        
        this.errore_string = [];
        if (this.indice_sezione_corrente == null) {
            return;
        } 
        
        if(this.controllaRisposte(this.sezione_corrente.domande)[0] == null){

     
            let risposte : RispostaQuestionarioCompilato[] = [];
            this.sezione_corrente.domande.forEach(domanda => {
                risposte.push(domanda.risposta);
            });
            this.loading = true;
            this.questCompService.salvaRisposte(this.progressivo_quest_comp, risposte)
                .subscribe(response => {
                    this.alertService.success("Salvataggio effettuato.");
                    this.sendMsgQuestComp(this.questionarioCompilato, 'Compilazione salvata');
                    this.loading = false;
                    if (!this.esiste_utente_succ) {
                        // ultima sezione: abilito il bottone 'Convalida'
                        this.questionarioCompilato.is_compilato = '1';
                    }
                },
                error => {
                    this.alertService.error(error);
                    this.loading = false;
                });
        }else{
            for(let i = 0; i < this.dom_err.length; i++){
                this.errore_string.push(this.dom_err[i].progressivo_sezione+"."+this.dom_err[i].progressivo_domanda);
            }
            this.alertService.error("Attenzione! sono presenti degli errori nelle domande: <br/>"+this.errore_string.join('<br/>'));
            return false;
        }
    }
    utentePrecedente(){
        if (this.questionarioCompilato.stato == '0') {
            if(this.salvaSezione() == false){
                return false;
            }
        }
        let indice_utente_corrente = this.questionarioCompilato.utenti_valutati.findIndex(u => u.username == this.utente_valutato_corrente);
        this.utente_valutato_corrente = this.questionarioCompilato.utenti_valutati[indice_utente_corrente-1].username;
        this.indice_sezione_corrente = 0;
        

        
        this.caricaSezione(this.utente_valutato_corrente, this.indice_sezione_corrente);
    }
    goToUtente(){
        if (this.questionarioCompilato.stato == '0') {
            if(this.salvaSezione() == false){
                
            }
        }
        this.indice_sezione_corrente = 0;
        this.caricaSezione(this.utente_valutato_corrente, this.indice_sezione_corrente);
    }
    utenteSuccessivo(){
        if (this.questionarioCompilato.stato == '0') {
            if(this.salvaSezione() == false){
                return false;
            }
        }
        let indice_utente_corrente = this.questionarioCompilato.utenti_valutati.findIndex(u => u.username == this.utente_valutato_corrente);
        this.utente_valutato_corrente = this.questionarioCompilato.utenti_valutati[indice_utente_corrente+1].username;
        this.indice_sezione_corrente = 0;        
        this.caricaSezione(this.utente_valutato_corrente, this.indice_sezione_corrente);
    }

    sezSuccessiva() {
        if (this.indice_sezione_corrente == null) {
            return;
        }
        if (this.questionarioCompilato.stato == '0') {
            if(this.salvaSezione() == false){
                return false;
            }
        }

        if (this.indice_sezione_corrente < this.questionarioCompilato.sezioni.length) {
            ++this.indice_sezione_corrente;
        }
        this.caricaSezione(this.utente_valutato_corrente, this.indice_sezione_corrente);
    }
    sezPrecedente() {
        if (this.indice_sezione_corrente == null) {
            return;
        }
        if (this.questionarioCompilato.stato == '0') {
            this.salvaSezione();
        }
        if (this.indice_sezione_corrente > 0) {
            --this.indice_sezione_corrente;
        } 
        this.caricaSezione(this.utente_valutato_corrente, this.indice_sezione_corrente);
    }


    shuffle(array: any[]) {
        //see https://stackoverflow.com/questions/2450954

        let currentIndex = array.length, temporaryValue : any, randomIndex : number;
      
        // While there remain elements to shuffle...
        while (0 !== currentIndex) {
      
          // Pick a remaining element...
          randomIndex = Math.floor(Math.random() * currentIndex);
          currentIndex -= 1;
      
          // And swap it with the current element.
          temporaryValue = array[currentIndex];
          array[currentIndex] = array[randomIndex];
          array[randomIndex] = temporaryValue;
        }
    }
    calc_esiste_prec_succ() {
        let indice_utente_corrente = this.questionarioCompilato.utenti_valutati ? this.questionarioCompilato.utenti_valutati.findIndex(u => u.username == this.utente_valutato_corrente) : null;

        let esiste_prec = true;
        let ind1 = indice_utente_corrente-1;
        if(this.questionarioCompilato.utenti_valutati[ind1] == undefined){
            esiste_prec = false;
        }
        this.esiste_utente_prec = esiste_prec;
        
        let esiste_succ = true;
        let ind2 = indice_utente_corrente+1;
        if(this.questionarioCompilato.utenti_valutati[ind2] == undefined){
            esiste_succ = false;
        }
        this.esiste_utente_succ = esiste_succ;
        
        let esiste_sez_prec = true;
        let ind3 = this.indice_sezione_corrente-1;
        if(this.questionarioCompilato.sezioni[ind3] == undefined){
            esiste_sez_prec = false;
        }
        this.esiste_sezione_prec = esiste_sez_prec;
        
        let esiste_sez_succ = true;
        let ind4 = this.indice_sezione_corrente+1;
        if(this.questionarioCompilato.sezioni[ind4] == undefined){
            esiste_sez_succ = false;
        }
        this.esiste_sezione_succ = esiste_sez_succ;
    }
    sendMsgQuestComp(q : QuestionarioCompilato | VistaQuestionariCompilabili, note : string) {
        let msg : Message = {
            what_has_changed: 'questionariCompilati',
            obj: q,
            note: note
          }
      this.websocketsService.sendMsg(msg);
    }
    onWebsocketMessage(msg : Message) {
        if (msg.what_has_changed == "progetti") {
            let p : Progetto  = msg.obj;
            if (p.id_progetto == this.questionarioCompilato.id_progetto) {
                if (p.stato == '2') {
                    this.alertService.error("Attenzione! Il Progetto è appena stato annullato! Aggiornare la pagina");
                } else if (p.stato == '3') {
                    this.alertService.error("Attenzione! Il Progetto è appena stato completato! Aggiornare la pagina");
                }
            }
        } else if (msg.what_has_changed == "questionari") {
            let q : Questionario  = msg.obj;
            if (q.id_questionario == this.questionarioCompilato.id_questionario) {
                if (q.stato == '2') {
                    this.alertService.error("Attenzione! Il Questionaro è appena stato annullato! Aggiornare la pagina");
                }
            }
        } else if (msg.what_has_changed == "questionariCompilati") {
            let q : QuestionarioCompilato  = msg.obj;
            if (q.progressivo_quest_comp == this.questionarioCompilato.progressivo_quest_comp) {
                this.alertService.error("Attenzione! Questo questionario è appena stato modificato da un altro utente! Aggiornare la pagina");
                this.questionarioCompilato.stato = '2'; // annullato... hack per evitare che sia modificabile
            }
        }
    }
}