import { Component, OnInit } from '@angular/core';
import { DatitestService } from './../_services/datitest.service';

@Component({
  selector: 'app-genera-dati-test',
  templateUrl: './genera-dati-test.component.html',
  styleUrls: ['./genera-dati-test.component.css']
})
export class GeneraDatiTestComponent implements OnInit {

  periodo = '2021-02'; //TODO datepicker

  constructor(private datitestService: DatitestService) { }

  ngOnInit(): void {
  }

  run() {
      this.datitestService.run(this.periodo).subscribe();
  }
}
