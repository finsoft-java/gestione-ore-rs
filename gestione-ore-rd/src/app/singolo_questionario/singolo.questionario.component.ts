import { Component, OnInit, OnDestroy, Output, Input, EventEmitter } from '@angular/core';
import { Subscription, Observable } from 'rxjs';
import { User, Questionario, Sezione, Domanda, RispostaAmmessa, RispostaQuestionarioCompilato } from '@/_models';
import { AuthenticationService, QuestionariService, AlertService } from '@/_services';
import { ActivatedRoute } from '@angular/router';

@Component({
    templateUrl: 'singolo.questionario.component.html',
    styleUrls: ['./singolo_questionario.css']})
export class SingoloQuestionarioComponent implements OnInit, OnDestroy {
    
    @Input() public guardaRisposte: boolean;
    @Output() public itemRemoved = new EventEmitter<RispostaAmmessa[]>();
    currentUserSubscription: Subscription;
    questSubscription: Subscription;
    currentUser: User;
    id_questionario: number;
    max_sezione: number;
    questionario: Questionario;  // con l'elenco di tutte le sezioni, ma non esplose
    sezione_corrente: Sezione; //esplosa, con tutte le domande e risposte
    indice_sezione_corrente: number;
    stato_questionario = ["Bozza","Valido","Annullato"];
    nuova_sezione: Sezione;
    is_nuova_sezione: boolean;
    nuova_domanda: Domanda;
    domandaCorrente: Domanda;
    htmlContent = '';
    editMode:boolean;
    esiste_prec = false;
    esiste_succ = false;
    disableCrea = false;
    
    constructor(
        private authenticationService: AuthenticationService,
        private questionariService: QuestionariService,
        private alertService: AlertService,
        private route: ActivatedRoute
    ) {
        this.currentUserSubscription = this.authenticationService.currentUser.subscribe(user => {
            this.currentUser = user;
        });
       
    } 

    ngOnInit() {
        this.questSubscription = this.route.params.subscribe(params => {
            this.id_questionario = +params['id_questionario']; // (+) converts string 'id' to a number
            this.getQuestionario();
        });        
    }
    

    ngOnDestroy() {
        this.currentUserSubscription.unsubscribe();
        this.questSubscription.unsubscribe();
    }
    private caricaSezione(indice: number) {
        if (!this.questionario || !this.questionario.sezioni || indice >= this.questionario.sezioni.length || indice < 0) {
            this.alertService.error(`La sezione ${indice} non esiste`);
            return;
        }
        let progressivo_sezione = this.questionario.sezioni[indice].progressivo_sezione;
        this.questionariService.getSezioneById(this.id_questionario, progressivo_sezione)
            .subscribe(response => {
                this.indice_sezione_corrente = indice;
                this.sezione_corrente = response["value"];
                if(this.sezione_corrente.domande[0] != null){
                    this.domandaCorrente = this.sezione_corrente.domande[0];
                }
                this.calc_esiste_prec_succ();
            },
            error => {
                this.alertService.error(error);
            });
    }
    removeItem(i: number) {
        this.sezione_corrente.domande.splice(i, 1);
    }
    changeEditMode(changeEditMode: boolean){
        this.editMode = changeEditMode;
    }
    getQuestionario(): void {
    
      this.questionariService.getById(this.id_questionario)
        .subscribe(response => {
            
            this.questionario = response["value"];
            let prog_sess = 1;
            if(this.questionario.sezioni[0] != null){
                let prog_sess = Math.max.apply(Math, this.questionario.sezioni.map(function(o) { return o.progressivo_sezione; })) + 1;
                this.max_sezione = prog_sess;
                this.nuova_sezione = {
                    id_questionario: this.id_questionario,
                    progressivo_sezione: prog_sess,
                    titolo: "",
                    descrizione: "",
                    domande: []
                };
                //Ora che ho il questionario, carico la prima sezione con tutte le domande
                this.caricaSezione(0);
            }else{
                this.nuova_sezione = {
                    id_questionario: this.id_questionario,
                    progressivo_sezione: prog_sess,
                    titolo: "",
                    descrizione: "",
                    domande: []
                };
            }
            
        },
        error => {
          this.alertService.error(error);
        });
    }
    sezSuccessiva() {
        if (this.indice_sezione_corrente == null) {
            return;
        }
        this.caricaSezione(this.indice_sezione_corrente+1);
        this.scrollToTop();
    }
    sezPrecedente() {
        if (this.indice_sezione_corrente == null) {
            return;
        }
        this.caricaSezione(this.indice_sezione_corrente-1);
        this.scrollToTop();
    }
    creaSezione() {
        this.disableCrea = true;
        if (this.questionario == null) {
            this.alertService.error("Questionario non ancora caricato, questo non dovrebbe succedere");
            return;
        }
        this.is_nuova_sezione = true;
        this.sezione_corrente = {
            id_questionario: this.id_questionario,
            progressivo_sezione: this.getNuovoProgressivoSezione(),
            titolo: "",
            descrizione: "",
            domande: []
        };
        this.questionario.sezioni.push(this.sezione_corrente);
    }
    getNuovoProgressivoSezione() {
        let nuovo_progr_sezione = 1;
        if(this.questionario.sezioni[0]){
            nuovo_progr_sezione = Math.max.apply(Math, this.questionario.sezioni.map(function(o) { return o.progressivo_sezione; })) + 1;
        }
        return nuovo_progr_sezione;
    }
    salvaSezione (nuova_sezione){            
        this.questionariService.creaSezione(nuova_sezione)
          .subscribe(response => {
                let nuova_sezione = response["value"];
                this.questionario.sezioni.push(nuova_sezione);
                this.indice_sezione_corrente = this.questionario.sezioni.length-1;
                this.sezione_corrente = nuova_sezione;

                let prog_sess = Math.max.apply(Math, this.questionario.sezioni.map(function(o) { return o.progressivo_sezione; })) + 1;

                this.nuova_sezione = {
                    id_questionario: this.id_questionario,
                    progressivo_sezione: prog_sess,
                    titolo: "",
                    descrizione: "",
                    domande: []
                };
                this.is_nuova_sezione = false;                
                this.alertService.success("Sezione salvata con successo");
                this.disableCrea = false;
                //this.scrollToTop();
          },
          error => {
            this.alertService.error(error);
          });
    }
    modificaSezione (nuova_sezione){
        this.questionariService.updateSezione(nuova_sezione).subscribe(response => {
            this.indice_sezione_corrente = response['value'].progressivo_sezione-1;
            this.sezione_corrente = response['value'];
            this.is_nuova_sezione = false;
            this.alertService.success("Sezione salvata con successo");
        },
        error => {
            this.alertService.error(error);
        });
    }
    duplicaSezioneCorrente() {
        if (this.sezione_corrente == null) {
            this.alertService.error("Duplico la sezione null?!? questo non dovrebbe succedere");
            return;
        }
        this.questionariService.duplicaSezione(this.sezione_corrente)
          .subscribe(response => {
                let nuova_sezione = response["value"];
                this.questionario.sezioni.push(nuova_sezione);
                this.indice_sezione_corrente = this.questionario.sezioni.length-1;
                this.sezione_corrente = nuova_sezione;
                this.alertService.success("Sezione duplicata con successo");
                //this.scrollToTop();
          },
          error => {
            this.alertService.error(error);
          });
    }

    eliminaSezioneCorrente() {
        if (this.sezione_corrente == null) {
            this.alertService.error("Duplico la sezione null?!? questo non dovrebbe succedere");
            return;
        }
        this.questionariService.deleteSezione(this.sezione_corrente.id_questionario,this.sezione_corrente.progressivo_sezione)
          .subscribe(response => {
                this.getQuestionario();
                this.alertService.success("Sezione eliminata con successo");
          },
          error => {
            this.alertService.error(error);
          });
    }
    creaDomanda() {
        this.nuova_domanda = {
            coeff_valutazione:1,
            descrizione:"",
            html_max:null,
            html_min:null,
            html_maxlength:null,
            html_pattern:null,
            html_type:"0",
            html_type_dec:"Text",
            id_questionario: this.id_questionario,
            obbligatorieta: false,
            obbligatorieta_dec: "NO",
            progressivo_domanda: null,
            progressivo_sezione: this.sezione_corrente.progressivo_sezione,
            rimescola:false,
            rimescola_dec:"NO",
            risposte:[],//risposteAmmesse
            risposta:null,
            creating:true
        };
        this.editMode = true;
        this.sezione_corrente.domande.push(this.nuova_domanda);
    }
        
    duplicaDomanda(index: number) {
        this.alertService.error("Non implementato");
    }
    eliminaDomanda(index: number) {
        this.alertService.error("Non implementato");
    }

    updQuestionario(questionario: Questionario){
        if(questionario.titolo != null && questionario.titolo != ''){
            this.questionariService.update(questionario).subscribe(response => {
                let id_progetto = response["value"].id_progetto;
                this.alertService.success("Questionario modificato con successo");
                //this.scrollToTop();
                //this.router.navigate(['/progetti', id_progetto]);
            },
            error => {
            this.alertService.error(error);
            });
        }else{
            this.alertService.error("Il titolo deve essere inserito");
        }
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

    simpleClone(obj: any) {
        return Object.assign({}, obj);
    }

    showRisposte(domanda: Domanda){
        this.domandaCorrente = domanda;
    }

    creaRisposta(){
        let risposta_nuova = new RispostaAmmessa();
        let progressivo_risposta = 1;
        this.domandaCorrente.risposte.forEach( r => {
            if (r.progressivo_risposta >= progressivo_risposta) {
                progressivo_risposta = 1 + parseInt(<any>r.progressivo_risposta);
            }
        });
        
        risposta_nuova.id_questionario = this.questionario.id_questionario;
        risposta_nuova.descrizione = '';
        risposta_nuova.progressivo_domanda= this.domandaCorrente.progressivo_domanda;
        risposta_nuova.progressivo_risposta = progressivo_risposta;
        risposta_nuova.progressivo_sezione= this.domandaCorrente.progressivo_sezione;
        risposta_nuova.valore = 1;
        risposta_nuova.creating = true;
        this.domandaCorrente.risposte.push(risposta_nuova);
    }

    calc_esiste_prec_succ() {
        this.esiste_prec = (this.questionario != null && this.indice_sezione_corrente > 0);
        this.esiste_succ = (this.questionario != null && this.indice_sezione_corrente < this.questionario.sezioni.length-1);
    }
}