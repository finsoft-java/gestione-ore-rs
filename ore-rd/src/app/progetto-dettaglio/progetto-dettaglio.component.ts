import { filter } from 'rxjs/operators';
import { TipologiaSpesaService } from './../_services/tipospesa.service';
import { TipologiaSpesaComponent } from './../tipologia-spesa/tipologia-spesa.component';
import { ProgettiWpService } from './../_services/progetti.wp.service';
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
import { PageEvent } from '@angular/material/paginator';
export const MY_FORMATS = {
  parse: {
      dateInput: 'LL'
  },
  display: {
      dateInput: 'DD-MM-YYYY',
      monthYearLabel: 'YYYY',
      dateA11yLabel: 'LL',
      monthYearA11yLabel: 'YYYY'
  }
};

@Component({
  selector: 'app-progetto-dettaglio',
  templateUrl: './progetto-dettaglio.component.html',
  styleUrls: ['./progetto-dettaglio.component.css'],
  providers: [{
    provide: DateAdapter,
    useClass: MomentDateAdapter,
    deps: [MAT_DATE_LOCALE, MAT_MOMENT_DATE_ADAPTER_OPTIONS]
  },
  {provide: MAT_DATE_FORMATS, useValue: MY_FORMATS}]
})
export class ProgettoDettaglioComponent implements OnInit {

  projectSubscription: Subscription;
  progetto!: Progetto;
  progettoWp!: ProgettoWp[];
  progetto_old!: Progetto;
  displayedColumns: string[] = ['descrizione','importo', 'tipologia', 'actions'];
  displayedColumnsWp: string[] = ['id','titolo', 'descrizione', 'dataInizio', 'dataFine','risorse','monteOre', 'actions'];
  dataSource = new MatTableDataSource<[]>();
  dataSourceWp = new MatTableDataSource<[]>();
  allTipologie: any[] = [];
  allMatricole: any[] = [];
  allTipiCosto: any[] = [];
  isNotAnnulable:boolean = false;
  id_progetto!: any;
  errore_stringa= '';

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
        this.progettoWp = response["value"];
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

  getRecord(prgSpesa: ProgettoSpesa){
    prgSpesa.isEditable = true;
  }

  getRecordwP(prgWp: ProgettoWp){
    prgWp.isEditable = true;
  }

