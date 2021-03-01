import { AlertService } from './../_services/alert.service';
import { AuthenticationService } from './../_services/authentication.service';
import { Router, ActivatedRoute } from '@angular/router';
import { FormGroup, FormControl, Validators, FormBuilder } from '@angular/forms';
import { Component, Input, OnInit, Output, EventEmitter } from '@angular/core';
import { first, map } from 'rxjs/operators';
@Component({
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.css']
})
export class LoginComponent implements OnInit {
  error: string = '';
  messaggio_errore_login = false;
  returnUrl: string = '';
  loading = false;

  constructor(public fb: FormBuilder,
    private route: ActivatedRoute,
    private router: Router,
    private authenticationService: AuthenticationService,
    private alertService: AlertService) {}

  form = this.fb.group({
    username: ['', [Validators.required]],
    password: ['', [Validators.required]]
  })

  get f() { return this.form.controls; }

  submit() {
    if (!this.form.invalid) {
      
      this.loading = true;
      return this.authenticationService.login(this.f.username.value,this.f.password.value)           
      .pipe(first())
      .subscribe(
          data => {
              this.router.navigate([this.returnUrl]);
          },
          error => {
              if (error.status == 401 || error.status == 403){
                  this.alertService.error("Credenziali non valide");
              } else {
                  // Ad esempio: Impossibile conettersi al server PHP
                  this.alertService.error(error);
              }
              this.loading = false;
          }
        )
    }else{
      return false;
    }
  }

  ngOnInit(): void {
    this.returnUrl = this.route.snapshot.queryParams['returnUrl'] || '/';   
  }

}
