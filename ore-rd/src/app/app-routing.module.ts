import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { AuthGuard } from './_guards/auth.guard';
import { ReportCompletoComponent } from './report-completo/report-completo.component';
import { RaccoltaDateFirmaComponent } from './raccolta-date-firma/raccolta-date-firma.component';
import { EsportazioneRapportiniComponent } from './esportazione-rapportini/esportazione-rapportini.component';
import { ImportazioneRapportiniComponent } from './importazione-rapportini/importazione-rapportini.component';
import { ImportazioneLulComponent } from './importazione-lul/importazione-lul.component';
import { TipologiaSpesaComponent } from './tipologia-spesa/tipologia-spesa.component';
import { ProgettiComponent } from './progetti/progetti.component';
import { ProgettoDettaglioComponent } from './progetto-dettaglio/progetto-dettaglio.component';
import { StoricoAssociazioniOreComponent } from './storico-associazioni-ore/storico-associazioni-ore.component';
import { LoginComponent } from './login/login.component';
import { GrigliaLulComponent } from './griglia-lul/griglia-lul.component';
import { GrigliaOreImportateComponent } from './griglia-ore-importate/griglia-ore-importate.component';
import { PartecipantiGlobaliComponent } from './partecipanti-globali/partecipanti-globali.component';
import { ImportazionePartecipantiComponent } from './importazione-partecipanti/importazione-partecipanti.component';
import { CommesseComponent } from './commesse/commesse.component';
import { ImportazioneCommesseComponent } from './importazione-commesse/importazione-commesse.component';
import { AssociazioneOreComponent } from './associazione-ore/associazione-ore.component';

const routes: Routes = [
  { path: 'login', component: LoginComponent},
  { path: 'progetti', component: ProgettiComponent, canActivate:[AuthGuard]},
  { path: 'progetto/:id_progetto', component: ProgettoDettaglioComponent, canActivate:[AuthGuard]},
  { path: 'progetto/nuovo', component: ProgettoDettaglioComponent, canActivate:[AuthGuard]},
  { path: 'progetto/:id_progetto/report', component: ReportCompletoComponent, canActivate:[AuthGuard]},
  { path: 'associazione-ore', component: AssociazioneOreComponent, canActivate:[AuthGuard]},
  { path: 'tipologie-spesa', component: TipologiaSpesaComponent, canActivate:[AuthGuard]},
  { path: 'importazione-lul', component: ImportazioneLulComponent, canActivate:[AuthGuard]},
  { path: 'importazione-ore', component: ImportazioneRapportiniComponent, canActivate:[AuthGuard]},
  { path: 'esportazione-rapportini', component: EsportazioneRapportiniComponent, canActivate:[AuthGuard]},
  { path: 'raccolta-date-firma', component: RaccoltaDateFirmaComponent, canActivate:[AuthGuard]},
  { path: 'regressione', component: StoricoAssociazioniOreComponent, canActivate:[AuthGuard]},
  { path: 'ore-importate', component: GrigliaOreImportateComponent, canActivate:[AuthGuard]},
  { path: 'lul-importati', component: GrigliaLulComponent, canActivate:[AuthGuard]},
  { path: 'partecipanti-globali', component: PartecipantiGlobaliComponent, canActivate:[AuthGuard]},
  { path: 'importazione-partecipanti', component: ImportazionePartecipantiComponent, canActivate:[AuthGuard]},
  { path: 'commesse', component: CommesseComponent, canActivate:[AuthGuard]},
  { path: 'importazione-commesse', component: ImportazioneCommesseComponent, canActivate:[AuthGuard]},
  { path: '**', redirectTo: 'progetti' }
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }
