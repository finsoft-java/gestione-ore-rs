import { Component, OnInit, OnDestroy, Output } from '@angular/core';
import { Subscription, Observable } from 'rxjs';
import { User, Questionario, Pagination } from '@/_models';
import { AuthenticationService, QuestionariService, AlertService, WebsocketService, Message } from '@/_services';
import { Router } from '@angular/router';

@Component({templateUrl: 'questionari.component.html'})
export class QuestionariComponent implements OnInit, OnDestroy {
    
    currentUserSubscription: Subscription;
    websocketsSubscription: Subscription;
    currentUser: User;
    questionari : Questionario[];
    searchString : string;
    questionari_visibili : Questionario[];
    loading = true;
    current_order: string = "asc";
    nome_colonna_ordinamento: string = "username";
    countQuestionari : number;
    pagination_def : Pagination;
    paginazione_current : Pagination;

    constructor(private authenticationService: AuthenticationService,
                private questionariService: QuestionariService,
                private alertService: AlertService,
                private websocketsService: WebsocketService,
                private router: Router){
        this.currentUserSubscription = this.authenticationService.currentUser.subscribe(user => {
            this.currentUser = user;
        });
        this.websocketsSubscription = websocketsService.messages.subscribe(msg => { this.onWebsocketMessage(msg); });
    } 

    ngOnInit() {
        this.pagination_def = new Pagination;
        this.pagination_def.mostra_bottone_stato = true;
        this.filter(this.pagination_def);
    }

    ngOnDestroy() {
        // unsubscribe to ensure no memory leaks
        this.currentUserSubscription.unsubscribe();
        this.websocketsSubscription.unsubscribe();
    }
    addZero(i) {
        if (i < 10) {
          i = "0" + i;
        }
        return i;
      }

    filter(p:Pagination){

        this.questionariService.getAllFiltered(p.row_per_page,p.start_item,p.search_string,this.nome_colonna_ordinamento+' '+this.current_order,p.mostra_solo_validi)
        .subscribe(response => {
            this.questionari = response["data"];
            this.countQuestionari = response["count"];
            this.questionari_visibili = this.questionari;
            this.loading = false;                
            this.paginazione_current = p;
        },
        error => {
            this.alertService.error(error);
            this.loading = false;
        });


    }
    crea() {
        let newQuest = new Questionario();
        newQuest.stato = "0";


        var d = new Date();
        var x = document.getElementById("demo");
        var h = this.addZero(d.getHours());
        var m = this.addZero(d.getMinutes());
        var s = this.addZero(d.getSeconds());


        newQuest.titolo = "Nuovo questionario delle "+h + ":" + m + ":" + s;
        newQuest.flag_comune = "0";
        newQuest.gia_compilato = "0";
        newQuest.utente_creazione = this.currentUser.username;
        newQuest.data_creazione = new Date();
        this.questionariService.insert(newQuest)
            .subscribe(response => {
                let q : Questionario = response["value"]; 
                this.sendMsgQuestionario(q, 'Creato nuovo questionario');
                this.router.navigate(['/questionari', q.id_questionario]);
            },
            error => {
                this.alertService.error(error);
            });
    }
    
    ordinamento(nome_colonna){
        if(this.current_order == 'asc'){
            this.current_order = 'desc';
        }else{
            this.current_order = 'asc';
        }
        this.nome_colonna_ordinamento = nome_colonna;
        this.filter(this.paginazione_current);
    }

    elimina(id_questionario: number, id_progetto: any): void {
        if(id_progetto == null){
            if(confirm("Stai per eliminare l'intero questionario! Procedere?")) {
                this.questionariService.delete(id_questionario)
                    .subscribe(response => {
                        let index = this.questionari.findIndex(q => q.id_questionario == id_questionario);
                        let oldQuest = this.questionari[index];
                        this.questionari.splice(index, 1);
                        //this.calcola_questionari_visibili();
                        this.sendMsgQuestionario(oldQuest, 'Il questionario è appena stato eliminato');
                        this.alertService.success("Il questionario è appena stato eliminato");
                    },
                    error => {
                        this.alertService.error(error);
                    });
            }
        }else{
            if(confirm("Stai per eliminare l'intero questionario! Procedere?")) {
                if(confirm("Il questionario è associato ad almeno un progetto! Confermare l 'eliminazione?")) {
                    this.questionariService.delete(id_questionario).subscribe(response => {
                        let index = this.questionari.findIndex(q => q.id_questionario == id_questionario);
                        let oldQuest = this.questionari[index];
                        this.questionari.splice(index, 1);
                        //this.calcola_questionari_visibili();
                        this.sendMsgQuestionario(oldQuest, 'Il questionario è appena stato eliminato');
                        
                        this.alertService.success("Il questionario è appena stato eliminato");
                    },
                    error => {
                        this.alertService.error(error);
                    });
                }
            }
        }
    }

    duplica(id_questionario: number) {
        this.questionariService.duplica(id_questionario)
            .subscribe(response => {
                let q : Questionario = response["value"];
                this.questionari.push(q);
                this.sendMsgQuestionario(q, 'Creato nuovo questionario');
                this.alertService.success("Questionario duplicato con successo");
            },
            error => {
                this.alertService.error(error);
            });
    }

    refresh() {
        this.filter(this.paginazione_current);
    }

    sendMsgQuestionario(q : Questionario, note : string) {
        let msg : Message = {
            what_has_changed: 'questionari',
            obj: q,
            note: note
          }
      this.websocketsService.sendMsg(msg);
    }

    onWebsocketMessage(msg : Message) {
        if (msg.what_has_changed == "questionari") {
            this.refresh();
        }
    }

}