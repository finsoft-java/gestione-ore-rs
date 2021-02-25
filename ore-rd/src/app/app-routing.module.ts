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
import { LoginComponent } from './login/login.component';
import { AuthGuard } from './_guards/auth.guard';
const routes: Routes = [
  { path: 'login', component: LoginComponent},
  { path: 'progetti', component: ProgettiComponent, canActivate:[AuthGuard]},
  { path: 'progetto/:id_progetto', component: ProgettoDettaglioComponent, canActivate:[AuthGuard]},
  { path: 'tipologie-spesa', component: TipologiaSpesaComponent, canActivate:[AuthGuard]},
  { path: 'importazione-lul', component: ImportazioneLulComponent, canActivate:[AuthGuard]},
  { path: 'importazione-rapportini', component: ImportazioneRapportiniComponent, canActivate:[AuthGuard]},
  { path: 'esportazione-rapportini', component: EsportazioneRapportiniComponent, canActivate:[AuthGuard]},
  { path: 'report-compatto', component: ReportCompattoComponent, canActivate:[AuthGuard]},
  { path: 'report-completo', component: ReportCompletoComponent, canActivate:[AuthGuard]},
  { path: 'dati-test', component: GeneraDatiTestComponent, canActivate:[AuthGuard]},
  { path: '**', redirectTo: 'progetti' }
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }
