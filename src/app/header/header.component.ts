import { style } from '@angular/animations';
import { Component, ElementRef, OnInit, ViewChild } from '@angular/core';

@Component({
  selector: 'app-header',
  templateUrl: './header.component.html',
  styleUrls: ['./header.component.scss']
})
export class HeaderComponent implements OnInit {
  @ViewChild('dropdownChange', { static: true }) dropdownChange: ElementRef<HTMLElement>;
  @ViewChild('dropdownNotif', { static: true }) dropdownNotif: ElementRef<HTMLElement>;
  @ViewChild('dropdownChat', { static: true }) dropdownChat: ElementRef<HTMLElement>;
  @ViewChild('menuAreaContent', { static: true }) menuArea: ElementRef<HTMLDivElement>;
  @ViewChild('notificationAreaContent', { static: true }) notificationArea: ElementRef<HTMLDivElement>;
  @ViewChild('chatAreaContent', { static: true }) chatArea: ElementRef<HTMLDivElement>;
  contentStyle: any = new Object();

  constructor(dropdown: ElementRef<HTMLElement>, divElement: ElementRef<HTMLDivElement>) {
    this.dropdownChange = dropdown;
    this.dropdownNotif = dropdown;
    this.dropdownChat = dropdown;
    this.menuArea = divElement;
    this.notificationArea = divElement;
    this.chatArea = divElement;
  }

  ngOnInit(): void {

  }

  ShowHideMenu() {
    this.contentStyle.width = "210px";
    this.contentStyle.height = "220px";
    this.menuArea.nativeElement.getAttribute("aria-dropdown") == "false" ? this.DropDownRotate(this.menuArea.nativeElement, "show") : this.DropDownRotate(this.menuArea.nativeElement, "hide");
    this.hideDropDown(this.chatArea.nativeElement);
    this.hideDropDown(this.notificationArea.nativeElement);
  }

  ShowHideNotification() {
    this.contentStyle.width = "355px";
    this.contentStyle.height = "700px";
    this.notificationArea.nativeElement.getAttribute("aria-dropdown") == "false" ? this.createContent(this.notificationArea.nativeElement, this.CreateInsideContent()) : this.hideDropDown(this.notificationArea.nativeElement);
    this.hideDropDown(this.menuArea.nativeElement);
    this.hideDropDown(this.chatArea.nativeElement);
    this.dropdownChange.nativeElement.setAttribute("aria-dropdown", "false");
    this.dropdownChange.nativeElement.style.transform = "rotate(0deg)";
  }

  ShowHideChat() {
    this.contentStyle.width = "436px";
    this.contentStyle.height = "434px"
    this.chatArea.nativeElement.getAttribute("aria-dropdown") == "false" ? this.createContent(this.chatArea.nativeElement, this.CreateInsideContent()) : this.hideDropDown(this.chatArea.nativeElement);
    this.hideDropDown(this.menuArea.nativeElement);
    this.hideDropDown(this.notificationArea.nativeElement);
    this.dropdownChange.nativeElement.setAttribute("aria-dropdown", "false");
    this.dropdownChange.nativeElement.style.transform = "rotate(0deg)";
  }

  dropdownMenu(type: any) {
    switch (type) {
      case 1:
        this.ShowHideMenu();
        break;
      case 2:
        this.ShowHideNotification();
        break;
      case 3:
        this.ShowHideChat();
        break;
      default:
        break;
    }

  }

  CreateInsideContent() {
    return "no data";
  }

  DropDownRotate(el: HTMLDivElement, check: string) {
    switch (check) {
      case "show":
        this.dropdownChange.nativeElement.setAttribute("aria-dropdown", "true");
        this.dropdownChange.nativeElement.style.transform = "rotate(180deg)";
        this.createContent(el, this.CreateInsideContent());
        break;
      case "hide":
        this.dropdownChange.nativeElement.setAttribute("aria-dropdown", "false");
        this.dropdownChange.nativeElement.style.transform = "rotate(0deg)";
        this.hideDropDown(el);
        break;
      default:
        break;
    }
  }

  hideDropDown(el: HTMLDivElement) {
    el.style.height = "0px";
    el.style.filter = "unset";
    el.setAttribute("aria-dropdown", "false");
  }

  createContent(el: HTMLDivElement, Content: any) {
    el.setAttribute("aria-dropdown", "true");
    el.style.background = "#fff";
    el.style.width = this.contentStyle.width;
    el.style.height = this.contentStyle.height;
    el.style.filter = "drop-shadow(0px 1px 10px rgba(0, 0, 0, 0.08))";
    el.innerHTML = Content;
  }

}
