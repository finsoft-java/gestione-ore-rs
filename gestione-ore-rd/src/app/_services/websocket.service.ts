import { Injectable } from "@angular/core";
import { Subject, Observer, Observable } from "rxjs";
import { AlertService } from ".";

export interface Message {
  what_has_changed: 'questionari'|'progetti'|'utenti'|'questionariCompilati';
  obj: any;
  note: string;
}

/**
 * Questo servizio crea e mantiene internamente un WebSocket
 * 
 * Il websocket è configurato per inviare Object di qualsiasi tipo
 * (ma non Blob e Arraybuffer, perchè esegue uno stringify)
 */
@Injectable({ providedIn: 'root' })
export class WebsocketService {

  public messages: Subject<Message>;

  constructor(
    private alertService: AlertService) {
    console.log("WebsocketService constructor");
    this.messages = this.create(config.websocketUrl);
  }


  public create(url: string): Subject<Message> {
    let ws = new WebSocket(url);
    let that = this;
    let observable = Observable.create((obs: Observer<Message>) => {
      ws.onmessage = function(evt : MessageEvent) {
        // Quando spedisco, spedisco l'oggetto Message
        // Quando ricevo, ricevo un MessageEvent, che contiene un attributo data : Message
        let msg : Message = JSON.parse(evt.data);
        console.log("new message received from websocket: ", msg);
        obs.next(msg);
      };
      ws.onerror = function(err) {
        obs.error(err);
      }
      ws.onclose = function() {
        //that.alertService.error("Impossibile contattare il server WebSocket! Segnalare l'anomalia e/o ricaricare la pagina");
        console.error("Impossibile contattare il server WebSocket! Segnalare l'anomalia e/o ricaricare la pagina");
        obs.complete();
      }
    });
    let observer = {
      next: (msg: Message) => {
        if (ws.readyState === WebSocket.OPEN) {
          ws.send(JSON.stringify(msg));
        }
      }
    };
    let subject = Subject.create(observer, observable);
    console.log("Using websocket: " + url);
    return subject;
  }

  sendMsg(msg : Message) {
    console.log("new message from client to websocket: ", msg);
    this.messages.next(msg);
  }
}