import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { AssociazioneOreService } from '../_services/associazione.ore';
import { AlertService } from '../_services/alert.service';
import { ProgettiService } from '../_services/progetti.service';
import { Periodo } from '../_models';
import { PeriodiService } from '../_services/periodi.service';
// tslint:disable-next-line:no-duplicate-imports


@Component({
  selector: 'app-associazione-ore',
  templateUrl: './associazione-ore.component.html',
  styleUrls: ['./associazione-ore.component.css']
})
export class AssociazioneOreComponent implements OnInit {

  message_success = '';
  message_error = '';
  allPeriodi: Periodo[] = [];
  filtroPeriodo?: Periodo;
  running = false;

  constructor(private associazioneOreService: AssociazioneOreService,
    private progettiService: ProgettiService,
    private periodiService: PeriodiService,
    private alertService: AlertService,
    private route: ActivatedRoute,
    private router: Router) { }

  ngOnInit(): void {
    this.getAllPeriodi();
  }

  getAllPeriodi() {
    this.periodiService.getAll().subscribe(response => {
      this.allPeriodi = response.data;
    })
  }

  resetAlertSuccess() {
    this.message_success = '';
  }

  resetAlertDanger() {
    this.message_error = '';
  }

  run() {
    this.message_success = 'Running, please wait...';
    this.resetAlertDanger();
    this.running = true;

    this.associazioneOreService.run(this.filtroPeriodo!.DATA_INIZIO, this.filtroPeriodo!.DATA_FINE).subscribe(response => {
      this.message_success = 'Elaborazione terminata. I dettagli verranno visualizzati in una nuova finestra.';
      this.message_error = response.value.error;
      this.running = false;
      const tab = window.open('about:blank', '_blank');
      if (tab != null) {
        tab.document.write('<html><body>' + response.value.success + '<br/>' + response.value.error + '</body></html>');
        tab.document.close(); // to finish loading the page
      }
    },
    error => {
      this.message_error = error;
      this.running = false;
    });
  }
}
