import { TipologiaSpesaService } from './../_services/tipospesa.service';
import { TipologiaSpesaComponent } from './../tipologia-spesa/tipologia-spesa.component';
import { ProgettiWpService } from './../_services/progetti.WP.service';
import { ProgettiSpesaService } from './../_services/progetti.spesa.service';
import { MatTableDataSource } from '@angular/material/table';
import { TipoCosto } from './../_models/tipocosto';
import { Matricola } from './../_models/matricola';
import { Subscription } from 'rxjs';
import { Progetto, ProgettoSpesa, ProgettoWp } from './../_models/progetto';
import { User } from './../_models/user';
import { ActivatedRoute, Router } from '@angular/router';
import { ProgettiService } from './../_services/progetti.service';
import { AlertService } from './../_services/alert.service';
import { AuthenticationService } from './../_services/authentication.service';
import { FormGroup, FormControl } from '@angular/forms';
import { Component, OnInit } from '@angular/core';



import {MomentDateAdapter, MAT_MOMENT_DATE_ADAPTER_OPTIONS} from '@angular/material-moment-adapter';
import {DateAdapter, MAT_DATE_FORMATS, MAT_DATE_LOCALE} from '@angular/material/core';
import {MatDatepicker} from '@angular/material/datepicker';
import {Moment} from 'moment';
import * as _moment from 'moment';
import { formatDate } from '@angular/common';

@Component({
  selector: 'app-progetto-dettaglio',
  templateUrl: './progetto-dettaglio.component.html',
  styleUrls: ['./progetto-dettaglio.component.css']
})
export class ProgettoDettaglioComponent implements OnInit {

  projectSubscription: Subscription;
  progetto!: Progetto;
  progetto_old!: Progetto;
  displayedColumns: string[] = ['descrizione','importo', 'tipologia', 'actions'];
  displayedColumnsWp: string[] = ['id','titolo', 'descrizione', 'dataInizio', 'dataFine','risorse', 'actions'];
  dataSource = new MatTableDataSource<[]>();
  dataSourceWp = new MatTableDataSource<[]>();
  allTipologie: any;
  allMatricole: any;
  allTipiCosto: any;
  isNotAnnulable:boolean = false;
  progetto_spesa!: ProgettoSpesa;
  progetto_wp!: ProgettoWp;
  id_progetto!: any;

  constructor(private authenticationService: AuthenticationService,
    private progettiService: ProgettiService,
    private tipologiaSpesaService: TipologiaSpesaService,
    private progettiSpesaService: ProgettiSpesaService,   
    private progettiWpService: ProgettiWpService,   
    private alertService: AlertService,
    private route: ActivatedRoute,
    private router: Router) {

      this.projectSubscription = this.route.params.subscribe(params => {
        if(params['id_progetto'] == 'nuovo'){
          this.id_progetto = null; 
        }else{
          this.id_progetto = +params['id_progetto']; 
        }
      },
        error => {
        this.alertService.error(error);
      });
    }


  ngOnInit(): void {
    if(this.id_progetto != null){
      this.getProgetto();
      this.getProgettoSpesa();
      this.getTipoSpesa();
    } else{
      this.progetto = new Progetto;
    }
    this.getMatricole();
    this.getSupervisor();
    this.getProgettowP();
  }

  
  getProgettowP(): void {
    this.progettiWpService.getById(this.id_progetto)
      .subscribe(response => {
        this.dataSourceWp = new MatTableDataSource<[]>(response["value"]);
      },
      error => {
        this.dataSourceWp = new MatTableDataSource<[]>();
      });
  }

  getProgettoSpesa(): void {
    this.progettiSpesaService.getById(this.id_progetto)
      .subscribe(response => {
        this.dataSource = new MatTableDataSource<[]>(response["value"]);
      },
      error => {
        this.dataSource = new MatTableDataSource<[]>();
      });
  }
  getProgetto(): void {
    this.progettiService.getById(this.id_progetto)
      .subscribe(response => {
        this.progetto = new Progetto;
        this.progetto = response["value"];
        this.progetto_old = response["value"];
      },
      error => {
        this.alertService.error(error);
      });
  }

  getRecord(a: ProgettoSpesa){
    a.isEditable = true;
  }

  getRecordwP(a: ProgettoWp){
    a.isEditable = true;
  }

  getMatricole(): void {
    this.progettiService.getAllMatricole()
      .subscribe(response => {
        this.allMatricole  = new Matricola;
        this.allMatricole = response["data"];
      },
      error => {
        this.alertService.error(error);
      });
  }

  getSupervisor(): void {
    this.progettiService.getAllTipiCostoPanthera()
      .subscribe(response => {
        this.allTipiCosto  = new TipoCosto;
        this.allTipiCosto = response["data"];
      },
      error => {
        this.alertService.error(error);
      });
  }

