import { ProgettiComponent } from './progetti/progetti.component';
import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

const routes: Routes = [
  { path: 'progetti', component: ProgettiComponent},
/*  { path: 'questionari_compilati', component: QuestionariDaCompilareComponent, data: {storico: true}, canActivate: [AuthGuard] },
  { path: 'questionari_da_compilare/:progressivo_quest_comp', component: CompilaQuestionarioComponent, canActivate: [AuthGuard] },
  { path: 'questionari_compilati/:progressivo_quest_comp', component: CompilaQuestionarioComponent, canActivate: [AuthGuard] },
  { path: 'progetti', component: ProgettiComponent, canActivate: [Role1Guard] },
  { path: 'progetti/:id_progetto', component: SingoloProgettoComponent, canActivate: [Role1Guard] },
  { path: 'questionari', component: QuestionariComponent, canActivate: [Role1Guard] },
  { path: 'questionari/:id_questionario', component: SingoloQuestionarioComponent, canActivate: [Role1Guard] },
  { path: 'utenti', component: UtentiComponent, canActivate: [Role2Guard] },
  { path: 'login', component: LoginComponent },
  { path: 'about', component: AboutComponent },*/
  { path: '**', redirectTo: 'progetti' }/**     era : '**'        **/
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }
