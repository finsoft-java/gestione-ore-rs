import { Component, Input, OnInit } from '@angular/core';
import { MatTableDataSource } from '@angular/material/table';
import { ProgettoCommessa } from '../_models';
import { AlertService } from '../_services/alert.service';
import { ProgettiCommesseService } from '../_services/progetti.commesse.service';

@Component({
  selector: 'app-progetto-commesse',
  templateUrl: './progetto-commesse.component.html',
  styleUrls: ['./progetto-commesse.component.css']
})
export class ProgettoCommesseComponent implements OnInit {
  
  displayedColumnsCommesseP: string[] = ['codCommessa', 'note', 'actions'];
  displayedColumnsCommesseC: string[] = ['codCommessa', 'pctCompatibilita', 'giustificativo', 'note', 'actions'];
  dataSourceCommesseDiProgetto = new MatTableDataSource<ProgettoCommessa>();
  dataSourceCommesseCompatibili = new MatTableDataSource<ProgettoCommessa>();

  @Input()
  idProgetto!: number|null;

  constructor(private alertService: AlertService,
    private progettiCommesseService: ProgettiCommesseService,) { }

  ngOnInit(): void {
    this.getProgettoCommesse();
  }

  
  getProgettoCommesse(): void {
    this.progettiCommesseService.getById(this.idProgetto!)
      .subscribe(response => {
        let progettoCommesseCompatibili: ProgettoCommessa[] = [];
        let progettoCommesseDiProgetto: ProgettoCommessa[] = [];
        if (response.data != null) {
          progettoCommesseDiProgetto = response.data.filter(x => x.PCT_COMPATIBILITA == 100);
          progettoCommesseCompatibili = response.data.filter(x => x.PCT_COMPATIBILITA < 100);
        }
        this.dataSourceCommesseCompatibili = new MatTableDataSource(progettoCommesseCompatibili);
        this.dataSourceCommesseDiProgetto = new MatTableDataSource(progettoCommesseDiProgetto);
      },
      error => {
        this.dataSourceCommesseCompatibili = new MatTableDataSource();
        this.dataSourceCommesseDiProgetto = new MatTableDataSource();
      });
  }

  getRecordCommessa(p: ProgettoCommessa) {
    if (this.dataSourceCommesseDiProgetto.data) {
      this.dataSourceCommesseDiProgetto.data.forEach(x => x.isEditable = false);
    }
    if (this.dataSourceCommesseCompatibili.data) {
      this.dataSourceCommesseCompatibili.data.forEach(x => x.isEditable = false);
    }
    p.isEditable = true;
  }

  nuovoProgettoCommessa(compat: boolean = false) {  
    let nuovo: ProgettoCommessa;
    nuovo = {
      ID_PROGETTO: this.idProgetto, 
      COD_COMMESSA: null, 
      PCT_COMPATIBILITA: compat ? 50 : 100,
      HAS_GIUSTIFICATIVO: 'N',
      GIUSTIFICATIVO_FILENAME: null,
      NOTE: null,
      isEditable: true,
      isInsert: true
    };
    if (compat) {
      const array = this.dataSourceCommesseCompatibili.data;
      array.push(nuovo);
      this.dataSourceCommesseCompatibili.data = array;
    } else {
      const array = this.dataSourceCommesseDiProgetto.data;
      array.push(nuovo);
      this.dataSourceCommesseDiProgetto.data = array;
    }
  } 

  deleteCommessa(p: ProgettoCommessa) {
    if (p.COD_COMMESSA != null && p.ID_PROGETTO != null) {
      this.progettiCommesseService.delete(p.ID_PROGETTO, p.COD_COMMESSA)
      .subscribe(response => {
        this.getProgettoCommesse();
      },
      error => {
        this.alertService.error(error);
      });
    }
  }

  annullaModificaCommessa(row: ProgettoCommessa) {
    this.getProgettoCommesse();
  }

  salvaModificaCommessa(p: ProgettoCommessa) {    
    console.log(p);

    if (p.isInsert) {
        this.progettiCommesseService.insert(p)
        .subscribe(response => {
          this.alertService.success("Commessa inserita con successo");
          if (p.PCT_COMPATIBILITA == 100) {
            this.dataSourceCommesseDiProgetto.data.splice(-1, 1);
            this.dataSourceCommesseDiProgetto.data.push(response.value);
            this.dataSourceCommesseDiProgetto.data = this.dataSourceCommesseDiProgetto.data;
          } else {
            this.dataSourceCommesseCompatibili.data.splice(-1, 1);
            this.dataSourceCommesseCompatibili.data.push(response.value);
            this.dataSourceCommesseCompatibili.data = this.dataSourceCommesseCompatibili.data;
          }
          p.isEditable = false;
        },
        error => {
          this.alertService.error(error);
        });
      
    } else {
        this.progettiCommesseService.update(p)
        .subscribe(response => {
          this.alertService.success("Commessa aggiornata con successo");
          p.isEditable = false;
        },
        error => {
          this.alertService.error(error);
        });
      }
  }

  uploadGiustificativo(p: ProgettoCommessa, event: any) {
    console.log(event);
    let file = event.target.files && event.target.files[0];
    console.log('Going to upload:', file);
    if (file) {
      this.progettiCommesseService.uploadGiustificativo(p.ID_PROGETTO!, p.COD_COMMESSA!, file).subscribe(response => {
        p.HAS_GIUSTIFICATIVO = 'Y';
        p.GIUSTIFICATIVO_FILENAME = file.name;
        this.alertService.success('Giustificativo caricato con successo');
      },
      error => {
        this.alertService.error(error);
      });
    }
  }

  deleteGiustificativo(p: ProgettoCommessa) {
    // TODO Some warning?
    this.progettiCommesseService.deleteGiustificativo(p.ID_PROGETTO!, p.COD_COMMESSA!).subscribe(response => {
      p.HAS_GIUSTIFICATIVO = 'N';
      p.GIUSTIFICATIVO_FILENAME = null;
      this.alertService.success('Giustificativo eliminato con successo');
    },
    error => {
      this.alertService.error(error);
    });
  }

  downloadGiustificativo(p: ProgettoCommessa) {
    this.progettiCommesseService.downloadGiustificativo(p.ID_PROGETTO!, p.COD_COMMESSA!).subscribe(response => {
      this.downloadFile(response, p.GIUSTIFICATIVO_FILENAME!);
    },
    error => {
      this.alertService.error(error);
    });
  }
  
  downloadFile(data: any, filename: string) {
      const blob = new Blob([data] /* , { type: 'applicazion/zip' } */ );
      const url = window.URL.createObjectURL(blob);
      var anchor = document.createElement("a");
      anchor.download = filename;
      anchor.href = url;
      anchor.click();
  }
}
