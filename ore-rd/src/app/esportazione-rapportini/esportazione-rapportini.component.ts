import { Component, OnInit } from '@angular/core';
import { UploadRapportiniService } from './../_services/upload.rapportini.service';

@Component({
  selector: 'app-esportazione-rapportini',
  templateUrl: './esportazione-rapportini.component.html',
  styleUrls: ['./esportazione-rapportini.component.css']
})
export class EsportazioneRapportiniComponent implements OnInit {

    periodo = '2021-02';

    constructor(private uploadRapportiniService: UploadRapportiniService) { }

    ngOnInit(): void {
    }

    download() {
        this.uploadRapportiniService.download(this.periodo).subscribe(response => {
            this.downloadFile(response);
        },
        error => {
            // TODO
        });
    }
  
    downloadFile(data: any) {
        const blob = new Blob([data], { type: 'applicazion/zip' });
        const url = window.URL.createObjectURL(blob);
        var anchor = document.createElement("a");
        anchor.download = "Esportazione.zip";
        anchor.href = url;
        anchor.click();
    }

}
