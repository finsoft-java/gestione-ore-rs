import { AlertComponent } from './_components/alert.component';
import { JwtInterceptor } from './_helpers/jwt.interceptor';
import { ErrorInterceptor } from './_helpers/error.interceptor';
import { HttpClient, HttpClientModule, HttpHandler, HTTP_INTERCEPTORS } from '@angular/common/http';
import { MatMenuModule } from '@angular/material/menu';
import { MatIconModule } from '@angular/material/icon';
import { CUSTOM_ELEMENTS_SCHEMA, NgModule } from '@angular/core';
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
import { RaccoltaDateFirmaComponent } from './raccolta-date-firma/raccolta-date-firma.component';
import { ReportCompletoComponent } from './report-completo/report-completo.component';
import { GeneraDatiTestComponent } from './genera-dati-test/genera-dati-test.component';
import { ProgettoDettaglioComponent } from './progetto-dettaglio/progetto-dettaglio.component';
import { MatSidenavModule } from '@angular/material/sidenav';
import { LoginComponent } from './login/login.component';
import { MatCardModule } from '@angular/material/card';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { MatTableModule } from '@angular/material/table';
import { DateAdapter, MatNativeDateModule } from '@angular/material/core';
import { MatPaginatorModule } from '@angular/material/paginator';
import { MatSortModule } from '@angular/material/sort';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatSelectModule } from '@angular/material/select';
import { TextFieldModule } from '@angular/cdk/text-field';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatMomentDateModule } from '@angular/material-moment-adapter';
import { MomentUtcDateAdapter } from './_helpers/date.adapter';
import { MatCheckboxModule } from '@angular/material/checkbox';

@NgModule({
  declarations: [
    AppComponent,
    ProgettiComponent,
    TipologiaSpesaComponent,
    ImportazioneLulComponent,
    ImportazioneRapportiniComponent,
    EsportazioneRapportiniComponent,
    RaccoltaDateFirmaComponent,
    ReportCompletoComponent,
    GeneraDatiTestComponent,
    ProgettoDettaglioComponent,
    LoginComponent,
    AlertComponent
  ],
  imports: [
    HttpClientModule,
    BrowserModule,
    AppRoutingModule,
    BrowserAnimationsModule,
    NgMaterialMultilevelMenuModule,
    MatIconModule,
    MatMenuModule,
    MatSidenavModule,
    MatCardModule,
    MatInputModule,
    MatButtonModule,
    FormsModule,
    ReactiveFormsModule,
    MatTableModule,
    MatNativeDateModule,
    MatPaginatorModule,
    MatSortModule,
    MatFormFieldModule,
    MatSelectModule,
    TextFieldModule,
    MatDatepickerModule,
    MatMomentDateModule,
    MatCheckboxModule
  ],
  providers: [{ provide: HTTP_INTERCEPTORS, useClass: ErrorInterceptor, multi: true },
              { provide: HTTP_INTERCEPTORS, useClass: JwtInterceptor, multi: true },
              { provide: DateAdapter, useClass: MomentUtcDateAdapter }],
  bootstrap: [AppComponent],
  schemas: [CUSTOM_ELEMENTS_SCHEMA]
})
export class AppModule { }
