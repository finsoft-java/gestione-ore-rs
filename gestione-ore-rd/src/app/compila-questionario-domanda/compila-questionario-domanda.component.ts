import {Component, Input, OnInit, EventEmitter, Output} from '@angular/core';
import { Domanda, Sezione } from '@/_models';
import { AuthenticationService, UserService, AlertService, QuestionariCompilatiService } from '@/_services';
import { Router, ActivatedRoute } from '@angular/router';

@Component({
  selector: '[compila-questionario-domanda]',
  templateUrl: './compila-questionario-domanda.component.html'
})
export class CompilaQuestionarioDomandaComponent implements OnInit {
  
  @Input() domanda : Domanda; //deve includere tutte le risposte
  @Input()  questionario_modificabile : boolean;

  @Output() compiled = new EventEmitter<void>();
  @Input() isValid = true;
  is_domanda_aperta = false;
  is_domanda_compilata = false;
  titleRispostaControlli : string;
  constructor(
      private authenticationService: AuthenticationService,
      private questCompService: QuestionariCompilatiService,
      private alertService: AlertService,
      private route: ActivatedRoute,
      private router: Router
  ) {
      }
  
  ngOnInit(): void {
    this.calc_flags();
    this.titleRispostaControlli = this.getTitleInput();
  }

  getTitleInput(){
    let title = '';
    switch (this.domanda.html_type) {
      case "0":
        if(this.domanda.html_pattern != null)
          title= "La risposta deve rispettare il pattern: "+this.domanda.html_pattern+" ";
          
        if(this.domanda.html_maxlength != null)
          title+= "Lunghezza Massima: "+this.domanda.html_maxlength+" ";
        break;
      case "1":
        if(this.domanda.html_min != null)
          title= "Valore minimo : "+this.domanda.html_min+" ";
          
        if(this.domanda.html_maxlength != null)
          title+= "Valore massimo: "+this.domanda.html_max+" ";
        break;
    
      default:
        break;
    }
    return title;
  }
  
  calc_flags() {
    this.is_domanda_aperta = (this.domanda.risposte == null || this.domanda.risposte.length == 0);

    let compilata = true;
    if (this.domanda.obbligatorieta) {
        if (this.is_domanda_aperta) {
          if(this.domanda.risposta){
            if (!this.domanda.risposta.risposta_aperta) {
              compilata = false;
            }
          }
        } else {
          if(this.domanda.risposta){
            if (!this.domanda.risposta.progressivo_risposta) {
              compilata = false;
            }
          }
        }
    }
    this.domanda.is_compilata = compilata;
    this.compiled.emit();
  }

}
