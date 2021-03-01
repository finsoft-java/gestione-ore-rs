import { ProgettiSpesaService } from './../_services/progetti.spesa.service';
import { MatTableDataSource } from '@angular/material/table';
import { TipoCosto } from './../_models/tipocosto';
import { Matricola } from './../_models/matricola';
import { Subscription } from 'rxjs';
import { Progetto, ProgettoSpesa } from './../_models/progetto';
import { User } from './../_models/user';
import { ActivatedRoute, Router } from '@angular/router';
import { ProgettiService } from './../_services/progetti.service';
import { AlertService } from './../_services/alert.service';
import { AuthenticationService } from './../_services/authentication.service';
import { FormGroup, FormControl } from '@angular/forms';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-progetto-dettaglio',
  templateUrl: './progetto-dettaglio.component.html',
  styleUrls: ['./progetto-dettaglio.component.css']
})
export class ProgettoDettaglioComponent implements OnInit {

  projectSubscription: Subscription;
  progetto!: Progetto;
  displayedColumns: string[] = ['descrizione','importo', 'tipologia', 'actions'];
  dataSource = new MatTableDataSource<[]>();
  allMatricole: any;
  allTipiCosto: any;
  progetto_spesa!: ProgettoSpesa;
  id_progetto!: any;
  constructor(private authenticationService: AuthenticationService,
    private progettiService: ProgettiService,
    private progettiSpesaService: ProgettiSpesaService,    
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
    } else{
      this.progetto = new Progetto;
    }
    this.getMatricole();
    this.getSupervisor();
  }
  nuovoProgettoSpesa() {

  }
  getProgettoSpesa(): void {
    this.progettiSpesaService.getById(this.id_progetto)
      .subscribe(response => {
        this.dataSource = new MatTableDataSource<[]>(response["value"]);
        console.log(this.dataSource.data);
      },
      error => {
        this.alertService.error(error);
      });
  }
  getProgetto(): void {
    this.progettiService.getById(this.id_progetto)
      .subscribe(response => {
        this.progetto = new Progetto;
        this.progetto = response["value"][0];
      },
      error => {
        this.alertService.error(error);
      });
  }

  getRecord(a: ProgettoSpesa){
  }

  getMatricole(): void {
    this.progettiService.getAllMatricole()
      .subscribe(response => {
        console.log(response);
        this.allMatricole  = new Matricola;
        this.allMatricole = response["data"];
        console.log(this.allMatricole);
      },
      error => {
        this.alertService.error(error);
      });
  }

  getSupervisor(): void {
    this.progettiService.getAllTipiCostoPanthera()
      .subscribe(response => {
        console.log(response);
        this.allTipiCosto  = new TipoCosto;
        this.allTipiCosto = response["data"];
        console.log(this.allMatricole);
      },
      error => {
        this.alertService.error(error);
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

}
