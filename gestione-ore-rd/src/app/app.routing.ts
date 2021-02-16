import { Routes, RouterModule } from '@angular/router';

import { LoginComponent } from './login';
import { AuthGuard, Role2Guard, Role1Guard } from './_guards';
import { UtentiComponent } from './utenti';
import { QuestionariDaCompilareComponent } from './questionari_da_compilare';
import { QuestionariComponent } from './questionari';
import { ProgettiComponent } from './progetti';
import { SingoloProgettoComponent } from './singolo_progetto';
import { SingoloQuestionarioComponent } from './singolo_questionario';
import { CompilaQuestionarioComponent } from './compila_questionario';
import { AboutComponent } from './about';

const appRoutes: Routes = [
    { path: 'questionari_da_compilare', component: QuestionariDaCompilareComponent, data: {storico: false}, canActivate: [AuthGuard] },
    { path: 'questionari_compilati', component: QuestionariDaCompilareComponent, data: {storico: true}, canActivate: [AuthGuard] },
    { path: 'questionari_da_compilare/:progressivo_quest_comp', component: CompilaQuestionarioComponent, canActivate: [AuthGuard] },
    { path: 'questionari_compilati/:progressivo_quest_comp', component: CompilaQuestionarioComponent, canActivate: [AuthGuard] },
    { path: 'progetti', component: ProgettiComponent, canActivate: [Role1Guard] },
    { path: 'progetti/:id_progetto', component: SingoloProgettoComponent, canActivate: [Role1Guard] },
    { path: 'questionari', component: QuestionariComponent, canActivate: [Role1Guard] },
    { path: 'questionari/:id_questionario', component: SingoloQuestionarioComponent, canActivate: [Role1Guard] },
    { path: 'utenti', component: UtentiComponent, canActivate: [Role2Guard] },
    { path: 'login', component: LoginComponent },
    { path: 'about', component: AboutComponent },
    { path: '**', redirectTo: 'questionari_da_compilare' }/**     era : '**'        **/
];

export const routing = RouterModule.forRoot(appRoutes);