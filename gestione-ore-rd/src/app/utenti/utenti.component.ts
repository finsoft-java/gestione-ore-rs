import { Component, OnInit, OnDestroy, Output } from '@angular/core';
import { Subscription } from 'rxjs';
import { User, Pagination } from '@/_models';
import { UserService, AuthenticationService, AlertService, WebsocketService, Message } from '@/_services';

@Component({templateUrl: 'utenti.component.html'})
export class UtentiComponent implements OnInit, OnDestroy {
    currentUser: User;
    currentUserSubscription: Subscription;
    websocketsSubscription: Subscription;
    utenti : User[];
    editing : boolean = false;
    message : string;
    current_order: string = "asc";
    nome_colonna_ordinamento: string = "username";
    searchString : string;
    utenti_visibili : User[]; //sottoinsieme di this.utenti determinato dalla Search
    loading = true;
    countUtenti : number;
    pagination_def : Pagination;
    paginazione_current : Pagination;
    
    constructor(
        private authenticationService: AuthenticationService,
        private userService: UserService,
        private alertService: AlertService,
        private websocketsService: WebsocketService
    ) {
        this.currentUserSubscription = this.authenticationService.currentUser.subscribe(user => {
            this.currentUser = user;
        });
        this.websocketsSubscription = websocketsService.messages.subscribe(msg => { this.onWebsocketMessage(msg); });
        
    }
    ngOnInit() {
        this.pagination_def = new Pagination;
        this.filter(this.pagination_def);
    }
    ngOnDestroy() {
        // unsubscribe to ensure no memory leaks
        this.websocketsSubscription.unsubscribe();
        this.currentUserSubscription.unsubscribe();
    }
    ordinamento(nome_colonna){
        if(this.current_order == 'asc'){
            this.current_order = 'desc';
        }else{
            this.current_order = 'asc';
        }
        this.nome_colonna_ordinamento = nome_colonna;
        this.filter(this.pagination_def); 
    }
    newUser() {
        let userNew = new User();
        userNew.nome = "";
        userNew.cognome = "";
        userNew.email = "";
        userNew.username = "";
        userNew.ruolo = "0";
        userNew.editing = true;
        userNew.creating = true;
        this.utenti.push(userNew);
        //this.set_search_string(null); // altrimenti la nuova riga non è visibile
    }
    getUsers(): void {
        this.loading = true;
        this.userService.getAll()
            .subscribe(response => {
                this.utenti = response["data"];
                //this.calcola_utenti_visibili();
                this.loading = false;
                if(this.current_order == 'asc'){
                    this.current_order ='desc';
                }else{                    
                    this.current_order ='asc';
                }
                this.ordinamento(this.nome_colonna_ordinamento);
            },
            error => {
                this.alertService.error(error);
                this.loading = false;
            });
    }

    filter(p:Pagination){

        this.userService.getAllFiltered(p.row_per_page,p.start_item,p.search_string,this.nome_colonna_ordinamento+' '+this.current_order)
        .subscribe(response => {
            this.utenti = response["data"];
            this.countUtenti = response["count"];
            this.utenti_visibili = this.utenti;
            this.loading = false;                
            this.paginazione_current = p;
        },
        error => {
            this.alertService.error(error);
            this.loading = false;
        });
    }
    removeItem(username: string) {
        let index = this.utenti.findIndex(user => user.username == username);
        let oldUtente = this.utenti[index];
        this.utenti.splice(index, 1);
        //this.calcola_utenti_visibili();
        this.sendMsgUtenti(oldUtente, 'L\'utente è appena stato eliminato');
    }
    refresh() {
        this.getUsers();
        
    }
    sync() {
        this.loading = true;
        this.userService.sync()
            .subscribe(response => {
                this.alertService.success(response["msg"]);
                this.getUsers();
                this.sendMsgUtenti(null, 'E\' appena stato eseguito un Sync con il server LDAP');
            },
            error => {
                this.alertService.error(error);
                this.loading = false;
            });
    }
    sendMsgUtenti(user : User, message : string) {
        let msg : Message = {
            what_has_changed: 'utenti',
            obj: user,
            note: message
          }
      this.websocketsService.sendMsg(msg);
    }
    onWebsocketMessage(msg : Message) {
        if (msg.what_has_changed == "utenti") {
            let utenteMod = <User>msg.obj;
            let username = utenteMod.username;
            if (username) {
                let utente = this.utenti.find(u => u.username == username);
                if (utente) {
                    if (utente.editing === true) {
                        this.alertService.error(`Attenzione! La riga '${username}' è appena stata modificata da un altro utente.`);
                        Object.assign(utente, utenteMod);
                        utente.editing = false;
                    }
                }
            }
        }
    }

}