import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';

import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { NgMaterialMultilevelMenuModule } from 'ng-material-multilevel-menu';
import { ProgettiComponent } from './progetti/progetti.component';
import { TipologiaSpesaComponent } from './tipologia-spesa/tipologia-spesa.component';
import { ImportazioneLulComponent } from './importazione-lul/importazione-lul.component';
import { ImportazioneRapportiniComponent } from './importazione-rapportini/importazione-rapportini.component';
import { EsportazioneRapportiniComponent } from './esportazione-rapportini/esportazione-rapportini.component';
import { ReportCompattoComponent } from './report-compatto/report-compatto.component';
import { ReportCompletoComponent } from './report-completo/report-completo.component';
import { GeneraDatiTestComponent } from './genera-dati-test/genera-dati-test.component';

@NgModule({
  declarations: [
    AppComponent,
    ProgettiComponent,
    TipologiaSpesaComponent,
    ImportazioneLulComponent,
    ImportazioneRapportiniComponent,
    EsportazioneRapportiniComponent,
    ReportCompattoComponent,
    ReportCompletoComponent,
    GeneraDatiTestComponent
  ],
  imports: [
    BrowserModule,
    AppRoutingModule,
    BrowserAnimationsModule,
    NgMaterialMultilevelMenuModule
  ],
  providers: [],
  bootstrap: [AppComponent]
})
export class AppModule { }
