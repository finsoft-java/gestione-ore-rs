import {Component, Input, OnInit, EventEmitter, Output} from '@angular/core';
import { Pagination } from '@/_models/pagination';
import { AuthenticationService, WebsocketService } from '@/_services';
import { Subscription } from 'rxjs';
import { User } from '@/_models';

@Component({
  selector: 'filter-table',
  templateUrl: './filter.table.component.html',
  styleUrls: ['./filter.table.component.css']
})
export class FilterTableComponent implements OnInit {

  @Output() filter = new EventEmitter<Pagination>();
  @Input() is_compilazioni : boolean;
  @Input() is_quest_prog : boolean;
  private _count : number;
  currentUserSubscription: Subscription;
  currentUser: User;
  pagination : Pagination;
  numPag : number;//pagina Corrente
  numPagine: number;//pagine totali
  constructor(private authenticationService: AuthenticationService,private websocketsService: WebsocketService) {
    this.currentUserSubscription = this.authenticationService.currentUser.subscribe(user => {
      this.currentUser = user;
  });
  }
 
  ngOnInit() {
    this.pagination = new Pagination();
    if(this.is_compilazioni){
      this.pagination.is_quest_comp = true;
    }
    if(this.is_quest_prog){
      this.pagination.mostra_bottone_stato = true;
    }
    this.numPagina();
  }

  get count(): number { 
    return this._count;
  }
  filter_admin(s : boolean) {
    this.pagination.mostra_solo_admin = !s;
    this.pagination.start_item = 0;
    this.filter.emit(this.pagination);    
  }
  filter_stato(s : boolean) {
    this.pagination.mostra_solo_validi = !s;
    this.pagination.start_item = 0;
    this.filter.emit(this.pagination);    
  }
  @Input()
  set count(count: number) {
    if(count == null){
      count=0;
    }
    this._count = count;
    this.numPagina();
  }

  numPagina(){
    if(this.pagination != null){
      this.numPagine = Math.floor(this._count/this.pagination.row_per_page)+1;
      this.numPag = Math.floor(this.pagination.start_item/this.pagination.row_per_page)+1;
    }
  }

  searchStringFunction(s : string){
    this.pagination.search_string = s;
    this.pagination.start_item = 0;
    this.filter.emit(this.pagination);
  }

  paginationPlusFunction(){
    if(this.pagination.start_item+this.pagination.row_per_page < this.count){
      this.pagination.start_item = this.pagination.start_item+this.pagination.row_per_page;
      this.filter.emit(this.pagination);
    }
    this.numPagina();
  }
  paginationMinusFunction(){
    if(this.pagination.start_item != 0){
      this.pagination.start_item = this.pagination.start_item-this.pagination.row_per_page;
      this.filter.emit(this.pagination);
    }    
    this.numPagina();
    
  }
}
