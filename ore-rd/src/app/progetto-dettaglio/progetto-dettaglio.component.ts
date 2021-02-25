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
  id_progetto!: number;
  constructor(private authenticationService: AuthenticationService,
    private progettiService: ProgettiService,
    private alertService: AlertService,
    private route: ActivatedRoute,
    private router: Router) {
      this.projectSubscription = this.route.params.subscribe(params => {
        this.id_progetto = +params['id_progetto']; 
      },
        error => {
        this.alertService.error(error);
      });
    }


  ngOnInit(): void {
    this.getProgetto();
    console.log(this.progetto);
  }

  getProgetto(): void {
    this.progettiService.getById(this.id_progetto)
      .subscribe(response => {
        console.log(response);
        //this.progetto = new Progetto();
        console.log("progetto ->",this.progetto);
      },
      error => {
        this.alertService.error(error);
      });
  }

}
