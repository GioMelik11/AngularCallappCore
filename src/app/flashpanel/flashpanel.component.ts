import { Component, ElementRef, OnInit, ViewChild } from '@angular/core';
import { ModalComponent } from './modal/modal.component';

@Component({
  selector: 'app-flashpanel',
  templateUrl: './flashpanel.component.html',
  styleUrls: ['./flashpanel.component.scss']
})
export class FlashpanelComponent implements OnInit {
  @ViewChild(ModalComponent, { read: ElementRef }) private footerElementRef: ElementRef;
  panelItems: any = new Object();
  clickedItem: any;

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

  showHidePanel(source: number, targetId: string) {
    var element = this.footerElementRef.nativeElement.children[0];
    this.clickedItem = document.getElementById(targetId);
    this.dynamicGetdialogContent(element, source);

  }

  dynamicGetdialogContent(element: HTMLElement, source: number) {

    switch (this.clickedItem.getAttribute("aria-show")) {
      case "false":
        this.hideDialog(element);
        this.clickedItem.setAttribute("aria-show", 'true');
        this.hideAll(document.querySelectorAll("div[flash-panel-item]"), this.clickedItem);
        setTimeout(() => {
          this.showDialog(element);
        }, 100);
        this.generateContent(element.children[0].children[0]);
        break;
      case "true":
        this.clickedItem.setAttribute("aria-show", 'false');
        this.hideDialog(element);
        break;
      default:
        break;
    }
  }

  showDialog(element: HTMLElement) {
    element.setAttribute("class", "modal show");
    element.setAttribute("aria-modal", "true");
    element.setAttribute("aria-hidden", "false");
    element.setAttribute("style", "display: block;");
  }

  hideDialog(element: any) {
    element.setAttribute("class", "modal");
    element.setAttribute("aria-hidden", "true");
    element.setAttribute("style", "display: none;");
  }

  generateContent(element: any) {
    element.innerHTML = "No Data";
  }

  hideAll(elements: NodeList, el: any) {
    elements.forEach(element => {
      var html = element as any;
      if (el.getAttribute("id") != html.getAttribute("id")) {
        html.setAttribute("aria-show", "false");
      }
    });
  }

}
