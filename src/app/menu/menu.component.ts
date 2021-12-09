import { Component, ElementRef, OnInit, ViewChild } from '@angular/core';

@Component({
  selector: 'app-menu',
  templateUrl: './menu.component.html',
  styleUrls: ['./menu.component.scss']
})
export class MenuComponent implements OnInit {
  @ViewChild('menu', { static: true }) menu: ElementRef<HTMLDivElement>;
  menuItem: any = new Object();

  constructor(menu: ElementRef<HTMLDivElement>) {
    this.menu = menu;
  }

  ngOnInit(): void {
    this.menuItem = [{
      id: "1",
      name: "მთავარი",
      route: "dashboard"
    }, {
      id: "1",
      name: "შემომავალი",
      route: "incomming"
    }, {
      id: "1",
      name: "ტესტ გვერდი",
      route: "test1"
    }, {
      id: "1",
      name: "ტესტ გვერდი 2",
      route: "test2"
    }];
  }

}
