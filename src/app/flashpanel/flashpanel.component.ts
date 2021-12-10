import { Component, ElementRef, OnInit, ViewChild } from '@angular/core';
import { ModalComponent } from '../modal/modal.component';

@Component({
  selector: 'app-flashpanel',
  templateUrl: './flashpanel.component.html',
  styleUrls: ['./flashpanel.component.scss']
})
export class FlashpanelComponent implements OnInit {
  @ViewChild(ModalComponent, { read: ElementRef }) private footerElementRef: ElementRef;
  panelItems: any = new Object();

  constructor(private modalToggle: ElementRef) {
    this.footerElementRef = modalToggle;
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

  showHidePanel(source: number) {
    var element = this.footerElementRef.nativeElement.children[0];
    if (element.getAttribute("aria-hidden") == "true") {
      element.setAttribute("class", "modal show");
      element.setAttribute("aria-modal", "true");
      element.setAttribute("aria-hidden", "false");
      element.setAttribute("style", "display: block;");
    } else {
      element.setAttribute("class", "modal");
      element.setAttribute("aria-hidden", "true");
      element.setAttribute("style", "display: none;");
    }



  }

}