  getMatricole(): void {
    this.progettiService.getAllMatricole()
      .subscribe(response => {
        this.allMatricole = response["data"];
      },
      error => {
        this.alertService.error(error);
      });
  }
 stampaRisorse(risorse: string[]){
    let arrayRisorse:any[] = [];
    risorse.forEach(element => {
      let  u  = this.allMatricole.find(x => x.MATRICOLA == element);
      if(this.allMatricole.find(x => x.MATRICOLA == element) != null)
        arrayRisorse.push(this.allMatricole.find(x => x.MATRICOLA == element).NOME);
    });
    return arrayRisorse;
 }
  getSupervisor(): void {
    this.progettiService.getAllTipiCostoPanthera()
      .subscribe(response => {
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
    
    if(this.progetto.DATA_FINE)
      this.progetto.DATA_FINE = formatDate(this.progetto.DATA_FINE,"YYYY-MM-dd","en-GB");

    if(this.progetto.DATA_INIZIO)
      this.progetto.DATA_INIZIO = formatDate(this.progetto.DATA_INIZIO,"YYYY-MM-dd","en-GB");
    
    if(this.id_progetto == null){
      this.progettiService.insert(this.progetto)
      .subscribe(response => {
        this.alertService.success("Progetto inserito con successo");
        this.router.navigate(['/progetto/'+response["value"][0]["ID_PROGETTO"]]);
      },
      error => {
        this.alertService.error(error);
      });
    } else {
      let no_error = true;
      let monte_totale_wp:number = 0;
      for(let i = 0; i < this.progettoWp.length; i++){
        no_error = this.controlliDate(this.progettoWp[i]);
        monte_totale_wp = (Number(monte_totale_wp) + Number(this.progettoWp[i].MONTE_ORE));
      }
      if(this.progetto.MONTE_ORE_TOT < monte_totale_wp){
        this.alertService.error("Il MonteOre dei WP supera quello del Progetto");
        no_error = false;
      }
      if(no_error){
        this.progettiService.update(this.progetto)
        .subscribe(response => {
          this.alertService.success("Progetto modificato con successo");
          this.router.navigate(['/progetto/'+response["value"]["ID_PROGETTO"]]);
        },
        error => {
          this.alertService.error(error);
        });
      }
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
    progettoWp_nuovo = {
      ID_PROGETTO:this.progetto.ID_PROGETTO, 
      ID_WP:null, 
      TITOLO:null, 
      DESCRIZIONE:null, 
      DATA_INIZIO: null, 
      DATA_FINE: null , 
      RISORSE:null, 
      MONTE_ORE: null, 
      isEditable:true,
      isInsert:true
    };
    let dataWp:any[] = [];
    if(this.dataSourceWp.data == null){
      this
      dataWp.push(progettoWp_nuovo);
    }else{
      dataWp = this.dataSourceWp.data;
      dataWp.push(progettoWp_nuovo);
    }
    this.dataSourceWp.data = dataWp;
  } 

  deleteChange(prgSpesa:any){
    this.progettiSpesaService.delete(prgSpesa.ID_SPESA)
        .subscribe(response => {
          this.getProgettoSpesa();
        },
        error => {
          this.alertService.error("Impossibile eliminare il record");
        });
  }

  deleteChangeWp(prgWp:any){
    this.progettiWpService.delete(prgWp.ID_WP,prgWp.ID_PROGETTO)
        .subscribe(response => {
          this.getProgettowP();
        },
        error => {
          this.alertService.error("La tipologia è stata già utilizzata per un ProgettoSpesa");
        });
  }
  

  salvaModifica(prgSpesa: ProgettoSpesa){
    prgSpesa.isEditable=false;
    if(prgSpesa.ID_SPESA == null){
      this.progettiSpesaService.insert(prgSpesa)
      .subscribe(response => {
        this.dataSource.data.splice(-1, 1);
        this.dataSource.data.push(response["value"][0]);
        this.dataSource.data = this.dataSource.data;
        this.alertService.success("Spesa salvata con successo");
      },
      error => {
        this.alertService.error(error);
      });
    } else {
      this.progettiSpesaService.update(prgSpesa)
      .subscribe(response => {
        this.alertService.success("Spesa modificata con successo");
        this.getProgettoSpesa();
      },
      error => {
        this.alertService.error(error);
      });
    }
  }

  controlliDate(wp:any){
    let datePrIniziale = '';
    let datePrFinale = '';
    let dateWpIniziale = '';
    let dateWpFinale = '';
    this.errore_stringa = '';

    if(this.progetto.DATA_INIZIO){
      datePrIniziale = formatDate(this.progetto.DATA_INIZIO,'yyyy-MM-dd','en_US');
    }else{
      this.errore_stringa += "Inserire la Data Inizio Progetto <br/>";
    }
    if(this.progetto.DATA_FINE){
      datePrFinale = formatDate(this.progetto.DATA_FINE,'yyyy-MM-dd','en_US');
    }else{
        this.errore_stringa += "Inserire la Data Fine Progetto <br/>";
    }

    if(wp.DATA_INIZIO != ''){
      dateWpIniziale = formatDate(wp.DATA_INIZIO,'yyyy-MM-dd','en_US');
    }else{
      this.errore_stringa += "Inserire la Data Inizio Wp <br/>";
    }

    if(wp.DATA_FINE != ''){
      dateWpFinale = formatDate(wp.DATA_FINE,'yyyy-MM-dd','en_US');
    }else{
        this.errore_stringa += "Inserire la Data Fine Wp <br/>";
    }
    console.log(datePrIniziale+" > "+dateWpIniziale);
    if(datePrIniziale != '' && dateWpIniziale != ''){
      if(datePrIniziale > dateWpIniziale){
        this.errore_stringa = "Un WP non può iniziare prima del Progetto <br/>";
        wp.DATA_INIZIO = '';
      }
    }
    
    if(datePrFinale != '' && dateWpFinale != ''){
      if(datePrFinale < dateWpFinale){
        this.errore_stringa += "Un WP non può finire dopo il Progetto <br/>";
        wp.DATA_FINE = '';
      }
    }

    if( wp.DATA_INIZIO == '' || wp.DATA_FINE == ''){
      this.alertService.error(this.errore_stringa);
      return false;
    }
    return true;
  }

  salvaModificaWp(prgWp: ProgettoWp){    
    console.log(prgWp);
    if(prgWp.DATA_FINE)
      prgWp.DATA_FINE = formatDate(prgWp.DATA_FINE,"YYYY-MM-dd","en-GB");

    if(prgWp.DATA_INIZIO)
      prgWp.DATA_INIZIO = formatDate(prgWp.DATA_INIZIO,"YYYY-MM-dd","en-GB");

    let error = false;
    if(prgWp.ID_WP == null){
      let monte_totale_wp = 0;
      if(this.progettoWp.length > 1){
        for(let i = 0; i < this.progettoWp.length; i++){
          monte_totale_wp = (Number(monte_totale_wp) + Number(this.progettoWp[i].MONTE_ORE));
        }
        if(this.progetto.MONTE_ORE_TOT < monte_totale_wp){
          this.alertService.error("Il MonteOre dei WP supera quello del Progetto");
          error = true;
        }
      }

      if(this.controlliDate(prgWp) && !error){
        this.progettiWpService.insert(prgWp)
        .subscribe(response => {
          this.alertService.success("Work Package inserito con successo");
          this.dataSourceWp.data.splice(-1, 1);
          this.dataSourceWp.data.push(response["value"][0]);
          this.dataSourceWp.data = this.dataSourceWp.data;
          prgWp.isEditable=false;
        },
        error => {
          this.alertService.error(error);
        });
      }
    } else {
      let monte_totale_wp = 0;
      if(this.progettoWp.length > 1){
        for(let i = 0; i < this.progettoWp.length; i++){
          monte_totale_wp = (Number(monte_totale_wp) + Number(this.progettoWp[i].MONTE_ORE));
        }
        if(this.progetto.MONTE_ORE_TOT < monte_totale_wp){
          this.alertService.error("Il MonteOre dei WP supera quello del Progetto");
          error = true;
        }
      }
      if(this.controlliDate(prgWp) && !error){
        this.progettiWpService.update(prgWp)
        .subscribe(response => {
          this.alertService.success("Work Package modificato con successo");
          prgWp.isEditable=false;
        },
        error => {
          this.alertService.error(error);
        });
      }
    }
  }
  

  undoChange(prgSpesa:ProgettoSpesa){
    prgSpesa.isEditable=false;
    if(prgSpesa.ID_PROGETTO == null){
      this.dataSource.data.splice(-1, 1);
      this.dataSource.data = this.dataSource.data;
    }
  }

  report() {
    this.router.navigate(["progetto", this.progetto.ID_PROGETTO, "report"]);
  }
}
