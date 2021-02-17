import { GeneraDatiTestComponent } from './genera-dati-test/genera-dati-test.component';
import { ReportCompletoComponent } from './report-completo/report-completo.component';
import { ReportCompattoComponent } from './report-compatto/report-compatto.component';
import { EsportazioneRapportiniComponent } from './esportazione-rapportini/esportazione-rapportini.component';
import { ImportazioneRapportiniComponent } from './importazione-rapportini/importazione-rapportini.component';
import { ImportazioneLulComponent } from './importazione-lul/importazione-lul.component';
import { TipologiaSpesaComponent } from './tipologia-spesa/tipologia-spesa.component';
import { ProgettiComponent } from './progetti/progetti.component';
import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { ProgettoDettaglioComponent } from './progetto-dettaglio/progetto-dettaglio.component';
const routes: Routes = [
  { path: 'progetti', component: ProgettiComponent},
  { path: 'progetto/:id', component: ProgettoDettaglioComponent},
  { path: 'tipologie-spesa', component: TipologiaSpesaComponent},
  { path: 'importazione-lul', component: ImportazioneLulComponent},
  { path: 'importazione-rapportini', component: ImportazioneRapportiniComponent},
  { path: 'esportazione-rapportini', component: EsportazioneRapportiniComponent},
  { path: 'report-compatto', component: ReportCompattoComponent},
  { path: 'report-completo', component: ReportCompletoComponent},
  { path: 'dati-test', component: GeneraDatiTestComponent},
  { path: '**', redirectTo: 'progetti' }
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }
