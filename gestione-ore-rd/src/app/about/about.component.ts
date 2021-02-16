import { Component, OnInit, OnDestroy } from '@angular/core';
import { StatisticsService } from '@/_services';

@Component({templateUrl: 'about.component.html'})
export class AboutComponent implements OnInit, OnDestroy {
    
    loaded = false;
    num_progetti : number;
    num_questionari : number;
    num_compilazioni : number;
    num_risposte : number;
    
    constructor(
        private statisticsService: StatisticsService)
    {
        
    }

    ngOnInit() {
        this.loadStatistics();
    }

    ngOnDestroy() {
    }
    
    loadStatistics() {
        this.statisticsService.get().subscribe(
            response => {

                this.num_progetti = response["value"]["num_progetti"]["tot"];
                this.num_questionari = response["value"]["num_questionari"]["tot"];
                this.num_compilazioni = response["value"]["num_compilazioni"]["1"] || '0';
                this.num_risposte = response["value"]["num_risposte"]["1"] || '0';
                this.loaded = true;
            }
        )
    }
}