  getTipoSpesa(){
    this.tipologiaSpesaService.getAll()
        .subscribe(response => {
          this.allTipologie = response["data"];
        },
        error => {
        });
  }

  salva() {
    if(this.id_progetto == null){
      this.progettiService.insert(this.progetto)
      .subscribe(response => {
        this.router.navigate(['/progetto/'+response["value"][0]["ID_PROGETTO"]]);
        this.alertService.success("Progetto inserito con successo");
      },
      error => {
        this.alertService.error(error);
      });
    } else {
      this.progettiService.update(this.progetto)
      .subscribe(response => {
        this.router.navigate(['/progetto/'+response["value"][0]["ID_PROGETTO"]]);
        this.alertService.success("Progetto modificato con successo");
      },
      error => {
        this.alertService.error(error);
      });
    }
    
  }

  nuovoProgettoSpesa() {  
    let progettoSpesa_nuovo:any;
    progettoSpesa_nuovo = {ID_PROGETTO:this.progetto.ID_PROGETTO,ID_SPESA:null, DESCRIZIONE:null,IMPORTO:null,TIPOLOGIA: {ID_TIPOLOGIA:null, DESCRIZIONE:null},isEditable:true,isInsert:true};
    let data:any[] = [];
    if(this.dataSource.data == null){
      data.push(progettoSpesa_nuovo);
    }else{
      data = this.dataSource.data;
      data.push(progettoSpesa_nuovo);
    }
    this.dataSource.data = data;
  } 

  nuovoProgettoWp() {  
    let progettoWp_nuovo:any;
    progettoWp_nuovo = {ID_PROGETTO:this.progetto.ID_PROGETTO,ID_SPESA:null, DESCRIZIONE:null,IMPORTO:null,TIPOLOGIA: {ID_TIPOLOGIA:null, DESCRIZIONE:null},isEditable:true,isInsert:true};
    let dataWp:any[] = [];
    if(this.dataSourceWp.data == null){
      dataWp.push(progettoWp_nuovo);
    }else{
      dataWp = this.dataSourceWp.data;
      dataWp.push(progettoWp_nuovo);
    }
    this.dataSourceWp.data = dataWp;
  } 

  nuovoWp() {  
    let progettoSpesa_nuovo:any;
    progettoSpesa_nuovo = {ID_PROGETTO:this.progetto.ID_PROGETTO,ID_SPESA:null, DESCRIZIONE:null,IMPORTO:null,TIPOLOGIA: {ID_TIPOLOGIA:null, DESCRIZIONE:null},isEditable:true,isInsert:true};
    let data:any[] = [];
    if(this.dataSource.data == null){
      data.push(progettoSpesa_nuovo);
    }else{
      data = this.dataSource.data;
      data.push(progettoSpesa_nuovo);
    }
    this.dataSource.data = data;
  } 

  deleteChange(a:any){
    this.progettiSpesaService.delete(a.ID_SPESA)
        .subscribe(response => {
          this.getProgettoSpesa();
        },
        error => {
          this.alertService.error("La tipologia è stata già utilizzata per un ProgettoSpesa");
        });
  }

  deleteChangeWp(a:any){
    this.progettiWpService.delete(a.ID_WP,a.ID_PROGETTO)
        .subscribe(response => {
          this.getProgettowP();
        },
        error => {
          this.alertService.error("La tipologia è stata già utilizzata per un ProgettoSpesa");
        });
  }
  

  salvaModifica(a: ProgettoSpesa){
    a.isEditable=false;
    if(a.ID_SPESA == null){
      this.progettiSpesaService.insert(a)
      .subscribe(response => {
        this.alertService.success("Tipologia inserita con successo");
        this.dataSource.data.splice(-1, 1);
        this.dataSource.data.push(response["value"][0]);
        this.dataSource.data = this.dataSource.data;
      },
      error => {
        this.alertService.error(error);
      });
    } else {
      this.progettiSpesaService.update(a)
      .subscribe(response => {
        this.getProgettoSpesa();
      },
      error => {
        this.alertService.error(error);
      });
    }
  }
  salvaModificaWp(a: ProgettoWp){
    a.isEditable=false;
    if(a.ID_WP == null){
      this.progettiWpService.insert(a)
      .subscribe(response => {
        this.alertService.success("Work Package inserito con successo");
        this.dataSourceWp.data.splice(-1, 1);
        this.dataSourceWp.data.push(response["value"][0]);
        this.dataSourceWp.data = this.dataSourceWp.data;
      },
      error => {
        this.alertService.error(error);
      });
    } else {
      this.progettiSpesaService.update(a)
      .subscribe(response => {
        this.getProgettowP();
      },
      error => {
        this.alertService.error(error);
      });
    }
  }
  

  undoChange(a:ProgettoSpesa){
    a.isEditable=false;
    if(a.ID_PROGETTO == null){
      this.dataSource.data.splice(-1, 1);
      this.dataSource.data = this.dataSource.data;
    }
  }

}
