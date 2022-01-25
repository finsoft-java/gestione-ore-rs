import { TipologiaSpesaService } from './../_services/tipospesa.service';
import { ProgettiPersoneService } from '../_services/progetti.persone.service';
import { ProgettiSpesaService } from './../_services/progetti.spesa.service';
import { MatTableDataSource } from '@angular/material/table';
import { Subscription } from 'rxjs';
import { Matricola, Progetto, ProgettoPersona, ProgettoSpesa, Tipologia } from './../_models';
import { ActivatedRoute, Router } from '@angular/router';
import { ProgettiService } from './../_services/progetti.service';
import { AlertService } from './../_services/alert.service';
import { AuthenticationService } from './../_services/authentication.service';
import { Component, OnInit } from '@angular/core';
import {MomentDateAdapter, MAT_MOMENT_DATE_ADAPTER_OPTIONS} from '@angular/material-moment-adapter';
import {DateAdapter, MAT_DATE_FORMATS, MAT_DATE_LOCALE} from '@angular/material/core';
import * as _moment from 'moment';
import { formatDate } from '@angular/common';

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

export const MONTE_ORE_MENSILE_PREVISTO = 1720 / 12; // 143.3333

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
  progetto_old!: Progetto;
  displayedColumns: string[] = ['descrizione','importo', 'tipologia', 'actions'];
  displayedColumnsPersone: string[] = ['nome', 'idDipendente', 'pctImpiego', 'actions'];
  
  dataSource = new MatTableDataSource<ProgettoSpesa>();
  dataSourcePersone = new MatTableDataSource<ProgettoPersona>();
  allTipologie: Tipologia[] = [];
  allMatricole: Matricola[] = [];
  allTipiCosto: {ID_TIPO_COSTO: string, DESCRIZIONE: string}[] = [];
  idProgetto!: number|null;
  errore_stringa = '';
  isPercentuali100 = true;

  constructor(private authenticationService: AuthenticationService,
    private progettiService: ProgettiService,
    private tipologiaSpesaService: TipologiaSpesaService,
    private progettiSpesaService: ProgettiSpesaService,   
    private progettiPersoneService: ProgettiPersoneService,
    private alertService: AlertService,
    private route: ActivatedRoute,
    private router: Router) {

      this.projectSubscription = this.route.params.subscribe(params => {
        if (params['id_progetto'] == 'nuovo') {
          this.idProgetto = null; 
        } else {
          this.idProgetto = +params['id_progetto']; 
        }
      },
        error => {
        this.alertService.error(error);
      });
    }


  ngOnInit(): void {
    if (this.idProgetto != null) {
      this.getProgetto();
      this.getProgettoSpesa();
      this.getTipoSpesa();
    } else{
      this.progetto = new Progetto;
    }
    this.getMatricole();
    this.getSupervisor();
    this.getProgettoPersone();
  }
  
  getProgettoPersone(): void {
    this.progettiPersoneService.getById(this.idProgetto!)
      .subscribe(response => {
        this.dataSourcePersone = new MatTableDataSource(response.data);
        this.checkIsPercentuali100();
      },
      error => {
        this.dataSourcePersone = new MatTableDataSource();
      });
  }

  getProgettoSpesa(): void {
    this.progettiSpesaService.getById(this.idProgetto!)
      .subscribe(response => {
        this.dataSource = new MatTableDataSource(response.data);
      },
      error => {
        this.dataSource = new MatTableDataSource();
      });
  }

  getProgetto(): void {
    this.progettiService.getById(this.idProgetto!)
      .subscribe(response => {
        this.progetto = new Progetto;
        this.progetto = response.value;
        this.progetto_old = response.value;
      },
      error => {
        this.alertService.error(error);
      });
  }

  getRecord(prgSpesa: ProgettoSpesa) {
    if (this.dataSource.data) {
      this.dataSource.data.forEach(x => x.isEditable = false);
    }
    prgSpesa.isEditable = true;
  }

  getRecordPersona(p: ProgettoPersona) {
    if (this.dataSourcePersone.data) {
      this.dataSourcePersone.data.forEach(x => x.isEditable = false);
    }
    p.isEditable = true;
  }

  // carica l'elenco di tutte le matricole da Panthera
  getMatricole(): void {
    this.progettiService.getAllMatricole()
      .subscribe(response => {
        this.allMatricole = response.data;
      },
      error => {
        this.alertService.error(error);
      });
  }

  getNomeMatricola(idDipendente: string): string {
    if (this.allMatricole == null) {
      return '(unknown)';
    }
    const pantheraObj = this.allMatricole.find(x => x.ID_DIPENDENTE == idDipendente);
    return pantheraObj != null && pantheraObj.DENOMINAZIONE != null ? pantheraObj.DENOMINAZIONE : '(unknown)';
  }

  getSupervisor(): void {
    this.progettiService.getAllTipiCostoPanthera()
      .subscribe(response => {
        this.allTipiCosto = response.data;
      },
      error => {
        this.alertService.error(error);
      });
  }

  getTipoSpesa() {
    this.tipologiaSpesaService.getAll()
        .subscribe(response => {
          this.allTipologie = response.data;
        },
        error => {
        });
  }

  salva() {
    if (this.progetto.DATA_FINE)
      this.progetto.DATA_FINE = formatDate(this.progetto.DATA_FINE,"YYYY-MM-dd","en-GB");

    if (this.progetto.DATA_INIZIO)
      this.progetto.DATA_INIZIO = formatDate(this.progetto.DATA_INIZIO,"YYYY-MM-dd","en-GB");

    if (this.progetto.DATA_ULTIMO_REPORT)
      this.progetto.DATA_ULTIMO_REPORT = formatDate(this.progetto.DATA_ULTIMO_REPORT,"YYYY-MM-dd","en-GB");
    
    if (this.idProgetto == null) {
      this.progettiService.insert(this.progetto)
      .subscribe(response => {
        this.alertService.success("Progetto inserito con successo");
        this.router.navigate(['/progetto/' + response.value.ID_PROGETTO]);
      },
      error => {
        this.alertService.error(error);
      });
    } else {
        this.progettiService.update(this.progetto)
        .subscribe(response => {
          this.alertService.success("Progetto modificato con successo");
          this.router.navigate(['/progetto/' + response.value.ID_PROGETTO]);
        },
        error => {
          this.alertService.error(error);
        });
      }
  }

  nuovoProgettoSpesa() {  
    let progettoSpesa_nuovo: any;
    progettoSpesa_nuovo = {ID_PROGETTO:this.progetto.ID_PROGETTO,ID_SPESA:null, DESCRIZIONE:null,IMPORTO:null,TIPOLOGIA: {ID_TIPOLOGIA:null, DESCRIZIONE:null},isEditable:true,isInsert:true};
    let data:any[] = [];
    if (this.dataSource.data == null) {
      data.push(progettoSpesa_nuovo);
    } else {
      data = this.dataSource.data;
      data.push(progettoSpesa_nuovo);
    }
    this.dataSource.data = data;
  } 

  nuovoProgettoPersona() {  
    let nuovo: ProgettoPersona;
    nuovo = {
      ID_PROGETTO: this.progetto.ID_PROGETTO, 
      ID_DIPENDENTE: null,
      PCT_IMPIEGO: 0, 
      isEditable: true,
      isInsert: true
    };
    let array: any[] = [];
    if (this.dataSourcePersone.data != null) {
      array = this.dataSourcePersone.data;
    }
    array.push(nuovo);
    this.dataSourcePersone.data = array;
  } 

  deleteChange(prgSpesa: ProgettoSpesa) {
    if (prgSpesa.ID_PROGETTO != null && prgSpesa.ID_SPESA != null) {
      this.progettiSpesaService.delete(prgSpesa.ID_PROGETTO, prgSpesa.ID_SPESA)
          .subscribe(response => {
            this.getProgettoSpesa();
          },
          error => {
            this.alertService.error("Impossibile eliminare il record");
          });
    }
  }

  deleteChangePersona(p: ProgettoPersona) {
    if (p.ID_DIPENDENTE != null && p.ID_PROGETTO != null) {
      this.progettiPersoneService.delete(p.ID_PROGETTO, p.ID_DIPENDENTE)
      .subscribe(response => {
        this.getProgettoPersone();
      },
      error => {
        this.alertService.error(error);
      });
    }
  }
  

  salvaModifica(prgSpesa: ProgettoSpesa) {
    if (prgSpesa.ID_SPESA == null) {
      if (prgSpesa.IMPORTO != null) {
        this.progettiSpesaService.insert(prgSpesa)
        .subscribe(response => {
          this.dataSource.data.splice(-1, 1);
          this.dataSource.data.push(response.value);
          this.dataSource.data = this.dataSource.data;
          this.alertService.success("Spesa salvata con successo");
          prgSpesa.isEditable = false;
        },
        error => {
          this.alertService.error(error);
        });
      }
    } else {
      if (prgSpesa.IMPORTO != null) {
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
  }

  annullaModifica(row: ProgettoSpesa) {
    this.getProgettoSpesa();
  }

  annullaModificaPersona(row: ProgettoPersona) {
    this.getProgettoPersone();
  }

  salvaModificaPersona(p: ProgettoPersona) {    
    console.log(p);

    if (p.isInsert) {
        this.progettiPersoneService.insert(p)
        .subscribe(response => {
          this.alertService.success("Matricola inserita con successo");
          this.dataSourcePersone.data.splice(-1, 1);
          this.dataSourcePersone.data.push(response.value);
          this.dataSourcePersone.data = this.dataSourcePersone.data;
          p.isEditable = false;
          this.checkIsPercentuali100();
        },
        error => {
          this.alertService.error(error);
        });
      
    } else {
        this.progettiPersoneService.update(p)
        .subscribe(response => {
          this.alertService.success("Matricola aggiornata con successo");
          p.isEditable = false;
          this.checkIsPercentuali100();
        },
        error => {
          this.alertService.error(error);
        });
      }
  }
  

  undoChange(prgSpesa: ProgettoSpesa) {
    prgSpesa.isEditable=false;
    if (prgSpesa.ID_PROGETTO == null) {
      this.dataSource.data.splice(-1, 1);
      this.dataSource.data = this.dataSource.data;
    }
  }

  report() {
    this.router.navigate(["progetto", this.progetto.ID_PROGETTO, "report"]);
  }

  associaOre() {
    this.router.navigate(["progetto", this.progetto.ID_PROGETTO, "associazione-ore"]);
  }

  /**
   * Arrotonda x a 2 decimali
   */
  round2(x: number) {
    return Math.round(x * 100) / 100;
  }

  /**
   * Restituisce l'equivalente di MONTE_ORE_TOT calcolato su base mensile, arrotondato a 2 decimali
   */
  monteOreMensile() {
    if (this.progetto.MONTE_ORE_TOT === null) {
      return null;
    }
    return this.round2(this.progetto.MONTE_ORE_TOT / MONTE_ORE_MENSILE_PREVISTO);
  }

  /**
   * Restituisce l'equivalente di COSTO_MEDIO_UOMO calcolato su base mensile, arrotondato a 2 decimali
   */
   costoMensile() {
    if (this.progetto.COSTO_MEDIO_UOMO === null) {
      return null;
    }
    return this.round2(this.progetto.COSTO_MEDIO_UOMO * MONTE_ORE_MENSILE_PREVISTO);
  }

  /**
   * Restituisce l'equivalente di OBIETTIVO_BUDGET_ORE calcolato su base mensile, arrotondato a 2 decimali
   */
  obiettivoBudgetMensile() {
    if (this.progetto.OBIETTIVO_BUDGET_ORE === null) {
      return null;
    }
    return this.round2(this.progetto.OBIETTIVO_BUDGET_ORE / MONTE_ORE_MENSILE_PREVISTO);
  }

  /**
   * Restituisce l'equivalente di COSTO_MEDIO_UOMO calcolato su base mensile, arrotondato a 2 decimali
   */
   costoTotaleBudget() {
    if (this.progetto.COSTO_MEDIO_UOMO === null || this.progetto.OBIETTIVO_BUDGET_ORE == null) {
      return null;
    }
    return this.round2(this.progetto.COSTO_MEDIO_UOMO * this.progetto.OBIETTIVO_BUDGET_ORE);
  }

  setMonteOreFmt($event: Event) {
    const value = ($event.target as HTMLInputElement).value;
    if (value != null && value != '') {
      this.progetto.MONTE_ORE_TOT = parseFloat(value.replace('.', '').replace(',', '.'));
    } else {
      this.progetto.MONTE_ORE_TOT = 0;
    }
  }

  setOreGiaAssegnateFmt($event: Event) {
    const value = ($event.target as HTMLInputElement).value;
    if (value != null && value != '') {
      this.progetto.ORE_GIA_ASSEGNATE = parseFloat(value.replace('.', '').replace(',', '.'));
    } else {
      this.progetto.ORE_GIA_ASSEGNATE = 0;
    }
  }

  setObiettivoBudgetFmt($event: Event) {
    const value = ($event.target as HTMLInputElement).value;
    if (value != null && value != '') {
      this.progetto.OBIETTIVO_BUDGET_ORE = parseFloat(value.replace('.', '').replace(',', '.'));
    } else {
      this.progetto.OBIETTIVO_BUDGET_ORE = 0;
    }
  }

  setCostoMedioFmt($event: Event) {
    const value = ($event.target as HTMLInputElement).value;
    if (value != null && value != '') {
      this.progetto.COSTO_MEDIO_UOMO = parseFloat(value.replace('.', '').replace(',', '.'));
    } else {
      this.progetto.COSTO_MEDIO_UOMO = null;
    }
  }

  setImportoFmt($event: Event, progSpesa: ProgettoSpesa) {
    const value = ($event.target as HTMLInputElement).value;
    if (value != null && value != '') {
      progSpesa.IMPORTO = parseFloat(value.replace('.', '').replace(',', '.'));
    } else {
      progSpesa.IMPORTO = null;
    }
  }

  ripartisciPercentuali() {
    const array = this.dataSourcePersone.data;
    if (array != null && array.length > 0) {
      const media = 100.0 / array.length;
      array.forEach(x => x.PCT_IMPIEGO = media);
      this.dataSourcePersone.data = array; //this is not useless!
      array.forEach(x => {
        this.progettiPersoneService.update(x)
          .subscribe(response => {
            // do nothing
          },
          error => {
            this.alertService.error(error);
          });
      });
    }
  }

  TOLLERANZA = 0.001;

  checkIsPercentuali100() {
    let sum = 0.0;
    this.dataSourcePersone.data.forEach(x => sum += parseFloat((<any>x.PCT_IMPIEGO!)));
    this.isPercentuali100 = (Math.abs(sum - 100) <= this.TOLLERANZA);
  }
}
