import { Matricola } from './../_models/matricola';
import { Subscription } from 'rxjs';
import { Progetto } from './../_models/progetto';
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
  allMatricole: any;
  id_progetto!: any;
  constructor(private authenticationService: AuthenticationService,
    private progettiService: ProgettiService,
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
    } else{
      this.progetto = new Progetto;
    }
    this.getMatricole();
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
