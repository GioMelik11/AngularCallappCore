import { Component, ElementRef, OnInit, ViewChild } from '@angular/core';
import { NgbModalConfig, NgbModal } from '@ng-bootstrap/ng-bootstrap';

@Component({
  selector: 'app-flashpanel',
  templateUrl: './flashpanel.component.html',
  styleUrls: ['./flashpanel.component.scss']
})
export class FlashpanelComponent implements OnInit {
  panelItems: any = new Object();

  constructor(config: NgbModalConfig, private modalService: NgbModal) {
    config.backdrop = 'static';
    config.keyboard = false;
  }

  ngOnInit(): void {
    this.panelItems = this.getPanelItems();
  }

  getPanelItems() {
    var data = [{
      source_id: 1,
      icon: "phone-o",
      count_queue: 1
    }, {
      source_id: 2,
      icon: "chat-o",
      count_queue: 20
    }, {
      source_id: 3,
      icon: "messenger-o",
      count_queue: 5
    }, {
      source_id: 4,
      icon: "mail-o",
      count_queue: 11
    }]

    return data;
  }

  showHidePanel(content: any, source: number) {
    this.modalService.open(content);

  }
}

