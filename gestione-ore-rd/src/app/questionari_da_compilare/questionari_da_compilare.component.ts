import { Component, OnInit, OnDestroy, Output } from '@angular/core';
import { Subscription, Observable } from 'rxjs';
import { User, QuestionarioCompilato, VistaQuestionariCompilabili, Pagination } from '@/_models';
import { AuthenticationService, QuestionariCompilatiService, AlertService, WebsocketService, Message } from '@/_services';
import { ActivatedRoute, Router } from '@angular/router';

@Component({templateUrl: 'questionari_da_compilare.component.html'})
export class QuestionariDaCompilareComponent implements OnInit, OnDestroy {
    
    currentUserSubscription: Subscription;
    questCompSubscription: Subscription;
    websocketsSubscription: Subscription;
    currentUser: User;
    questionari: VistaQuestionariCompilabili[];
    storico: boolean;
    loading = true;
    searchString : string;
    mostra_solo_admin: boolean= false;
    quest_comp_visibili : VistaQuestionariCompilabili[];
    current_order: string = "asc";
    nome_colonna_ordinamento: string = "titolo_questionario";
    countQuestionari: number;
    pagination_def : Pagination;
    paginazione_current : Pagination;

    constructor(
        private authenticationService: AuthenticationService,
        private questCompService: QuestionariCompilatiService,
        private alertService: AlertService,
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
        this.pagination_def = new Pagination;
        
        
        this.questCompSubscription = this.route.data.subscribe(data => {
            this.storico = (data['storico'] != null && data['storico'] != '' && data['storico']);
            this.filter(this.pagination_def);
         });
         
    }

    ngOnDestroy() {
        // unsubscribe to ensure no memory leaks
        this.currentUserSubscription.unsubscribe();
        this.questCompSubscription.unsubscribe();
        this.websocketsSubscription.unsubscribe();
    }
    /*
    getLista(): void {
        this.loading = true;
        this.questCompService.getAll(this.storico)
            .subscribe(response => {
                this.questionari = response["data"];
                this.calcola_questionari_visibili();
                this.loading = false;
            },
            error => {
                this.alertService.error(error);
                this.loading = false;
            });
    }*/
    refresh() {
        this.filter(this.paginazione_current);
    }
    /*
    filter_admin() {
        this.mostra_solo_admin = !this.mostra_solo_admin;
        if(this.mostra_solo_admin){
            let utente_collegato = this.currentUser.nome_utente;
            this.questionari = this.questionari.filter(obj => obj.nome_utente == utente_collegato);
            //this.calcola_questionari_visibili();
        }else{
            this.refresh();
        }
    }
    */
    ordinamento(nome_colonna){
        if(this.current_order == 'asc'){
            //this.progetti_visibili = this.progetti_visibili.sort((a,b) =>  (a[nome_colonna] > b[nome_colonna] ? -1 : 1));//desc
            this.current_order = 'desc';
        }else{
            //this.progetti_visibili = this.progetti_visibili.sort((a,b) =>  (a[nome_colonna] > b[nome_colonna] ? 1 : -1));//asc
            this.current_order = 'asc';
        }
        this.nome_colonna_ordinamento = nome_colonna; 
        this.filter(this.paginazione_current);
    }

    filter(p:Pagination){

        this.questCompService.getAllFiltered(this.storico,p.row_per_page,p.start_item,p.search_string,this.nome_colonna_ordinamento+' '+this.current_order,p.mostra_solo_admin)
        .subscribe(response => {
            this.questionari = response["data"];
            this.countQuestionari = response["count"];
            this.quest_comp_visibili = this.questionari;
            this.loading = false;                
            this.paginazione_current = p;
        },
        error => {
            this.alertService.error(error);
            this.loading = false;
        });


    }

    removeItem(progressivoQuestComp: number) {
        let index = this.questionari.findIndex(obj => obj.progressivo_quest_comp == progressivoQuestComp);
        let q = this.questionari[index];
        this.questionari.splice(index, 1);
        //this.calcola_questionari_visibili();
        this.sendMsgQuestComp(q, 'Il questionario è appena stato rimosso');
    }
    /*
    set_search_string(searchString) {
        this.searchString = searchString;
        this.calcola_questionari_visibili();
    }
    calcola_questionari_visibili() {
        if (!this.searchString) {
            this.quest_comp_visibili = this.questionari;
        } else {
            let s = this.searchString.toLowerCase();
            this.quest_comp_visibili = this.questionari.filter(q => 
                (q.titolo_progetto != null && q.titolo_progetto.toLowerCase().includes(s)) ||
                (q.titolo_questionario != null && q.titolo_questionario.toLowerCase().includes(s)) ||
                (q.stato_quest_comp_dec != null && q.stato_quest_comp_dec.toLowerCase().includes(s))
            );
        }
    }
    */
    sendMsgQuestComp(q : QuestionarioCompilato | VistaQuestionariCompilabili, note : string) {
        let msg : Message = {
            what_has_changed: 'questionariCompilati',
            obj: q,
            note: note
          }
      this.websocketsService.sendMsg(msg);
    }
    onWebsocketMessage(msg : Message) {
        if (msg.what_has_changed == "progetti" || msg.what_has_changed == "questionari") {
            this.refresh();
        }
    }
}