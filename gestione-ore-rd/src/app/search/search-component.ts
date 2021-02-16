import { Component, OnInit, OnDestroy, Output, EventEmitter, Input } from '@angular/core';
import { User } from '@/_models';

@Component({
    selector: 'search',
    templateUrl: 'search-component.html'})
export class SearchComponent implements OnInit, OnDestroy {
    
    @Input() searchString : string;
    @Output() public doSearch = new EventEmitter<string>();
    previousSearch : string;

    ngOnInit() {
    }
    ngOnDestroy() {
    }
    emit_do_search($event) {
        if ($event != null && $event.key == "Escape") {
            // Al tasto ESC ripulisco la casellina
            this.previousSearch = this.searchString = null;
        } else if (this.previousSearch == this.searchString) {
            // L'utente ha schiacciato CTRL, ENTER, SHIFT, ...
            return;
        }
        this.previousSearch = this.searchString;
        this.doSearch.emit(this.searchString);
    }

}