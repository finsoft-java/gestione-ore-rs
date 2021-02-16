import { NgModule }      from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { ReactiveFormsModule, FormsModule }    from '@angular/forms';
import { HttpClientModule, HTTP_INTERCEPTORS } from '@angular/common/http';
// used to create fake backend
import { fakeBackendProvider } from './_helpers';
import { AppComponent }  from './app.component';
import { routing }        from './app.routing';
import { AlertComponent } from './_components';
import { JwtInterceptor, ErrorInterceptor } from './_helpers';
import { LoginComponent } from './login';
import { TableUtentiRowComponent } from './table-utenti-row/table-utenti-row.component';
import { UtentiComponent } from './utenti';
import { QuestionariDaCompilareComponent } from './questionari_da_compilare';
import { QuestionariComponent } from './questionari';
import { ProgettiComponent } from './progetti';
import { SingoloProgettoComponent } from './singolo_progetto';
import { TableQuestCompRowComponent } from './table-quest-comp-row';
import { SingoloQuestionarioComponent } from './singolo_questionario';
import { CompilaQuestionarioComponent } from './compila_questionario';
import { TableQuestionariRowComponent } from './table-questionari-row/table-questionari-row.component';
import { TableRuoliRowComponent } from './table-ruoli-row';
import { SearchComponent } from './search';
import { TableDomandeRowComponent } from './table-domande-row'; 
import { AboutComponent } from './about';
import { CompilaQuestionarioDomandaComponent } from './compila-questionario-domanda';
import { TableRisposteRowComponent } from './table-risposte-row';
import { TruncatePipe } from './_pipes';
import { FilterTableComponent } from './filter-table'
import {A11yModule} from '@angular/cdk/a11y';
import {BidiModule} from '@angular/cdk/bidi';
import {ObserversModule} from '@angular/cdk/observers';
import {OverlayModule} from '@angular/cdk/overlay';
import {PlatformModule} from '@angular/cdk/platform';
import {PortalModule} from '@angular/cdk/portal';
import {ScrollDispatchModule} from '@angular/cdk/scrolling';
import {CdkStepperModule} from '@angular/cdk/stepper';
import {CdkTableModule} from '@angular/cdk/table';
import {BrowserAnimationsModule} from '@angular/platform-browser/animations';
import { QuillModule } from 'ngx-quill';
import { NgSelect2Module } from 'ng-select2';
import {
    MatAutocompleteModule,
    MatButtonModule,
    MatButtonToggleModule,
    MatCardModule,
    MatCheckboxModule,
    MatChipsModule,
    MatDatepickerModule,
    MatDialogModule,
    MatExpansionModule,
    MatGridListModule,
    MatIconModule,
    MatInputModule,
    MatListModule,
    MatMenuModule,
    MatNativeDateModule,
    MatProgressBarModule,
    MatProgressSpinnerModule,
    MatRadioModule,
    MatRippleModule,
    MatSelectModule,
    MatSidenavModule,
    MatSliderModule,
    MatSlideToggleModule,
    MatSnackBarModule,
    MatStepperModule,
    MatTableModule,
    MatTabsModule,
    MatToolbarModule,
    MatTooltipModule
  } from '@angular/material';
import { SanitizerPipe } from './_services/sanitizer.pipe';
@NgModule({
    imports: [
        BrowserModule,
        ReactiveFormsModule,
        HttpClientModule,
        FormsModule,
        routing,
        A11yModule,
        BidiModule,
        ObserversModule,
        OverlayModule,
        PlatformModule,
        PortalModule,
        ScrollDispatchModule,
        CdkStepperModule,
        CdkTableModule,
        BrowserAnimationsModule,
        NgSelect2Module,
        QuillModule.forRoot({
            modules: {
              toolbar: [
                    ['bold', 'italic', 'underline', 'strike'],        // toggled buttons
                    ['blockquote', 'code-block'],
                
                    [{ 'header': 1 }, { 'header': 2 }],               // custom button values
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'script': 'sub'}, { 'script': 'super' }],      // superscript/subscript
                    [{ 'indent': '-1'}, { 'indent': '+1' }],          // outdent/indent
                    [{ 'direction': 'rtl' }],                         // text direction
                
                    [{ 'size': ['small', false, 'large', 'huge'] }],  // custom dropdown
                    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                
                    [{ 'color': [] }, { 'background': [] }],          // dropdown with defaults from theme
                    [{ 'font': [] }],
                    [{ 'align': [] }],
                
                    ['clean']                                         // remove formatting button
                ]
            }
          }),  /* il .forroot() evita l'errore: No provider for InjectionToken config */
        // Material
        MatAutocompleteModule,
        MatButtonModule,
        MatButtonToggleModule,
        MatCardModule,
        MatCheckboxModule,
        MatChipsModule,
        MatDatepickerModule,
        MatDialogModule,
        MatExpansionModule,
        MatGridListModule,
        MatIconModule,
        MatInputModule,
        MatListModule,
        MatMenuModule,
        MatProgressBarModule,
        MatProgressSpinnerModule,
        MatRadioModule,
        MatRippleModule,
        MatSelectModule,
        MatSidenavModule,
        MatSlideToggleModule,
        MatSliderModule,
        MatSnackBarModule,
        MatStepperModule,
        MatTableModule,
        MatTabsModule,
        MatToolbarModule,
        MatTooltipModule,
        MatNativeDateModule
    ],
    declarations: [
        AppComponent,
        AlertComponent,
        UtentiComponent,
        ProgettiComponent,
        SingoloProgettoComponent,
        QuestionariComponent,
        SingoloQuestionarioComponent,
        QuestionariDaCompilareComponent,
        CompilaQuestionarioComponent,
        CompilaQuestionarioDomandaComponent,
        TableQuestCompRowComponent,
        TableUtentiRowComponent,
        TableQuestionariRowComponent,
        TableRuoliRowComponent,
        TableDomandeRowComponent,
        TableRisposteRowComponent,
        FilterTableComponent,
        LoginComponent,
        SearchComponent,
        AboutComponent,
        SanitizerPipe,
        TruncatePipe
    ],
    exports:[
        TruncatePipe
    ],
    providers: [
        { provide: HTTP_INTERCEPTORS, useClass: JwtInterceptor, multi: true },
        { provide: HTTP_INTERCEPTORS, useClass: ErrorInterceptor, multi: true },

        // provider used to create fake backend
        fakeBackendProvider
    ],
    bootstrap: [AppComponent]
})

export class AppModule { }