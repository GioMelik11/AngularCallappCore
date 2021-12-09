import { Component, ElementRef, OnInit, ViewChild } from '@angular/core';
import { NavigationEnd, Router } from '@angular/router';
import { filter } from 'rxjs/operators';

@Component({
  selector: 'app-menu',
  templateUrl: './menu.component.html',
  styleUrls: ['./menu.component.scss']
})
export class MenuComponent {
  @ViewChild('menu', { static: true }) menu: ElementRef<HTMLDivElement>;
  menuItem: any = new Object();
  currentRoute: any;

  constructor(menu: ElementRef<HTMLDivElement>, private router: Router) {
    this.menu = menu;

    this.router.events
      .pipe(filter(event => event instanceof NavigationEnd))
      .subscribe(event => {
        this.currentRoute = event as Object;
        this.currentRoute = this.currentRoute.url;
      })

    this.menuItem = [{
      id: "1",
      name: "მთავარი",
      route: "dashboard",
      icon: "dashboard"
    }, {
      id: "2",
      name: "შემომავალი",
      route: "incomming",
      icon: "incomming"
    }, {
      id: "3",
      name: "ცნობარები",
      route: "#",
      icon: "ref"
    }, {
      id: "4",
      name: "ტესტ გვერდი",
      route: "test2",
      icon: "crm"
    }];
  }

}
